@extends('layouts.app')
@section('title', 'الموردين | مصنع المنتجات')
@section('page_title', 'إدارة الموردين')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bolder mb-1 d-none d-md-block"><i class="bi bi-truck text-orange me-2"></i>الموردين</h4>
        <p class="text-muted mb-0 small d-none d-md-block">إجمالي {{ method_exists($suppliers, 'total') ? $suppliers->total() : $suppliers->count() }} مورد مسجل في النظام</p>
    </div>
    <button class="btn btn-orange shadow-sm rounded-pill px-4 flex-grow-1 flex-md-grow-0 text-center" data-bs-toggle="modal" data-bs-target="#supplierModal">
        <i class="bi bi-plus-lg me-1"></i> إضافة مورد جديد
    </button>
</div>

{{-- Stats Row --}}
@php
    $totalPurchases = $suppliers->sum('purchases_sum_total_price');
    $totalPaid      = $suppliers->sum('purchases_sum_paid_amount');
    $totalDebt      = $suppliers->sum(fn($s) => max(0, $s->calculated_debt));
@endphp
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-truck stat-icon text-muted"></i>
            <p class="text-muted small fw-bold mb-1">الموردين</p>
            <div class="stat-amount text-dark">{{ method_exists($suppliers, 'total') ? $suppliers->total() : $suppliers->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-cart-check stat-icon text-primary opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">مشتريات</p>
            <div class="stat-amount text-primary" style="font-size:1.1rem;">{{ number_format($totalPurchases, 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <i class="bi bi-check-circle stat-icon text-success opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">المدفوع</p>
            <div class="stat-amount text-success" style="font-size:1.1rem;">{{ number_format($totalPaid, 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-danger">
            <i class="bi bi-exclamation-triangle stat-icon text-danger opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">المديونية</p>
            <div class="stat-amount text-danger" style="font-size:1.1rem;">{{ number_format($totalDebt, 0) }}</div>
        </div>
    </div>
</div>

{{-- Search Bar --}}
<div class="search-bar-wrap mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0" style="border-radius:14px 0 0 14px; border:1.5px solid #e2e8f0; border-left:none;">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="searchInput" class="form-control border-start-0" placeholder="🔍 بحث عن مورد..." style="border:1.5px solid #e2e8f0; border-right:none; border-radius:0 14px 14px 0; font-size:.95rem;" oninput="filterSuppliers(this.value)">
    </div>
</div>

{{-- ═══ MOBILE VIEW: Cards ═══ --}}
<div class="d-md-none" id="mobileSuppliers">
    @forelse($suppliers as $supplier)
    @php $debt = $supplier->calculated_debt; @endphp
    <a href="{{ route('suppliers.show', $supplier) }}" class="supplier-card-mobile text-decoration-none" data-search="{{ $supplier->name }} {{ $supplier->company_name ?? '' }} {{ $supplier->phone ?? '' }}">
        <div class="scm-header d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="scm-icon bg-orange-soft text-orange">
                    <i class="bi bi-truck"></i>
                </span>
                <div>
                    <div class="fw-bold text-dark">{{ $supplier->name }}</div>
                    @if($supplier->company_name)
                        <small class="text-muted" style="font-size:0.75rem;">{{ $supplier->company_name }}</small>
                    @endif
                </div>
            </div>
            @if($debt > 0)
                <span class="badge badge-danger rounded-pill px-2" style="font-size:0.72rem;">عليه: {{ number_format($debt, 0) }}</span>
            @elseif($debt < 0)
                <span class="badge badge-success rounded-pill px-2" style="font-size:0.72rem;">له: {{ number_format(abs($debt), 0) }}</span>
            @else
                <span class="badge badge-secondary rounded-pill px-2" style="font-size:0.72rem;">مسدد</span>
            @endif
        </div>
        <div class="scm-body">
            <div class="d-flex justify-content-between text-muted mb-1" style="font-size:0.8rem;">
                <span><i class="bi bi-telephone-fill me-1"></i>{{ $supplier->phone ?? 'لا يوجد رقم' }}</span>
                <span><i class="bi bi-cart me-1"></i>مشتريات: <strong class="text-dark">{{ number_format($supplier->purchases_sum_total_price ?? 0, 0) }}</strong></span>
            </div>
        </div>
    </a>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-truck text-muted d-block mb-3 opacity-25" style="font-size:3rem;"></i>
        <h6 class="text-muted fw-bold">لا يوجد موردين مسجلين</h6>
    </div>
    @endforelse
    @if(method_exists($suppliers, 'hasPages') && $suppliers->hasPages())
        <div class="mt-3">{{ $suppliers->links() }}</div>
    @endif
</div>

{{-- ═══ DESKTOP VIEW: Table ═══ --}}
<div class="content-card d-none d-md-block">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>قائمة الموردين</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0" id="suppliersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم / الشركة</th>
                    <th>الهاتف</th>
                    <th class="text-center">إجمالي المشتريات</th>
                    <th class="text-center">المدفوع</th>
                    <th class="text-center">المديونية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                <tr class="supplier-row-desk align-middle" data-search="{{ $supplier->name }} {{ $supplier->company_name ?? '' }} {{ $supplier->phone ?? '' }}">
                    <td class="text-muted small">{{ $supplier->id }}</td>
                    <td>
                        <a href="{{ route('suppliers.show', $supplier) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-truck text-orange"></i>
                            </span>
                            <div>
                                <div>{{ $supplier->name }}</div>
                                @if($supplier->company_name)
                                    <small class="text-muted fw-normal">{{ $supplier->company_name }}</small>
                                @endif
                            </div>
                        </a>
                    </td>
                    <td class="text-muted">{{ $supplier->phone ?? '—' }}</td>
                    <td class="text-center fw-bold">{{ number_format($supplier->purchases_sum_total_price ?? 0, 0) }} <small class="text-muted fw-normal">ج.م</small></td>
                    <td class="text-center fw-bold text-success">{{ number_format($supplier->purchases_sum_paid_amount ?? 0, 0) }} <small class="text-muted fw-normal">ج.م</small></td>
                    <td class="text-center">
                        @php $debt = $supplier->calculated_debt; @endphp
                        @if($debt > 0)
                            <span class="badge badge-danger rounded-pill px-3 py-1">{{ number_format($debt, 0) }} ج.م</span>
                        @elseif($debt < 0)
                            <span class="badge badge-success rounded-pill px-3 py-1">{{ number_format(abs($debt), 0) }} ج.م (لنا)</span>
                        @else
                            <span class="badge badge-secondary rounded-pill px-3 py-1">مسدد</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="bi bi-truck text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا يوجد موردين مسجلون</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(method_exists($suppliers, 'hasPages') && $suppliers->hasPages())
        <div class="mt-4">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>

{{-- Modal --}}
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 40px rgba(0,0,0,.12);">
            <div class="modal-header" style="border-bottom: 2px solid var(--primary-light); background:#fffbf8; border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="sModalTitle">
                    <i class="bi bi-truck text-orange me-2"></i>إضافة مورد جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="supplierForm" action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="sMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">اسم المورد <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="sName" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">اسم الشركة / المصنع</label>
                            <input type="text" name="company_name" id="sCompany" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">رقم الهاتف</label>
                            <input type="text" name="phone" id="sPhone" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark small">العنوان</label>
                            <input type="text" name="address" id="sAddress" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark small">ملاحظات</label>
                            <textarea name="notes" id="sNotes" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-4">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Supplier Mobile Cards */
.supplier-card-mobile {
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
.supplier-card-mobile:active {
    background: #fff8f4;
    border-color: var(--primary);
    transform: scale(0.98);
}
.scm-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 1rem;
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
document.getElementById('supplierModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('sModalTitle').innerHTML = '<i class="bi bi-truck text-orange me-2"></i>إضافة مورد جديد';
    document.getElementById('supplierForm').action = "{{ route('suppliers.store') }}";
    document.getElementById('sMethod').value = 'POST';
    document.getElementById('supplierForm').reset();
});

function filterSuppliers(q) {
    q = q.toLowerCase().trim();
    
    // Mobile cards
    document.querySelectorAll('.supplier-card-mobile').forEach(card => {
        card.style.display = card.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
    
    // Desktop table
    document.querySelectorAll('.supplier-row-desk').forEach(row => {
        row.style.display = row.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush
