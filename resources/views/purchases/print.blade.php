@extends('layouts.print')
@php
    $title = 'إيصال استلام خامات';
@endphp

@section('title', $title . ' - ' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT))
@if($purchase->supplier && $purchase->supplier->phone)
    @section('customer_phone', $purchase->supplier->phone)
@endif

@section('content')
<div class="row mb-3">
    <div class="col-6">
        <h3 class="fw-bold text-decoration-underline mb-3">{{ $title }}</h3>
        <table class="table table-sm table-borderless mb-0 fw-bold">
            <tr>
                <td class="p-0 pb-1" style="width: 120px">رقم الإيصال:</td>
                <td class="p-0 pb-1">#{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td class="p-0 pb-1">تاريخ الاستلام:</td>
                <td class="p-0 pb-1">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}</td>
            </tr>
        </table>
    </div>
    <div class="col-6 text-end">
        <div class="border border-dark p-2 d-inline-block text-start rounded" style="min-width: 200px;">
            <div class="fw-bold fs-6 mb-1 text-decoration-underline">بيانات المورد</div>
            <div class="fw-bold">{{ $purchase->supplier->name ?? 'غير محدد' }}</div>
            @if($purchase->supplier && $purchase->supplier->phone)
                <div>{{ $purchase->supplier->phone }}</div>
            @endif
        </div>
    </div>
</div>

<table class="table table-bordered border-dark table-sm mb-3 text-center align-middle">
    <thead class="table-light border-dark">
        <tr>
            <th class="py-2" style="width: 5%">م</th>
            <th class="py-2" style="width: 45%">الصنف / الخامة</th>
            <th class="py-2" style="width: 15%">الكمية</th>
            <th class="py-2" style="width: 15%">سعر الوحدة</th>
            <th class="py-2" style="width: 20%">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td class="text-start fw-bold">{{ $purchase->rawMaterial->name ?? 'غير محدد' }}</td>
            <td class="fw-bold">{{ $purchase->quantity }}</td>
            <td class="fw-bold">{{ number_format($purchase->unit_price, 2) }}</td>
            <td class="fw-bold">{{ number_format($purchase->total_price, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="row mt-4">
    <div class="col-7">
        @if($purchase->notes)
            <div class="border border-dark p-2 rounded mb-2">
                <span class="fw-bold text-decoration-underline">ملاحظات:</span><br>
                {{ $purchase->notes }}
            </div>
        @endif
        
        <div class="mt-4 text-center d-flex justify-content-around fw-bold">
            <div>
                <div>توقيع المستلم</div>
                <div class="mt-4 border-top border-dark border-2 pt-1" style="width: 150px;"></div>
            </div>
            <div>
                <div>ختم الشركة</div>
                <div class="mt-4 border-top border-dark border-2 pt-1" style="width: 150px;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-5">
        <table class="table table-bordered border-dark table-sm fw-bold">
            <tr>
                <td class="table-light w-50">إجمالي الفاتورة</td>
                <td class="text-center fs-5">{{ number_format($purchase->total_price, 2) }}</td>
            </tr>
            <tr>
                <td class="table-light">المدفوع نقداً</td>
                <td class="text-center text-success fs-5">{{ number_format($purchase->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="table-light">المتبقي (آجل)</td>
                <td class="text-center text-danger fs-5">{{ number_format($purchase->total_price - $purchase->paid_amount, 2) }}</td>
            </tr>
        </table>
    </div>
</div>
@endsection
