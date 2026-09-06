@extends('layouts.app')
@section('title', 'العملاء | مصنع المنتجات')
@section('page_title', 'إدارة العملاء')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bolder mb-1 d-none d-md-block"><i class="bi bi-people-fill text-orange me-2"></i>العملاء</h4>
        <p class="text-muted mb-0 small d-none d-md-block">إجمالي {{ $customers->total() ?? $customers->count() }} عميل مسجل في النظام</p>
    </div>
    <button class="btn btn-orange shadow-sm rounded-pill px-4 flex-grow-1 flex-md-grow-0 text-center" data-bs-toggle="modal" data-bs-target="#customerModal">
        <i class="bi bi-plus-lg me-1"></i> إضافة عميل جديد
    </button>
</div>

{{-- Stats Row --}}
@php
    $totalWholesale = $customers->where('type','wholesale')->count();
    $totalRetail    = $customers->where('type','retail')->count();
@endphp
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-people-fill stat-icon text-muted"></i>
            <p class="text-muted small fw-bold mb-1">إجمالي العملاء</p>
            <div class="stat-amount text-dark">{{ $customers->total() ?? $customers->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-shop stat-icon text-primary opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">عملاء الجملة</p>
            <div class="stat-amount text-primary">{{ $totalWholesale }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-bag stat-icon text-success opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">عملاء التجزئة</p>
            <div class="stat-amount text-success">{{ $totalRetail }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-receipt-cutoff stat-icon text-orange opacity-50"></i>
            <p class="text-muted small fw-bold mb-1">إجمالي الفواتير</p>
            <div class="stat-amount text-orange">{{ $customers->sum('invoices_count') }}</div>
        </div>
    </div>
</div>

{{-- Search Bar --}}
<div class="search-bar-wrap mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0" style="border-radius:14px 0 0 14px; border:1.5px solid #e2e8f0; border-left:none;">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="searchInput" class="form-control border-start-0" placeholder="🔍 بحث عن عميل..." style="border:1.5px solid #e2e8f0; border-right:none; border-radius:0 14px 14px 0; font-size:.95rem;" oninput="filterCustomers(this.value)">
    </div>
</div>

{{-- ═══ MOBILE VIEW: Cards ═══ --}}
<div class="d-md-none" id="mobileCustomers">
    @forelse($customers as $c)
    <a href="{{ route('customers.show', $c) }}" class="customer-card-mobile text-decoration-none" data-search="{{ $c->name }} {{ $c->phone ?? '' }}">
        <div class="ccm-header d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="ccm-icon bg-orange-soft text-orange">
                    <i class="bi bi-person-fill"></i>
                </span>
                <span class="fw-bold text-dark">{{ $c->name }}</span>
            </div>
            @if($c->type == 'wholesale')
                <span class="badge bg-orange text-white rounded-pill px-2" style="font-size:0.7rem;">جملة</span>
            @else
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-2" style="font-size:0.7rem;">تجزئة</span>
            @endif
        </div>
        <div class="ccm-body">
            <div class="d-flex justify-content-between text-muted mb-1" style="font-size:0.8rem;">
                <span><i class="bi bi-telephone-fill me-1"></i>{{ $c->phone ?? 'لا يوجد رقم' }}</span>
                <span><i class="bi bi-receipt me-1"></i>{{ $c->invoices_count }} فاتورة</span>
            </div>
        </div>
    </a>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-people text-muted d-block mb-3 opacity-25" style="font-size:3rem;"></i>
        <h6 class="text-muted fw-bold">لا يوجد عملاء مسجلين</h6>
    </div>
    @endforelse
    @if(method_exists($customers, 'hasPages') && $customers->hasPages())
        <div class="mt-3">{{ $customers->links() }}</div>
    @endif
</div>

{{-- ═══ DESKTOP VIEW: Table ═══ --}}
<div class="content-card d-none d-md-block">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>قائمة العملاء</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0" id="customersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>النوع</th>
                    <th class="text-center">عدد الفواتير</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr class="customer-row-desk align-middle" data-search="{{ $c->name }} {{ $c->phone ?? '' }}">
                    <td class="text-muted small">{{ $c->id }}</td>
                    <td>
                        <a href="{{ route('customers.show', $c) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-person-fill text-orange"></i>
                            </span>
                            {{ $c->name }}
                        </a>
                    </td>
                    <td class="text-muted">{{ $c->phone ?? '—' }}</td>
                    <td>
                        @if($c->type == 'wholesale')
                            <span class="badge badge-orange rounded-pill px-3 py-1">جملة</span>
                        @else
                            <span class="badge badge-muted rounded-pill px-3 py-1">تجزئة</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="fw-bold">{{ $c->invoices_count }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="bi bi-people text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا يوجد عملاء مسجلون</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(method_exists($customers, 'hasPages') && $customers->hasPages())
        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    @endif
</div>

{{-- Modal Add/Edit Customer --}}
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 40px rgba(0,0,0,.12);">
            <div class="modal-header" style="border-bottom: 2px solid var(--primary-light); background:#fffbf8; border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="cModalTitle">
                    <i class="bi bi-person-plus-fill text-orange me-2"></i>إضافة عميل جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm" action="{{ route('customers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="cMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">الاسم <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="cName" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" required placeholder="اسم العميل">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark small">الهاتف</label>
                            <input type="text" name="phone" id="cPhone" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" placeholder="اختياري">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark small">النوع</label>
                            <select name="type" id="cType" class="form-control no-search" style="border-radius:10px; border: 1.5px solid #e2e8f0;">
                                <option value="retail">تجزئة</option>
                                <option value="wholesale">جملة</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">البريد الإلكتروني</label>
                            <input type="email" name="email" id="cEmail" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" placeholder="اختياري">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">العنوان</label>
                            <input type="text" name="address" id="cAddress" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" placeholder="اختياري">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark small">ملاحظات</label>
                            <textarea name="notes" id="cNotes" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" rows="2" placeholder="اختياري"></textarea>
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
/* Customer Mobile Cards */
.customer-card-mobile {
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
.customer-card-mobile:active {
    background: #fff8f4;
    border-color: var(--primary);
    transform: scale(0.98);
}
.ccm-icon {
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
document.getElementById('customerModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('cModalTitle').innerHTML = '<i class="bi bi-person-plus-fill text-orange me-2"></i>إضافة عميل جديد';
    document.getElementById('customerForm').reset();
    document.getElementById('customerForm').action = "{{ route('customers.store') }}";
    document.getElementById('cMethod').value = 'POST';
});

function filterCustomers(q) {
    q = q.toLowerCase().trim();
    
    // Mobile cards
    document.querySelectorAll('.customer-card-mobile').forEach(card => {
        card.style.display = card.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
    
    // Desktop table
    document.querySelectorAll('.customer-row-desk').forEach(row => {
        row.style.display = row.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush
