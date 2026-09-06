<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Attendance;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Worker;
use App\Models\WorkerProduction;
use App\Services\WorkerWageCalculationService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ClearsLayoutCache;
    public function __construct(private WorkerWageCalculationService $wageService) {}
    /**
     * Unified attendance page: ALL workers shown.
     * Daily workers => present/absent
     * Production workers => choose product + quantity = present
     */
    public function fastLogging(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $allWorkers = Worker::where('is_active', true)->orderBy('worker_type')->orderBy('name')->get();
        $products = Product::where('is_active', true)->get();
        $scissorsWorkers = Worker::where('is_active', true)->scissors()->get();

        // Get today's records to pre-fill the form
        $todayRecords = Attendance::where('date', $date)->pluck('status', 'worker_id')->toArray();
        $todayProductions = WorkerProduction::where('date', $date)->get()->keyBy('worker_id');

        return view('attendance.fast', compact('allWorkers', 'products', 'scissorsWorkers', 'date', 'todayRecords', 'todayProductions'));
    }

    /**
     * Weekly/Monthly Attendance Grid for Supervisors (No Financials)
     */
    public function weeklyGrid(Request $request)
    {
        $today = \Carbon\Carbon::today();
        
        // Find the most recent Saturday (beginning of this week)
        $currentWeekStart = $today->copy();
        while ($currentWeekStart->dayOfWeek !== \Carbon\Carbon::SATURDAY) {
            $currentWeekStart->subDay();
        }

        $startDateStr = $request->get('start_date', $currentWeekStart->format('Y-m-d'));
        $startDate = \Carbon\Carbon::parse($startDateStr);
        $endDate = $startDate->copy()->addDays(6);
        $endDateStr = $endDate->format('Y-m-d');

        $workers = Worker::where('is_active', true)->orderBy('worker_type')->get();

        // Get all days for columns
        $days = [];
        for ($i = 0; $i <= 6; $i++) {
            $date = $startDate->copy()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'name' => $date->translatedFormat('l'),
                'day_month' => $date->format('d/m'),
            ];
        }

        // Fetch all attendances and productions for this week
        $attendances = Attendance::whereBetween('date', [$startDateStr, $endDateStr])->get()->groupBy('worker_id');
        $productions = WorkerProduction::whereBetween('date', [$startDateStr, $endDateStr])->get()->groupBy('worker_id');
        
        $grid = [];

        foreach ($workers as $worker) {
            $wId = $worker->id;
            $grid[$wId] = [
                'worker' => $worker,
                'days' => [],
            ];

            $workerAttendances = $attendances[$wId] ?? collect();
            $workerProductions = $productions[$wId] ?? collect();

            $totalWorkingDays = 0;

            foreach ($days as $day) {
                $date = $day['date'];
                $dayAtt = $workerAttendances->first(fn($a) => $a->date->format('Y-m-d') === $date);
                $dayProd = $workerProductions->first(fn($p) => $p->date->format('Y-m-d') === $date);

                $status = $dayAtt ? $dayAtt->status : 'absent';
                
                if ($status === 'present') {
                    $totalWorkingDays++;
                }

                $grid[$wId]['days'][] = [
                    'date' => $date,
                    'status' => $status,
                    'has_production' => $dayProd ? true : false,
                ];
            }

            $grid[$wId]['total_days'] = $totalWorkingDays;
        }

        return view('attendance.weekly', compact('days', 'grid', 'startDateStr', 'endDateStr'));
    }

    /**
     * Store unified attendance for ALL workers at once.
     */
    public function storeUnified(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'workers' => 'required|array',
        ]);

        $date = $request->date;
        $saved = 0;

        foreach ($request->workers as $workerId => $data) {
            $worker = Worker::find($workerId);
            if (!$worker) continue;
            
            $type = $data['type'] ?? 'daily';

            if ($type === 'daily') {
                // Daily worker: save attendance record
                Attendance::updateOrCreate(
                    ['worker_id' => $workerId, 'date' => $date],
                    [
                        'status' => $data['status'] ?? 'absent',
                        'notes' => $data['notes'] ?? null,
                    ]
                );
                $saved++;
            } else {
                // Production worker
                $productId = $data['product_id'] ?? null;
                $quantity = intval($data['quantity'] ?? 0);
                $scissorsId = $data['scissors_id'] ?? null;

                if ($productId && $quantity > 0) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    $pay = $this->wageService->calculate($worker, $product, $quantity);
                    
                    // Mark as present
                    Attendance::updateOrCreate(
                        ['worker_id' => $workerId, 'date' => $date],
                        [
                            'status' => 'present',
                            'notes' => $data['notes'] ?? null,
                        ]
                    );

                    // Track old quantity BEFORE update so we only add the difference to inventory
                    $existingProd = WorkerProduction::where('worker_id', $workerId)
                        ->where('date', $date)
                        ->where('product_id', $productId)
                        ->first();
                    $oldQty = $existingProd ? $existingProd->quantity : 0;

                    // Record production for this worker
                    WorkerProduction::updateOrCreate(
                        ['worker_id' => $workerId, 'date' => $date, 'product_id' => $productId],
                        [
                            'production_role' => $worker->production_role ?? 'machinist',
                            'scissors_worker_id' => ($worker->production_role === 'machinist' && $scissorsId) ? $scissorsId : null,
                            'quantity' => $quantity,
                            'labor_cost_per_piece' => $pay['labor_cost_per_piece'],
                            'total_pay' => $pay['total_pay'],
                            'inventory_added' => $worker->production_role !== 'scissors',
                            'notes' => $data['notes'] ?? null,
                        ]
                    );

                    // If Machinist selected a Scissors worker, create/update record for the scissors worker too!
                    if ($worker->production_role === 'machinist' && $scissorsId) {
                        $scissorsWorker = Worker::find($scissorsId);
                        if ($scissorsWorker) {
                            $scPay = $this->wageService->calculate($scissorsWorker, $product, $quantity);
                            WorkerProduction::updateOrCreate(
                                ['worker_id' => $scissorsId, 'date' => $date, 'product_id' => $productId],
                                [
                                    'production_role' => 'scissors',
                                    'machinist_worker_id' => $workerId,
                                    'quantity' => $quantity,
                                    'labor_cost_per_piece' => $scPay['labor_cost_per_piece'],
                                    'total_pay' => $scPay['total_pay'],
                                    'inventory_added' => false,
                                    'notes' => 'مقصوصات للمكنجي: ' . $worker->name,
                                ]
                            );
                            // Also mark the scissors worker as present
                            Attendance::updateOrCreate(
                                ['worker_id' => $scissorsId, 'date' => $date],
                                ['status' => 'present']
                            );
                        }
                    }

                    // Inventory: Only add the DIFFERENCE (new qty - old qty) to prevent double-counting
                    if ($worker->production_role !== 'scissors') {
                        $diff = $quantity - $oldQty;
                        if ($diff != 0) {
                            $inventory = \App\Models\Inventory::firstOrCreate(
                                ['product_id' => $productId],
                                ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
                            );
                            if ($diff > 0) {
                                $inventory->increment('quantity_in', $diff);
                                $inventory->increment('current_stock', $diff);
                                
                                // NEW LOGIC: Deduct Raw Materials from Stock
                                $product->loadMissing('materials');
                                foreach ($product->materials as $material) {
                                    $neededQty = $material->pivot->quantity_needed * $diff;
                                    $material->decrement('current_stock', $neededQty);
                                }
                            } else {
                                // Quantity reduced, adjust stock down
                                $absDiff = abs($diff);
                                $inventory->decrement('quantity_in', $absDiff);
                                $inventory->decrement('current_stock', $absDiff);
                                
                                // NEW LOGIC: Return Raw Materials to Stock if production was cancelled/reduced
                                $product->loadMissing('materials');
                                foreach ($product->materials as $material) {
                                    $neededQty = $material->pivot->quantity_needed * $absDiff;
                                    $material->increment('current_stock', $neededQty);
                                }
                            }
                            $inventory->update(['last_updated' => now()]);
                        }
                    }

                    $saved++;
                } else {
                    // No product selected = absent
                    Attendance::updateOrCreate(
                        ['worker_id' => $workerId, 'date' => $date],
                        [
                            'status' => 'absent',
                            'notes' => $data['notes'] ?? null,
                        ]
                    );
                    
                    // If they had a production record for today, we could delete it, but let's just leave it or set quantity 0.
                    $saved++;
                }
            }
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', "تم حفظ تحضير {$saved} موظف بنجاح وتم إضافة الإنتاج للمخزن فوراً.");
    }

    // Keep old methods for backward compatibility
    public function storeFastLogging(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
        ]);

        foreach ($request->attendance as $workerId => $data) {
            Attendance::updateOrCreate(
                ['worker_id' => $workerId, 'date' => $request->date],
                [
                    'status' => $data['status'],
                    'overtime_hours' => $data['overtime_hours'] ?? 0,
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم حفظ تحضير الموظفين اليوميين بنجاح');
    }

    public function storeProductionLogging(Request $request)
    {
        $this->clearLayoutCache();
        return redirect()->route('attendance.fast')->with('success', 'يرجى استخدام صفحة التحضير الموحدة الجديدة.');
    }

    public function index(Request $request)
    {
        $weekStart = $request->get('week_start', now()->startOfWeek(\Carbon\Carbon::SATURDAY)->format('Y-m-d'));
        $startDate = \Carbon\Carbon::parse($weekStart);
        
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $startDate->copy()->addDays($i);
        }
        
        $workers = Worker::where('is_active', true)->orderBy('worker_type')->get();
        $attendances = Attendance::whereBetween('date', [$dates[0]->format('Y-m-d'), $dates[6]->format('Y-m-d')])->get();
        $productions = WorkerProduction::with('product')->whereBetween('date', [$dates[0]->format('Y-m-d'), $dates[6]->format('Y-m-d')])->get();
        $products = Product::where('is_active', true)->get();
        $scissorsWorkers = Worker::where('is_active', true)->scissors()->get();

        return view('attendance.weekly', compact('workers', 'dates', 'attendances', 'productions', 'products', 'scissorsWorkers', 'weekStart'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,half_day,holiday',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
        ]);

        $workedHours = 0;
        if ($request->status === 'present' && $request->time_in && $request->time_out) {
            $in = \Carbon\Carbon::parse($request->time_in);
            $out = \Carbon\Carbon::parse($request->time_out);
            if ($out->lessThan($in)) {
                $out->addDay(); // Handles night shifts crossing midnight
            }
            $workedHours = $in->diffInMinutes($out) / 60;
        }

        Attendance::updateOrCreate(
            ['worker_id' => $request->worker_id, 'date' => $request->date],
            [
                'status' => $request->status,
                'time_in' => $request->time_in,
                'time_out' => $request->time_out,
                'worked_hours' => round($workedHours, 2),
                'overtime_hours' => $request->overtime_hours ?? 0,
                'notes' => $request->notes ?? null
            ]
        );

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تسجيل الحضور بنجاح');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $request->validate([
            'status' => 'required|in:present,absent,late,half_day,holiday',
        ]);

        $attendance->update($request->all());

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تعديل بيانات الحضور');
    }
}
