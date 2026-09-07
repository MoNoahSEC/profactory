@extends('layouts.app')

@section('title', 'الطلبيات | ' . config('app.name'))
@section('page_title', 'إدارة الطلبيات')

@push('styles')

@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bolder m-0"><i class="bi bi-cart-check-fill text-orange me-2"></i> إدارة الطلبيات</h4>
    <div>
        <a href="{{ route('orders.create') }}" class="btn btn-outline-orange rounded-pill px-4 fw-bold">
            <i class="bi bi-plus-lg me-1"></i> طلبية جديدة
        </a>
    </div>
</div>

<div class="row g-2 mb-4">
    <!-- Global Total Card -->
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-color: #334155;">
            <i class="bi bi-wallet2 stat-icon text-white opacity-25"></i>
            <p class="text-white opacity-75 small fw-bold mb-1">إجمالي الطلبيات النشطة</p>
            <div class="stat-amount text-white">{{ number_format($stats['total_active_amount'], 0) }}</div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-warning">
            <i class="bi bi-clock-history stat-icon text-warning opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">طلبيات قيد التنفيذ</p>
            <div class="stat-amount text-warning">{{ $stats['pending_count'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-success">
            <i class="bi bi-check-all stat-icon text-success opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">مكتملة (غير مفوترة)</p>
            <div class="stat-amount text-success">{{ $stats['completed_count'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-info">
            <i class="bi bi-file-earmark-check stat-icon text-info opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">محولة لفواتير</p>
            <div class="stat-amount text-info">{{ $stats['converted_count'] }}</div>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>سجل الطلبيات</h6>
        <input type="text" id="searchInput" class="form-control form-control-sm w-auto flex-grow-1" placeholder="🔍 بحث..." style="border-radius:10px; border: 1.5px solid #e2e8f0; max-width: 300px;">
    </div>
    
    {{-- MOBILE VIEW --}}
    <div class="d-md-none p-2">
        @forelse($orders as $order)
            @php
                $remaining = $order->total_amount - ($order->paid_deposit ?? 0);
            @endphp
            <div class="glass-card mb-3 p-3 bg-white border rounded-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <a href="{{ route('orders.edit', $order) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                            <i class="bi bi-cart text-orange"></i>
                        </span>
                        {{ $order->order_number }}
                    </a>
                    <span class="fw-bold text-muted small"><i class="bi bi-calendar me-1"></i>{{ $order->order_date->format('Y-m-d') }}</span>
                </div>
                
                <div class="mb-2">
                    <div class="text-muted small mb-1">العميل:</div>
                    @if($order->customer_id)
                        <a href="{{ route('customers.show', $order->customer_id) }}" class="btn btn-sm btn-glass text-primary px-3 py-1 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-person-fill"></i> {{ $order->customer->name ?? $order->customer_name }}
                        </a>
                    @else
                        <span class="badge bg-secondary rounded-pill">{{ $order->customer_name ?? 'غير محدد' }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">الأصناف والكميات:</div>
                    <ul class="list-unstyled mb-0">
                        @foreach($order->items as $item)
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                @if($item->product_id)
                                    <span class="fw-bold text-dark" style="font-size: 0.9rem;">
                                        <i class="bi bi-box-seam text-warning me-1"></i> {{ $item->product->name ?? '-' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">—</span>
                                @endif
                                <span>
                                    <span class="badge bg-light text-dark border">{{ $item->quantity }}</span>
                                    <small class="text-muted ms-1">× {{ number_format($item->unit_price, 0) }}</small>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="row g-2 mb-2 text-center small fw-bold">
                <div class="row g-2 mb-2 text-center small fw-bold">
                    <div class="col-4">
                        <div class="bg-light rounded p-1 border">إجمالي<br><span class="text-success">{{ number_format($order->total_amount, 0) }}</span></div>
                    </div>
                    <div class="col-4">
                        <div class="badge-warning rounded p-1 w-100 d-block">عربون<br><span class="text-dark">{{ $order->paid_deposit > 0 ? number_format($order->paid_deposit, 0) : '—' }}</span></div>
                    </div>
                    <div class="col-4">
                        <div class="badge-danger rounded p-1 w-100 d-block">متبقي<br><span>{{ number_format($remaining, 0) }}</span></div>
                    </div>
                </div>

                <div class="text-center mt-2 pt-2 border-top">
                    @if($order->converted_to_invoice)
                        <span class="badge badge-info rounded-pill px-3 py-2"><i class="bi bi-file-earmark-check"></i> مفوتر</span>
                    @elseif($order->status == 'awaiting_approval')
                        <span class="badge badge-danger rounded-pill px-3 py-2"><i class="bi bi-shield-exclamation"></i> بانتظار المراجعة</span>
                    @elseif($order->status == 'completed')
                        <span class="badge badge-success rounded-pill px-3 py-2"><i class="bi bi-check-all"></i> مكتمل</span>
                    @else
                        <span class="badge badge-warning rounded-pill px-3 py-2"><i class="bi bi-clock-history"></i> جاري</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted fw-bold border rounded-3 bg-light">لا توجد طلبيات مسجلة حالياً.</div>
        @endforelse
    </div>

    {{-- DESKTOP VIEW --}}
    <div class="table-responsive d-none d-md-block">
        <table class="table table-clean mb-0" id="ordersTable">
                    <thead>
                        <tr>
                            <th>رقم الطلبية</th>
                            <th>تاريخ الطلب</th>
                            <th>العميل</th>
                            <th>الأصناف والكميات</th>
                            <th>الإجمالي</th>
                            <th>العربون</th>
                            <th>المتبقي</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $remaining = $order->total_amount - ($order->paid_deposit ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('orders.edit', $order) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                            <i class="bi bi-cart text-orange"></i>
                                        </span>
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td data-label="تاريخ الطلب"><span class="smart-badge">{{ $order->order_date->format('Y-m-d') }}</span></td>
                                <td data-label="العميل" class="fw-bold text-primary">
                                    @if($order->customer_id)
                                        <a href="{{ route('customers.show', $order->customer_id) }}" class="btn btn-sm btn-glass text-primary px-3 py-1 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-person-fill"></i> {{ $order->customer->name ?? $order->customer_name }}
                                        </a>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">{{ $order->customer_name ?? 'غير محدد' }}</span>
                                    @endif
                                </td>
                                <td data-label="الأصناف">
                                    <ul class="list-unstyled mb-0 text-start">
                                        @foreach($order->items as $item)
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                                                @if($item->product_id)
                                                    <a href="{{ route('products.cost', $item->product_id) }}" class="btn btn-sm btn-glass text-warning px-3 py-0 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.8rem;">
                                                        <i class="bi bi-box-seam"></i> {{ $item->product->name ?? '-' }}
                                                    </a>
                                                @else
                                                    <span class="badge bg-secondary rounded-pill">—</span>
                                                @endif
                                                <span>
                                                    <span class="badge bg-light text-dark border">{{ $item->quantity }}</span>
                                                    <small class="text-muted ms-1">× {{ number_format($item->unit_price, 0) }}</small>
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>

                                <td data-label="الإجمالي" class="fw-bold text-success">{{ number_format($order->total_amount, 0) }}</td>
                                <td data-label="العربون">
                                    @if($order->paid_deposit > 0)
                                        <span class="text-warning fw-bold">{{ number_format($order->paid_deposit, 0) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="المتبقي" class="fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($remaining, 0) }}</td>
                                <td data-label="الحالة">
                                    @if($order->converted_to_invoice)
                                        <span class="badge badge-info rounded-pill px-2 py-1"><i class="bi bi-file-earmark-check"></i> مفوتر</span>
                                    @elseif($order->status == 'awaiting_approval')
                                        <span class="badge badge-danger rounded-pill px-2 py-1"><i class="bi bi-shield-exclamation"></i> بانتظار المراجعة</span>
                                    @elseif($order->status == 'completed')
                                        <span class="badge badge-success rounded-pill px-2 py-1"><i class="bi bi-check-all"></i> مكتمل</span>
                                    @else
                                        <span class="badge badge-warning rounded-pill px-2 py-1"><i class="bi bi-clock-history"></i> جاري</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted py-4">لا توجد طلبيات مسجلة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
    </div>
            <div class="mt-3 d-flex justify-content-center">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>



@endsection
