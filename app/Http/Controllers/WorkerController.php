<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ClearsLayoutCache;
use Illuminate\Http\Request;
use App\Models\Worker;
use App\Models\SalaryRecord;
use App\Models\WorkerAdvance;
use App\Models\WorkerProduction;
use App\Models\WorkerPenalty;

class WorkerController extends Controller
{
    use ClearsLayoutCache;
    public function index(Request $request)
    {
        $query = Worker::latest();
        if (!$request->has('show_inactive')) {
            $query->where('is_active', true);
        }
        $workers = $query->paginate(50);
        return view('workers.index', compact('workers'));
    }

    public function store(Request $request)
    {
        Log::info('Worker store request received', ['data' => $request->all()]);
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'national_id' => 'nullable|string|max:20',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'hire_date' => 'required|date',
                'job_title' => 'required|string|max:100',
                'worker_type' => 'required|in:daily,production',
                'production_role' => 'nullable|in:machinist,scissors',
                'shift_type' => 'required|in:morning,evening,night',
                'factory_location' => 'required|string',
                'daily_wage' => 'nullable|numeric|min:0',
                'hourly_wage' => 'nullable|numeric|min:0',
                'shift_wage' => 'nullable|numeric|min:0',
                'wage_system' => 'nullable|in:shift,piece',
                'piece_price' => 'nullable|numeric|min:0',
                'daily_wage_type' => 'nullable|in:daily,hourly',
                'notes' => 'nullable|string',
            ]);

            // Generate code without blocking lock (WAL mode handles concurrency)
            $lastId = Worker::max('id') ?? 0;
            $nextNum = $lastId + 1;
            $code = 'W-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            $isAdmin = auth()->user()->hasRole('Admin');

            Worker::create(array_merge($request->all(), [
                'code' => $code,
                'is_active' => true,
                'production_role' => $request->worker_type === 'production' ? ($request->production_role ?: 'machinist') : null,
                'daily_wage' => $isAdmin ? ($request->daily_wage ?: 0) : 0,
                'hourly_wage' => $isAdmin ? ($request->worker_type == 'daily' ? ($request->hourly_wage ?: 0) : 0) : 0,
                'wage_system' => $request->worker_type === 'production' ? ($request->wage_system ?: 'shift') : 'shift',
                'daily_wage_type' => $request->worker_type === 'daily' ? ($request->daily_wage_type ?: 'daily') : null,
                'shift_wage' => $isAdmin ? ($request->shift_wage ?: 0) : 0,
            ]));

            $this->clearLayoutCache();

            if ($request->filled('return_url')) {
                return redirect($request->return_url)->with('success', 'تم إضافة الموظف بنجاح');
            }
            return redirect()->route('workers.index')->with('success', 'تم إضافة الموظف بنجاح');
        } catch (\Exception $e) {
            Log::error('Worker store error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء حفظ الموظف. الرجاء المحاولة مرة أخرى.');
        }
    }


    public function update(Request $request, Worker $worker)
    {
        Log::info('Worker update request', $request->all());
        try {
        $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'hire_date' => 'required|date',
            'job_title' => 'required|string|max:100',
            'worker_type' => 'required|in:daily,production',
            'production_role' => 'nullable|in:machinist,scissors',
            'shift_type' => 'required|in:morning,evening,night',
            'factory_location' => 'required|string',
            'daily_wage' => 'nullable|numeric|min:0',
            'hourly_wage' => 'nullable|numeric|min:0',
            'shift_wage' => 'nullable|numeric|min:0',
            'wage_system' => 'nullable|in:shift,piece',
            'piece_price' => 'nullable|numeric|min:0',
            'daily_wage_type' => 'nullable|in:daily,hourly',
            'notes' => 'nullable|string',
        ]);

        $isAdmin = auth()->user()->hasRole('Admin');
        
        $updateData = array_merge($request->except('is_active'), [
            'is_active' => $request->has('is_active'),
            'production_role' => $request->worker_type === 'production' ? ($request->production_role ?: 'machinist') : null,
        ]);





            if ($isAdmin) {
                $updateData['daily_wage'] = $request->daily_wage ?: 0;
                $updateData['hourly_wage'] = $request->worker_type == 'daily' ? ($request->hourly_wage ?: 0) : 0;
                $updateData['wage_system'] = $request->worker_type === 'production' ? ($request->wage_system ?: 'shift') : 'shift';
                $updateData['daily_wage_type'] = $request->worker_type === 'daily' ? ($request->daily_wage_type ?: 'daily') : null;
                $updateData['shift_wage'] = $request->shift_wage ?: 0;
            }

            $worker->update($updateData);
            $this->clearLayoutCache();
            
            if ($request->filled('return_url')) {
                return redirect($request->return_url)->with('success', 'تم تعديل بيانات الموظف بنجاح');
            }
            return redirect()->route('workers.index')->with('success', 'تم تعديل بيانات الموظف بنجاح');
        } catch (\Exception $e) {
            Log::error('Worker update error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء تعديل الموظف. الرجاء المحاولة مرة أخرى.');
        }
    }

    public function destroy(Worker $worker)
    {
        // حماية من حذف موظف له سجلات مالية
        $hasSalaries = \App\Models\SalaryRecord::where('worker_id', $worker->id)->exists();
        $hasAdvances = \App\Models\WorkerAdvance::where('worker_id', $worker->id)->where('is_deducted', false)->exists();
        $hasProductions = \App\Models\WorkerProduction::where('worker_id', $worker->id)->exists();

        if ($hasSalaries || $hasAdvances || $hasProductions) {
            return redirect()->route('workers.index')->with('error', 'لا يمكن حذف موظف له سجلات رواتب أو سلف أو إنتاج. يمكنك إلغاء تفعيله بدلاً من ذلك.');
        }

        // حذف الحضور والجزاءات المرتبطة
        $worker->attendances()->delete();
        \App\Models\WorkerPenalty::where('worker_id', $worker->id)->delete();
        $worker->delete();
        return redirect()->route('workers.index')->with('success', 'تم حذف الموظف بنجاح');
    }

    public function show(Worker $worker)
    {
        return $this->statement($worker);
    }

    public function statement(Worker $worker)
    {
        $advances = \App\Models\WorkerAdvance::where('worker_id', $worker->id)->orderBy('date', 'desc')->get();
        $penalties = \App\Models\WorkerPenalty::where('worker_id', $worker->id)->orderBy('date', 'desc')->get();
        $salaries = \App\Models\SalaryRecord::where('worker_id', $worker->id)->orderBy('payment_date', 'desc')->get();
        
        $totalAdvancesBorrowed = $advances->sum('amount');
        $totalAdvancesPaid = $advances->where('is_deducted', true)->sum('amount'); // This is a bit simplified, but for a true statement we might just show them. Actually, since we roll them over, summing them isn't mathematically sound for total debt, the active ones are the debt.
        
        $currentDebt = $advances->where('is_deducted', false)->sum('amount');
        $currentPenalties = $penalties->where('is_deducted', false)->sum('amount');
        
        // Let's create a combined timeline
        $timeline = collect();
        
        foreach($advances as $a) {
            $timeline->push([
                'date' => $a->date,
                'type' => 'advance_taken',
                'amount' => $a->amount,
                'notes' => $a->notes ?? 'سلفة نقدية',
                'icon' => 'bi-cash-coin text-warning',
                'color' => 'warning'
            ]);
        }
        
        foreach($penalties as $p) {
            $reason = $p->reason_type == 'absent' ? 'غياب' : ($p->reason_type == 'damage' ? 'إتلاف' : 'جزاء');
            $timeline->push([
                'date' => $p->date,
                'type' => 'penalty',
                'amount' => $p->amount,
                'notes' => 'خصم/جزاء: ' . $reason . ' - ' . ($p->notes ?? ''),
                'icon' => 'bi-exclamation-triangle text-danger',
                'color' => 'danger'
            ]);
        }
        
        foreach($salaries as $s) {
            $timeline->push([
                'date' => $s->payment_date,
                'type' => 'salary_paid',
                'amount' => $s->net_salary,
                'notes' => 'صرف راتب أسبوع (' . $s->start_date . ' إلى ' . $s->end_date . ') - خصم سلف: ' . $s->advances,
                'icon' => 'bi-check-circle text-success',
                'color' => 'success'
            ]);
            
            if ($s->advances > 0) {
                $timeline->push([
                    'date' => $s->payment_date,
                    'type' => 'advance_paid',
                    'amount' => $s->advances,
                    'notes' => 'سداد سلفة بالخصم من الراتب',
                    'icon' => 'bi-arrow-down-right-circle text-primary',
                    'color' => 'primary'
                ]);
            }
        }
        
        $timeline = $timeline->sortByDesc('date')->values();
        
        return view('workers.statement', compact('worker', 'timeline', 'currentDebt', 'currentPenalties'));
    }

    public function adjustBalance(Request $request, Worker $worker)
    {
        $request->validate([
            'type' => 'required|in:advance,penalty,credit',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'required|string',
        ]);

        if ($request->type === 'advance') {
            \App\Models\WorkerAdvance::create([
                'worker_id' => $worker->id,
                'amount' => $request->amount,
                'date' => $request->date,
                'notes' => '[تسوية يدوية] ' . $request->notes,
                'is_deducted' => false,
            ]);
        } elseif ($request->type === 'penalty') {
            \App\Models\WorkerPenalty::create([
                'worker_id' => $worker->id,
                'amount' => $request->amount,
                'date' => $request->date,
                'reason_type' => 'other',
                'notes' => '[تسوية يدوية] ' . $request->notes,
                'is_deducted' => false,
            ]);
        } elseif ($request->type === 'credit') {
            // A credit reduces debt. We can add a negative advance or just mark oldest advances as deducted
            // The simplest way to handle credits in this system is a negative advance.
            \App\Models\WorkerAdvance::create([
                'worker_id' => $worker->id,
                'amount' => -$request->amount,
                'date' => $request->date,
                'notes' => '[تسوية إسقاط/رصيد دائن] ' . $request->notes,
                'is_deducted' => false,
            ]);
        }

        return redirect()->back()->with('success', 'تم تنفيذ التسوية بنجاح.');
    }

    public function resetBalance(Worker $worker)
    {
        // Mark all pending advances as deducted — use PHP loop (SQLite has no CONCAT)
        \App\Models\WorkerAdvance::where('worker_id', $worker->id)
            ->where('is_deducted', false)
            ->get()
            ->each(function ($adv) {
                $adv->update([
                    'is_deducted' => true,
                    'notes' => ($adv->notes ? $adv->notes . ' ' : '') . '[تم الإسقاط وتصفير الحساب]',
                ]);
            });

        \App\Models\WorkerPenalty::where('worker_id', $worker->id)
            ->where('is_deducted', false)
            ->get()
            ->each(function ($pen) {
                $pen->update([
                    'is_deducted' => true,
                    'notes' => ($pen->notes ? $pen->notes . ' ' : '') . '[تم الإسقاط وتصفير الحساب]',
                ]);
            });

        return redirect()->back()->with('success', 'تم تصفير حساب الموظف بالكامل.');
    }
}
