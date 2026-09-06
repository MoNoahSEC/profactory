<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ClearsLayoutCache;
    public function index()
    {
        $suppliers = Supplier::withSum('purchases', 'total_price')
            ->withSum('purchases', 'paid_amount')
            ->paginate(50);
            
        // الصافي الحقيقي = مديونية المشتريات + رصيد الدفعات (deposit_balance موجب = مبلغ إضافي مدين علينا)
        foreach ($suppliers as $supplier) {
            $purchaseDebt = ($supplier->purchases_sum_total_price ?? 0) - ($supplier->purchases_sum_paid_amount ?? 0);
            $supplier->calculated_debt = max(0, $purchaseDebt) + $supplier->deposit_balance;
        }
            
        return view('suppliers.index', compact('suppliers'));
    }

    public function printDirect(Supplier $supplier)
    {
        $url = route('suppliers.print', $supplier->id);
        $cmd = "start msedge --headless --disable-gpu --print-to-default \"{$url}\"";
        
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen($cmd, "r"));
                return back()->with('success', 'تم إرسال أمر الطباعة إلى طابعة الكمبيوتر بنجاح.');
            }
            return back()->with('error', 'الطباعة المباشرة مدعومة فقط على خوادم الويندوز.');
        } catch (\Exception $e) {
            return back()->with('error', 'فشل في إرسال أمر الطباعة: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Supplier::create($request->all());
        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم إضافة المورد بنجاح');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($request->all());
        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تعديل بيانات المورد');
    }

    public function show(\App\Models\Supplier $supplier)
    {
        $supplier->load(['purchases.rawMaterial']);
        $purchases = $supplier->purchases()->latest('purchase_date')->latest('id')->get();
        
        $totalPurchases = $purchases->sum('total_price');
        $totalPaid = $purchases->sum('paid_amount');
        $remainingDebt = max(0, $totalPurchases - $totalPaid);
        
        $transactions = \App\Models\CashTransaction::where('reference_type', \App\Models\Supplier::class)
            ->where('reference_id', $supplier->id)
            ->latest('transaction_date')
            ->latest('id')
            ->get();
            
        $materials = \App\Models\RawMaterial::all();

        return view('suppliers.show', compact('supplier', 'purchases', 'totalPurchases', 'totalPaid', 'remainingDebt', 'transactions', 'materials'));
    }

    public function storePayment(Request $request, \App\Models\Supplier $supplier, \App\Services\CashLedgerService $ledger)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $paymentAmount = $request->amount;
        $originalAmount = $paymentAmount;

        \Illuminate\Support\Facades\DB::transaction(function () use ($supplier, &$paymentAmount, $request, $ledger, $originalAmount) {
            // Apply payment to oldest unpaid purchases first
            $unpaidPurchases = $supplier->purchases()
                ->whereRaw('total_price > paid_amount')
                ->orderBy('purchase_date', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($unpaidPurchases as $purchase) {
                if ($paymentAmount <= 0) break;

                $remainingOnPurchase = $purchase->total_price - $purchase->paid_amount;
                $payNow = min($paymentAmount, $remainingOnPurchase);
                
                $purchase->increment('paid_amount', $payNow);
                $paymentAmount -= $payNow;
            }

            // Record the transaction on the supplier level, not individual purchase
            $ledger->record(
                'supplier_payment',
                $originalAmount,
                "سداد دفعة للمورد: {$supplier->name}" . ($request->notes ? " — {$request->notes}" : ''),
                $supplier,
                $request->date
            );
        });

        $this->clearLayoutCache();
        return redirect()->route('suppliers.show', $supplier)->with('success', 'تم تسجيل السداد وتوزيعه على الفواتير القديمة بنجاح');
    }

    public function destroy(\App\Models\Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن حذف مورد له فواتير شراء مسجلة.');
        }
        $supplier->delete();
        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم حذف المورد');
    }

    public function storeDeposit(Request $request, \App\Models\Supplier $supplier, \App\Services\CashLedgerService $ledger)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        \App\Models\SupplierDeposit::create([
            'supplier_id' => $supplier->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'type' => 'deposit',
            'description' => $request->description,
        ]);

        $supplier->increment('deposit_balance', $request->amount);

        // Record outgoing cash to supplier (advance payment)
        $ledger->record(
            'supplier_payment',
            $request->amount,
            'دفعة مقدمة / حساب للمورد: ' . $supplier->name . ' - ' . ($request->description ?? ''),
            $supplier,
            $request->date
        );

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تسجيل الدفعة للمورد وخصمها من الخزينة بنجاح');
    }

    public function adjustBalance(Request $request, \App\Models\Supplier $supplier)
    {
        $request->validate([
            'type'        => 'required|in:debt,credit',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $amount = (float) $request->amount;

        \App\Models\SupplierDeposit::create([
            'supplier_id' => $supplier->id,
            'amount'      => $amount,
            'date'        => $request->date,
            'type'        => $request->type === 'credit' ? 'deposit' : 'debt_adjustment',
            'description' => $request->description,
        ]);

        if ($request->type === 'credit') {
            // رصيد للمورد (المصنع دافعله زيادة قديمة أو رصيد ليه)
            $supplier->increment('deposit_balance', $amount);
            $msg = 'تم إضافة ' . number_format($amount, 2) . ' ج.م كرصيد دائن للمورد';
        } else {
            // دين قديم على المورد (رصيد للمصنع عند المورد) أو مديونية
            // wait, if type is debt_adjustment -> debt ON the supplier? Or debt ON the factory?
            // "دين قديم" usually means the factory owes the supplier.
            // Let's decrement deposit_balance (meaning factory owes more / supplier has negative deposit balance).
            $supplier->decrement('deposit_balance', $amount);
            $msg = 'تم تسجيل ' . number_format($amount, 2) . ' ج.م كمديونية قديمة';
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', $msg);
    }

    public function updateAdjustment(Request $request, \App\Models\SupplierDeposit $deposit)
    {
        if ($deposit->type !== 'debt_adjustment' && $deposit->type !== 'deposit') {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن تعديل هذا النوع من الحركات.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $oldAmount = $deposit->amount;
        $newAmount = (float) $request->amount;
        $diff = $newAmount - $oldAmount;

        $deposit->update([
            'amount' => $newAmount,
            'date' => $request->date,
            'description' => $request->description,
        ]);

        if ($deposit->type === 'deposit') {
            $deposit->supplier->increment('deposit_balance', $diff);
        } elseif ($deposit->type === 'debt_adjustment') {
            $deposit->supplier->decrement('deposit_balance', $diff);
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تعديل الحركة بنجاح وتحديث الرصيد.');
    }

    public function destroyAdjustment(\App\Models\SupplierDeposit $deposit)
    {
        if ($deposit->type !== 'debt_adjustment' && $deposit->type !== 'deposit') {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن حذف هذا النوع من الحركات.');
        }

        $amount = $deposit->amount;
        $supplier = $deposit->supplier;

        if ($deposit->type === 'deposit') {
            $supplier->decrement('deposit_balance', $amount);
        } elseif ($deposit->type === 'debt_adjustment') {
            $supplier->increment('deposit_balance', $amount);
        }

        $deposit->delete();

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم حذف الحركة بنجاح ورد الرصيد لحساب المورد.');
    }

    public function print(\App\Models\Supplier $supplier)
    {
        $supplier->load(['purchases', 'deposits']);
        $sorted = $supplier->getStatement();
        
        $totalPurchases = $supplier->purchases->sum('total_price');
        $totalPaid = $supplier->purchases->sum('paid_amount');
        $purchaseDebt = max(0, $totalPurchases - $totalPaid);
        $depositBalance = $supplier->deposit_balance;
        
        $netBalance = $depositBalance + $purchaseDebt;

        return view('suppliers.print', compact('supplier', 'sorted', 'totalPurchases', 'totalPaid', 'purchaseDebt', 'depositBalance', 'netBalance'));
    }
}
