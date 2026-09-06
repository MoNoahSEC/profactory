<?php

namespace App\Http\Controllers;

use App\Models\WorkerPenalty;
use App\Models\Worker;
use Illuminate\Http\Request;

class WorkerPenaltyController extends Controller
{
    public function index()
    {
        $penalties = WorkerPenalty::with('worker')->orderBy('date', 'desc')->get();
        $workers = Worker::where('is_active', true)->get();
        return view('penalties.index', compact('penalties', 'workers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'amount' => 'required|numeric|min:1',
            'reason_type' => 'required|string',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        WorkerPenalty::create($request->all());

        return redirect()->route('penalties.index')->with('success', 'تم تسجيل الخصم بنجاح وسيتم خصمه تلقائياً عند صرف الراتب.');
    }

    public function destroy(WorkerPenalty $penalty)
    {
        if ($penalty->is_deducted) {
            return redirect()->route('penalties.index')->with('error', 'لا يمكن حذف خصم تم سداده/تطبيقه بالفعل في الراتب.');
        }

        $penalty->delete();
        return redirect()->route('penalties.index')->with('success', 'تم إلغاء الخصم بنجاح.');
    }
}
