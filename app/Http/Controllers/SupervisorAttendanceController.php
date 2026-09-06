<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupervisorAttendanceController extends Controller
{
    /**
     * Display a mobile-optimized dashboard for the Supervisor.
     */
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $allWorkers = Worker::where('is_active', true)->orderBy('name')->get();
        $attendances = Attendance::whereDate('date', $date)->get()->keyBy('worker_id');

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

        // Add any remaining workers that don't match the specific names
        $otherWorkers = $allWorkers->whereNotIn('factory_location', $groupsOrder);
        if ($otherWorkers->isNotEmpty()) {
            $groupedWorkers->put('أخرى', $otherWorkers);
        }

        // Stats
        $totalWorkers = $allWorkers->count();
        $present = $attendances->where('status', 'present')->count() + $attendances->where('status', 'half_day')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $unmarked = $totalWorkers - ($present + $absent);

        return view('supervisor.attendance.index', compact('groupedWorkers', 'attendances', 'date', 'totalWorkers', 'present', 'absent', 'unmarked'));
    }

    /**
     * Store or update attendance via AJAX.
     */
    public function store(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'status' => 'required|in:present,absent,half_day,unmarked',
            'date' => 'required|date',
            'action' => 'nullable|in:check_in,check_out',
            'notes' => 'nullable|string'
        ]);

        $date = $request->date;
        $workerId = $request->worker_id;

        if ($request->status === 'unmarked') {
            Attendance::where('worker_id', $workerId)->whereDate('date', $date)->delete();
            return response()->json(['success' => true, 'message' => 'تم إلغاء التحضير']);
        }

        $attendance = Attendance::firstOrNew([
            'worker_id' => $workerId,
            'date' => $date
        ]);

        $attendance->status = $request->status;
        
        // Exact time logging if provided
        if ($request->action === 'check_in' && !$attendance->time_in) {
            $attendance->time_in = now()->format('H:i:s');
        } elseif ($request->action === 'check_out') {
            $attendance->time_out = now()->format('H:i:s');
        }

        if ($request->filled('notes')) {
            $attendance->notes = $request->notes;
        }

        $attendance->save();

        return response()->json([
            'success' => true, 
            'message' => 'تم تسجيل الحضور',
            'data' => [
                'status' => $attendance->status,
                'time_in' => $attendance->time_in,
                'time_out' => $attendance->time_out,
                'notes' => $attendance->notes
            ]
        ]);
    }

    /**
     * Mark all unmarked workers as absent for a specific date.
     */
    public function markAllAbsent(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $workers = Worker::where('is_active', true)->get();
        $existingAttendances = Attendance::whereDate('date', $date)->pluck('worker_id')->toArray();

        $count = 0;
        foreach ($workers as $worker) {
            if (!in_array($worker->id, existingAttendances)) {
                Attendance::create([
                    'worker_id' => $worker->id,
                    'date' => $date,
                    'status' => 'absent',
                    'notes' => 'تم تسجيل الغياب تلقائياً (إغلاق اليوم)'
                ]);
                $count++;
            }
        }

        return back()->with('success', "تم إغلاق اليوم وتسجيل $count موظفين كغائبين بنجاح.");
    }
}
