<?php

namespace App\Http\Controllers;

use App\Models\Treasury;
use App\Models\CashTransaction;
use App\Services\CashLedgerService;
use Illuminate\Http\Request;

class TreasuryController extends Controller
{
    // The main treasury dashboard is now routed to expenses.index 
    // to combine all financial operations in one screen.

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,wallet,custody',
            'initial_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $treasury = Treasury::create([
            'name' => $request->name,
            'type' => $request->type,
            'initial_balance' => $request->initial_balance,
            'current_balance' => $request->initial_balance,
            'is_active' => true,
            'notes' => $request->notes,
        ]);

        if ($request->initial_balance > 0) {
            CashTransaction::create([
                'treasury_id' => $treasury->id,
                'type' => 'adjustment',
                'amount' => $request->initial_balance,
                'balance_after' => $request->initial_balance,
                'description' => 'الرصيد الافتتاحي للخزينة',
                'transaction_date' => now()->toDateString(),
            ]);
        }

        return redirect()->route('expenses.index')->with('success', 'تم إنشاء الخزينة بنجاح');
    }

    public function transfer(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'from_treasury_id' => 'required|exists:treasuries,id',
            'to_treasury_id' => 'required|exists:treasuries,id|different:from_treasury_id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Check sufficient balance
        $fromTreasury = Treasury::findOrFail($request->from_treasury_id);
        if ($fromTreasury->current_balance < $request->amount) {
            return redirect()->route('expenses.index')->with('error', 'الرصيد غير كافٍ في الخزينة المحول منها للقيام بعملية التحويل');
        }

        $ledger->transfer(
            $request->from_treasury_id,
            $request->to_treasury_id,
            $request->amount,
            $request->note ?: 'تحويل مالي بين الخزائن',
            $request->date
        );

        return redirect()->route('expenses.index')->with('success', 'تم تحويل المبلغ بنجاح');
    }

    public function deposit(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
        ]);

        $ledger->record('deposit', $request->amount, $request->description, null, $request->transaction_date, $request->treasury_id);

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل الإيداع بنجاح');
    }

    public function withdraw(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
        ]);

        $ledger->record('expense', $request->amount, $request->description, null, $request->transaction_date, $request->treasury_id);

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل السحب بنجاح');
    }
    public function printLedger(Request $request)
    {
        $request->validate([
            'treasury_id' => 'required|exists:treasuries,id'
        ]);

        $treasury = Treasury::findOrFail($request->treasury_id);
        $transactions = CashTransaction::where('treasury_id', $treasury->id)
                            ->orderBy('transaction_date', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        // Pass simple string indicating it's a full ledger report
        $reportTitle = "كشف حركة خزينة شامل: " . $treasury->name;

        // Since we don't have a specific ledger print view right now, 
        // we can reuse expenses.print if we format it properly, or create a simple print view.
        // I will reuse the generic print view we have or we will make a quick inline view for the ledger.
        
        return view('expenses.print_report', [
            'transactions' => $transactions,
            'title' => $reportTitle,
            'settings' => $settings,
            'filter' => 'all', // all time
            'totalIn' => $transactions->where('amount', '>', 0)->sum('amount'),
            'totalOut' => $transactions->where('amount', '<', 0)->sum(function($t) { return abs($t->amount); }),
            'finalBalance' => $treasury->current_balance,
            'treasuryName' => $treasury->name
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $treasury = Treasury::findOrFail($id);
        
        // منع حذف الخزينة الرئيسية أو خزينة عهدة المحاسب
        if ($treasury->is_default || $treasury->type == 'custody') {
            return redirect()->route('expenses.index')->with('error', 'عفواً، لا يمكن حذف الخزينة الرئيسية أو خزينة العهدة اليومية.');
        }

        // لو الخزنة فيها رصيد أو حركات، يتم نقل الحركات للخزينة الرئيسية حتى لا تضيع البيانات
        $mainTreasury = Treasury::where('is_default', true)->first();
        if ($mainTreasury) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($treasury, $mainTreasury) {
                // نقل الرصيد
                $mainTreasury->increment('current_balance', $treasury->current_balance);
                
                // نقل حركات الخزينة
                CashTransaction::where('treasury_id', $treasury->id)->update([
                    'treasury_id' => $mainTreasury->id,
                    'description' => \Illuminate\Support\Facades\DB::raw("description || ' (منقول من الخزينة المحذوفة: " . $treasury->name . ")'")
                ]);
                
                // حذف الخزينة
                $treasury->delete();
            });
            return redirect()->route('expenses.index')->with('success', 'تم حذف الخزينة ونقل رصيدها وحركاتها إلى الخزينة الرئيسية بنجاح.');
        }

        return redirect()->route('expenses.index')->with('error', 'حدث خطأ، لا توجد خزينة رئيسية.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $treasury = Treasury::findOrFail($id);
        $treasury->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'تم تعديل اسم الخزينة بنجاح');
    }
}
