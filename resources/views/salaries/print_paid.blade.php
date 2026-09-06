@extends('layouts.print')
@section('title', 'تقرير المنصرف للموظفين')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold text-decoration-underline">تقرير الرواتب المنصرفة فعلياً</h3>
    <h5 class="mt-2 text-muted">عن الفترة من {{ $startDate }} إلى {{ $endDate }}</h5>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="border p-3 rounded bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">إجمالي المنصرف للموظفين:</h5>
            <h3 class="mb-0 fw-bold text-success">{{ number_format($totalPaid, 2) }} ج.م</h3>
        </div>
    </div>
</div>

<table class="table table-bordered table-sm text-center align-middle" style="font-size: 0.9rem;">
    <thead class="table-light">
        <tr>
            <th>اسم الموظف</th>
            <th>الراتب الأساسي</th>
            <th>الإنتاج</th>
            <th>خصم سلف</th>
            <th>خصميات (غياب/جزاء)</th>
            <th>صافي المنصرف (ج.م)</th>
            <th>توقيع الموظف</th>
        </tr>
    </thead>
    <tbody>
        @forelse($salaries as $salary)
        <tr>
            <td class="fw-bold text-start">{{ $salary->worker->name }}</td>
            <td>{{ $salary->base_salary > 0 ? number_format($salary->base_salary, 2) : '-' }}</td>
            <td>{{ $salary->production_pay > 0 ? number_format($salary->production_pay, 2) : '-' }}</td>
            <td class="text-danger">{{ $salary->advances > 0 ? number_format($salary->advances, 2) : '-' }}</td>
            <td class="text-danger">{{ $salary->deductions > 0 ? number_format($salary->deductions, 2) : '-' }}</td>
            <td class="text-success fw-bold fs-6">{{ number_format($salary->net_salary, 2) }}</td>
            <td></td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-3">لم يتم صرف رواتب لأي موظف في هذه الفترة حتى الآن.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-5 text-center text-muted small border-top pt-3">
    <p>تم استخراج التقرير من النظام في: {{ now()->format('Y-m-d H:i') }}</p>
</div>
@endsection
