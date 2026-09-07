@extends('layouts.app')

@section('title', 'مشتريات الخام | ' . config('app.name'))
@section('page_title', 'مشتريات المواد الخام')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bolder mb-1 d-none d-md-block"><i class="bi bi-cart-plus-fill text-orange me-2"></i>مشتريات المواد الخام</h4>
        <p class="text-muted mb-0 small d-none d-md-block">إدارة فواتير مشتريات المصنع للخامات</p>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-orange shadow-sm rounded-pill px-4 flex-grow-1 text-center">
        <i class="bi bi-plus-lg me-1"></i> تسجيل فاتورة جديدة
    </a>
</div>

{{-- Stats --}}
@php
    $pageTotal = $purchases->sum('total_price');
    $pagePaid = $purchases->sum('paid_amount');
    $pageDebt = $purchases->sum(fn($p) => max(0, $p->total_price - $p->paid_amount));
@endphp

<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="stat-card">
            <i class="bi bi-cart-check stat-icon text-muted"></i>
            <p class="text-muted small fw-bold mb-1">مشتريات</p>
            <div class="stat-amount text-dark" style="font-size:1.1rem;">{{ number_format($pageTotal, 0) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card stat-success">
            <i class="bi bi-check-circle stat-icon text-success opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">مدفوع</p>
            <div class="stat-amount text-success" style="font-size:1.1rem;">{{ number_format($pagePaid, 0) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card stat-danger">
            <i class="bi bi-exclamation-triangle stat-icon text-danger opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">مديونية</p>
            <div class="stat-amount text-danger" style="font-size:1.1rem;">{{ number_format($pageDebt, 0) }}</div>
        </div>
    </div>
</div>

{{-- Search Bar --}}
<div class="search-bar-wrap mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0" style="border-radius:14px 0 0 14px; border:1.5px solid #e2e8f0; border-left:none;">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="searchInput" class="form-control border-start-0" placeholder="🔍 بحث عن فاتورة، مورد، مادة خام..." style="border:1.5px solid #e2e8f0; border-right:none; border-radius:0 14px 14px 0; font-size:.95rem;" oninput="filterPurchases(this.value)">
    </div>
</div>

{{-- ═══ MOBILE VIEW: Cards ═══ --}}
<div class="d-md-none" id="mobilePurchases">
    @forelse($purchases as $p)
    @php $rem = $p->total_price - $p->paid_amount; @endphp
    <div class="purchase-card-mobile" data-search="{{ $p->id }} {{ $p->supplier->name ?? '' }} {{ $p->rawMaterial->name ?? '' }}">
        <div class="pcm-header">
            <div class="d-flex align-items-center gap-2">
                <span class="pcm-icon bg-orange-soft text-orange"><i class="bi bi-cart-fill"></i></span>
                <div>
                    <span class="fw-bold text-dark d-block">#{{ $p->id }}</span>
                    <small class="text-muted" style="font-size:0.7rem;">{{ $p->purchase_date->format('Y-m-d') }}</small>
                </div>
            </div>
            @if($rem <= 0)
                <span class="badge badge-success rounded-pill px-2" style="font-size:0.72rem;">
                    <i class="bi bi-check-all"></i> خالص
                </span>
            @else
                <span class="badge badge-danger rounded-pill px-2" style="font-size:0.72rem;">
                    آجل
                </span>
            @endif
        </div>
        <div class="pcm-body mt-2">
            <div class="mb-1 text-primary fw-bold" style="font-size:0.9rem;">
                <i class="bi bi-truck me-1"></i>{{ $p->supplier->name ?? 'غير معروف' }}
            </div>
            <div class="mb-1 fw-bold text-dark" style="font-size:0.85rem;">
                {{ $p->rawMaterial->name ?? 'غير محدد' }} — {{ $p->quantity }} {{ $p->rawMaterial->unit ?? '' }}
            </div>
            <div class="d-flex justify-content-between mt-2" style="font-size:0.8rem;">
                <div>إجمالي: <span class="fw-bold text-orange">{{ number_format($p->total_price, 2) }}</span></div>
                <div>مدفوع: <span class="fw-bold text-success">{{ number_format($p->paid_amount, 2) }}</span></div>
            </div>
            @if($rem > 0)
            <div class="text-danger fw-bold mt-1" style="font-size:0.8rem; text-align:left;">
                باقي: {{ number_format($rem, 2) }}
            </div>
            @endif
        </div>
        <div class="pcm-footer d-flex gap-2 mt-2 pt-2 border-top">
            <a href="{{ route('purchases.print', $p) }}" target="_blank" class="btn btn-sm btn-light flex-grow-1" style="border-radius:10px;"><i class="bi bi-printer text-muted me-1"></i> طباعة</a>
            <a href="{{ route('purchases.edit', $p) }}" class="btn btn-sm btn-light flex-grow-1 text-primary" style="border-radius:10px;"><i class="bi bi-pencil-square me-1"></i> تعديل</a>
            @hasrole('Admin')
            <form action="{{ route('purchases.destroy', $p) }}" method="POST" class="d-inline flex-grow-1 m-0 p-0" onsubmit="return confirm('حذف الفاتورة؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-light text-danger w-100" style="border-radius:10px;"><i class="bi bi-trash"></i></button>
            </form>
            @endhasrole
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-cart-x text-muted d-block mb-3 opacity-25" style="font-size:3rem;"></i>
        <h6 class="text-muted fw-bold">لا توجد فواتير مسجلة</h6>
    </div>
    @endforelse
    @if($purchases->hasPages())
        <div class="mt-3">{{ $purchases->links() }}</div>
    @endif
</div>

{{-- ═══ DESKTOP VIEW: Table ═══ --}}
<div class="content-card d-none d-md-block">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>سجل فواتير الشراء</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0">
            <thead>
                <tr>
                    <th>رقم/التاريخ</th>
                    <th>المورد</th>
                    <th>المادة الخام</th>
                    <th class="text-center">الكمية</th>
                    <th class="text-center">السعر</th>
                    <th class="text-center">الإجمالي</th>
                    <th class="text-center">المدفوع</th>
                    <th class="text-center">المتبقي</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)
                <tr class="purchase-row-desk" data-search="{{ $p->id }} {{ $p->supplier->name ?? '' }} {{ $p->rawMaterial->name ?? '' }}">
                    <td>
                        <span class="d-block fw-bold text-dark">#{{ $p->id }}</span>
                        <span class="badge badge-muted rounded-pill px-2 py-1" style="font-size:0.75rem;">{{ $p->purchase_date->format('Y-m-d') }}</span>
                    </td>
                    <td>
                        @if($p->supplier_id)
                            <a href="{{ route('suppliers.show', $p->supplier_id) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:32px;height:32px;font-size:.9rem;flex-shrink:0;">
                                    <i class="bi bi-truck text-orange"></i>
                                </span>
                                <div>
                                    <span class="d-block">{{ $p->supplier->name ?? 'غير معروف' }}</span>
                                </div>
                            </a>
                        @else
                            <span class="text-muted">غير معروف</span>
                        @endif
                    </td>
                    <td class="fw-bold text-primary">{{ $p->rawMaterial->name ?? 'غير محدد' }}</td>
                    <td class="text-center fw-bold">{{ $p->quantity }} <small class="text-muted fw-normal">{{ $p->rawMaterial->unit ?? '' }}</small></td>
                    <td class="text-center">{{ number_format($p->unit_price, 2) }}</td>
                    <td class="text-center fw-bold text-orange">{{ number_format($p->total_price, 2) }}</td>
                    <td class="text-center fw-bold text-success">{{ number_format($p->paid_amount, 2) }}</td>
                    <td class="text-center">
                        @php $rem = $p->total_price - $p->paid_amount; @endphp
                        @if($rem > 0)
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;">{{ number_format($rem, 2) }}</span>
                        @else
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;"><i class="bi bi-check-all"></i> خالص</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown" style="width:32px; height:32px;">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius:12px;">
                                <li>
                                    <a class="dropdown-item fw-bold text-dark" href="{{ route('purchases.print', $p) }}" target="_blank">
                                        <i class="bi bi-printer text-muted me-2"></i> طباعة الفاتورة
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item fw-bold text-primary" href="{{ route('purchases.edit', $p) }}">
                                        <i class="bi bi-pencil-square me-2"></i> تعديل
                                    </a>
                                </li>
                                @hasrole('Admin')
                                <li>
                                    <form action="{{ route('purchases.destroy', $p) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفاتورة وعكس المخزون والخزينة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item fw-bold text-danger">
                                            <i class="bi bi-trash me-2"></i> حذف
                                        </button>
                                    </form>
                                </li>
                                @endhasrole
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-cart-x text-muted display-4 d-block mb-3 opacity-25"></i>
                        <h5 class="text-muted fw-bold">لا توجد فواتير مشتريات مسجلة</h5>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($purchases->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-center py-3">
            {{ $purchases->links() }}
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
/* Purchase Mobile Cards */
.purchase-card-mobile {
    display: block;
    background: #fff;
    border: 1.5px solid #f1f5f9;
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
}
.pcm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f8fafc;
    padding-bottom: 0.5rem;
}
.pcm-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
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
</style>
@endpush

@push('scripts')
<script>
function filterPurchases(q) {
    q = q.toLowerCase().trim();
    
    // Mobile cards
    document.querySelectorAll('.purchase-card-mobile').forEach(card => {
        card.style.display = card.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
    
    // Desktop table
    document.querySelectorAll('.purchase-row-desk').forEach(row => {
        row.style.display = row.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush
