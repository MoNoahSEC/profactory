@extends('layouts.print')
@section('title', 'إيصال سلفة — ' . ($advance->worker->name ?? ''))
@section('customer_phone', $advance->worker->phone ?? '')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold text-decoration-underline">إيصال استلام سلفة نقدية</h3>
</div>

<div class="row mb-4 border-bottom pb-3">
    <div class="col-6">
        <strong>التاريخ:</strong> {{ \Carbon\Carbon::parse($advance->date)->format('Y-m-d') }}<br>
        <strong>رقم الإيصال:</strong> #{{ str_pad($advance->id, 5, '0', STR_PAD_LEFT) }}
    </div>
    <div class="col-6 text-end">
        <strong>الموظف:</strong> {{ $advance->worker->name }}<br>
        <strong>حالة السلفة:</strong> {!! $advance->is_deducted ? '<span class="text-success">مخصومة</span>' : '<span class="text-danger">غير مخصومة</span>' !!}
    </div>
</div>

<table class="table table-bordered text-center mt-4 mb-5">
    <thead class="table-light">
        <tr>
            <th>البيان</th>
            <th>المبلغ (ج.م)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="fw-bold fs-5">قيمة السلفة المستلمة نقداً</td>
            <td class="fw-bold fs-4 text-primary">{{ number_format($advance->amount, 2) }}</td>
        </tr>
        @if($advance->notes)
        <tr>
            <td class="text-muted">ملاحظات</td>
            <td>{{ $advance->notes }}</td>
        </tr>
        @endif
    </tbody>
</table>

<div class="row mt-5 pt-5 text-center">
    <div class="col-6">
        <p><strong>توقيع المستلم (الموظف)</strong></p>
        <p>.................................</p>
    </div>
    <div class="col-6">
        <p><strong>توقيع الصراف / المدير</strong></p>
        <p>.................................</p>
    </div>
</div>

<div class="mt-4 pt-4 border-top text-center text-muted small">
    <p>أقر أنا الموقع أعلاه باستلامي مبلغ وقدره {{ number_format($advance->amount, 2) }} ج.م، وأوافق على خصمه من راتبي.</p>
</div>
@endsection
