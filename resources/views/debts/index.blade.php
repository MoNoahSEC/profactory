@extends('layouts.app')
@section('title', 'الديون والأقساط | مصنع المنتجات')
@section('page_title', 'الديون والأقساط')

@section('content')

{{-- ── Header ── --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-journal-bookmark text-orange me-2"></i>الديون والأقساط</h4>
        <p class="text-muted mb-0 small">متابعة الديون المستحقة لك وعليك، ومواعيد الأقساط</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-orange rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDebtModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة دين جديد
        </button>
    </div>
</div>

{{-- ── Stats Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-danger h-100">
            <i class="bi bi-arrow-up-circle-fill stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">ديون علينا (متبقي)</p>
            <div class="stat-amount" style="color:#dc2626 !important;">{{ number_format($owedByUsTotal, 0) }}</div>
            <small class="text-muted">ج.م</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-success h-100">
            <i class="bi bi-arrow-down-circle-fill stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">ديون لنا (متبقي)</p>
            <div class="stat-amount" style="color:#16a34a !important;">{{ number_format($owedToUsTotal, 0) }}</div>
            <small class="text-muted">ج.م</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card {{ $overdueInstallments > 0 ? 'stat-danger' : 'stat-warning' }} h-100">
            <i class="bi bi-exclamation-triangle-fill stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">أقساط متأخرة</p>
            <div class="stat-amount" style="{{ $overdueInstallments > 0 ? 'color:#dc2626 !important;' : '' }}">{{ $overdueInstallments }}</div>
            <small class="text-muted">قسط</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-info h-100">
            <i class="bi bi-calendar-event-fill stat-icon text-info"></i>
            <p class="text-muted small fw-bold mb-1">أقساط قادمة (30 يوم)</p>
            <div class="stat-amount text-info">{{ $upcomingInstallments }}</div>
            <small class="text-muted">قسط</small>
        </div>
    </div>
</div>

{{-- ── Upcoming Installments This Month ── --}}
@if($upcomingThisMonth->count() > 0)
<div class="content-card mb-4" style="border-left: 4px solid #f59e0b;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-warning"><i class="bi bi-alarm me-2"></i>أقساط مستحقة هذا الشهر</h6>
        <span class="badge bg-warning text-dark rounded-pill">{{ $upcomingThisMonth->count() }} قسط</span>
    </div>
    <div class="row g-2">
        @foreach($upcomingThisMonth as $inst)
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('debts.show', $inst->debt_id) }}" class="text-decoration-none">
                <div class="p-3 rounded-3" style="background: {{ $inst->is_overdue ? '#fee2e2' : '#fef3c7' }}; border: 1px solid {{ $inst->is_overdue ? '#fca5a5' : '#fde68a' }}; transition: transform 0.2s;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi {{ $inst->is_overdue ? 'bi-exclamation-circle-fill text-danger' : 'bi-clock-fill text-warning' }} fs-5"></i>
                        <strong class="text-dark text-truncate" title="{{ $inst->debt->party_name }}">{{ $inst->debt->party_name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark fs-6">{{ number_format($inst->amount, 0) }} ج.م</span>
                        <small class="{{ $inst->is_overdue ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $inst->due_date->format('d/m') }}
                            @if($inst->is_overdue)
                                (متأخر)
                            @endif
                        </small>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Debts Table ── --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>سجل الديون</h6>
        <input type="text" id="searchInput" class="form-control form-control-sm w-auto" placeholder="🔍 بحث عن جهة أو دين..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:200px;">
    </div>

    <div class="table-responsive">
        <table class="table table-clean mb-0" id="debtsTable">
            <thead>
                <tr>
                    <th>الجهة / الشخص</th>
                    <th>النوع</th>
                    <th class="text-center">المبلغ الإجمالي</th>
                    <th class="text-center">تقدم السداد</th>
                    <th class="text-center">المتبقي</th>
                    <th class="text-center">الأقساط</th>
                    <th class="text-center">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($debts as $debt)
                <tr>
                    <td>
                        <a href="{{ route('debts.show', $debt) }}" class="fw-bold text-primary text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="background:#eff6ff; color:#3b82f6; width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-journal-text"></i>
                            </span>
                            <div>
                                {{ $debt->party_name }}
                                @if($debt->debt_date)
                                <small class="text-muted d-block fw-normal">{{ $debt->debt_date->format('Y-m-d') }}</small>
                                @endif
                            </div>
                        </a>
                    </td>
                    <td>
                        @if($debt->type == 'owed_to_us')
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;"><i class="bi bi-arrow-down me-1"></i>لنا</span>
                        @else
                            <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;"><i class="bi bi-arrow-up me-1"></i>علينا</span>
                        @endif
                    </td>
                    <td class="text-center fw-bold text-dark">{{ number_format($debt->total_amount, 2) }} <small class="text-muted fw-normal">ج.م</small></td>
                    <td style="min-width: 150px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px; background-color:#e2e8f0;">
                                <div class="progress-bar {{ $debt->progress_percentage >= 100 ? 'bg-success' : ($debt->progress_percentage > 0 ? 'bg-warning' : 'bg-secondary') }}"
                                     style="width: {{ $debt->progress_percentage }}%"></div>
                            </div>
                            <small class="fw-bold text-nowrap">{{ $debt->progress_percentage }}%</small>
                        </div>
                        <small class="text-muted d-block text-center mt-1">{{ number_format($debt->paid_amount, 0) }} مسدد</small>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold {{ $debt->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($debt->remaining_amount, 2) }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($debt->installments_count > 0)
                            <span class="badge rounded-pill px-3 py-1 fw-bold bg-info bg-opacity-10 text-info border border-info">
                                {{ $debt->paid_installments_count }}/{{ $debt->installments_count }}
                            </span>
                            @if($debt->overdue_installments_count > 0)
                                <span class="badge bg-danger rounded-pill mt-1 d-block mx-auto" style="width:fit-content">{{ $debt->overdue_installments_count }} متأخر</span>
                            @endif
                        @else
                            <span class="text-muted fw-bold">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($debt->status == 'paid')
                            <span class="badge rounded-pill px-3 py-1 fw-bold bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle me-1"></i>مسدد</span>
                        @elseif($debt->status == 'overdue')
                            <span class="badge rounded-pill px-3 py-1 fw-bold bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-clock me-1"></i>متأخر</span>
                        @else
                            <span class="badge rounded-pill px-3 py-1 fw-bold bg-warning bg-opacity-10 text-warning border border-warning"><i class="bi bi-hourglass-split me-1"></i>جاري</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">لا توجد ديون مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Supplier Debts Table ── --}}
@if($suppliersWithDebts->count() > 0)
<div class="content-card mt-4" style="border-left: 4px solid #ef4444;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-truck me-2"></i>مديونيات الموردين (تلقائية من فواتير الشراء)</h6>
        <span class="badge bg-danger text-white rounded-pill px-3 py-1 fs-6">{{ number_format($supplierDebtsTotal, 2) }} ج.م إجمالي ديوننا</span>
    </div>

    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead>
                <tr>
                    <th>اسم المورد</th>
                    <th class="text-center">إجمالي المشتريات منه</th>
                    <th class="text-center">المدفوع له</th>
                    <th class="text-center">الديون المتبقية علينا</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suppliersWithDebts as $supplier)
                <tr>
                    <td>
                        <a href="{{ route('suppliers.show', $supplier) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-person-lines-fill text-orange"></i>
                            </span>
                            {{ $supplier->name }}
                        </a>
                    </td>
                    <td class="text-center text-muted fw-bold">{{ number_format($supplier->total_purchases, 2) }}</td>
                    <td class="text-center text-success fw-bold">{{ number_format($supplier->total_paid, 2) }}</td>
                    <td class="text-center text-danger fw-bold fs-5">{{ number_format($supplier->calculated_debt, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

{{-- ── Add Debt Modal ── --}}
<div class="modal fade" id="addDebtModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="fw-bold mb-0"><i class="bi bi-plus-circle-fill text-orange me-2"></i>تسجيل دين جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('debts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">نوع الدين <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required style="border-radius:10px;">
                                <option value="owed_to_us">دين لنا (مستحقات خارجية)</option>
                                <option value="owed_by_us">دين علينا (التزام خارجي)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">تاريخ نشأة الدين <span class="text-danger">*</span></label>
                            <input type="date" name="debt_date" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted fw-bold">اسم الجهة / الشخص المستدين <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" class="form-control" required placeholder="مثال: مقاول س، عميل ص، ..." style="border-radius:10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">إجمالي المبلغ <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="total_amount" class="form-control fw-bold fs-5 text-primary" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">ملاحظات والتفاصيل</label>
                            <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-5">حفظ الدين</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Search
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('#debtsTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
        });
    });
</script>
@endpush
