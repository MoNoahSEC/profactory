@extends('layouts.app')
@section('title', $worker->name . ' | كشف الحساب')
@section('page_title', 'ملف الموظف')

@section('content')

{{-- ═══ Worker Profile Header ═══ --}}
<div class="wsp-header content-card mb-3">
    <div class="wsp-hero">
        <div class="wsp-avatar">{{ mb_substr($worker->name, 0, 1) }}</div>
        <div class="wsp-info">
            <h5 class="wsp-name">{{ $worker->name }}</h5>
            <div class="wsp-sub">{{ $worker->job_title }}</div>
            <div class="wsp-tags mt-1">
                <span class="wsp-tag {{ $worker->is_active ? 'tag-active' : 'tag-inactive' }}">
                    {{ $worker->is_active ? 'نشط' : 'موقوف' }}
                </span>
                <span class="wsp-tag tag-neutral">
                    <i class="bi bi-geo-alt-fill me-1"></i>{{ $worker->factory_location }}
                </span>
                @if($worker->worker_type === 'production')
                <span class="wsp-tag tag-prod">
                    <i class="bi bi-hammer me-1"></i>{{ $worker->role_label }}
                </span>
                @else
                <span class="wsp-tag tag-daily">يومية</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="wsp-actions">
        @hasrole('Admin')
        <button class="btn btn-orange btn-sm flex-fill"
                onclick="editWorker({{ json_encode($worker) }}, '{{ url()->current() }}')">
            <i class="bi bi-pencil-fill me-1"></i>تعديل
        </button>
        <button class="btn btn-outline-warning btn-sm flex-fill"
                data-bs-toggle="modal" data-bs-target="#adjustmentModal">
            <i class="bi bi-sliders me-1"></i>تسوية
        </button>
        <form action="{{ route('workers.destroy', $worker) }}" method="POST" class="flex-fill"
              onsubmit="return confirm('حذف الموظف نهائياً؟ لا يمكن التراجع!');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-trash me-1"></i>حذف
            </button>
        </form>
        @endhasrole
        <a href="{{ route('workers.index') }}" class="btn btn-light btn-sm flex-fill">
            <i class="bi bi-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

{{-- ═══ Financial Summary ═══ --}}
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="fin-card fin-warning">
            <div class="fin-label">السلف الحالية</div>
            <div class="fin-value">{{ number_format($currentDebt, 0) }}<small>ج.م</small></div>
        </div>
    </div>
    <div class="col-6">
        <div class="fin-card fin-danger">
            <div class="fin-label">الخصومات</div>
            <div class="fin-value">{{ number_format($currentPenalties, 0) }}<small>ج.م</small></div>
        </div>
    </div>
</div>

{{-- ═══ Worker Details ═══ --}}
<div class="content-card mb-3">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="bi bi-info-circle-fill text-orange me-2"></i>البيانات الأساسية</h6>
    </div>
    <div class="p-3">
        <div class="wsp-details-grid">
            <div class="wsp-detail-item">
                <span class="wdl">الكود</span>
                <span class="wdv fw-bold text-orange">{{ $worker->code }}</span>
            </div>
            <div class="wsp-detail-item">
                <span class="wdl">الوردية</span>
                <span class="wdv">{{ ['morning'=>'صباحي','evening'=>'مسائي','night'=>'ليلي'][$worker->shift_type] ?? $worker->shift_type }}</span>
            </div>
            <div class="wsp-detail-item">
                <span class="wdl">تاريخ التعيين</span>
                <span class="wdv">{{ $worker->hire_date ? $worker->hire_date->format('Y-m-d') : '—' }}</span>
            </div>
            @if($worker->national_id)
            <div class="wsp-detail-item">
                <span class="wdl">الرقم القومي</span>
                <span class="wdv">{{ $worker->national_id }}</span>
            </div>
            @endif
            @if($worker->phone)
            <div class="wsp-detail-item">
                <span class="wdl">الهاتف</span>
                <span class="wdv"><a href="tel:{{ $worker->phone }}" class="text-orange fw-bold">{{ $worker->phone }}</a></span>
            </div>
            @endif
            @hasrole('Admin')
            <div class="wsp-detail-item">
                <span class="wdl">نظام الأجر</span>
                <span class="wdv fw-bold text-success">
                    @if($worker->worker_type === 'production')
                        @if(($worker->wage_system ?? 'shift') === 'piece') قطعة
                        @else ورديات — {{ number_format($worker->shift_wage, 0) }} ج/وردية @endif
                    @else
                        @if($worker->daily_wage_type === 'hourly') {{ number_format($worker->hourly_wage, 0) }} ج/ساعة
                        @else {{ number_format($worker->daily_wage, 0) }} ج/يوم @endif
                    @endif
                </span>
            </div>
            @endhasrole
        </div>
    </div>
</div>

{{-- ═══ Financial Timeline ═══ --}}
<div class="content-card">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-orange me-2"></i>السجل المالي</h6>
    </div>
    <div class="p-3">
        @forelse($timeline as $item)
        <div class="fin-timeline-item {{ $item['color'] }}">
            <div class="fti-icon">
                <i class="bi {{ explode(' ', $item['icon'])[0] }}"></i>
            </div>
            <div class="fti-body">
                <div class="fti-notes">{{ $item['notes'] }}</div>
                <div class="fti-date">{{ \Carbon\Carbon::parse($item['date'])->format('Y-m-d') }}</div>
            </div>
            <div class="fti-amount {{ $item['type'] == 'salary_paid' ? 'text-success' : 'text-danger' }}">
                {{ $item['type'] == 'salary_paid' ? '+' : '-' }}{{ number_format($item['amount'], 0) }}
                <small>ج</small>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-30"></i>
            لا يوجد سجل مالي لهذا الموظف حتى الآن.
        </div>
        @endforelse
    </div>
</div>

{{-- Reset Balance --}}
@hasrole('Admin')
<div class="mt-3 text-center">
    <form action="{{ route('workers.reset_balance', $worker->id) }}" method="POST"
          onsubmit="return confirm('تأكيد تصفير حساب الموظف بالكامل؟');">
        @csrf
        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4">
            <i class="bi bi-eraser me-1"></i>تصفير الحساب الكامل
        </button>
    </form>
</div>
@endhasrole

{{-- Adjustment Modal --}}
<div class="modal fade" id="adjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-dark">تسوية رصيد يدوية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('workers.adjust_balance', $worker->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted">نوع التسوية <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-control-glass" required>
                            <option value="advance">إضافة سلفة / مديونية</option>
                            <option value="penalty">إضافة خصم / جزاء</option>
                            <option value="credit">إسقاط جزء من السلف</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">المبلغ (ج.م) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-glass fw-bold" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control form-control-glass" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">ملاحظات <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control form-control-glass" rows="2" required placeholder="بيان التسوية..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check2-circle me-1"></i>تنفيذ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('workers._modal')

@push('styles')
<style>
/* ═══ WORKER STATEMENT — MOBILE STYLES ═══ */
.wsp-header { overflow: visible; }
.wsp-hero {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem 1.2rem;
    border-bottom: 1px solid #f1f5f9;
}
.wsp-avatar {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 900;
    flex-shrink: 0;
}
.wsp-info { flex: 1; min-width: 0; }
.wsp-name { font-size: 1.05rem; font-weight: 900; color: #1e293b; margin: 0; }
.wsp-sub  { font-size: .8rem; color: #64748b; margin-top: .1rem; }
.wsp-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
.wsp-tag {
    font-size: .68rem; font-weight: 700; border-radius: 20px;
    padding: .2rem .55rem; border: 1px solid transparent;
}
.tag-active   { background:#dcfce7; color:#16a34a; border-color:#86efac; }
.tag-inactive { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }
.tag-neutral  { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
.tag-prod     { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
.tag-daily    { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }

.wsp-actions {
    display: flex;
    gap: .5rem;
    padding: .8rem 1rem;
    flex-wrap: wrap;
}
.wsp-actions .btn { font-size: .82rem; border-radius: 10px; padding: .5rem .6rem; }
.wsp-actions form { flex: 1; min-width: 0; display: flex; }

/* Financial Cards */
.fin-card {
    border-radius: 14px;
    padding: .9rem .8rem;
    text-align: center;
    border: 1.5px solid;
}
.fin-warning { background: #fffbeb; border-color: #fcd34d; }
.fin-danger  { background: #fff1f2; border-color: #fca5a5; }
.fin-label { font-size: .72rem; font-weight: 700; color: #64748b; margin-bottom: .2rem; }
.fin-value {
    font-size: 1.35rem;
    font-weight: 900;
    color: #92400e;
    line-height: 1;
}
.fin-danger .fin-value { color: #dc2626; }
.fin-value small { font-size: .7rem; font-weight: 600; margin-right: .15rem; }

/* Details Grid */
.wsp-details-grid { display: flex; flex-direction: column; gap: .6rem; }
.wsp-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .5rem 0;
    border-bottom: 1px solid #f8fafc;
    font-size: .88rem;
}
.wsp-detail-item:last-child { border-bottom: none; }
.wdl { color: #64748b; font-weight: 600; }
.wdv { color: #1e293b; font-weight: 700; text-align: left; }

/* Financial Timeline */
.fin-timeline-item {
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: .7rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.fin-timeline-item:last-child { border-bottom: none; }
.fti-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}
.fin-timeline-item.warning .fti-icon { background: #fef3c7; color: #d97706; }
.fin-timeline-item.danger  .fti-icon { background: #fee2e2; color: #dc2626; }
.fin-timeline-item.success .fti-icon { background: #dcfce7; color: #16a34a; }
.fti-body { flex: 1; min-width: 0; }
.fti-notes {
    font-size: .83rem; font-weight: 600; color: #1e293b;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.fti-date { font-size: .72rem; color: #94a3b8; margin-top: .1rem; }
.fti-amount {
    font-size: .95rem; font-weight: 900; flex-shrink: 0;
    text-align: left; direction: ltr;
}
.fti-amount small { font-size: .65rem; }
</style>
@endpush

@endsection
