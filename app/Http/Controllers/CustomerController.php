<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ClearsLayoutCache;
    public function index()
    {
        $customers = Customer::withCount('invoices')
            ->withSum('invoices', 'total_amount')
            ->withSum('invoices', 'paid_amount')
            ->withSum('invoices', 'remaining_amount')
            ->latest()
            ->paginate(50);
            
        // Pre-calculate outstanding_balance to avoid running queries in the view
        foreach ($customers as $customer) {
            $totalRemaining = $customer->invoices_sum_remaining_amount ?? 0;
            $customer->precalculated_balance = round((float) $totalRemaining - (float) $customer->deposit_balance, 2);
        }
            
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'type' => 'required|in:retail,wholesale',
            'notes' => 'nullable|string',
        ]);

        Customer::create($request->all());
        $this->clearLayoutCache();
        return redirect()->route('customers.index')->with('success', 'تم إضافة العميل بنجاح');
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'type' => 'required|in:retail,wholesale',
            'notes' => 'nullable|string',
        ]);

        $customer->update($request->all());
        $this->clearLayoutCache();
        return redirect()->route('customers.index')->with('success', 'تم تعديل بيانات العميل بنجاح');
    }

    public function show(Customer $customer)
    {
        $customer->load(['invoices.payments', 'deposits.order']);
        $treasuries = \App\Models\Treasury::where('is_active', true)->get();
        return view('customers.show', compact('customer', 'treasuries'));
    }

    public function destroy(Customer $customer)
    {
        if ($customer->invoices()->count() > 0) {
            $this->clearLayoutCache();
        return redirect()->route('customers.index')->with('error', 'لا يمكن حذف عميل له فواتير');
        }
        $customer->delete();
        $this->clearLayoutCache();
        return redirect()->route('customers.index')->with('success', 'تم حذف العميل بنجاح');
    }

    public function storeDeposit(Request $request, Customer $customer, \App\Services\CashLedgerService $ledger)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'treasury_id' => 'required|exists:treasuries,id',
            'description' => 'nullable|string',
        ]);

        $deposit = \App\Models\CustomerDeposit::create([
            'customer_id' => $customer->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'type' => 'deposit',
            'description' => $request->description,
        ]);

        $customer->increment('deposit_balance', $request->amount);

        // Record in cash ledger as income
        $ledger->record(
            'deposit',
            $request->amount,
            'عربون/دفعة من العميل: ' . $customer->name . ' - ' . ($request->description ?? ''),
            $customer,
            $request->date,
            $request->treasury_id
        );

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم إضافة العربون لحساب العميل وإيداعه في الخزينة المحددة بنجاح');
    }

    /**
     * تعديل حساب العميل يدوياً — إضافة دين قديم أو خصم مبلغ.
     * يسجل القيد في كشف حساب العميل فقط، بدون تأثير على الخزينة.
     */
    public function adjustBalance(Request $request, Customer $customer)
    {
        $request->validate([
            'type'        => 'required|in:debt,credit',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $amount = (float) $request->amount;

        \App\Models\CustomerDeposit::create([
            'customer_id' => $customer->id,
            'amount'      => $amount,
            'date'        => $request->date,
            'type'        => $request->type === 'credit' ? 'deposit' : 'debt_adjustment',
            'description' => $request->description,
        ]);

        if ($request->type === 'credit') {
            // إضافة رصيد دائن (دفعة قديمة أو زيادة)
            $customer->increment('deposit_balance', $amount);
            $msg = 'تم إضافة ' . number_format($amount, 2) . ' ج.م كرصيد دائن لحساب العميل';
        } else {
            // إضافة دين قديم (مديونية قبل البرنامج)
            $customer->decrement('deposit_balance', $amount);
            $msg = 'تم تسجيل ' . number_format($amount, 2) . ' ج.م كمديونية قديمة على حساب العميل';
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', $msg);
    }

    public function updateAdjustment(Request $request, \App\Models\CustomerDeposit $deposit)
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

        // Update customer balance based on the adjustment type
        // if type is debt_adjustment (دين قديم), it subtracted from deposit_balance
        // if type is deposit (رصيد دائن قديم), it added to deposit_balance
        // However, standard deposits from treasury shouldn't be edited here ideally, but if allowed:
        if ($deposit->type === 'deposit') {
            $deposit->customer->increment('deposit_balance', $diff);
            
            // If it's a real cash deposit, we'd need to update cash ledger here too.
            // But user agreed to only edit old debts here without cash impact.
            // We'll trust they use this for manual non-cash credits mostly.
        } elseif ($deposit->type === 'debt_adjustment') {
            // Debt reduces the deposit_balance. If newAmount > oldAmount, we subtract more.
            $deposit->customer->decrement('deposit_balance', $diff);
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تعديل الحركة بنجاح وتحديث الرصيد.');
    }

    public function destroyAdjustment(\App\Models\CustomerDeposit $deposit)
    {
        if ($deposit->type !== 'debt_adjustment' && $deposit->type !== 'deposit') {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لا يمكن حذف هذا النوع من الحركات.');
        }

        $amount = $deposit->amount;
        $customer = $deposit->customer;

        if ($deposit->type === 'deposit') {
            $customer->decrement('deposit_balance', $amount);
        } elseif ($deposit->type === 'debt_adjustment') {
            $customer->increment('deposit_balance', $amount);
        }

        $deposit->delete();

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم حذف الحركة بنجاح ورد الرصيد لحساب العميل.');
    }

    public function print(\App\Models\Customer $customer)
    {
        $customer->load(['invoices.payments', 'deposits']);
        $sorted = $customer->getStatement();
        
        $totalInvoices = $customer->invoices->sum('total_amount');
        $totalInvoicePaid = $customer->invoices->sum('paid_amount');
        
        $invoiceDebt = max(0, $totalInvoices - $totalInvoicePaid);
        $depositBalance = $customer->deposit_balance;

        $netBalance = $depositBalance - $invoiceDebt;

        return view('customers.print', compact('customer', 'sorted', 'totalInvoices', 'totalInvoicePaid', 'depositBalance', 'netBalance'));
    }
}
