@extends('layouts.print')
@php
    $title = \App\Models\Setting::get('invoice_title', 'فاتورة مبيعات');
    $lblInvNo = \App\Models\Setting::get('lbl_invoice_no', 'رقم الفاتورة');
    $lblDate = \App\Models\Setting::get('lbl_date', 'تاريخ الفاتورة');
    $lblDue = \App\Models\Setting::get('lbl_due_date', 'تاريخ الاستحقاق');
    $lblCustomer = \App\Models\Setting::get('lbl_customer', 'بيانات العميل');
    
    $lblItem = \App\Models\Setting::get('lbl_item', 'البيان');
    $lblQty = \App\Models\Setting::get('lbl_qty', 'الكمية');
    $lblPrice = \App\Models\Setting::get('lbl_price', 'سعر الوحدة');
    $lblDisc = \App\Models\Setting::get('lbl_discount', 'الخصم');
    $lblTotal = \App\Models\Setting::get('lbl_total', 'الإجمالي');
    
    $lblSubtotal = \App\Models\Setting::get('lbl_subtotal', 'المجموع الفرعي');
    $lblTax = \App\Models\Setting::get('lbl_tax', 'الضريبة');
    $lblNet = \App\Models\Setting::get('lbl_net_total', 'الإجمالي العام');
    $lblPaid = \App\Models\Setting::get('lbl_paid', 'المدفوع');
    $lblRem = \App\Models\Setting::get('lbl_remaining', 'المتبقي');
    
    $lblSig1 = \App\Models\Setting::get('lbl_signature1', 'توقيع المستلم');
    $lblSig2 = \App\Models\Setting::get('lbl_signature2', 'ختم الشركة');
    
    $showTax = \App\Models\Setting::get('show_tax', 'yes') === 'yes';
    $showDisc = \App\Models\Setting::get('show_discount', 'yes') === 'yes';
    $showPhone = \App\Models\Setting::get('show_customer_phone', 'yes') === 'yes';
    $showRem = \App\Models\Setting::get('show_remaining', 'yes') === 'yes';
    $terms = \App\Models\Setting::get('invoice_terms', '');
@endphp

@section('title', $title . ' - ' . $invoice->invoice_number)
@if($invoice->customer && $invoice->customer->phone)
    @section('customer_phone', $invoice->customer->phone)
@endif

@section('content')
<div class="row mb-3">
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
            @if($invoice->due_date)
            <tr>
                <td class="p-0 pb-1">{{ $lblDue }}:</td>
                <td class="p-0 pb-1">{{ $invoice->due_date->format('Y-m-d') }}</td>
            </tr>
            @endif
        </table>
    </div>
    <div class="col-6">
        <div class="border border-2 border-dark p-2 h-100">
            <h5 class="fw-bold border-bottom border-dark pb-1 mb-2">{{ $lblCustomer }}</h5>
            <p class="mb-1 fw-bold fs-5">{{ $invoice->customer->name ?? 'عميل نقدي' }}</p>
            @if($showPhone && isset($invoice->customer->phone))
            <p class="mb-1">الهاتف: <span dir="ltr">{{ $invoice->customer->phone }}</span></p>
            @endif
            @if(isset($invoice->customer->address))
            <p class="mb-0">العنوان: {{ $invoice->customer->address }}</p>
            @endif
        </div>
    </div>
</div>

<table class="table table-bordered mb-3">
    <thead class="text-center">
        <tr>
            <th style="width: 40px">م</th>
            <th>{{ $lblItem }}</th>
            <th style="width: 80px">{{ $lblQty }}</th>
            <th style="width: 100px">السعر</th>
            <th style="width: 120px">{{ $lblTotal }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $index => $item)
        <tr class="text-center fw-bold">
            <td>{{ $index + 1 }}</td>
            <td class="text-start">{{ $item->product->name ?? '-' }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->unit_price, 2) }}</td>
            <td>{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
        <!-- Fill empty rows if items are too few to maintain layout structure -->
        @for($i = count($invoice->items); $i < 5; $i++)
        <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        @endfor
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
        
        @if($terms)
        <div class="border border-dark p-2 mt-auto" style="font-size: 0.9rem;">
            <p class="mb-0 fw-bold">{{ $terms }}</p>
        </div>
        @endif
    </div>
    <div class="col-5">
        <table class="table table-bordered text-end fw-bold mb-0">
            <tr>
                <th class="w-50">{{ $lblSubtotal }}</th>
                <td>{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
            </tr>
            @if($invoice->discount_value > 0)
            <tr>
                <th>خصم</th>
                <td>{{ number_format($invoice->discount_type === 'percent' ? ($invoice->subtotal * $invoice->discount_value / 100) : $invoice->discount_value, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
            </tr>
            @endif
            @if($showTax && $invoice->tax_amount > 0)
            <tr>
                <th>{{ $lblTax }} ({{ $invoice->tax_rate }}%)</th>
                <td>{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
            </tr>
            @endif
            <tr>
                <th class="fs-5 bg-light">{{ $lblNet }}</th>
                <td class="fs-5 bg-light">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
            </tr>
            <tr>
                <th>{{ $lblPaid }}</th>
                <td>{{ number_format($invoice->paid_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
            </tr>
            @if($showRem)
            <tr>
                <th>{{ $lblRem }}</th>
                <td>{{ number_format($invoice->remaining_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
            </tr>
            @endif
            {{-- ✅ الرصيد السابق --}}
            @if(isset($previousBalance) && $previousBalance != 0 && isset($invoice->customer_id) && $invoice->customer_id)
            <tr style="border-top: 2px solid #333;">
                <th class="bg-warning bg-opacity-25 text-dark">رصيد سابق</th>
                <td class="bg-warning bg-opacity-25 fw-bold text-dark">
                    @if($previousBalance > 0)
                        {{ number_format($previousBalance, 2) }} {{ $invoice->currency ?? 'ج.م' }} (عليه)
                    @elseif($previousBalance < 0)
                        {{ number_format(abs($previousBalance), 2) }} {{ $invoice->currency ?? 'ج.م' }} (له)
                    @else
                        0.00 (خالص)
                    @endif
                </td>
            </tr>
            @endif
            @if(isset($customerBalance) && isset($invoice->customer_id) && $invoice->customer_id)
            <tr>
                <th class="pt-2 border-top border-2 border-dark fs-6 bg-light text-center" colspan="2">إجمالي حساب العميل الحالي</th>
            </tr>
            <tr>
                <td class="fs-6 bg-light fw-bold text-dark text-center" colspan="2" style="direction: ltr;">
                    @if($customerBalance > 0)
                        {{ number_format($customerBalance, 2) }} {{ $invoice->currency ?? 'ج.م' }} (عليه)
                    @elseif($customerBalance < 0)
                        {{ number_format(abs($customerBalance), 2) }} {{ $invoice->currency ?? 'ج.م' }} (له)
                    @else
                        0.00 (خالص)
                    @endif
                </td>
            </tr>
            @endif
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
