@extends('layouts.app')
@section('title', 'قائمة التحميل | مصنع المنتجات')
@section('page_title', 'طلبيات قيد التحميل')

@section('content')
<div class="glass-card water-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0"><i class="bi bi-truck text-primary me-2"></i>طلبيات بانتظار التحميل</h5>
        <a href="{{ route('loading.create-adhoc') }}" class="btn btn-outline-primary fw-bold">
            <i class="bi bi-plus-circle me-1"></i> تحميل حر (بدون طلبية)
        </a>
    </div>

    @forelse($orders as $order)
    <div class="card glass-panel border-0 mb-3 hover-lift shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary">
                <div>
                    <span class="badge bg-dark text-light mb-1 px-2 py-1"><i class="bi bi-hash"></i> {{ $order->order_number }}</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $order->customer_name ?? $order->customer->name ?? 'عميل نقدي' }}</h5>
                </div>
                <div class="text-end">
                    <span class="badge bg-warning text-dark px-3 py-2 fs-7 rounded-pill border border-warning shadow-sm"><i class="bi bi-hourglass-split pulse-dot-warning"></i> بانتظار التحميل</span>
                </div>
            </div>
            
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border border-secondary text-center h-100 d-flex flex-column justify-content-center">
                        <small class="text-muted d-block mb-1"><i class="bi bi-calendar3"></i> تاريخ الطلب</small>
                        <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') }}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border border-secondary text-center h-100 d-flex flex-column justify-content-center">
                        <small class="text-muted d-block mb-1"><i class="bi bi-box-seam"></i> الكمية التقريبية</small>
                        <span class="fw-bold text-primary">{{ $order->items->sum('quantity') ?? 'متعدد' }} <small>قطعة</small></span>
                    </div>
                </div>
                @if($order->notes)
                <div class="col-12 mt-2">
                    <div class="p-2 bg-warning bg-opacity-10 rounded-3 border border-warning text-dark small">
                        <i class="bi bi-info-circle-fill text-warning me-1"></i> <strong>ملاحظات:</strong> {{ $order->notes }}
                    </div>
                </div>
                @endif
            </div>

            <a href="{{ route('loading.show', $order) }}" class="btn btn-glass w-100 fs-5 py-3 fw-bold rounded-4 shadow-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-truck fs-4"></i> بدء عملية التحميل
            </a>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-shield-check fs-1 text-success mb-3 d-block"></i>
        <h5 class="fw-bold text-muted">لا توجد طلبيات بانتظار التحميل حالياً.</h5>
    </div>
    @endforelse
</div>
@endsection

