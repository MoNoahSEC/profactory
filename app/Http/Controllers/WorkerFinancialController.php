<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\WorkerAdvance;
use App\Models\WorkerPenalty;
use App\Models\WorkerBonus;
use Illuminate\Http\Request;

class WorkerFinancialController extends Controller
{
    public function index()
    {
        // جلب جميع المعاملات من الجداول الثلاثة ودمجها لترتيبها زمنياً
        $advances = WorkerAdvance::with('worker')->get()->map(function ($item) {
            $item->record_type = 'advance';
            return $item;
        });

        $penalties = WorkerPenalty::with('worker')->get()->map(function ($item) {
            $item->record_type = 'penalty';
            return $item;
        });

        $bonuses = WorkerBonus::with('worker')->get()->map(function ($item) {
            $item->record_type = 'bonus';
            // map is_paid to is_deducted so we have a unified interface flag
            $item->is_deducted = $item->is_paid;
            return $item;
        });

        // دمج وترتيب تنازلي حسب التاريخ
        $transactions = $advances->concat($penalties)->concat($bonuses)->sortByDesc('date');

        // جلب أسماء الموظفين النشطين وترتيبهم أبجدياً
        $workers = Worker::where('is_active', true)->orderBy('name')->get();

        return view('financials.index', compact('transactions', 'workers'));
    }

    public function store(Request $request, \App\Services\CashLedgerService $ledger)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'type' => 'required|in:advance,penalty,bonus',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $worker = Worker::find($request->worker_id);

        if ($request->type === 'advance') {
            $advance = WorkerAdvance::create($request->except('type'));
            // السلفة تخصم من الخزينة فوراً
            $ledger->record(
                'worker_advance',
                $request->amount,
                'سلفة نقدية للموظف: ' . $worker->name . ' — ' . ($request->notes ?? ''),
                $advance,
                $request->date
            );
            $msg = 'تم تسجيل السلفة وخصم ' . number_format($request->amount) . ' ج.م من الخزينة فوراً.';
        } elseif ($request->type === 'penalty') {
            $data = $request->except('type');
            if (empty($data['reason_type'])) {
                $data['reason_type'] = 'other';
            }
            WorkerPenalty::create($data);
            $msg = 'تم تسجيل الخصم بنجاح.';
        } elseif ($request->type === 'bonus') {
            WorkerBonus::create($request->except('type'));
            $msg = 'تم تسجيل المكافأة بنجاح وسيتم صرفها مع الراتب.';
        }

        return redirect()->route('financials.index')->with('success', $msg);
    }

    public function destroy(Request $request, $id)
    {
        $type = $request->query('type');

        if ($type === 'advance') {
            $advance = WorkerAdvance::findOrFail($id);
            if ($advance->is_deducted) {
                return redirect()->route('financials.index')->with('error', 'لا يمكن حذف سلفة تم خصمها بالفعل من الراتب');
            }
            \App\Models\CashTransaction::where('reference_type', get_class($advance))
                ->where('reference_id', $advance->id)
                ->delete();
            $advance->delete();
            app(\App\Services\CashLedgerService::class)->recalculateLedger();
            
        } elseif ($type === 'penalty') {
            $penalty = WorkerPenalty::findOrFail($id);
            if ($penalty->is_deducted) {
                return redirect()->route('financials.index')->with('error', 'لا يمكن حذف خصم تم تطبيقه بالفعل');
            }
            $penalty->delete();
            
        } elseif ($type === 'bonus') {
            $bonus = WorkerBonus::findOrFail($id);
            if ($bonus->is_paid) {
                return redirect()->route('financials.index')->with('error', 'لا يمكن حذف مكافأة تم صرفها بالفعل');
            }
            $bonus->delete();
        } else {
            return redirect()->route('financials.index')->with('error', 'نوع المعاملة غير صالح');
        }

        return redirect()->route('financials.index')->with('success', 'تم حذف السجل بنجاح.');
    }
}
