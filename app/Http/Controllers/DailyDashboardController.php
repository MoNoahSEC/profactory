<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\WorkerProduction;
use Illuminate\Http\Request;

class DailyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));

        // Hourly Workers Present Today
        $hourlyAttendances = Attendance::with('worker')
            ->where('date', $date)
            ->where('status', 'present')
            ->whereHas('worker', function($q) {
                $q->where('worker_type', 'daily');
            })
            ->get();

        // Production pairs today
        $productions = WorkerProduction::with(['worker', 'scissorsWorker', 'product'])
            ->where('date', $date)
            ->where('production_role', 'machinist')
            ->get();

        // Calculate Totals
        $totalHourlyWages = 0;
        foreach ($hourlyAttendances as $att) {
            $wage = $att->worked_hours > 0 ? ($att->worked_hours * $att->worker->hourly_wage) : $att->worker->daily_wage;
            $att->calculated_wage = $wage;
            $totalHourlyWages += $wage;
        }

        $totalProductionWages = 0;
        $totalCages = 0;
        foreach ($productions as $prod) {
            $totalCages += $prod->quantity;
            $machinistWage = $prod->total_pay;
            $scissorsWage = 0;
            if ($prod->scissors_worker_id) {
                $scissorsProduction = WorkerProduction::where('worker_id', $prod->scissors_worker_id)
                    ->where('machinist_worker_id', $prod->worker_id)
                    ->where('date', $date)
                    ->where('product_id', $prod->product_id)
                    ->first();
                $scissorsWage = $scissorsProduction?->total_pay ?? 0;
            }
            $prod->machinist_wage = $machinistWage;
            $prod->scissors_wage = $scissorsWage;
            $totalProductionWages += ($machinistWage + $scissorsWage);
        }

        return view('production.daily', compact('date', 'hourlyAttendances', 'productions', 'totalHourlyWages', 'totalProductionWages', 'totalCages'));
    }
}
