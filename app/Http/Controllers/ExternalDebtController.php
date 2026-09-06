<?php

namespace App\Http\Controllers;

use App\Models\DebtInstallment;
use App\Models\ExternalDebt;
use App\Services\CashLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExternalDebtController extends Controller
{
    /**
     * عرض قائمة الديون مع الملخصات.
     */
    public function index()
    {
        $debts = ExternalDebt::with(['installments'])->orderBy('created_at', 'desc')->get();

        // ── Stats ──
        $owedByUsTotal = $debts->where('type', 'owed_by_us')->where('status', '!=', 'paid')->sum('remaining_amount');
        $owedToUsTotal = $debts->where('type', 'owed_to_us')->where('status', '!=', 'paid')->sum('remaining_amount');

        // ── Supplier Debts ──
        $suppliers = \App\Models\Supplier::with('purchases')->get();
        $suppliersWithDebts = collect();
        $supplierDebtsTotal = 0;

        foreach ($suppliers as $supplier) {
            $totalPurchases = $supplier->purchases->sum('total_price');
            $totalPaid = $supplier->purchases->sum('paid_amount');
            $debt = $totalPurchases - $totalPaid;
            
            if ($debt > 0) {
                $supplierDebtsTotal += $debt;
                $supplier->calculated_debt = $debt;
                $supplier->total_purchases = $totalPurchases;
                $supplier->total_paid = $totalPaid;
                $suppliersWithDebts->push($supplier);
            }
        }

        // Add supplier debts to total "owed by us"
        $owedByUsTotal += $supplierDebtsTotal;

        $overdueInstallments = DebtInstallment::overdue()->count();
        $upcomingInstallments = DebtInstallment::upcoming(30)->count();

        // Upcoming installments this month
        $upcomingThisMonth = DebtInstallment::where('status', '!=', 'paid')
            ->whereBetween('due_date', [Carbon::today(), Carbon::today()->endOfMonth()])
            ->with('debt')
            ->orderBy('due_date')
            ->get();

        return view('debts.index', compact(
            'debts',
            'owedByUsTotal',
            'owedToUsTotal',
            'overdueInstallments',
            'upcomingInstallments',
            'upcomingThisMonth',
            'suppliersWithDebts',
            'supplierDebtsTotal'
        ));
    }

    /**
     * عرض تفاصيل دين واحد مع أقساطه.
     */
    public function show(ExternalDebt $debt)
    {
        $debt->load('installments');

        // Cash transactions linked to this debt
        $transactions = \App\Models\CashTransaction::where('reference_type', ExternalDebt::class)
            ->where('reference_id', $debt->id)
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return view('debts.show', compact('debt', 'transactions'));
    }

    /**
     * إنشاء دين جديد (مع أو بدون أقساط).
     */
    public function store(Request $request)
    {
        $request->validate([
            'party_name' => 'required|string|max:255',
            'type' => 'required|in:owed_to_us,owed_by_us',
            'amount' => 'required|numeric|min:0.01',
            'debt_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'has_installments' => 'nullable|boolean',
            'installments_count' => 'nullable|integer|min:2|max:120',
            'first_installment_date' => 'nullable|date',
            'installment_interval' => 'nullable|in:monthly,weekly',
        ]);

        $debt = ExternalDebt::create([
            'party_name' => $request->party_name,
            'type' => $request->type,
            'amount' => $request->amount,
            'total_amount' => $request->amount,
            'paid_amount' => 0,
            'installments_count' => 0,
            'debt_date' => $request->debt_date ?? now()->toDateString(),
            'due_date' => $request->due_date,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        // Generate installments if requested
        if ($request->has_installments && $request->installments_count >= 2) {
            $startDate = $request->first_installment_date ?? now()->addMonth()->toDateString();
            $interval = $request->installment_interval ?? 'monthly';
            $debt->generateInstallments($request->installments_count, $startDate, $interval);
        }

        return redirect()->route('debts.index')->with('success', 'تم إضافة الدين بنجاح' . ($debt->installments_count > 0 ? " وتقسيطه على {$debt->installments_count} قسط" : ''));
    }

    /**
     * تعديل بيانات الدين.
     */
    public function update(Request $request, ExternalDebt $debt)
    {
        $request->validate([
            'party_name' => 'required|string|max:255',
            'type' => 'required|in:owed_to_us,owed_by_us',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'nullable|date',
            'debt_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,overdue',
        ]);

        $oldTotal = (float) $debt->total_amount;
        $newTotal = (float) $request->amount;

        $debt->update([
            'party_name' => $request->party_name,
            'type' => $request->type,
            'amount' => $request->amount,
            'total_amount' => $newTotal,
            'due_date' => $request->due_date,
            'debt_date' => $request->debt_date,
            'notes' => $request->notes,
            'status' => $request->status,
        ]);

        // If total changed and there are installments, regenerate unpaid ones proportionally
        if ($oldTotal != $newTotal && $debt->installments_count > 0) {
            $unpaidInstallments = $debt->installments()->where('status', '!=', 'paid')->get();
            $remainingToDistribute = $newTotal - $debt->paid_amount;
            $count = $unpaidInstallments->count();

            if ($count > 0 && $remainingToDistribute > 0) {
                $perInstallment = round($remainingToDistribute / $count, 2);
                $remainder = $remainingToDistribute - ($perInstallment * $count);

                foreach ($unpaidInstallments as $i => $inst) {
                    $amt = $perInstallment;
                    if ($i === $count - 1) $amt += $remainder;
                    $inst->update(['amount' => $amt]);
                }
            }
        }

        return redirect()->route('debts.index')->with('success', 'تم تحديث بيانات الدين');
    }

    /**
     * سداد قسط محدد.
     */
    public function payInstallment(Request $request, ExternalDebt $debt, DebtInstallment $installment, CashLedgerService $ledger)
    {
        if ($installment->status === 'paid') {
            return redirect()->back()->with('error', 'تم سداد هذا القسط مسبقاً');
        }

        if ($installment->external_debt_id !== $debt->id) {
            abort(403);
        }

        $request->validate([
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $paidDate = $request->paid_date ?? now()->toDateString();

        // Mark installment as paid
        $installment->update([
            'status' => 'paid',
            'paid_date' => $paidDate,
            'notes' => $request->notes ?? $installment->notes,
        ]);

        // Update debt paid amount
        $debt->recordPayment($installment->amount);

        // Record in cash ledger
        $cashType = $debt->type === 'owed_to_us' ? 'installment_in' : 'installment_out';
        $direction = $debt->type === 'owed_to_us' ? 'مستلم من' : 'مدفوع لـ';

        $ledger->record(
            $cashType,
            $installment->amount,
            "قسط #{$installment->installment_number} {$direction}: {$debt->party_name}",
            $debt,
            $paidDate
        );

        $msg = "تم سداد القسط #{$installment->installment_number} بمبلغ " . number_format($installment->amount, 2) . " ج.م وتحديث الخزينة";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * سداد مبلغ جزئي (بدون ربط بقسط محدد).
     */
    public function payPartial(Request $request, ExternalDebt $debt, CashLedgerService $ledger)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $debt->remaining_amount,
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $paidDate = $request->paid_date ?? now()->toDateString();

        // Record payment on debt
        $debt->recordPayment($request->amount);

        // Record in cash ledger
        $cashType = $debt->type === 'owed_to_us' ? 'installment_in' : 'installment_out';
        $direction = $debt->type === 'owed_to_us' ? 'دفعة مستلمة من' : 'دفعة مسددة لـ';

        $ledger->record(
            $cashType,
            $request->amount,
            "{$direction}: {$debt->party_name}" . ($request->notes ? " — {$request->notes}" : ''),
            $debt,
            $paidDate
        );

        $msg = "تم سداد " . number_format($request->amount, 2) . " ج.م وتحديث الخزينة. المتبقي: " . number_format($debt->remaining_amount, 2) . " ج.م";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * سداد كامل المتبقي.
     */
    public function payAll(Request $request, ExternalDebt $debt, CashLedgerService $ledger)
    {
        if ($debt->status === 'paid') {
            return redirect()->back()->with('error', 'تم سداد هذا الدين بالكامل مسبقاً');
        }

        $remaining = $debt->remaining_amount;
        $paidDate = $request->paid_date ?? now()->toDateString();

        // Mark all unpaid installments as paid
        $debt->installments()->where('status', '!=', 'paid')->update([
            'status' => 'paid',
            'paid_date' => $paidDate,
        ]);

        // Update debt
        $debt->update([
            'paid_amount' => $debt->total_amount,
            'status' => 'paid',
        ]);

        // Record in cash ledger
        $cashType = $debt->type === 'owed_to_us' ? 'external_debt_in' : 'external_debt_out';
        $direction = $debt->type === 'owed_to_us' ? 'سداد كامل دين مستحق لنا من' : 'سداد كامل دين مستحق علينا لـ';

        $ledger->record(
            $cashType,
            $remaining,
            "{$direction}: {$debt->party_name}",
            $debt,
            $paidDate
        );

        return redirect()->back()->with('success', 'تم سداد الدين بالكامل وتحديث الخزينة بمبلغ ' . number_format($remaining, 2) . ' ج.م');
    }

    /**
     * حذف دين مع عكس قيوده المالية.
     */
    public function destroy(ExternalDebt $debt, CashLedgerService $ledger)
    {
        \App\Models\CashTransaction::where('reference_type', get_class($debt))
            ->where('reference_id', $debt->id)
            ->delete();

        // Installments cascade-deleted via FK
        $debt->delete();
        
        $ledger->recalculateLedger();

        return redirect()->route('debts.index')->with('success', 'تم حذف الدين من السجل وتحديث الخزينة كأنه لم يكن');
    }

    /**
     * طباعة سند الدين.
     */
    public function print(ExternalDebt $debt)
    {
        $debt->load('installments');
        return view('debts.print', compact('debt'));
    }
}
