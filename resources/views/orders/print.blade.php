@extends('layouts.print')

@section('title', 'طلبية ' . $order->order_number)
@section('customer_phone', $order->customer->phone ?? '')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold">طلبية رقم {{ $order->order_number }}</h3>
    <p class="mb-0">تاريخ الطلب: {{ $order->order_date->format('Y-m-d') }}</p>
</div>

<div class="row mb-3">
    <div class="col-6">
        <strong>العميل:</strong> {{ $order->customer->name ?? $order->customer_name ?? '-' }}<br>
        <strong>العنوان:</strong> {{ $order->address ?? '-' }}
    </div>
    <div class="col-6 text-start">
        <strong>الحالة:</strong> {{ $order->smart_status }}<br>
        <strong>الإجمالي:</strong> {{ number_format($order->total_amount, 2) }} {{ $order->currency ?? 'ج.م' }}<br>
        <strong>العربون:</strong> {{ number_format($order->paid_deposit ?? 0, 2) }} {{ $order->currency ?? 'ج.م' }}
    </div>
</div>

<table class="table table-bordered w-100">
    <thead>
        <tr>
            <th>المنتج</th>
            <th>المطلوب</th>
            <th>السعر</th>
            <th>الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product->name ?? '-' }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->unit_price, 2) }}</td>
            <td>{{ number_format($item->total_price, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($order->shipments->count())
<h5 class="mt-4 fw-bold">الشحنات</h5>
<table class="table table-bordered w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>السائق</th>
            <th>السيارة</th>
            <th>الحالة</th>
            <th>التاريخ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->shipments as $shipment)
        <tr>
            <td>{{ $shipment->id }}</td>
            <td>{{ $shipment->driver->name ?? $shipment->driver_name ?? '-' }}</td>
            <td>{{ $shipment->truck_details ?? '-' }}</td>
            <td>{{ $shipment->status }}</td>
            <td>{{ $shipment->shipped_at?->format('Y-m-d H:i') ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($order->notes)
<p class="mt-3"><strong>ملاحظات:</strong> {{ $order->notes }}</p>
@endif
@endsection
