@extends('layouts.app')
@section('title', 'الفواتير | مصنع المنتجات')
@section('page_title', 'إدارة الفواتير')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h4 class="fw-bolder mb-1 d-none d-md-block"><i class="bi bi-receipt text-orange me-2"></i>قائمة الفواتير</h4>
        <p class="text-muted mb-0 small d-none d-md-block">{{ $stats['count'] }} فاتورة — {{ $stats['paid_count'] }} مدفوعة — {{ $stats['overdue_count'] }} متأخرة</p>
    </div>
    <div class="d-flex gap-2 flex-wrap w-sm-100">
        <a href="{{ route('invoices.fake') }}" class="btn btn-outline-orange rounded-pill px-4 shadow-sm fw-bold flex-grow-1 text-center">
            <i class="bi bi-printer me-1"></i> فاتورة سريعة
        </a>
        <a href="{{ route('invoices.create') }}" class="btn btn-orange rounded-pill px-4 shadow-sm fw-bold flex-grow-1 text-center">
            <i class="bi bi-plus-lg me-1"></i> إنشاء فاتورة
        </a>
    </div>
</div>

{{-- Stats Row --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-graph-up stat-icon text-muted"></i>
            <p class="text-muted small fw-bold mb-1">المبيعات</p>
            <div class="stat-amount">{{ number_format($stats['total_sales'], 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <i class="bi bi-cash-stack stat-icon text-success opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">المُحصَّل</p>
            <div class="stat-amount text-success">{{ number_format($stats['collected'], 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-danger">
            <i class="bi bi-exclamation-circle stat-icon text-danger opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">ديون</p>
            <div class="stat-amount text-danger">{{ number_format($stats['remaining'], 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-calendar-check stat-icon text-primary opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">الشهر الحالي</p>
            <div class="stat-amount text-primary">{{ number_format($stats['this_month'], 0) }}</div>
        </div>
    </div>
</div>

{{-- Search Bar --}}
<div class="search-bar-wrap mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0" style="border-radius:14px 0 0 14px; border:1.5px solid #e2e8f0; border-left:none;">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="invoiceSearch" class="form-control border-start-0" placeholder="🔍 بحث عن فاتورة أو عميل..." style="border:1.5px solid #e2e8f0; border-right:none; border-radius:0 14px 14px 0; font-size:.95rem;" oninput="filterInvoices(this.value)">
    </div>
</div>

@php 
    $statusLabels = ['draft'=>'مسودة', 'sent'=>'مُرسلة', 'paid'=>'مدفوعة', 'partial'=>'جزئي', 'overdue'=>'متأخرة']; 
    $bgColors = [
        'draft'   => 'badge-secondary',
        'sent'    => 'badge-info',
        'paid'    => 'badge-success',
        'partial' => 'badge-warning',
        'overdue' => 'badge-danger',
    ];
@endphp

{{-- ═══ MOBILE VIEW: Cards ═══ --}}
<div class="d-md-none" id="mobileInvoices">
    @forelse($invoices as $inv)
    @php $pct = $inv->total_amount > 0 ? round(($inv->paid_amount/$inv->total_amount)*100) : 0; @endphp
    <a href="{{ route('invoices.show', $inv) }}" class="invoice-card-mobile text-decoration-none" data-search="{{ $inv->invoice_number }} {{ $inv->customer->name ?? '' }}">
        <div class="icm-header">
            <div class="d-flex align-items-center gap-2">
                <span class="icm-icon"><i class="bi bi-receipt"></i></span>
                <span class="fw-bold text-dark">{{ $inv->invoice_number }}</span>
            </div>
            <span class="badge border {{ $bgColors[$inv->status] ?? 'bg-light text-dark' }} rounded-pill px-2" style="font-size:0.7rem;">
                {{ $statusLabels[$inv->status] ?? $inv->status }}
            </span>
        </div>
        <div class="icm-body mt-2">
            <div class="text-primary fw-bold mb-1">
                <i class="bi bi-person-fill me-1"></i>{{ $inv->customer->name ?? '—' }}
            </div>
            <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                <span><i class="bi bi-calendar me-1"></i>{{ $inv->invoice_date->format('Y-m-d') }}</span>
                <span class="fw-bold text-dark">{{ number_format($inv->total_amount, 0) }} ج.م</span>
            </div>
            <div class="mt-2">
                <div class="d-flex justify-content-between mb-1" style="font-size:0.7rem;">
                    <span class="text-success fw-bold">دفع: {{ number_format($inv->paid_amount, 0) }}</span>
                    <span class="text-danger fw-bold">باقي: {{ number_format($inv->remaining_amount, 0) }}</span>
                </div>
                <div class="progress" style="height:4px; background-color:#e2e8f0; border-radius:4px;">
                    <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                </div>
            </div>
        </div>
    </a>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-receipt text-muted d-block mb-2" style="font-size:3rem;opacity:.3;"></i>
        <span class="text-muted fw-bold">لا توجد فواتير</span>
    </div>
    @endforelse
    <div class="mt-3">{{ $invoices->links() }}</div>
</div>

{{-- ═══ DESKTOP VIEW: Table ═══ --}}
<div class="content-card d-none d-md-block">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>سجل الفواتير</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0" id="invoicesTable">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th>التاريخ</th>
                    <th>الاستحقاق</th>
                    <th class="text-center">الإجمالي</th>
                    <th class="text-center">المُحصّل</th>
                    <th class="text-center">المتبقي</th>
                    <th class="text-center">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                @php $pct = $inv->total_amount > 0 ? round(($inv->paid_amount/$inv->total_amount)*100) : 0; @endphp
                <tr class="invoice-row-desk" data-search="{{ $inv->invoice_number }} {{ $inv->customer->name ?? '' }}">
                    <td>
                        <a href="{{ route('invoices.show', $inv) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-receipt text-orange"></i>
                            </span>
                            {{ $inv->invoice_number }}
                        </a>
                    </td>
                    <td>
                        @if($inv->customer_id)
                            <a href="{{ route('customers.show', $inv->customer_id) }}" class="text-decoration-none fw-bold text-primary d-flex align-items-center gap-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="background:#eff6ff; color:#3b82f6; width:32px;height:32px;font-size:.9rem;">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                                {{ $inv->customer->name }}
                            </a>
                        @else
                            <span class="text-muted fw-bold">—</span>
                        @endif
                    </td>
                    <td><span class="badge badge-muted rounded-pill px-3 py-1">{{ $inv->invoice_date->format('Y-m-d') }}</span></td>
                    <td class="text-muted">{{ $inv->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="text-center fw-bold">{{ number_format($inv->total_amount, 0) }} <small class="text-muted fw-normal">ج.م</small></td>
                    <td class="text-center">
                        <div class="d-flex flex-column align-items-center justify-content-center">
                            <span class="text-success fw-bold">{{ number_format($inv->paid_amount, 0) }}</span>
                            <div class="progress w-100 mt-1" style="height:4px; max-width:80px; background-color:#e2e8f0;">
                                <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        @if($inv->remaining_amount > 0)
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;">
                                {{ number_format($inv->remaining_amount, 0) }} ج.م
                            </span>
                        @else
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;">
                                <i class="bi bi-check-all"></i> خالص
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge rounded-pill border {{ $bgColors[$inv->status] ?? 'bg-light text-dark' }} px-3 py-1 fw-bold">
                            {{ $statusLabels[$inv->status] ?? $inv->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-receipt text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا توجد فواتير مسجلة حتى الآن</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $invoices->links() }}
    </div>
</div>

@endsection

@push('styles')
<style>
/* Invoice Mobile Cards */
.invoice-card-mobile {
    display: block;
    background: #fff;
    border: 1.5px solid #f1f5f9;
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.2s;
}
.invoice-card-mobile:active {
    background: #fff8f4;
    border-color: var(--primary);
    transform: scale(0.98);
}
.icm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f8fafc;
    padding-bottom: 0.5rem;
}
.icm-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 8px;
    font-size: 0.9rem;
}
/* search input focus */
.search-bar-wrap .form-control:focus {
    box-shadow: 0 0 0 3px rgba(234,88,12,.1);
    border-color: var(--primary) !important;
    outline: none;
}
.search-bar-wrap .form-control:focus + .input-group-text,
.search-bar-wrap .input-group:focus-within .input-group-text {
    border-color: var(--primary) !important;
}

@media (max-width: 575px) {
    .w-sm-100 .btn { width: 48%; }
}
</style>
@endpush

@push('scripts')
<script>
function filterInvoices(q) {
    q = q.toLowerCase().trim();
    
    // Mobile cards
    document.querySelectorAll('.invoice-card-mobile').forEach(card => {
        card.style.display = card.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
    
    // Desktop table
    document.querySelectorAll('.invoice-row-desk').forEach(row => {
        row.style.display = row.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush
