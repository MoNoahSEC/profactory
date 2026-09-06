@extends('layouts.print')
@section('title', 'أمر إنتاج ' . $productionOrder->order_number)

@section('content')
<div class="row mb-4">
    <div class="col-6">
        <h4 class="fw-bold">أمر تشغيل / إنتاج</h4>
        <p class="mb-1"><strong>رقم الأمر:</strong> {{ $productionOrder->order_number }}</p>
        <p class="mb-1"><strong>تاريخ الأمر:</strong> {{ $productionOrder->production_date->format('Y-m-d') }}</p>
        @php $statusLabels=['pending'=>'معلق','in_progress'=>'جاري','completed'=>'مكتمل','cancelled'=>'ملغي']; @endphp
        <p class="mb-1"><strong>الحالة:</strong> {{ $statusLabels[$productionOrder->status] ?? $productionOrder->status }}</p>
    </div>
    <div class="col-6 text-end">
        <h5 class="fw-bold">بيانات المنتج</h5>
        <p class="mb-1"><strong>المنتج:</strong> {{ $productionOrder->product->name ?? '-' }} ({{ $productionOrder->product->code ?? '-' }})</p>
        <p class="mb-1"><strong>الكمية المطلوبة:</strong> {{ $productionOrder->quantity_ordered }}</p>
        <p class="mb-1"><strong>الكمية المنتجة:</strong> {{ $productionOrder->quantity_produced }}</p>
    </div>
</div>

<h5 class="fw-bold mb-3 border-bottom border-dark pb-2">التكاليف التشغيلية (استخدام داخلي)</h5>
<table class="table table-bordered mb-4">
    <thead class="table-light text-center">
        <tr>
            <th>تكلفة الخامات</th>
            <th>تكلفة الموظفينة</th>
            <th>المصاريف العامة</th>
            <th>التكلفة الكلية للمشغولة</th>
            <th>تكلفة الوحدة الواحدة</th>
        </tr>
    </thead>
    <tbody>
        <tr class="text-center">
            <td>{{ number_format($productionOrder->cost->material_cost ?? 0, 2) }} ج.م</td>
            <td>{{ number_format($productionOrder->cost->labor_cost ?? 0, 2) }} ج.م</td>
            <td>{{ number_format($productionOrder->cost->overhead_cost ?? 0, 2) }} ج.م</td>
            <td class="fw-bold">{{ number_format($productionOrder->cost->total_cost ?? 0, 2) }} ج.م</td>
            <td class="fw-bold">{{ number_format($productionOrder->cost->cost_per_unit ?? 0, 2) }} ج.م</td>
        </tr>
    </tbody>
</table>

<div class="row mt-4">
    <div class="col-12">
        <p><strong>ملاحظات:</strong> {{ $productionOrder->notes ?? 'لا يوجد ملاحظات' }}</p>
    </div>
</div>

<div class="row mt-5 text-center">
    <div class="col-4">
        <p><strong>مشرف الإنتاج</strong></p>
        <p>.................................</p>
    </div>
    <div class="col-4">
        <p><strong>أمين المخزن (استلام)</strong></p>
        <p>.................................</p>
    </div>
    <div class="col-4">
        <p><strong>إدارة المصنع</strong></p>
        <p>.................................</p>
    </div>
</div>
@endsection
