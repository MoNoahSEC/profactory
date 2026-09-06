@extends('layouts.print')
@section('title', 'كشف إنتاج وعمل جميع الموظفين')

@section('content')
<style>
    @page { size: landscape; }
    .table-xs { font-size: 0.8rem; }
    .table-xs th, .table-xs td { padding: 0.25rem; }
</style>

@foreach($groupedWorkers as $groupName => $groupWorkers)
<div class="factory-section" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-decoration-underline">كشف إنتاج وعمل - {{ $groupName }}</h3>
        <h5 class="mt-2 text-muted">عن الفترة من {{ $startDateStr }} إلى {{ $endDateStr }}</h5>
    </div>

    <table class="table table-bordered table-xs text-center align-middle mb-4">
        <thead class="table-light border-dark">
            <tr>
                <th style="width: 15%;">اسم الموظف</th>
                @foreach($days as $day)
                    <th style="width: 9%;">{{ $day['name'] }}<br><small class="text-muted">{{ $day['day_month'] }}</small></th>
                @endforeach
                <th style="width: 10%;">الإجمالي<br><small class="text-muted">وردية / أقفاص / يوم</small></th>
                <th style="width: 10%;">الاستحقاق<br><small class="text-muted">ج.م</small></th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupWorkers as $worker)
                @php $wId = $worker->id; $workerGrid = $grid[$wId]; @endphp
                <tr>
                    <td class="fw-bold text-start">{{ $worker->name }}</td>
                    
                    @foreach($days as $day)
                        @php
                            $date = $day['date'];
                            $cell = $workerGrid['days'][$date];
                            $cellText = trim($cell['display_text'] ?? '');
                        @endphp
                        <td>
                            @if($cellText && $cellText !== '-')
                                <span class="{{ $cell['display_color'] ?? '' }} fw-bold">{{ $cellText }}</span>
                            @endif
                        </td>
                    @endforeach

                    <td class="fw-bold text-primary">
                        @php $sumText = trim($workerGrid['summary_text'] ?? ''); @endphp
                        @if($sumText && $sumText !== '-')
                            {!! nl2br(e($sumText)) !!}
                        @endif
                    </td>
                    <td class="fw-bold text-success">
                        @if(($workerGrid['total_pay'] ?? 0) > 0)
                            {{ number_format($workerGrid['total_pay'], 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

<div class="mt-5 text-center text-muted small border-top pt-3">
    <p>تم استخراج التقرير من النظام في: {{ now()->format('Y-m-d H:i') }}</p>
</div>
@endsection
