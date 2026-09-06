<?php

namespace App\Http\Controllers;

use App\Models\DailyCustodyReport;
use App\Models\DailyCustodyEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyCustodyController extends Controller
{
    /** عرض قائمة التقارير اليومية */
    public function index(Request $request)
    {
        $reports = DailyCustodyReport::latest('report_date')->paginate(30);
        return view('daily_custody.index', compact('reports'));
    }

    /** عرض أو إنشاء تقرير يوم معين */
    public function show($date = null)
    {
        $date = $date ? Carbon::parse($date)->toDateString() : today()->toDateString();
        $report = DailyCustodyReport::firstOrNew(['report_date' => $date]);

        $entries = $report->exists
            ? $report->entries()->orderBy('entry_time')->orderBy('id')->get()
            : collect();

        $typeLabels = DailyCustodyReport::entryTypeLabels();

        return view('daily_custody.show', compact('report', 'entries', 'date', 'typeLabels'));
    }

    /** حفظ / تحديث بيانات التقرير الأساسية (العهدة الافتتاحية) */
    public function store(Request $request)
    {
        $request->validate([
            'report_date'     => 'required|date',
            'opening_custody' => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:300',
            'created_by'      => 'nullable|string|max:100',
        ]);

        $report = DailyCustodyReport::updateOrCreate(
            ['report_date' => $request->report_date],
            [
                'opening_custody' => $request->opening_custody,
                'notes'           => $request->notes,
                'created_by'      => $request->created_by,
            ]
        );

        return redirect()->route('daily-custody.show', $report->report_date)
            ->with('success', 'تم حفظ بيانات العهدة بنجاح.');
    }

    /** إضافة قيد جديد للتقرير */
    public function addEntry(Request $request, DailyCustodyReport $report)
    {
        $request->validate([
            'direction'   => 'required|in:in,out',
            'type'        => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'entry_time'  => 'nullable|string',
        ]);

        DailyCustodyEntry::create([
            'daily_custody_report_id' => $report->id,
            'direction'               => $request->direction,
            'type'                    => $request->type,
            'amount'                  => $request->amount,
            'description'             => $request->description,
            'entry_time'              => $request->entry_time ?: now()->format('H:i'),
        ]);

        return redirect()->route('daily-custody.show', $report->report_date)
            ->with('success', 'تم إضافة القيد بنجاح.');
    }

    /** حذف قيد */
    public function deleteEntry(DailyCustodyEntry $entry)
    {
        $date = $entry->report->report_date;
        $entry->delete();
        return redirect()->route('daily-custody.show', $date)
            ->with('success', 'تم حذف القيد.');
    }

    /** طباعة تقرير نهاية اليوم */
    public function print(DailyCustodyReport $report)
    {
        $entries  = $report->entries()->orderBy('entry_time')->orderBy('id')->get();
        $inEntries  = $entries->where('direction', 'in');
        $outEntries = $entries->where('direction', 'out');
        $typeLabels = DailyCustodyReport::entryTypeLabels();

        return view('daily_custody.print', compact('report', 'entries', 'inEntries', 'outEntries', 'typeLabels'));
    }
}
