@extends('layouts.app')
@section('title', 'الورش الخارجية | مصنع المنتجات')
@section('page_title', 'الورش الخارجية')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-tools text-orange me-2"></i>الورش الخارجية</h4>
        <p class="text-muted mb-0 small">إجمالي {{ $workshops->count() }} ورشة مسجلة في النظام</p>
    </div>
    <button class="btn btn-orange shadow-sm" data-bs-toggle="modal" data-bs-target="#workshopModal">
        <i class="bi bi-plus-lg me-1"></i> إضافة ورشة جديدة
    </button>
</div>

@if(session('success'))
    <div class="alert border-0 rounded-3 shadow-sm mb-4 fw-bold" style="background:var(--primary-light);color:var(--primary-dark);">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close float-start" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4 fw-bold">
        <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close float-start" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Stats --}}
@php
    $totalDebt = $workshops->sum(fn($w) => max(0, $w->precalculated_balance));
    $totalCredit = $workshops->sum(fn($w) => max(0, -$w->precalculated_balance));
    $settled = $workshops->filter(fn($w) => $w->precalculated_balance == 0)->count();
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-tools stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">إجمالي الورش</p>
            <div class="stat-amount">{{ $workshops->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-danger">
            <i class="bi bi-exclamation-triangle stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">مديونية علينا (ج.م)</p>
            <div class="stat-amount" style="font-size:1.3rem; color:#dc2626 !important;">{{ number_format($totalDebt, 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <i class="bi bi-wallet stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">رصيد لصالحنا (ج.م)</p>
            <div class="stat-amount" style="font-size:1.3rem; color:#16a34a !important;">{{ number_format($totalCredit, 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-check-circle stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">مسددة بالكامل</p>
            <div class="stat-amount">{{ $settled }}</div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>قائمة الورش</h6>
        <input type="text" id="searchInput" class="form-control form-control-sm w-auto" placeholder="🔍 بحث..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:200px;">
    </div>
    {{-- MOBILE VIEW --}}
    <div class="d-md-none p-2" id="workshopsMobileList">
        @forelse($workshops as $workshop)
            <div class="glass-card mb-3 p-3 bg-white border rounded-3 shadow-sm workshop-item">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <a href="{{ route('workshops.show', $workshop) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                            <i class="bi bi-tools text-orange"></i>
                        </span>
                        <span class="workshop-name">{{ $workshop->name }}</span>
                    </a>
                    <span class="text-muted small">#{{ $workshop->id }}</span>
                </div>
                
                @if($workshop->phone || $workshop->address)
                <div class="mb-2 text-muted small">
                    @if($workshop->phone)
                        <div class="mb-1"><i class="bi bi-telephone text-orange me-1"></i> {{ $workshop->phone }}</div>
                    @endif
                    @if($workshop->address)
                        <div><i class="bi bi-geo-alt text-orange me-1"></i> {{ $workshop->address }}</div>
                    @endif
                </div>
                @endif
                
                <div class="text-center mt-3 pt-2 border-top">
                    @php $bal = $workshop->precalculated_balance; @endphp
                    @if($bal > 0)
                        <span class="badge rounded-pill px-3 py-2 fw-bold w-100 fs-6" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;">
                            {{ number_format($bal, 2) }} على الورشة
                        </span>
                    @elseif($bal < 0)
                        <span class="badge rounded-pill px-3 py-2 fw-bold w-100 fs-6" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;">
                            {{ number_format(abs($bal), 2) }} لصالحهم
                        </span>
                    @else
                        <span class="badge badge-muted rounded-pill px-3 py-2 w-100 fs-6">مسدد</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted fw-bold border rounded-3 bg-light">لا توجد ورش مسجلة بعد</div>
        @endforelse
    </div>

    {{-- DESKTOP VIEW --}}
    <div class="table-responsive d-none d-md-block">
        <table class="table table-clean mb-0" id="workshopsTable">
            <thead>
                <tr>
                    <th class="d-none d-md-table-cell">#</th>
                    <th>اسم الورشة</th>
                    <th class="d-none d-md-table-cell">الهاتف</th>
                    <th class="d-none d-lg-table-cell">العنوان</th>
                    <th class="text-center">المديونية الحالية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workshops as $workshop)
                <tr>
                    <td class="text-muted small d-none d-md-table-cell">{{ $workshop->id }}</td>
                    <td>
                        <a href="{{ route('workshops.show', $workshop) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-tools text-orange"></i>
                            </span>
                            {{ $workshop->name }}
                        </a>
                    </td>
                    <td class="text-muted d-none d-md-table-cell" dir="ltr">{{ $workshop->phone ?? '—' }}</td>
                    <td class="text-muted d-none d-lg-table-cell">{{ $workshop->address ?? '—' }}</td>
                    <td class="text-center">
                        @php $bal = $workshop->precalculated_balance; @endphp
                        @if($bal > 0)
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;">
                                {{ number_format($bal, 2) }} على الورشة
                            </span>
                        @elseif($bal < 0)
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;">
                                {{ number_format(abs($bal), 2) }} لصالحهم
                            </span>
                        @else
                            <span class="badge badge-muted rounded-pill px-3 py-1">مسدد</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="bi bi-tools text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا توجد ورش مسجلة بعد</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $workshops->links() }}
    </div>
</div>

@include('workshops._modal')

@push('scripts')
<script>
document.getElementById('searchInput').addEventListener('input', function() {
    let term = this.value.toLowerCase();
    
    // Desktop Search
    document.querySelectorAll('#workshopsTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
    
    // Mobile Search
    document.querySelectorAll('#workshopsMobileList .workshop-item').forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
@endpush
@endsection
