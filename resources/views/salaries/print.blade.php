@extends('layouts.print')
@section('title', 'إيصال قبض — ' . ($salary->worker->name ?? ''))
@section('customer_phone', $salary->worker->phone ?? '')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold text-decoration-underline">إيصال قبض أسبوعي</h3>
    <h5 class="mt-2">من {{ $salary->start_date->format('Y-m-d') }} إلى {{ $salary->end_date->format('Y-m-d') }}</h5>
</div>

<div class="row mb-3 border-bottom pb-3">
    <div class="col-6">
        <strong>التاريخ:</strong> {{ $salary->payment_date ? $salary->payment_date->format('Y-m-d') : date('Y-m-d') }}<br>
        <strong>رقم الإيصال:</strong> #{{ str_pad($salary->id, 5, '0', STR_PAD_LEFT) }}
    </div>
    <div class="col-6 text-end">
        <strong>الموظف:</strong> {{ $salary->worker->name }}<br>
        <strong>الوظيفة:</strong> {{ $salary->worker->role_label ?? ($salary->worker->job_title ?? 'موظف') }}<br>
        <strong>الحالة:</strong> {{ $salary->payment_status == 'paid' ? '✅ خالص' : '⏳ غير مدفوع' }}
    </div>
</div>

{{-- Daily Workers: Show attendance summary --}}
@if($salary->worker->worker_type === 'daily' && isset($attendances) && $attendances->count() > 0)
@php
    $totalDays = 0; $totalHours = 0;
    foreach($attendances as $att) {
        if($att->status === 'present') { $totalDays += 1; $totalHours += ($att->worked_hours ?? 0); }
        elseif($att->status === 'half_day') { $totalDays += 0.5; $totalHours += ($att->worked_hours ?? 4); }
    }
@endphp
<div class="mb-4">
    <h6 class="fw-bold text-decoration-underline mb-2">سجل الحضور خلال الأسبوع</h6>
    <table class="table table-sm table-bordered text-center" style="font-size: 0.9rem;">
        <thead class="table-light">
            <tr><th>التاريخ</th><th>الحالة</th><th>وقت الحضور</th><th>وقت الانصراف</th><th>الساعات</th></tr>
        </thead>
        <tbody>
            @foreach($attendances as $att)
            <tr>
                <td>{{ \Carbon\Carbon::parse($att->date)->format('Y-m-d') }}</td>
                <td>
                    @if($att->status === 'present') <span class="text-success fw-bold">حاضر</span>
                    @elseif($att->status === 'half_day') <span class="text-warning fw-bold">نصف يوم</span>
                    @else <span class="text-danger">غائب</span>
                    @endif
                </td>
                <td>{{ $att->time_in ? \Carbon\Carbon::parse($att->time_in)->format('H:i') : '-' }}</td>
                <td>{{ $att->time_out ? \Carbon\Carbon::parse($att->time_out)->format('H:i') : '-' }}</td>
                <td class="fw-bold">{{ $att->worked_hours > 0 ? $att->worked_hours . ' س' : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="2" class="text-start">الإجمالي</td>
                <td colspan="2">{{ $totalDays }} يوم</td>
                <td class="text-primary">{{ $totalHours > 0 ? $totalHours . ' ساعة' : '-' }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- Production Workers: production details --}}
@if(isset($productions) && $productions->count() > 0)
<div class="mb-4">
    <h6 class="fw-bold text-decoration-underline mb-2">تفاصيل الإنتاج (بالقطعة / الورديات)</h6>
    <table class="table table-sm table-bordered text-center" style="font-size: 0.9rem;">
        <thead class="table-light">
            <tr><th>التاريخ</th><th>المنتج</th><th>الكمية</th><th>الأجر المستحق (ج.م)</th></tr>
        </thead>
        <tbody>
            @foreach($productions as $prod)
            <tr>
                <td>{{ \Carbon\Carbon::parse($prod->date)->format('Y-m-d') }}</td>
                <td>{{ $prod->product->name ?? 'غير محدد' }}</td>
                <td>{{ $prod->quantity }}</td>
                <td>{{ number_format($prod->total_pay, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Advances: show total owed, deducted this time, remaining --}}
@if(isset($actualDebtBeforeThisSalary) && ($actualDebtBeforeThisSalary > 0 || (isset($periodAdvances) && $periodAdvances->count() > 0)))
@php
    $totalOwed = $actualDebtBeforeThisSalary;
    $deducted = $salary->advances ?? 0;
    $remaining = max(0, $totalOwed - $deducted);
@endphp
<div class="mb-4">
    <h6 class="fw-bold text-decoration-underline mb-2 text-danger">السلف والمديونية</h6>
    <table class="table table-sm table-bordered text-center" style="font-size: 0.9rem;">
        <thead class="table-light">
            <tr><th>البيان / التاريخ</th><th>المبلغ (ج.م)</th><th>ملاحظات</th></tr>
        </thead>
        <tbody>
            @if($actualDebtBeforeThisSalary > 0 && (!isset($periodAdvances) || $periodAdvances->sum('amount') < $actualDebtBeforeThisSalary))
            <tr>
                <td class="text-start">رصيد ديون وسلف سابقة</td>
                <td class="text-danger fw-bold">{{ number_format($actualDebtBeforeThisSalary - ($periodAdvances->sum('amount') ?? 0), 2) }}</td>
                <td class="text-muted small">مرحل من أسابيع ماضية</td>
            </tr>
            @endif
            @if(isset($periodAdvances) && $periodAdvances->count() > 0)
                @foreach($periodAdvances as $adv)
                <tr>
                    <td>سلفة في {{ \Carbon\Carbon::parse($adv->date)->format('Y-m-d') }}</td>
                    <td class="text-danger fw-bold">{{ number_format($adv->amount, 2) }}</td>
                    <td>{{ $adv->notes ?: '-' }}</td>
                </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr class="fw-bold border-top bg-light">
                <td class="text-start">إجمالي الدين قبل هذا الراتب</td>
                <td class="text-danger">{{ number_format($totalOwed, 2) }}</td>
                <td></td>
            </tr>
            <tr class="fw-bold">
                <td class="text-start">تم خصمه في هذا الراتب</td>
                <td class="text-warning">- {{ number_format($deducted, 2) }}</td>
                <td></td>
            </tr>
            <tr class="fw-bold {{ $remaining > 0 ? 'table-danger' : 'table-success' }}">
                <td class="text-start">المتبقي (يُرحَّل للأسبوع القادم)</td>
                <td class="{{ $remaining > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($remaining, 2) }}</td>
                <td>{{ $remaining > 0 ? '⚠️ يُخصم لاحقاً' : '✅ تم السداد' }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

@if(isset($penalties) && $penalties->count() > 0)
<div class="mb-4">
    <h6 class="fw-bold text-decoration-underline mb-2 text-danger">الخصومات المطبقة خلال الفترة</h6>
    <table class="table table-sm table-bordered text-center" style="font-size: 0.9rem;">
        <thead class="table-light">
            <tr><th>التاريخ</th><th>المبلغ (ج.م)</th><th>السبب</th></tr>
        </thead>
        <tbody>
            @foreach($penalties as $pen)
            <tr>
                <td>{{ \Carbon\Carbon::parse($pen->date)->format('Y-m-d') }}</td>
                <td class="text-danger fw-bold">{{ number_format($pen->amount, 2) }}</td>
                <td>{{ $pen->reason ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<h6 class="fw-bold text-decoration-underline mb-2 mt-4">الملخص المالي</h6>
<table class="table table-bordered text-center">
    <thead class="table-light">
        <tr><th>البيان</th><th>القيمة (ج.م)</th></tr>
    </thead>
    <tbody>
        @if($salary->base_salary > 0)
        <tr>
            <td>أجر الحضور (@if($salary->worker->daily_wage_type === 'hourly') بالساعة @else باليومية @endif)</td>
            <td>{{ number_format($salary->base_salary, 2) }}</td>
        </tr>
        @endif
        @if($salary->production_pay > 0)
        <tr class="table-info">
            <td>أجر الإنتاج بالقطعة</td>
            <td class="fw-bold text-primary">{{ number_format($salary->production_pay, 2) }}</td>
        </tr>
        @endif
        @if($salary->deductions > 0)
        <tr>
            <td class="text-danger">خصميات (غياب / جزاءات)</td>
            <td class="text-danger">- {{ number_format($salary->deductions, 2) }}</td>
        </tr>
        @endif
        @if(($salary->advances ?? 0) > 0)
        <tr>
            <td class="text-danger">سلف مخصومة هذا الأسبوع</td>
            <td class="text-danger">- {{ number_format($salary->advances, 2) }}</td>
        </tr>
        @endif
    </tbody>
    <tfoot>
        <tr class="fw-bold fs-5 bg-light">
            <td class="text-start">✅ صافي المبلغ المستلم:</td>
            <td class="text-success">{{ number_format($salary->getRawOriginal('net_salary'), 2) }} ج.م</td>
        </tr>
    </tfoot>
</table>

<div class="row mt-5 text-center">
    <div class="col-6">
        <p><strong>توقيع الموظف بالاستلام</strong></p>
        <p>.................................</p>
    </div>
    <div class="col-6">
        <p><strong>توقيع المحاسب / المدير</strong></p>
        <p>.................................</p>
    </div>
</div>
@endsection

