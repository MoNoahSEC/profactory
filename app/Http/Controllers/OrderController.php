<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\CustomerDeposit;
use App\Services\CashLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ClearsLayoutCache;
    public function index()
    {
        $orders = Order::with(['customer:id,name', 'items.product:id,name'])
            ->select('id','order_number','order_date','customer_id','customer_name',
                     'total_amount','paid_deposit','status','loading_status','converted_to_invoice','updated_at')
            ->latest()
            ->paginate(30);

        $stats = [
            'total_active_amount' => Order::where('converted_to_invoice', false)->sum('total_amount'),
            'pending_count'       => Order::whereIn('status', ['pending', 'in_progress'])->where('converted_to_invoice', false)->count(),
            'completed_count'     => Order::where('status', 'completed')->where('converted_to_invoice', false)->count(),
            'converted_count'     => Order::where('converted_to_invoice', true)->count(),
        ];

        return view('orders.index', compact('orders', 'stats'));
    }

    public function create()
    {
        $customers = Customer::select('id', 'name', 'type')->orderBy('name')->get();
        $products  = Product::where('is_active', true)
            ->select('id', 'name', 'code', 'selling_price', 'cages_per_carton')
            ->with('inventory:id,product_id,current_stock')
            ->orderBy('name')
            ->get();
            
        return view('orders.create', compact('customers', 'products'));
    }

    public function show(Order $order)
    {
        $this->clearLayoutCache();
        return redirect()->route('orders.edit', $order);
    }

    public function print(Order $order)
    {
        $order->load(['customer', 'items.product', 'shipments.items.orderItem.product']);

        return view('orders.print', compact('order'));
    }

    public function store(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'paid_deposit' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:50',
        ]);

        $customerId = $request->customer_id;
        $customerName = $request->customer_name;
        $address = $request->address;
        $paidDeposit = (float) ($request->paid_deposit ?? 0);

        if (!$customerId && $customerName) {
            $customer = Customer::create([
                'name' => $customerName,
                'address' => $address,
                'type' => 'retail',
            ]);
            $customerId = $customer->id;
        }

        DB::transaction(function () use ($request, $ledger, &$order, $customerId, $customerName, $address, &$orderNumber, $paidDeposit) {
            $lastOrder = Order::lockForUpdate()->latest('id')->first();
            $nextNum = $lastOrder ? intval(substr($lastOrder->order_number, 4)) + 1 : 1;
            $orderNumber = 'ORD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'address' => $address,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'delivery_date' => $request->delivery_date,
                'status' => 'pending',
                'loading_status' => 'pending',
                'total_amount' => 0,
                'paid_deposit' => $paidDeposit,
                'notes' => $request->notes,
                'currency' => $request->currency ?? 'ج.م',
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $unitPrice = isset($item['unit_price']) && $item['unit_price'] > 0
                    ? $item['unit_price']
                    : $product->selling_price;
                $lineTotal = $unitPrice * $item['quantity'];
                $totalAmount += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            if ($paidDeposit > 0 && $customerId) {
                $customer = Customer::find($customerId);

                CustomerDeposit::create([
                    'customer_id' => $customerId,
                    'amount' => $paidDeposit,
                    'date' => $request->order_date,
                    'type' => 'deposit',
                    'description' => "عربون للطلبية رقم {$orderNumber}",
                    'order_id' => $order->id,
                ]);

                $customer->increment('deposit_balance', $paidDeposit);

                $ledger->record(
                    'order_deposit',
                    $paidDeposit,
                    "عربون طلبية {$orderNumber} — العميل: " . ($customer->name ?? $customerName),
                    $order,
                    $request->order_date
                );
            }
        });

        $this->clearLayoutCache();
        return redirect()->back()->with('success', "تم إنشاء الطلبية {$orderNumber} بنجاح. أرسلها للتحميل عند جاهزية المخزون.");
    }

    public function edit(Order $order)
    {
        $order->load('items.product');
        $customers = Customer::all();
        $products = Product::where('is_active', true)->get();

        return view('orders.edit', compact('order', 'customers', 'products'));
    }

    public function update(Request $request, Order $order)
    {
        if ($order->converted_to_invoice) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن تعديل طلبية تم تحويلها إلى فاتورة.');
        }

        if ($order->shipments()->exists()) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن تعديل طلبية بعد بدء عملية الشحن.');
        }

        $request->validate([
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($request, $order) {
            $order->update([
                'customer_name' => $request->customer_name,
                'customer_id' => $request->customer_id,
                'address' => $request->address,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'delivery_date' => $request->delivery_date,
                'notes' => $request->notes,
                'currency' => $request->currency ?? 'ج.م',
            ]);

            $order->items()->delete();

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $unitPrice = isset($item['unit_price']) && $item['unit_price'] > 0
                    ? $item['unit_price']
                    : $product->selling_price;
                $lineTotal = $unitPrice * $item['quantity'];
                $totalAmount += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);
        });

        $this->clearLayoutCache();
        return redirect()->route('orders.index')->with('success', "تم تعديل الطلبية {$order->order_number} بنجاح.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $order->update([
            'status' => $request->status,
            'loading_status' => $request->status === 'completed' ? 'loaded' : 'pending',
            'delivery_date' => $request->status === 'completed' ? now()->toDateString() : $order->delivery_date,
        ]);

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تحديث حالة الطلبية.');
    }

    public function convertToInvoice(Order $order, CashLedgerService $ledger)
    {
        if ($order->converted_to_invoice) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'تم تحويل هذه الطلبية إلى فاتورة مسبقاً.');
        }

        $order->load('items.product', 'customer');

        $invoiceNumber = '';
        $invoice = DB::transaction(function () use ($order, &$invoiceNumber) {
            $lastInvoice = Invoice::lockForUpdate()->latest('id')->first();
            $nextNum = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 9)) + 1 : 1;
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            return Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $order->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $order->total_amount,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total_amount' => $order->total_amount,
                'paid_amount' => $order->paid_deposit,
                'status' => $order->paid_deposit >= $order->total_amount ? 'paid' : ($order->paid_deposit > 0 ? 'partial' : 'sent'),
                'notes' => "فاتورة محولة من الطلبية رقم {$order->order_number}",
            ]);
        });

        $actualInvoiceTotal = 0;
        foreach ($order->items as $item) {
            if ($order->status === 'completed' || $order->loading_status === 'loaded') {
                $qty = (int)$item->loaded_quantity;
            } else {
                $qty = (int)$item->quantity;
            }

            if ($qty > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'quantity' => $qty,
                    'unit_price' => $item->unit_price,
                    'discount' => 0,
                    'total' => $qty * $item->unit_price,
                ]);
                $actualInvoiceTotal += ($qty * $item->unit_price);
            }
        }

        $invoice->update([
            'subtotal' => $actualInvoiceTotal,
            'total_amount' => $actualInvoiceTotal,
            'status' => $order->paid_deposit >= $actualInvoiceTotal ? 'paid' : ($order->paid_deposit > 0 ? 'partial' : 'sent'),
        ]);

        if ($order->paid_deposit > 0 && $order->customer_id) {
            $customer = Customer::find($order->customer_id);
            if ($customer && $customer->deposit_balance >= $order->paid_deposit) {
                $customer->decrement('deposit_balance', $order->paid_deposit);

                CustomerDeposit::create([
                    'customer_id' => $customer->id,
                    'amount' => $order->paid_deposit,
                    'date' => now()->toDateString(),
                    'type' => 'applied',
                    'description' => "تطبيق عربون — تحويل الطلبية {$order->order_number} إلى فاتورة {$invoiceNumber}",
                    'order_id' => $order->id,
                ]);

                \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $order->paid_deposit,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'deposit',
                    'notes' => "تطبيق عربون الطلبية رقم {$order->order_number}",
                ]);
            }
        }

        $order->update(['converted_to_invoice' => true]);

        $this->clearLayoutCache();
        return redirect()->route('invoices.show', $invoice)->with('success', "تم تحويل الطلبية {$order->order_number} إلى فاتورة {$invoiceNumber} بنجاح.");
    }

    public function approveAndInvoice(Request $request, Order $order, CashLedgerService $ledger)
    {
        if ($order->status !== 'awaiting_approval') {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'هذه الطلبية ليست في انتظار المراجعة.');
        }

        $order->load('items');
        $newTotalAmount = 0;

        foreach ($order->items as $item) {
            $approvedQty = (int) $request->input("items.{$item->id}", $item->loaded_quantity);
            $item->update(['loaded_quantity' => $approvedQty]);
            $newTotalAmount += ($approvedQty * $item->unit_price);
        }

        $order->update([
            'total_amount' => $newTotalAmount,
            'status' => 'completed',
            'delivery_date' => now()->toDateString(),
        ]);

        return $this->convertToInvoice($order, $ledger);
    }

    public function sendToLoading(Order $order, Request $request)
    {
        if (in_array($order->status, ['completed', 'awaiting_approval'], true) || $order->converted_to_invoice) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن إرسال هذه الطلبية للتحميل.');
        }

        if ($order->loading_status === 'loaded') {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'الطلبية مُحمّلة بالكامل بالفعل.');
        }

        $order->update([
            'loading_status' => 'loading',
            'status' => 'in_progress',
            'loader_id' => $request->loader_id ?? null,
        ]);

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم إرسال الطلبية لموظف التحميل بنجاح.');
    }

    public function destroy(Order $order)
    {
        if (in_array($order->status, ['completed', 'awaiting_approval'], true)) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن حذف طلبية مكتملة أو قيد المراجعة.');
        }

        if ($order->shipments()->exists()) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن حذف طلبية لها شحنات مسجلة.');
        }

        if ($order->paid_deposit > 0 && $order->customer_id) {
            $customer = Customer::find($order->customer_id);
            if ($customer) {
                $customer->decrement('deposit_balance', $order->paid_deposit);
                CustomerDeposit::where('order_id', $order->id)->delete();
                \App\Models\CashTransaction::where('reference_type', get_class($order))
                    ->where('reference_id', $order->id)
                    ->delete();
            }
        }

        $order->delete();
        
        app(CashLedgerService::class)->recalculateLedger();

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم حذف الطلبية وتحديث الخزينة.');
    }
}
