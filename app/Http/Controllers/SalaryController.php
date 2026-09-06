<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Attendance;
use App\Models\Product;
use App\Models\SalaryRecord;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use App\Models\WorkerProduction;
use App\Services\CashLedgerService;
use App\Services\WorkerWageCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryController extends Controller
{
    use ClearsLayoutCache;
    public function __construct(private WorkerWageCalculationService $wageService) {}
    public function index(Request $request)
    {
        $today = Carbon::today();
        
        // Find the most recent Saturday (beginning of this week)
        $currentWeekStart = $today->copy();
        while ($currentWeekStart->dayOfWeek !== Carbon::SATURDAY) {
            $currentWeekStart->subDay();
        }

        $startDateStr = $request->get('start_date', $currentWeekStart->format('Y-m-d'));
        $startDate = Carbon::parse($startDateStr);
        $endDate = $startDate->copy()->addDays(6);
        $endDateStr = $endDate->format('Y-m-d');


        // Fetch active workers OR inactive workers that have activity this week or pending advances
        $allWorkers = Worker::where('is_active', true)
            ->orWhereHas('attendances', function ($query) use ($startDateStr, $endDateStr) {
                $query->whereBetween('date', [$startDateStr, $endDateStr]);
            })
            ->orWhereHas('productions', function ($query) use ($startDateStr, $endDateStr) {
                $query->whereBetween('date', [$startDateStr, $endDateStr]);
            })
            ->orWhere('pending_advance_balance', '>', 0)
            ->orderBy('name')
            ->get();
        
        $groupsOrder = [
            'مصنع الاقفاص',
            'مصنع البلاستيك',
            'مصنع التقفيل',
            'مصنع السحب'
        ];

        $groupedWorkers = collect();
        foreach ($groupsOrder as $group) {
            $groupWorkers = $allWorkers->where('factory_location', $group);
            if ($groupWorkers->isNotEmpty()) {
                $groupedWorkers->put($group, $groupWorkers);
            }
        }

        $otherWorkers = $allWorkers->whereNotIn('factory_location', $groupsOrder);
        if ($otherWorkers->isNotEmpty()) {
            $groupedWorkers->put('أخرى', $otherWorkers);
        }

        // Use the grouped (and ordered) workers for the grid so the UI headers work correctly
        $workers = $groupedWorkers->flatten(); 
        $products = Product::where('is_active', true)->get();

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
        $productions = WorkerProduction::with(['product'])->whereBetween('date', [$startDateStr, $endDateStr])->get()->groupBy('worker_id');
        
        // Advances: fetch ALL undeducted advances (not just up to this week)
        // This ensures old and new advances are shown and deducted at payday.
        $advances = WorkerAdvance::where('is_deducted', false)
            ->get()->groupBy('worker_id');
            
        $penalties = \App\Models\WorkerPenalty::where('is_deducted', false)
            ->get()->groupBy('worker_id');

        $bonuses = \App\Models\WorkerBonus::where('is_paid', false)
            ->get()->groupBy('worker_id');

        // Fetch generated salary records if paid
        $salaries = SalaryRecord::whereDate('start_date', $startDateStr)
            ->whereDate('end_date', $endDateStr)
            ->get()->keyBy('worker_id');

        // Prepare the Grid Data
        $grid = [];

        foreach ($workers as $worker) {
            $wId = $worker->id;
            $grid[$wId] = [
                'worker' => $worker,
                'days' => [],
                'total_pay' => 0,
                'total_advances' => 0,
                'total_penalties' => 0,
                'total_bonuses' => 0,
                'total_hours' => 0,
                'net_salary' => 0,
                'summary_text' => '',
                'salary_record' => $salaries[$wId] ?? null,
                'is_paid' => isset($salaries[$wId]) && $salaries[$wId]->payment_status === 'paid',
                'advances_list' => collect(), // For detailed display in pay modal and print
            ];

            $workerAttendances = $attendances[$wId] ?? collect();
            $workerProductions = $productions[$wId] ?? collect();
            $workerAdvances = $advances[$wId] ?? collect();
            $workerPenalties = $penalties[$wId] ?? collect();
            $workerBonuses = $bonuses[$wId] ?? collect();

            $totalAdvances = $workerAdvances->sum('amount');
            $totalPenalties = $workerPenalties->sum('amount');
            $totalBonuses = $workerBonuses->sum('amount');
            
            $grid[$wId]['total_advances'] = $totalAdvances;
            $grid[$wId]['total_penalties'] = $totalPenalties;
            $grid[$wId]['total_bonuses'] = $totalBonuses;
            $grid[$wId]['advances_list'] = $workerAdvances; // Keep the full list for print/modal

            $hasHourlyEntries = false;
            $totalWorkingDays = 0;    // Always counts calendar days
            $totalWorkingHours = 0;   // Counts actual worked hours
            $totalProductionPay = 0;
            $totalProductionQty = 0;

            foreach ($days as $day) {
                $date = $day['date'];
                
                // Fix: compare formatted date strings, not Carbon objects
                $dayAtt = $workerAttendances->first(fn($a) => $a->date->format('Y-m-d') === $date);
                $dayProds = $workerProductions->filter(fn($p) => $p->date->format('Y-m-d') === $date);

                $cell = [
                    'date' => $date,
                    'status' => $dayAtt ? $dayAtt->status : null,
                    'time_in' => $dayAtt ? ($dayAtt->time_in ? \Carbon\Carbon::parse($dayAtt->time_in)->format('H:i') : '') : '',
                    'time_out' => $dayAtt ? ($dayAtt->time_out ? \Carbon\Carbon::parse($dayAtt->time_out)->format('H:i') : '') : '',
                    'productions' => $dayProds->values()->toArray(),
                    'display_text' => '-',
                    'display_color' => 'text-muted',
                ];

                if ($worker->worker_type === 'daily') {
                    if ($cell['status'] === 'present') {
                        $cell['display_color'] = 'text-success fw-bold';
                        if ($dayAtt && $dayAtt->worked_hours > 0) {
                            $cell['display_text'] = $dayAtt->worked_hours . ' س';
                            $totalWorkingHours += $dayAtt->worked_hours;
                            $hasHourlyEntries = true;
                        } else {
                            $cell['display_text'] = 'حاضر';
                        }
                        // Always count 1 day regardless of hours
                        $totalWorkingDays += 1;
                    } elseif ($cell['status'] === 'half_day') {
                        $cell['display_text'] = 'نصف يوم';
                        $cell['display_color'] = 'text-warning fw-bold';
                        $totalWorkingDays += 0.5;
                        $totalWorkingHours += ($dayAtt && $dayAtt->worked_hours > 0) ? $dayAtt->worked_hours : 4;
                    } elseif ($cell['status'] === 'absent') {
                        $cell['display_text'] = 'غائب';
                        $cell['display_color'] = 'text-danger';
                    }
                } else {
                    if ($dayProds->count() > 0) {
                        $qty = $dayProds->sum('quantity');
                        
                        $grouped = $dayProds->groupBy('product_id')->map(function($group) {
                            $pName = $group->first()->product->name ?? '';
                            $total = $group->sum('quantity');
                            return "{$total} {$pName}";
                        })->implode('، ');
                        
                        $cell['display_text'] = $grouped;
                        $cell['display_color'] = 'text-primary fw-bold';
                        
                        foreach ($dayProds as $prod) {
                            // استخدام القيمة المحفوظة مباشرة بدلاً من إعادة الحساب في كل مرة (أسرع بكثير)
                            $totalProductionPay += (float) $prod->total_pay;
                        }
                        $totalProductionQty += $qty;
                    } elseif ($cell['status'] === 'absent') {
                        $cell['display_text'] = 'غائب';
                        $cell['display_color'] = 'text-danger';
                    }
                }

                $grid[$wId]['days'][$date] = $cell;
            }

            // Calculate Totals
            if ($worker->worker_type === 'daily') {
                $pay = 0;
                $summaryTxt = '';
                
                if ($worker->daily_wage_type === 'hourly') {
                    $pay = $totalWorkingHours * ($worker->hourly_wage > 0 ? $worker->hourly_wage : 0);
                    $summaryTxt = $totalWorkingHours > 0 ? $totalWorkingHours . ' س' : '';
                } else {
                    $pay = $totalWorkingDays * ($worker->daily_wage ?? 0);
                    $summaryTxt = $totalWorkingDays > 0 ? $totalWorkingDays . ' يوم' : '';
                    if ($totalWorkingHours > 0) {
                        $summaryTxt .= ($summaryTxt ? ' (' . $totalWorkingHours . ' س)' : $totalWorkingHours . ' س');
                    }
                }
                
                $grid[$wId]['total_pay'] = $pay;
                $grid[$wId]['summary_text'] = $summaryTxt;
                $grid[$wId]['total_working'] = $totalWorkingDays;
                $grid[$wId]['total_hours'] = $totalWorkingHours;
            } else {
                $totalShifts = 0;
                $wageSystem = $worker->wage_system ?? 'shift';
                
                if ($workerProductions && $workerProductions->count() > 0) {
                    foreach ($workerProductions as $prod) {
                        if ($prod->product && $prod->product->shift_target_quantity > 0) {
                            $totalShifts += ($prod->quantity / $prod->product->shift_target_quantity);
                        }
                    }
                }

                $grid[$wId]['total_pay'] = $totalProductionPay;

                $summaryLines = [];
                if ($wageSystem === 'piece') {
                    if ($totalProductionQty > 0) {
                        $summaryLines[] = $totalProductionQty . ' قطعة/منتج';
                    }
                } else {
                    if ($totalShifts > 0) {
                        $shiftNum = fmod($totalShifts, 1) == 0.0
                            ? (int) $totalShifts
                            : rtrim(rtrim(number_format($totalShifts, 1), '0'), '.');
                        $summaryLines[] = $shiftNum . ' وردية';
                    }
                    if ($totalProductionQty > 0) {
                        $summaryLines[] = $totalProductionQty . ' منتج';
                    }
                }
                $grid[$wId]['summary_text'] = !empty($summaryLines) ? implode("\n", $summaryLines) : '';
            }

            // Net = total pay - advances - penalties + bonuses
            $grid[$wId]['net_salary'] = $grid[$wId]['total_pay'] - $totalAdvances - $totalPenalties + $totalBonuses;
            
            $grid[$wId]['is_paid'] = isset($salaries[$wId]);
            $grid[$wId]['salary_record'] = $salaries[$wId] ?? null;
        }

        // Sort grid by factory order then by name
        $factoryOrder = array_flip($groupsOrder);
        uksort($grid, function($a, $b) use ($grid, $factoryOrder) {
            $workerA = $grid[$a]['worker'];
            $workerB = $grid[$b]['worker'];
            $factoryA = $factoryOrder[$workerA->factory_location] ?? 99;
            $factoryB = $factoryOrder[$workerB->factory_location] ?? 99;
            if ($factoryA !== $factoryB) return $factoryA - $factoryB;
            return strcmp($workerA->name, $workerB->name);
        });

        $scissorsWorkers = Worker::where('is_active', true)->scissors()->get();

        $prevWeek = Carbon::parse($startDateStr)->subWeek()->format('Y-m-d');
        $nextWeek = Carbon::parse($startDateStr)->addWeek()->format('Y-m-d');

        if ($request->has('print_all')) {
            return view('salaries.print_all', compact('groupedWorkers', 'grid', 'days', 'startDateStr', 'endDateStr', 'products', 'scissorsWorkers', 'prevWeek', 'nextWeek'));
        }

        return view('salaries.index', compact('groupedWorkers', 'grid', 'days', 'startDateStr', 'endDateStr', 'products', 'scissorsWorkers', 'prevWeek', 'nextWeek'));
    }

    public function updateDailyRecord(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'date' => 'required|date',
            'type' => 'required|in:daily,production',
        ]);

        try {
            $hasAnyProduction = false;
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, &$hasAnyProduction) {
                $worker = Worker::find($request->worker_id);
                // استخدام createFromFormat لتجنب مشاكل timezone مع أيام نهاية الأسبوع
                $dateStr = Carbon::createFromFormat('Y-m-d', $request->date)->format('Y-m-d');

                if ($request->type === 'daily') {
                    if ($request->status === 'present') {
                        $workedHours = 0;
                        if (($worker->daily_wage_type ?? 'daily') === 'hourly' && $request->time_in && $request->time_out) {
                            $in = \Carbon\Carbon::parse($request->time_in);
                            $out = \Carbon\Carbon::parse($request->time_out);
                            if ($out->lessThan($in)) {
                                $out->addDay();
                            }
                            $workedHours = $in->diffInMinutes($out) / 60;
                            if ($workedHours > 16) {
                                throw new \Exception('ساعات العمل المحسوبة تتجاوز 16 ساعة!');
                            }
                        }
                        $att = Attendance::where('worker_id', $worker->id)->whereDate('date', $dateStr)->first();
                        $data = [
                            'status' => 'present',
                            'time_in' => (($worker->daily_wage_type ?? 'daily') === 'hourly') ? $request->time_in : null,
                            'time_out' => (($worker->daily_wage_type ?? 'daily') === 'hourly') ? $request->time_out : null,
                            'worked_hours' => round($workedHours, 2),
                        ];
                        if ($att) $att->update($data);
                        else Attendance::create(array_merge(['worker_id' => $worker->id, 'date' => $dateStr], $data));
                    } elseif ($request->status === 'half_day') {
                        $att = Attendance::where('worker_id', $worker->id)->whereDate('date', $dateStr)->first();
                        $data = [
                            'status' => 'half_day',
                            'time_in' => null,
                            'time_out' => null,
                            'worked_hours' => 0,
                        ];
                        if ($att) $att->update($data);
                        else Attendance::create(array_merge(['worker_id' => $worker->id, 'date' => $dateStr], $data));
                    } else {
                        $att = Attendance::where('worker_id', $worker->id)->whereDate('date', $dateStr)->first();
                        $data = ['status' => 'absent', 'time_in' => null, 'time_out' => null, 'worked_hours' => 0];
                        if ($att) $att->update($data);
                        else Attendance::create(array_merge(['worker_id' => $worker->id, 'date' => $dateStr], $data));
                    }
                    // Delete any accidental production logs
                    WorkerProduction::where('worker_id', $worker->id)->whereDate('date', $dateStr)->delete();
                } else {
                    $productIds = $request->product_ids ?? [];
                    $quantities = $request->quantities ?? [];
                    $scissorsIds = $request->scissors_ids ?? [];
                    $deleteAll = $request->boolean('delete_all_production') || empty($productIds);

                    $isMachinist = ($worker->production_role === 'machinist');

                    // Revert old inventory if it was added
                    $oldQuery = WorkerProduction::where('worker_id', $worker->id)->whereDate('date', $dateStr);
                    if (!$isMachinist && $worker->production_role === 'scissors') {
                        $oldQuery->whereNull('machinist_worker_id');
                    }
                    $oldProds = $oldQuery->get();
                    
                    foreach ($oldProds as $op) {
                        if ($op->inventory_added) {
                            $inv = \App\Models\Inventory::where('product_id', $op->product_id)->first();
                            if ($inv) {
                                $inv->decrement('quantity_in', $op->quantity);
                                $inv->decrement('current_stock', $op->quantity);
                            }
                        }
                    }

                    // Delete old production for this worker/date first, then create fresh
                    $deleteQuery = WorkerProduction::where('worker_id', $worker->id)->whereDate('date', $dateStr);
                    if (!$isMachinist && $worker->production_role === 'scissors') {
                        $deleteQuery->whereNull('machinist_worker_id');
                    }
                    $deleteQuery->delete();
                    if ($isMachinist) {
                        // Delete old scissors records linked to this machinist for this date
                        WorkerProduction::where('machinist_worker_id', $worker->id)->whereDate('date', $dateStr)->delete();
                    }

                    $hasAnyProduction = false;
                    for ($i = 0; $i < count($productIds); $i++) {
                        $qty = intval($quantities[$i] ?? 0);
                        $prodId = $productIds[$i];
                        $scissorsId = $scissorsIds[$i] ?? null;

                        if ($qty > 0 && $prodId) {
                            $hasAnyProduction = true;
                            $product = Product::find($prodId);
                            if (!$product) continue;
                            
                            $pay = $this->wageService->calculate($worker, $product, $qty);

                            WorkerProduction::create([
                                'worker_id' => $worker->id,
                                'date' => $dateStr,
                                'product_id' => $prodId,
                                'quantity' => $qty,
                                'scissors_worker_id' => $scissorsId,
                                'labor_cost_per_piece' => $pay['labor_cost_per_piece'],
                                'total_pay' => $pay['total_pay'],
                                'inventory_added' => $isMachinist,
                            ]);
                            
                            if ($isMachinist) {
                                $inv = \App\Models\Inventory::firstOrCreate(
                                    ['product_id' => $prodId],
                                    ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0]
                                );
                                $inv->increment('quantity_in', $qty);
                                $inv->increment('current_stock', $qty);
                                $inv->update(['last_updated' => now()]);
                            }
                            
                            // If there's a scissors worker linked
                            if ($worker->production_role === 'machinist' && $scissorsId) {
                                $scissorsWorker = Worker::find($scissorsId);
                                if ($scissorsWorker) {
                                    $scPay = $this->wageService->calculate($scissorsWorker, $product, $qty);
                                    
                                    WorkerProduction::create([
                                        'worker_id' => $scissorsId,
                                        'date' => $dateStr,
                                        'product_id' => $prodId,
                                        'machinist_worker_id' => $worker->id, // Track who he worked behind!
                                        'quantity' => $qty,
                                        'labor_cost_per_piece' => $scPay['labor_cost_per_piece'],
                                        'total_pay' => $scPay['total_pay'],
                                    ]);
                                    $scAtt = Attendance::where('worker_id', $scissorsId)->whereDate('date', $dateStr)->first();
                                    if ($scAtt) $scAtt->update(['status' => 'present']);
                                    else Attendance::create(['worker_id' => $scissorsId, 'date' => $dateStr, 'status' => 'present']);
                                }
                            }
                        }
                    }

                    if ($hasAnyProduction) {
                        $att = Attendance::where('worker_id', $worker->id)->whereDate('date', $dateStr)->first();
                        if ($att) $att->update(['status' => 'present']);
                        else Attendance::create(['worker_id' => $worker->id, 'date' => $dateStr, 'status' => 'present']);
                    } else {
                        // All production deleted - mark worker as absent for this day
                        $att = Attendance::where('worker_id', $worker->id)->whereDate('date', $dateStr)->first();
                        if ($att) $att->update(['status' => 'absent', 'time_in' => null, 'time_out' => null, 'worked_hours' => 0]);
                        // No attendance record needed if absent and no record exists
                    }
                }
            });

            return response()->json(['success' => true, 'deleted_all' => !isset($hasAnyProduction) || !$hasAnyProduction]);
        } catch (\Exception $e) {
            \Log::error('updateDailyRecord Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteProduction(\App\Models\WorkerProduction $production)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($production) {
                // Reverse inventory if it was added by a machinist
                if ($production->inventory_added) {
                    $inv = \App\Models\Inventory::where('product_id', $production->product_id)->first();
                    if ($inv) {
                        $inv->decrement('quantity_in', $production->quantity);
                        $inv->decrement('current_stock', $production->quantity);
                    }
                    // Also reverse raw material consumption
                    $product = \App\Models\Product::with('materials')->find($production->product_id);
                    if ($product) {
                        foreach ($product->materials as $material) {
                            $neededQty = $material->pivot->quantity_needed * $production->quantity;
                            $material->increment('current_stock', $neededQty);
                        }
                    }
                    // Delete any linked scissors worker records (machinist-created) safely
                    \App\Models\WorkerProduction::where('machinist_worker_id', $production->worker_id)
                        ->where('date', $production->date)
                        ->where('product_id', $production->product_id)
                        ->where('quantity', $production->quantity)
                        ->limit(1)
                        ->delete();
                }

                $production->delete();
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('deleteProduction Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function clearDayProduction(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'date' => 'required|date',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                $workerId = $request->worker_id;
                $dateStr = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                $worker = \App\Models\Worker::findOrFail($workerId);
                $isMachinist = ($worker->production_role === 'machinist');

                // Get all productions for this worker on this date (use whereDate for safety)
                $productions = \App\Models\WorkerProduction::where('worker_id', $workerId)
                    ->whereDate('date', $dateStr)
                    ->get();

                foreach ($productions as $production) {
                    // Reverse inventory if it was added by a machinist
                    if ($production->inventory_added) {
                        $inv = \App\Models\Inventory::where('product_id', $production->product_id)->first();
                        if ($inv) {
                            $inv->decrement('quantity_in', $production->quantity);
                            $inv->decrement('current_stock', $production->quantity);
                        }
                        // Also reverse raw material consumption
                        $product = \App\Models\Product::with('materials')->find($production->product_id);
                        if ($product) {
                            foreach ($product->materials as $material) {
                                $neededQty = $material->pivot->quantity_needed * $production->quantity;
                                $material->increment('current_stock', $neededQty);
                            }
                        }
                    }
                    $production->delete();
                }

                if ($isMachinist) {
                    // Delete old scissors records linked to this machinist for this date
                    \App\Models\WorkerProduction::where('machinist_worker_id', $workerId)->whereDate('date', $dateStr)->delete();
                }

                // Mark worker as absent since we cleared all production
                $att = \App\Models\Attendance::where('worker_id', $workerId)->whereDate('date', $dateStr)->first();
                if ($att) {
                    $att->update(['status' => 'absent', 'time_in' => null, 'time_out' => null, 'worked_hours' => 0]);
                }
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('clearDayProduction Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function scissorsSummary(Request $request)
    {
        $workerId = $request->worker_id;
        $dateStr = \Carbon\Carbon::parse($request->date)->format('Y-m-d');

        $productions = WorkerProduction::with(['machinistWorker', 'product'])
            ->where('worker_id', $workerId)
            ->whereNotNull('machinist_worker_id')
            ->where('date', $dateStr)
            ->get();

        return response()->json(['success' => true, 'productions' => $productions]);
    }

    public function markAllAsPaid(Request $request, \App\Services\CashLedgerService $ledger)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'total_pay' => 'required|numeric',
            'advances' => 'required|numeric',
            'penalties' => 'required|numeric',
            'net_salary' => 'required|numeric',
            'bonus' => 'nullable|numeric|min:0',
        ]);

        $worker = Worker::find($request->worker_id);

        $exists = SalaryRecord::where('worker_id', $worker->id)
            ->whereDate('start_date', $request->start_date)
            ->whereDate('end_date', $request->end_date)
            ->first();

        if ($exists) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'تم صرف راتب هذا الأسبوع مسبقاً للموظف ' . $worker->name);
        }

        $salary = SalaryRecord::create([
            'worker_id' => $worker->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'working_days' => 0,
            'absent_days' => 0,
            'overtime_hours' => 0,
            'base_salary' => $worker->worker_type === 'daily' ? $request->total_pay : 0,
            'production_pay' => $worker->worker_type === 'production' ? $request->total_pay : 0,
            'overtime_pay' => 0,
            'deductions' => $request->penalties,
            'bonuses' => floatval($request->bonus ?? 0),
            'advances' => $request->advances,
            'net_salary' => $request->net_salary, // This already includes bonus (calculated in JS)
            'payment_status' => 'paid',
            'payment_date' => now(),
        ]);

        // Mark penalties as deducted
        \App\Models\WorkerPenalty::where('worker_id', $worker->id)
            ->where('is_deducted', false)
            ->update(['is_deducted' => true]);

        $bonusToPay = floatval($request->bonus ?? 0);
        if ($bonusToPay > 0) {
            $unpaidBonuses = \App\Models\WorkerBonus::where('worker_id', $worker->id)
                ->where('is_paid', false)
                ->orderBy('date', 'asc')
                ->get();
                
            $totalExisting = $unpaidBonuses->sum('amount');
            
            if ($bonusToPay <= $totalExisting) {
                foreach ($unpaidBonuses as $ub) {
                    if ($bonusToPay <= 0) break;
                    if ($ub->amount <= $bonusToPay) {
                        $ub->update(['is_paid' => true, 'salary_record_id' => $salary->id]);
                        $bonusToPay -= $ub->amount;
                    } else {
                        // Split bonus
                        $remaining = $ub->amount - $bonusToPay;
                        $ub->update(['amount' => $bonusToPay, 'is_paid' => true, 'salary_record_id' => $salary->id]);
                        \App\Models\WorkerBonus::create([
                            'worker_id' => $worker->id,
                            'amount' => $remaining,
                            'date' => $ub->date,
                            'reason' => $ub->reason . ' (باقي مرحل)',
                            'is_paid' => false
                        ]);
                        $bonusToPay = 0;
                    }
                }
            } else {
                // They paid ALL existing bonuses PLUS some extra
                foreach ($unpaidBonuses as $ub) {
                    $ub->update(['is_paid' => true, 'salary_record_id' => $salary->id]);
                }
                $extraBonus = $bonusToPay - $totalExisting;
                \App\Models\WorkerBonus::create([
                    'worker_id' => $worker->id,
                    'amount' => $extraBonus,
                    'date' => $request->start_date,
                    'reason' => 'مكافأة مع القبض',
                    'is_paid' => true,
                    'salary_record_id' => $salary->id
                ]);
            }
        }

        $deductAmount = floatval($request->advances);

        // خصم السلف: تحديث رصيد الموظف مباشرة بدون rollover
        if ($deductAmount > 0) {
            // إغلاق السلف القائمة حسب الأقدمية حتى يصل للمبلغ المخصوم
            $activeAdvances = WorkerAdvance::where('worker_id', $worker->id)
                ->where('is_deducted', false)
                ->orderBy('date', 'asc')
                ->get();

            $remaining = $deductAmount;
            foreach ($activeAdvances as $adv) {
                if ($remaining <= 0) break;
                if ($remaining >= $adv->amount) {
                    $adv->update(['is_deducted' => true]);
                    $remaining -= $adv->amount;
                } else {
                    // Split the advance
                    $oldAmount = $adv->amount;
                    $adv->update(['amount' => $oldAmount - $remaining]);
                    
                    \App\Models\WorkerAdvance::create([
                        'worker_id' => $worker->id,
                        'amount' => $remaining,
                        'date' => $adv->date,
                        'is_deducted' => true,
                        'notes' => 'سداد جزئي من سلفة بتاريخ ' . $adv->date
                    ]);
                    $remaining = 0;
                }
            }

            // إنقاص رصيد الموظف بمقدار المخصوم فعلاً
            $newBalance = max(0, $worker->pending_advance_balance - $deductAmount);
            $worker->update(['pending_advance_balance' => $newBalance]);
        }

        // Record expense in cash ledger if there is net salary
        if ($request->net_salary > 0) {
            $ledger->record(
                'salary',
                $request->net_salary,
                "صرف راتب الأسبوع ({$request->start_date} إلى {$request->end_date}) — الموظف: {$worker->name}",
                $salary,
                now()->toDateString()
            );
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', "تم صرف الراتب بنجاح للموظف {$worker->name}");
    }

    public function calculate(Request $request)
    {
        // ... (Keep existing if needed, or remove) ...
        $this->clearLayoutCache();
        return redirect()->back();
    }

    public function markAsPaid(SalaryRecord $salary)
    {
        // Update to paid logic...
        $this->clearLayoutCache();
        return redirect()->back();
    }

    /**
     * عكس (إلغاء) عملية صرف الراتب بشكل كامل وآمن
     */
    public function reversePayment(SalaryRecord $salary, CashLedgerService $ledger)
    {
        $worker = Worker::find($salary->worker_id);

        DB::transaction(function () use ($salary, $worker, $ledger) {
            // 1. إرجاع السلف المخصومة إلى حالة "غير مخصوم"
            if ($salary->advances > 0) {
                // نحتاج إعادة فتح السلف التي أُغلقت عند الصرف
                // نحسب الإجمالي المخصوم ونفتح السلف بالأقدمية
                $deducted = WorkerAdvance::where('worker_id', $salary->worker_id)
                    ->where('is_deducted', true)
                    ->orderByDesc('date')
                    ->get();

                $toReopen = $salary->advances;
                foreach ($deducted as $adv) {
                    if ($toReopen <= 0) break;
                    $adv->update(['is_deducted' => false]);
                    $toReopen -= $adv->amount;
                }

                // إرجاع رصيد الموظف
                $worker->increment('pending_advance_balance', $salary->advances);
            }

            // 2. إرجاع الجزاءات إلى "غير مخصومة"
            \App\Models\WorkerPenalty::where('worker_id', $salary->worker_id)
                ->where('is_deducted', true)
                ->where('date', '>=', $salary->start_date->format('Y-m-d'))
                ->where('date', '<=', $salary->end_date->format('Y-m-d'))
                ->update(['is_deducted' => false]);

            // 3. إرجاع المكافآت إلى "غير مدفوعة"
            \App\Models\WorkerBonus::where('worker_id', $salary->worker_id)
                ->where('is_paid', true)
                ->where('date', '>=', $salary->start_date->format('Y-m-d'))
                ->where('date', '<=', $salary->end_date->format('Y-m-d'))
                ->update(['is_paid' => false]);

            // 4. عكس قيد الخزينة
            if ($salary->net_salary > 0) {
                \App\Models\CashTransaction::where('reference_type', SalaryRecord::class)
                    ->where('reference_id', $salary->id)
                    ->delete();
                $ledger->recalculateLedger();
            }

            // 5. حذف سجل الراتب
            $salary->delete();
        });

        $this->clearLayoutCache();
        return redirect()->back()->with('success',
            'تم إلغاء صرف الراتب للموظف ' . $worker->name . ' وتمت استعادة جميع الأرصدة بنجاح.');
    }

    public function print(SalaryRecord $salary)
    {
        $salary->load('worker');
        $startStr = $salary->start_date->format('Y-m-d');
        $endStr   = $salary->end_date->format('Y-m-d');
        
        // Attendance records for this week (for daily workers)
        $attendances = \App\Models\Attendance::where('worker_id', $salary->worker_id)
            ->whereBetween('date', [$startStr, $endStr])
            ->orderBy('date')
            ->get();

        // Production records for this week (for piece-rate workers)
        $productions = \App\Models\WorkerProduction::with('product')
            ->where('worker_id', $salary->worker_id)
            ->whereBetween('date', [$startStr, $endStr])
            ->orderBy('date')
            ->get();

        // Calculate real historical debt without rollovers
        $totalLifetimeAdvances = \App\Models\WorkerAdvance::where('worker_id', $salary->worker_id)
            ->where(function($q) {
                $q->whereNull('notes')->orWhere('notes', 'not like', 'باقي رصيد سلف سابقة%');
            })
            ->whereDate('date', '<=', $salary->payment_date ?? now())
            ->sum('amount');
            
        $previousDeductions = \App\Models\SalaryRecord::where('worker_id', $salary->worker_id)
            ->where('id', '<', $salary->id)
            ->sum('advances');
            
        $actualDebtBeforeThisSalary = max(0, $totalLifetimeAdvances - $previousDeductions);

        // Get only the real advances taken in this specific period to show in the table
        $periodAdvances = \App\Models\WorkerAdvance::where('worker_id', $salary->worker_id)
            ->whereBetween('date', [$startStr, $endStr])
            ->where(function($q) {
                $q->whereNull('notes')->orWhere('notes', 'not like', 'باقي رصيد سلف سابقة%');
            })
            ->orderBy('date')
            ->get();

        // Penalties in the period
        $penalties = \App\Models\WorkerPenalty::where('worker_id', $salary->worker_id)
            ->whereBetween('date', [$startStr, $endStr])
            ->get();
            
        return view('salaries.print', compact('salary', 'attendances', 'productions', 'actualDebtBeforeThisSalary', 'periodAdvances', 'penalties'));
    }

    public function printPaid(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'يجب تحديد فترة الصرف.');
        }

        $salaries = \App\Models\SalaryRecord::with('worker')
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->where('payment_status', 'paid')
            ->get();

        $totalPaid = $salaries->sum('net_salary');

        return view('salaries.print_paid', compact('salaries', 'startDate', 'endDate', 'totalPaid'));
    }
    public function recalculate(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'يجب تحديد فترة الصرف.');
        }

        $wageService = new \App\Services\WorkerWageCalculationService();

        // Get all unpaid productions in this period
        $productions = \App\Models\WorkerProduction::whereBetween('date', [$startDate, $endDate])
            // Do not recalculate if salary record is already paid, but currently there's no strict link in the table.
            ->get();

        $updatedCount = 0;
        foreach ($productions as $prod) {
            $worker = \App\Models\Worker::find($prod->worker_id);
            $product = \App\Models\Product::find($prod->product_id);
            
            if ($worker && $product && $prod->quantity > 0) {
                $pay = $wageService->calculate($worker, $product, $prod->quantity);
                
                // Only update if there is a difference to save queries
                if ($prod->labor_cost_per_piece != $pay['labor_cost_per_piece'] || $prod->total_pay != $pay['total_pay']) {
                    $prod->update([
                        'labor_cost_per_piece' => $pay['labor_cost_per_piece'],
                        'total_pay' => $pay['total_pay'],
                    ]);
                    $updatedCount++;
                }
            }
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم إعادة احتساب وتصحيح ' . $updatedCount . ' سجل إنتاج بناءً على أحدث الأسعار بنجاح.');
    }
}
