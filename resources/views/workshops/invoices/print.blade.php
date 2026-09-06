@extends('layouts.print')

@php
    $title = 'فاتورة ورشة خارجية (منصرف / وارد)';
    $lblInvNo = 'رقم الفاتورة';
    $lblDate = 'تاريخ الفاتورة';
    $lblWorkshop = 'بيانات الورشة';
    $lblSig1 = 'توقيع المستلم (الورشة)';
    $lblSig2 = 'توقيع أمين المخزن';
@endphp

@section('title', $title . ' - ' . $invoice->invoice_number)
@if($invoice->workshop && $invoice->workshop->phone)
    @section('customer_phone', $invoice->workshop->phone)
@endif

@section('content')
<div class="row mb-4">
    <div class="col-6">
        <h3 class="fw-bold text-decoration-underline mb-3">{{ $title }}</h3>
        <table class="table table-sm table-borderless mb-0 fw-bold">
            <tr>
                <td class="p-0 pb-1" style="width: 120px">{{ $lblInvNo }}:</td>
                <td class="p-0 pb-1">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td class="p-0 pb-1">{{ $lblDate }}:</td>
                <td class="p-0 pb-1">{{ $invoice->invoice_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="p-0 pb-1">المسؤول (المصدر):</td>
                <td class="p-0 pb-1">{{ $invoice->creator->name ?? 'النظام' }}</td>
            </tr>
        </table>
    </div>
    <div class="col-6">
        <div class="border border-2 border-dark p-2 h-100">
            <h5 class="fw-bold border-bottom border-dark pb-1 mb-2">{{ $lblWorkshop }}</h5>
            <p class="mb-1 fw-bold fs-5">{{ $invoice->workshop->name }}</p>
            @if($invoice->workshop->phone)
            <p class="mb-1">الهاتف: <span dir="ltr">{{ $invoice->workshop->phone }}</span></p>
            @endif
            @if($invoice->workshop->address)
            <p class="mb-0">العنوان: {{ $invoice->workshop->address }}</p>
            @endif
        </div>
    </div>
</div>

<h5 class="fw-bold bg-light p-2 border border-dark border-bottom-0 mb-0 mt-3">1. منصرف للورشة (مبيعات خامات)</h5>
<table class="table table-bordered mb-4">
    <thead class="text-center">
        <tr>
            <th style="width: 40px">م</th>
            <th>الخامة</th>
            <th style="width: 100px">الكمية</th>
            <th style="width: 120px">السعر</th>
            <th style="width: 150px">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @php $hasMaterials = false; $count = 1; @endphp
        @foreach($invoice->items as $item)
            @if($item->item_type === 'raw_material')
                @php $hasMaterials = true; @endphp
                <tr class="text-center fw-bold">
                    <td>{{ $count++ }}</td>
                    <td class="text-start">{{ $item->rawMaterial->name ?? 'غير معروف' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endif
        @endforeach
        @if(!$hasMaterials)
            <tr><td colspan="5" class="text-center fw-bold text-muted">لا توجد خامات منصرفة في هذه الفاتورة.</td></tr>
        @endif
    </tbody>
</table>

<h5 class="fw-bold bg-light p-2 border border-dark border-bottom-0 mb-0 mt-3">2. وارد من الورشة (مشتريات منتجات)</h5>
<table class="table table-bordered mb-4">
    <thead class="text-center">
        <tr>
            <th style="width: 40px">م</th>
            <th>المنتج</th>
            <th style="width: 100px">الكمية (حبة)</th>
            <th style="width: 100px">الكراتين</th>
            <th style="width: 120px">السعر</th>
            <th style="width: 150px">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @php $hasProducts = false; $count = 1; @endphp
        @foreach($invoice->items as $item)
            @if($item->item_type === 'product')
                @php 
                    $hasProducts = true; 
                    $cages = $item->product->cages_per_carton ?? 1;
                    $cartons = $cages > 0 ? ($item->quantity / $cages) : 0;
                @endphp
                <tr class="text-center fw-bold">
                    <td>{{ $count++ }}</td>
                    <td class="text-start">{{ $item->product->name ?? 'غير معروف' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($cartons, 2) }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endif
        @endforeach
        @if(!$hasProducts)
            <tr><td colspan="6" class="text-center fw-bold text-muted">لا توجد منتجات واردة في هذه الفاتورة.</td></tr>
        @endif
    </tbody>
</table>

<div class="row">
    <div class="col-7">
        @if($invoice->notes)
        <div class="mb-3">
            <span class="fw-bold text-decoration-underline">ملاحظات:</span>
            <p class="mt-1 fw-bold">{{ $invoice->notes }}</p>
        </div>
        @endif
    </div>
    <div class="col-5">
        <table class="table table-bordered text-end fw-bold mb-0">
            <tr>
                <th class="w-50">إجمالي المنصرف (لهم)</th>
                <td>{{ number_format($invoice->total_materials_sold, 2) }} ج.م</td>
            </tr>
            <tr>
                <th>إجمالي الوارد (منهم)</th>
                <td>{{ number_format($invoice->total_products_bought, 2) }} ج.م</td>
            </tr>
            <tr>
                <th class="fs-5 bg-light">الصافي</th>
                <td class="fs-5 bg-light" style="direction: ltr;">
                    @if($invoice->net_amount > 0)
                        {{ number_format($invoice->net_amount, 2) }} (لصالحنا)
                    @elseif($invoice->net_amount < 0)
                        {{ number_format(abs($invoice->net_amount), 2) }} (لصالحهم)
                    @else
                        0.00 (خالص)
                    @endif
                </td>
            </tr>
            <tr>
                <th>المدفوع نقداً (بالورقة)</th>
                <td>{{ number_format($invoice->paid_amount, 2) }} ج.م</td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-5 text-center fw-bold">
    <div class="col-6">
        <p class="mb-4">{{ $lblSig1 }}</p>
        <p>.................................</p>
    </div>
    <div class="col-6">
        <p class="mb-4">{{ $lblSig2 }}</p>
        <p>.................................</p>
    </div>
</div>
@endsection
