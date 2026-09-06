@extends('layouts.app')
@section('title', 'الموظفين | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'إدارة الموظفين')

@section('content')

@php
    $activeCount     = $workers->where('is_active', true)->count();
    $productionCount = $workers->where('worker_type', 'production')->count();
    $dailyCount      = $workers->where('worker_type', 'daily')->count();
@endphp

{{-- Page Header --}}
<div class="workers-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bolder mb-0 d-none d-md-block"><i class="bi bi-person-badge-fill text-orange me-2"></i>الموظفين</h4>
        <p class="text-muted mb-0 small d-none d-md-block">إجمالي {{ $workers->count() }} موظف</p>
    </div>
    @hasrole('Admin')
    <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#workerModal">
        <i class="bi bi-plus-lg me-1"></i> إضافة موظف
    </button>
    @endhasrole
</div>

{{-- Stats Row --}}
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="stat-card-mobile">
            <div class="scm-icon bg-orange-soft"><i class="bi bi-people-fill text-orange"></i></div>
            <div class="scm-value">{{ $workers->count() }}</div>
            <div class="scm-label">إجمالي</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card-mobile">
            <div class="scm-icon" style="background:#dcfce7;"><i class="bi bi-check-circle-fill" style="color:#16a34a;"></i></div>
            <div class="scm-value" style="color:#16a34a;">{{ $activeCount }}</div>
            <div class="scm-label">نشطون</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card-mobile">
            <div class="scm-icon" style="background:#fef3c7;"><i class="bi bi-hammer" style="color:#d97706;"></i></div>
            <div class="scm-value" style="color:#d97706;">{{ $productionCount }}</div>
            <div class="scm-label">إنتاج</div>
        </div>
    </div>
</div>

{{-- Search Bar --}}
<div class="search-bar-wrap mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0" style="border-radius:14px 0 0 14px; border:1.5px solid #e2e8f0; border-left:none;">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="searchInput" class="form-control border-start-0"
               placeholder="ابحث باسم الموظف..."
               style="border:1.5px solid #e2e8f0; border-right:none; border-radius:0 14px 14px 0; font-size:.95rem;">
    </div>
</div>

{{-- ═══ MOBILE VIEW: Cards ═══ --}}
<div class="d-md-none" id="mobileWorkers">
    @forelse($workers as $w)
    <a href="{{ route('workers.statement', $w) }}" class="worker-card-mobile text-decoration-none" id="wc-{{ $w->id }}">
        <div class="wcm-left">
            <div class="wcm-avatar {{ $w->is_active ? 'active' : 'inactive' }}">
                {{ mb_substr($w->name, 0, 1) }}
            </div>
        </div>
        <div class="wcm-body">
            <div class="wcm-name">{{ $w->name }}</div>
            <div class="wcm-meta">
                <span><i class="bi bi-briefcase-fill me-1"></i>{{ $w->job_title }}</span>
                <span class="mx-1">·</span>
                <span><i class="bi bi-geo-alt-fill me-1"></i>{{ $w->factory_location }}</span>
            </div>
            <div class="wcm-badges">
                @if($w->worker_type === 'production')
                    <span class="wcm-badge badge-prod"><i class="bi bi-hammer me-1"></i>{{ $w->role_label }}</span>
                @else
                    <span class="wcm-badge badge-daily"><i class="bi bi-calendar-week me-1"></i>يومية</span>
                @endif
                @if($w->is_active)
                    <span class="wcm-badge badge-active">نشط</span>
                @else
                    <span class="wcm-badge badge-inactive">موقوف</span>
                @endif
            </div>
        </div>
        <div class="wcm-right">
            <i class="bi bi-chevron-left text-muted"></i>
        </div>
    </a>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-people text-muted d-block mb-2" style="font-size:3rem;opacity:.3;"></i>
        <span class="text-muted fw-bold">لا يوجد موظفون</span>
    </div>
    @endforelse
</div>

{{-- ═══ DESKTOP VIEW: Table ═══ --}}
<div class="content-card d-none d-md-block">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>قائمة الموظفين</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0" id="workersTableDesktop">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>الاسم والمصنع</th>
                    <th>الوظيفة</th>
                    <th>التصنيف</th>
                    @hasrole('Admin')<th class="text-center">الأجر</th>@endhasrole
                    <th class="text-center">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $w)
                <tr class="worker-row" data-search="{{ strtolower($w->name . ' ' . $w->job_title . ' ' . $w->factory_location . ' ' . $w->code) }}">
                    <td>
                        <span class="badge badge-muted rounded-pill px-2 py-1 fw-bold" style="font-size:.78rem;">{{ $w->code }}</span>
                    </td>
                    <td>
                        <a href="{{ route('workers.statement', $w) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:34px;height:34px;font-size:.95rem;flex-shrink:0;">
                                <i class="bi bi-person-fill text-orange"></i>
                            </span>
                            <div>
                                <div>{{ $w->name }}</div>
                                <small class="text-muted fw-normal"><i class="bi bi-geo-alt-fill"></i> {{ $w->factory_location }}</small>
                            </div>
                        </a>
                    </td>
                    <td>
                        <div class="fw-bold text-dark small">{{ $w->job_title }}</div>
                        <small class="text-muted"><i class="bi bi-calendar me-1"></i>{{ $w->hire_date ? $w->hire_date->format('Y-m-d') : '—' }}</small>
                    </td>
                    <td>
                        @if($w->worker_type === 'production')
                            <span class="badge badge-orange rounded-pill px-2 py-1"><i class="bi bi-hammer me-1"></i>{{ $w->role_label }}</span>
                        @else
                            <span class="badge badge-muted rounded-pill px-2 py-1"><i class="bi bi-person-badge me-1"></i>راتب ثابت</span>
                        @endif
                    </td>
                    @hasrole('Admin')
                    <td class="text-center">
                        @if($w->worker_type === 'production')
                            @if(($w->wage_system ?? 'shift') === 'piece')
                                <span class="badge badge-orange rounded-pill">قطعة</span>
                            @else
                                <span class="badge badge-muted rounded-pill">ورديات</span>
                                @if($w->shift_wage > 0)
                                    <div class="text-orange fw-bold small mt-1">{{ number_format($w->shift_wage, 0) }} ج/وردية</div>
                                @endif
                            @endif
                        @else
                            @if($w->daily_wage_type === 'hourly')
                                <div class="text-success fw-bold small">{{ number_format($w->hourly_wage, 0) }} ج/ساعة</div>
                            @else
                                <div class="text-success fw-bold small">{{ number_format($w->daily_wage, 0) }} ج/يوم</div>
                            @endif
                        @endif
                    </td>
                    @endhasrole
                    <td class="text-center">
                        @if($w->is_active)
                            <span class="badge rounded-pill px-2 py-1" style="background:#dcfce7;color:#16a34a;border:1px solid #86efac;font-size:.78rem;">نشط</span>
                        @else
                            <span class="badge rounded-pill px-2 py-1" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;font-size:.78rem;">موقوف</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="bi bi-people text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا يوجد موظفون مسجلون</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $workers->links() }}</div>
</div>

{{-- Mobile pagination --}}
<div class="d-md-none mt-3">{{ $workers->links() }}</div>

@include('workers._modal')

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('workerModal'));
        modal.show();
    });
</script>
@endif

@push('styles')
<style>
/* ═══ WORKERS INDEX — MOBILE CARD STYLES ═══ */
.workers-header { padding: 0 0 .5rem; }

/* Compact stat cards for mobile */
.stat-card-mobile {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: .7rem .5rem;
    text-align: center;
    transition: all .2s;
}
.scm-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: .3rem;
}
.scm-value {
    font-size: 1.35rem;
    font-weight: 900;
    color: var(--primary);
    line-height: 1;
}
.scm-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 700;
    margin-top: .15rem;
}

/* Search bar */
.search-bar-wrap .form-control:focus {
    box-shadow: 0 0 0 3px rgba(234,88,12,.1);
    border-color: var(--primary) !important;
    outline: none;
}
.search-bar-wrap .form-control:focus + .input-group-text,
.search-bar-wrap .input-group:focus-within .input-group-text {
    border-color: var(--primary) !important;
}

/* Worker Mobile Card */
.worker-card-mobile {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid #f1f5f9;
    border-radius: 16px;
    padding: .9rem 1rem;
    margin-bottom: .6rem;
    text-decoration: none;
    transition: all .2s;
    color: inherit;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    gap: .85rem;
}
.worker-card-mobile:active {
    background: #fff8f4;
    border-color: var(--primary);
    transform: scale(.98);
}
.wcm-left { flex-shrink: 0; }
.wcm-avatar {
    width: 46px; height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 900;
    color: #fff;
    background: var(--primary);
    flex-shrink: 0;
}
.wcm-avatar.inactive { background: #94a3b8; }
.wcm-body { flex: 1; min-width: 0; }
.wcm-name {
    font-weight: 800;
    color: #1e293b;
    font-size: .95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}
.wcm-meta {
    font-size: .75rem;
    color: #64748b;
    margin-top: .1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wcm-badges {
    display: flex;
    gap: .3rem;
    flex-wrap: wrap;
    margin-top: .3rem;
}
.wcm-badge {
    font-size: .68rem;
    font-weight: 700;
    border-radius: 20px;
    padding: .18rem .55rem;
    border: 1px solid transparent;
}
.badge-prod  { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
.badge-daily { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-active   { background:#dcfce7; color:#16a34a; border-color:#86efac; }
.badge-inactive { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }
.wcm-right { flex-shrink: 0; color: #cbd5e1; }

/* Desktop search via JS */
.worker-row.hidden { display: none; }
</style>
@endpush

@push('scripts')
<script>
const searchInput = document.getElementById('searchInput');

// Mobile search
searchInput.addEventListener('input', function() {
    const term = this.value.toLowerCase().trim();

    // Mobile cards
    document.querySelectorAll('#mobileWorkers .worker-card-mobile').forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(term) ? '' : 'none';
    });

    // Desktop table
    document.querySelectorAll('#workersTableDesktop .worker-row').forEach(row => {
        const data = (row.dataset.search || '') + row.innerText.toLowerCase();
        row.classList.toggle('hidden', !data.includes(term));
    });
});
</script>
@endpush

@endsection
