@extends('layouts.print')
@section('title', 'إيصال / سند دين - ' . $debt->party_name)

@section('content')
<div class="mb-4">
    <h3 class="text-center mb-1 text-decoration-underline">
        {{ $debt->type === 'owed_to_us' ? 'سند استحقاق (كمبيالة/دين لنا)' : 'سند مديونية (إقرار بالدين علينا)' }}
    </h3>
    <p class="text-center text-muted">تاريخ السند: {{ \Carbon\Carbon::parse($debt->created_at)->format('Y/m/d') }}</p>
</div>

<div class="border border-dark p-3 mb-4 rounded">
    <div class="row mb-3">
        <div class="col-6">
            <strong>قيمة الدين: </strong> <span class="fs-4">{{ number_format($debt->amount, 2) }} ج.م</span>
        </div>
        <div class="col-6 text-end">
            <strong>رقم السند: </strong> #{{ str_pad($debt->id, 5, '0', STR_PAD_LEFT) }}
        </div>
    </div>
    
    <div class="mb-3 fs-5">
        @if($debt->type === 'owed_to_us')
            <strong>أقر أنا الموقع أدناه:</strong> {{ $debt->party_name }}<br>
            بأن في ذمتي لمصنع برو فاكتوري مبلغاً وقدره ({{ number_format($debt->amount, 2) }} جنيه مصري).
        @else
            <strong>نقر نحن مصنع برو فاكتوري:</strong><br>
            بأن في ذمتنا للسيد / الجهة: {{ $debt->party_name }} مبلغاً وقدره ({{ number_format($debt->amount, 2) }} جنيه مصري).
        @endif
    </div>

    @if($debt->due_date)
    <div class="mb-3 fs-5">
        <strong>أتعهد بسداد هذا المبلغ بالكامل في موعد أقصاه:</strong> {{ \Carbon\Carbon::parse($debt->due_date)->format('Y/m/d') }}
    </div>
    @endif

    @if($debt->notes)
    <div class="mb-3">
        <strong>ملاحظات وتفاصيل أخرى:</strong><br>
        {{ $debt->notes }}
    </div>
    @endif
</div>

<div class="row mt-5 pt-5 text-center">
    <div class="col-4">
        <strong>المقر بما فيه</strong><br>
        (......................................)
    </div>
    <div class="col-4">
        <strong>شاهد أول</strong><br>
        (......................................)
    </div>
    <div class="col-4">
        <strong>شاهد ثاني</strong><br>
        (......................................)
    </div>
</div>
@endsection
