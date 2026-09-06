<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    use ClearsLayoutCache;
    public function index()
    {
        $invoices = Invoice::with('customer')->latest()->paginate(50);
        
        $stats = \Illuminate\Support\Facades\Cache::remember('invoices_index_stats', 60, function () {
            return [
                'total_sales' => Invoice::sum('total_amount'),
                'collected' => Invoice::sum('paid_amount'),
                'remaining' => Invoice::sum('remaining_amount'),
                'count' => Invoice::count(),
                'paid_count' => Invoice::where('status', 'paid')->count(),
                'overdue_count' => Invoice::where('status', 'overdue')->count(),
                'this_month' => Invoice::whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year)->sum('total_amount'),
            ];
        });

        return view('invoices.index', compact('invoices', 'stats'));
    }

    public function create()
    {
        $customers = Customer::select('id', 'name', 'type')->orderBy('name')->get();
        $products  = Product::where('is_active', true)
            ->select('id', 'name', 'code', 'selling_price', 'cages_per_carton')
            ->orderBy('name')
            ->get();
        return view('invoices.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        // السماح بإما customer_id أو customer_name
        $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'discount_type' => 'required|in:amount,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:50',
        ]);

        // حل هوية العميل: إما ID موجود أو إنشاء جديد
        $customerId = $request->customer_id;
        if (!$customerId && $request->filled('customer_name')) {
            $customer = Customer::firstOrCreate(
                ['name' => trim($request->customer_name)],
                ['type' => 'retail', 'balance' => 0]
            );
            $customerId = $customer->id;
        } elseif (!$customerId) {
            $this->clearLayoutCache();
        return redirect()->back()->withErrors(['customer_id' => 'يجب تحديد العميل أو كتابة اسمه'])->withInput();
        } else {
            $customer = Customer::find($customerId);
        }

        $invoiceNumber = '';

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, &$invoiceNumber, $customerId, $customer) {
            // ترقيم تلقائي مع قفل الجدول
            $lastInvoice = Invoice::lockForUpdate()->latest('id')->first();
            // Safely parse the numeric part: INV-YYYY-XXXX => last segment
            $lastNum = 0;
            if ($lastInvoice) {
                $parts = explode('-', $lastInvoice->invoice_number);
                $lastNum = intval(end($parts));
            }
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);

            // حساب الإجماليات
            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0);
                $subtotal += max(0, $itemTotal); // منع أي سطر سالب
            }

            $discountValue = $request->discount_value ?? 0;
            if ($request->discount_type === 'percent') {
                $discountAmount = $subtotal * (min(100, max(0, $discountValue)) / 100);
            } else {
                $discountAmount = min($subtotal, max(0, $discountValue)); // الخصم لا يتجاوز الإجمالي
            }

            $afterDiscount = $subtotal - $discountAmount;
            $taxRate = max(0, $request->tax_rate ?? 0);
            $taxAmount = $afterDiscount * ($taxRate / 100);
            $totalAmount = round($afterDiscount + $taxAmount, 2);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id'   => $customerId,
                'invoice_date'  => $request->invoice_date,
                'due_date'      => $request->due_date,
                'status'        => 'draft',
                'subtotal'      => $subtotal,
                'discount_type' => $request->discount_type,
                'discount_value'=> $discountValue,
                'tax_rate'      => $taxRate,
                'tax_amount'    => $taxAmount,
                'total_amount'  => $totalAmount,
                'paid_amount'   => 0,
                'remaining_amount' => $totalAmount, // بقى كان صفراً دائماً!
                'notes'         => $request->notes,
                'created_by'    => Auth::id(),
                'currency'      => $request->currency ?? 'ج.م',
            ]);

            foreach ($request->items as $item) {
                $itemTotal = max(0, $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0));
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount'   => $item['discount'] ?? 0,
                    'total'      => $itemTotal,
                ]);

                // خصم من المخزون إذا تم طلب ذلك
                if ($request->boolean('deduct_inventory')) {
                    $inventory = Inventory::where('product_id', $item['product_id'])->first();
                    if ($inventory) {
                        $inventory->decrement('current_stock', $item['quantity']);
                        $inventory->increment('quantity_out', $item['quantity']);
                        $inventory->update(['last_updated' => now()]);
                    }
                }
            }

            // تسديد دفعة فورية إذا وجدت
            $paidAmount = floatval($request->paid_amount ?? 0);
            if ($paidAmount > 0) {
                // لا يمكن سداد مبلغ أكبر من المتبقي
                $paidAmount = min($paidAmount, $totalAmount);
                
                \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $paidAmount,
                    'payment_date' => $request->invoice_date,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'notes' => 'دفعة مسددة عند إنشاء الفاتورة',
                    'created_by' => Auth::id()
                ]);

                $remaining = max(0, $totalAmount - $paidAmount);
                $invoice->paid_amount = $paidAmount;
                $invoice->remaining_amount = $remaining;
                $invoice->status = $remaining <= 0 ? 'paid' : 'partial';
                $invoice->save();
                $ledger = app(\App\Services\CashLedgerService::class);
                $ledger->record(
                    'payment',
                    $paidAmount,
                    "دفعة من الفاتورة {$invoice->invoice_number} - العميل: {$customer->name}",
                    $invoice,
                    $request->invoice_date
                );
            }
        });

        $this->clearLayoutCache();
        return redirect()->route('invoices.index')->with('success', "تم إنشاء الفاتورة {$invoiceNumber} بنجاح");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items.product', 'payments']);
        $customerBalance = $invoice->customer ? $invoice->customer->outstanding_balance : 0;
        // الرصيد السابق = الرصيد الحالي - المتبقي من هذه الفاتورة (أي ما كان عليه قبلها)
        $previousBalance = round($customerBalance - $invoice->remaining_amount, 2);
        
        $treasuries = \App\Models\Treasury::where('is_active', true)->get();
        return view('invoices.show', compact('invoice', 'customerBalance', 'previousBalance', 'treasuries'));
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['customer', 'items.product', 'payments']);
        $customerBalance = $invoice->customer ? $invoice->customer->outstanding_balance : 0;
        // الرصيد السابق = الرصيد الحالي - المتبقي من هذه الفاتورة
        $previousBalance = round($customerBalance - $invoice->remaining_amount, 2);
        return response()
            ->view('invoices.print', compact('invoice', 'customerBalance', 'previousBalance'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function destroy(Invoice $invoice)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
            $invoice->load(['items', 'payments']);

            // 1. Reverse inventory for each item
            foreach ($invoice->items as $item) {
                $inventory = Inventory::where('product_id', $item->product_id)->first();
                if ($inventory) {
                    $inventory->increment('current_stock', $item->quantity);
                    $inventory->decrement('quantity_out', $item->quantity);
                    $inventory->update(['last_updated' => now()]);
                }
            }

            // 2. Reverse cash ledger entries for each payment
            $ledger = app(\App\Services\CashLedgerService::class);
            foreach ($invoice->payments as $payment) {
                $ledger->record(
                    'payment_reversal',
                    $payment->amount,
                    "عكس دفعة الفاتورة {$invoice->invoice_number} (محذوفة)",
                    null,
                    now()->toDateString()
                );
            }

            // 3. If converted from order, reset order status
            if (str_contains((string)$invoice->notes, 'فاتورة محولة من الطلبية رقم')) {
                preg_match('/ORD-\d+/', $invoice->notes, $matches);
                if (!empty($matches[0])) {
                    $order = \App\Models\Order::where('order_number', $matches[0])->first();
                    if ($order) {
                        $order->update(['converted_to_invoice' => false]);
                        $appliedDeposit = \App\Models\CustomerDeposit::where('order_id', $order->id)
                            ->where('type', 'applied')->first();
                        if ($appliedDeposit) {
                            $customer = \App\Models\Customer::find($appliedDeposit->customer_id);
                            if ($customer) {
                                $customer->increment('deposit_balance', $appliedDeposit->amount);
                            }
                            $appliedDeposit->delete();
                        }
                    }
                }
            }

            // 4. Delete payments, items, then invoice
            $invoice->payments()->delete();
            $invoice->items()->delete();
            $invoice->delete();
        });

        $this->clearLayoutCache();
        return redirect()->route('invoices.index')->with('success', 'تم حذف الفاتورة وعكس المخزون والخزينة بنجاح');
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items.product', 'customer', 'payments');
        $customers = Customer::all();
        $products = Product::where('is_active', true)->get();
        $customerBalance = $invoice->customer->outstanding_balance;
        return view('invoices.edit', compact('invoice', 'customers', 'products', 'customerBalance'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'discount_type' => 'required|in:amount,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:50',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $invoice) {
            
            // تحميل البنود القديمة قبل الحذف
            $invoice->load('items');

            // ===== 1. إرجاع المخزون للبنود القديمة =====
            foreach ($invoice->items as $oldItem) {
                $inventory = Inventory::where('product_id', $oldItem->product_id)->first();
                if ($inventory) {
                    $inventory->increment('current_stock', $oldItem->quantity);
                    $inventory->decrement('quantity_out', $oldItem->quantity);
                    $inventory->update(['last_updated' => now()]);
                }
            }

            // ===== 2. حذف البنود القديمة =====
            $invoice->items()->delete();

            // ===== 3. حساب الإجماليات الجديدة =====
            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemTotal = max(0, $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0));
                $subtotal += $itemTotal;
            }

            $discountValue = $request->discount_value ?? 0;
            if ($request->discount_type === 'percent') {
                $discountAmount = $subtotal * ($discountValue / 100);
            } else {
                $discountAmount = $discountValue;
            }

            $afterDiscount = $subtotal - $discountAmount;
            $taxRate = $request->tax_rate ?? 0;
            $taxAmount = $afterDiscount * ($taxRate / 100);
            $totalAmount = $afterDiscount + $taxAmount;

            // ===== 4. تحديث بيانات الفاتورة =====
            $oldTotalAmount = $invoice->total_amount;

            $invoice->update([
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'discount_type' => $request->discount_type,
                'discount_value' => $discountValue,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'currency' => $request->currency ?? 'ج.م',
            ]);

            // ===== 5. إضافة البنود الجديدة وخصم المخزون =====
            foreach ($request->items as $item) {
                $itemTotal = max(0, $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0));
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $itemTotal,
                ]);

                // خصم الكميات الجديدة من المخزون
                $inventory = Inventory::where('product_id', $item['product_id'])->first();
                if ($inventory) {
                    $inventory->decrement('current_stock', $item['quantity']);
                    $inventory->increment('quantity_out', $item['quantity']);
                    $inventory->update(['last_updated' => now()]);
                }
            }

            // ===== 6. تحديث حالة الفاتورة والمبلغ المتبقي =====
            $invoice->refresh();
            app(\App\Services\InvoiceAccountingService::class)->syncFromPaidAmount($invoice);
        });

        $this->clearLayoutCache();
        return redirect()->route('invoices.show', $invoice)->with('success', 'تم تعديل الفاتورة بنجاح وتم تحديث المخزون');
    }

    /**
     * API: Return customer balance for AJAX call from create/edit form
     */
    public function customerBalance(Customer $customer): \Illuminate\Http\JsonResponse
    {
        $balance = $customer->outstanding_balance;
        return response()->json(['balance' => $balance, 'name' => $customer->name]);
    }

    public function fake()
    {
        $customers = Customer::all();
        $products = Product::where('is_active', true)->get();
        return view('invoices.fake', compact('customers', 'products'));
    }

    public function printFake(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'discount_type' => 'required|in:amount,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:50',
        ]);

        $subtotal = 0;
        $items = collect();
        foreach ($request->items as $itemData) {
            $itemTotal = $itemData['quantity'] * $itemData['unit_price'] - ($itemData['discount'] ?? 0);
            $subtotal += $itemTotal;
            
            $product = Product::find($itemData['product_id']);
            
            $items->push((object)[
                'product' => clone $product,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'discount' => $itemData['discount'] ?? 0,
                'total' => $itemTotal,
            ]);
        }

        $discountValue = $request->discount_value ?? 0;
        if ($request->discount_type === 'percent') {
            $discountAmount = $subtotal * ($discountValue / 100);
        } else {
            $discountAmount = $discountValue;
        }

        $afterDiscount = $subtotal - $discountAmount;
        $taxRate = $request->tax_rate ?? 0;
        $taxAmount = $afterDiscount * ($taxRate / 100);
        $totalAmount = $afterDiscount + $taxAmount;

        $invoice = (object)[
            'invoice_number' => 'FAKE-' . date('Ymd-His'),
            'invoice_date' => \Carbon\Carbon::parse($request->invoice_date),
            'due_date' => $request->due_date ? \Carbon\Carbon::parse($request->due_date) : null,
            'customer' => clone Customer::find($request->customer_id),
            'subtotal' => $subtotal,
            'discount_type' => $request->discount_type,
            'discount_value' => $discountValue,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalAmount,
            'notes' => $request->notes,
            'currency' => $request->currency ?? 'ج.م',
            'items' => $items,
        ];

        $customerObj = Customer::find($request->customer_id);
        $customerBalance = $customerObj ? $customerObj->outstanding_balance : 0;
        // In fake print, the invoice doesn't exist in DB, so it hasn't affected the balance yet.
        // Thus, the current balance IS the "old balance", and we add the fake remaining to get the "new balance".
        $customerBalance += $invoice->remaining_amount;

        return view('invoices.print', compact('invoice', 'customerBalance'));
    }
}

