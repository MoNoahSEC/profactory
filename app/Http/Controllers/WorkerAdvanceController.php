<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Worker;
use App\Models\WorkerAdvance;
use App\Services\CashLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkerAdvanceController extends Controller
{
    use ClearsLayoutCache;
    public function index(Request $request)
    {
        $this->clearLayoutCache();
        return redirect()->route('expenses.index', ['#advances-tab']);
    }

    /**
     * تسجيل سلفة جديدة + خصم من الخزينة + زيادة رصيد الموظف
     */
    public function store(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'worker_id'   => 'required|exists:workers,id',
            'treasury_id' => 'required|exists:treasuries,id',
            'amount'      => 'required|numeric|min:1',
            'date'        => 'required|date',
            'notes'       => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $ledger) {
            $worker = Worker::lockForUpdate()->find($request->worker_id);

            $advance = WorkerAdvance::create([
                'worker_id'   => $request->worker_id,
                'amount'      => $request->amount,
                'date'        => $request->date,
                'is_deducted' => false,
                'notes'       => $request->notes,
            ]);

            // زيادة رصيد الدين
            $worker->increment('pending_advance_balance', $request->amount);

            // خصم من الخزينة المحددة فوراً
            $ledger->record(
                'worker_advance',
                $request->amount,
                'سلفة نقدية: ' . $worker->name . ($request->notes ? ' — ' . $request->notes : ''),
                $advance,
                $request->date,
                $request->treasury_id
            );
        });

        $this->clearLayoutCache();
        return redirect()->route('expenses.index', ['#advances-tab'])
            ->with('success', 'تم تسجيل السلفة وخصم ' . number_format($request->amount, 0) . ' ج.م من الخزينة.');
    }

    /**
     * حذف سلفة + إرجاع للخزينة + إنقاص رصيد الموظف
     */
    public function destroy(WorkerAdvance $advance, CashLedgerService $ledger)
    {
        if ($advance->is_deducted) {
            $this->clearLayoutCache();
        return redirect()->route('expenses.index', ['#advances-tab'])
                ->with('error', 'لا يمكن حذف سلفة تم خصمها بالفعل من الراتب.');
        }

        DB::transaction(function () use ($advance, $ledger) {
            $worker = Worker::lockForUpdate()->find($advance->worker_id);

            // إنقاص رصيد الموظف
            $newBalance = max(0, $worker->pending_advance_balance - $advance->amount);
            $worker->update(['pending_advance_balance' => $newBalance]);

            // حذف القيد من الخزينة
            \App\Models\CashTransaction::where('reference_type', get_class($advance))
                ->where('reference_id', $advance->id)
                ->delete();

            $advance->delete();
            $ledger->recalculateLedger();
        });

        $this->clearLayoutCache();
        return redirect()->route('expenses.index', ['#advances-tab'])
            ->with('success', 'تم حذف السلفة وإرجاع المبلغ للخزينة.');
    }

    /**
     * طباعة إيصال سلفة
     */
    public function print(WorkerAdvance $advance)
    {
        $advance->load('worker');
        return view('advances.print', compact('advance'));
    }
}
