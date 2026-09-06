@extends('layouts.print')
@section('title', 'حركة الخزينة')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold text-decoration-underline">تقرير حركة الخزينة الشامل</h3>
    <h5 class="mt-2 text-muted">
        @if($filter === 'daily')
            حركة اليوم ({{ now()->format('Y-m-d') }})
        @elseif($filter === 'weekly')
            حركة الأسبوع الحالي
        @elseif($filter === 'monthly')
            حركة شهر {{ now()->format('m/Y') }}
        @elseif($filter === 'yearly')
            حركة عام {{ now()->format('Y') }}
        @endif
    </h5>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="border p-3 rounded bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">الرصيد النقدي الحالي:</h5>
            <h3 class="mb-0 fw-bold text-primary">{{ number_format($summary['balance'], 2) }} ج.م</h3>
        </div>
    </div>
</div>

<div class="row mb-4 text-center">
    <div class="col-6">
        <div class="border border-success rounded p-3 bg-success bg-opacity-10 text-success fw-bold">
            إجمالي الوارد للفترة: {{ number_format($summary['period_income'], 2) }} ج.م
        </div>
    </div>
    <div class="col-6">
        <div class="border border-danger rounded p-3 bg-danger bg-opacity-10 text-danger fw-bold">
            إجمالي المنصرف للفترة: {{ number_format($summary['period_expenses'], 2) }} ج.م
        </div>
    </div>
</div>

<h6 class="fw-bold text-decoration-underline mb-3">تفاصيل الحركة:</h6>
<table class="table table-bordered table-sm text-center" style="font-size: 0.9rem;">
    <thead class="table-light">
        <tr>
            <th>التاريخ</th>
            <th>رقم الحركة</th>
            <th>النوع</th>
            <th>المبلغ (ج.م)</th>
            <th>البيان</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $t)
        <tr>
            <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('Y-m-d') }}</td>
            <td class="text-muted">#{{ $t->id }}</td>
            <td>
                @if($t->type === 'in')
                    <span class="text-success fw-bold">وارد</span>
                @else
                    <span class="text-danger fw-bold">منصرف</span>
                @endif
            </td>
            <td class="fw-bold {{ $t->type === 'in' ? 'text-success' : 'text-danger' }}">
                {{ number_format($t->amount, 2) }}
            </td>
            <td class="text-start">{{ $t->description }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center text-muted py-3">لا توجد حركات مسجلة خلال هذه الفترة.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-5 text-center text-muted small border-top pt-3">
    <p>تم استخراج التقرير من النظام في: {{ now()->format('Y-m-d H:i') }}</p>
</div>
@endsection
