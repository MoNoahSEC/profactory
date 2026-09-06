@extends('layouts.app')
@section('title', 'تفاصيل الدين | مصنع المنتجات')
@section('page_title', 'تفاصيل الدين والأقساط')

@section('content')

{{-- ── Header ── --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bolder mb-0"><i class="bi bi-journal-text text-orange me-2"></i>{{ $debt->party_name }}</h4>
            @if($debt->type == 'owed_to_us')
                <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;"><i class="bi bi-arrow-down me-1"></i>دين لنا</span>
            @else
                <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;"><i class="bi bi-arrow-up me-1"></i>دين علينا</span>
            @endif
            @if($debt->status == 'paid')
                <span class="badge rounded-pill px-3 py-1 fw-bold bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle me-1"></i>مسدد بالكامل</span>
            @elseif($debt->status == 'overdue')
                <span class="badge rounded-pill px-3 py-1 fw-bold bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-clock me-1"></i>متأخر</span>
            @else
                <span class="badge rounded-pill px-3 py-1 fw-bold bg-warning bg-opacity-10 text-warning border border-warning"><i class="bi bi-hourglass-split me-1"></i>جاري</span>
            @endif
        </div>
        <p class="text-muted mb-0 small">{{ $debt->notes ?: 'لا توجد ملاحظات إضافية' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('debts.index') }}" class="btn btn-light rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-arrow-right me-1"></i> عودة للديون
        </a>
        <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#editDebtModal">
            <i class="bi bi-pencil me-1"></i> تعديل
        </button>
        <form action="{{ route('debts.destroy', $debt) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدين نهائياً؟ سيتم عكس قيود الدفع المرتبطة به في الخزينة.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-trash me-1"></i> حذف
            </button>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- ── Debt Info Card ── --}}
    <div class="col-lg-4">
        <div class="content-card h-100">
            <h6 class="fw-bold mb-4"><i class="bi bi-info-circle text-primary me-2"></i>معلومات وموقف الدين</h6>
            
            <div class="d-flex justify-content-between align-items-center border-bottom border-light pb-3 mb-3">
                <span class="text-muted fw-bold">المبلغ الإجمالي</span>
                <strong class="fs-5 text-dark">{{ number_format($debt->total_amount, 2) }} <small class="fs-6 text-muted">ج.م</small></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center border-bottom border-light pb-3 mb-3">
                <span class="text-muted fw-bold">المسدد حتى الآن</span>
                <strong class="text-success fs-5">{{ number_format($debt->paid_amount, 2) }} <small class="fs-6 text-muted">ج.م</small></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center border-bottom border-light pb-3 mb-3">
                <span class="text-muted fw-bold">المتبقي</span>
                <strong class="text-danger fs-5">{{ number_format($debt->remaining_amount, 2) }} <small class="fs-6 text-muted">ج.م</small></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center pb-2 mb-2">
                <span class="text-muted fw-bold">تاريخ نشأة الدين</span>
                <strong class="text-dark">{{ $debt->debt_date ? $debt->debt_date->format('Y-m-d') : '-' }}</strong>
            </div>

            <div class="mt-4 pt-4 border-top border-light">
                <div class="d-flex justify-content-between mb-2">
                    <small class="fw-bold text-muted">نسبة السداد</small>
                    <small class="fw-bolder">{{ $debt->progress_percentage }}%</small>
                </div>
                <div class="progress" style="height: 10px; background-color:#e2e8f0;">
                    <div class="progress-bar {{ $debt->progress_percentage >= 100 ? 'bg-success' : 'bg-primary' }} progress-bar-striped progress-bar-animated"
                         style="width: {{ $debt->progress_percentage }}%"></div>
                </div>
            </div>

            <div class="mt-4 d-grid gap-2">
                @if($debt->status != 'paid')
                    <button class="btn btn-success fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#payPartialModal">
                        <i class="bi bi-cash-stack me-2"></i>سداد دفعة حرة (تُخصم من الإجمالي)
                    </button>
                    <form action="{{ route('debts.payAll', $debt) }}" method="POST" onsubmit="return confirm('هل تريد سداد كامل المتبقي ({{ number_format($debt->remaining_amount, 2) }} ج.م) دفعة واحدة؟')">
                        @csrf
                        <button type="submit" class="btn btn-outline-success fw-bold rounded-pill w-100 shadow-sm mt-2">
                            <i class="bi bi-check2-all me-2"></i>سداد كامل المبلغ المتبقي
                        </button>
                    </form>
                @endif
                <a href="{{ route('debts.print', $debt) }}" target="_blank" class="btn btn-dark fw-bold rounded-pill shadow-sm mt-2">
                    <i class="bi bi-printer me-2"></i>طباعة كشف الحساب
                </a>
            </div>
        </div>
    </div>

    {{-- ── Installments Card ── --}}
    <div class="col-lg-8">
        <div class="content-card h-100">
            <h6 class="fw-bold mb-4"><i class="bi bi-calendar2-week text-orange me-2"></i>جدول الأقساط المجدولة</h6>
            
            @if($debt->installments_count > 0)
                <div class="table-responsive">
                    <table class="table table-clean align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>قيمة القسط</th>
                                <th>تاريخ الاستحقاق</th>
                                <th class="text-center">الحالة</th>
                                <th>تاريخ السداد</th>
                                <th class="text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($debt->installments as $inst)
                            <tr class="{{ $inst->is_overdue ? 'bg-danger bg-opacity-10' : '' }}">
                                <td class="fw-bold text-muted">{{ $inst->installment_number }}</td>
                                <td class="fw-bold text-dark">{{ number_format($inst->amount, 2) }} ج.م</td>
                                <td>
                                    <span class="badge {{ $inst->is_overdue ? 'bg-danger' : 'bg-light text-dark border' }} rounded-pill px-3 py-1">
                                        {{ $inst->due_date->format('Y-m-d') }}
                                    </span>
                                    @if($inst->is_overdue)
                                        <br><small class="text-danger fw-bold d-block mt-1"><i class="bi bi-exclamation-triangle"></i> متأخر {{ abs($inst->days_remaining) }} يوم</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($inst->status == 'paid')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3"><i class="bi bi-check-circle"></i> مسدد</span>
                                    @else
                                        <span class="badge {{ $inst->is_overdue ? 'bg-danger bg-opacity-10 text-danger border border-danger' : 'bg-warning bg-opacity-10 text-warning border border-warning' }} rounded-pill px-3">
                                            {{ $inst->is_overdue ? 'متأخر' : 'غير مسدد' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted fw-bold">{{ $inst->paid_date ? $inst->paid_date->format('Y-m-d') : '—' }}</td>
                                <td class="text-center">
                                    @if($inst->status != 'paid')
                                    <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold" title="سداد هذا القسط"
                                            onclick="openPayInstallmentModal({{ $inst->id }}, {{ $inst->amount }}, '{{ $inst->due_date->format('Y-m-d') }}')">
                                        <i class="bi bi-cash"></i> سداد
                                    </button>
                                    @else
                                    <span class="text-success fw-bolder fs-5"><i class="bi bi-check2-all"></i></span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="text-muted mb-3"><i class="bi bi-info-circle display-4 opacity-25"></i></div>
                    <h6 class="fw-bold text-muted mb-1">لا توجد أقساط مجدولة لهذا الدين</h6>
                    <p class="text-muted small mb-0">هذا الدين مسجل كمبلغ إجمالي بدون خطة تقسيط شهرية.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Ledger Transactions ── --}}
<div class="content-card">
    <h6 class="fw-bold mb-4"><i class="bi bi-clock-history text-primary me-2"></i>سجل الدفعات (تأثير الخزينة)</h6>
    
    @if($transactions->count() > 0)
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead>
                <tr>
                    <th>تاريخ الحركة</th>
                    <th class="text-center">نوع الحركة</th>
                    <th class="text-center">المبلغ</th>
                    <th>البيان / الوصف</th>
                    <th class="text-center">رصيد الخزينة وقتها</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $t)
                <tr>
                    <td class="fw-bold text-muted">{{ $t->transaction_date->format('Y-m-d') }}</td>
                    <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ \App\Models\CashTransaction::typeLabels()[$t->type] ?? $t->type }}</span></td>
                    <td class="text-center fw-bold fs-6 {{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}" style="direction:ltr;">
                        {{ $t->amount > 0 ? '+' : '' }}{{ number_format($t->amount, 2) }}
                    </td>
                    <td class="fw-bold text-dark">{{ $t->description }}</td>
                    <td class="text-center text-muted fw-bold" style="direction:ltr;">{{ number_format($t->balance_after, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center py-5 mb-0 fw-bold"><i class="bi bi-slash-circle fs-3 d-block mb-2 text-muted opacity-50"></i>لم يتم تسجيل أي دفعات مالية لهذا الدين حتى الآن.</p>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

{{-- ── Edit Debt Modal ── --}}
<div class="modal fade" id="editDebtModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#eff6ff,#fff); border-bottom:2px solid #bfdbfe; border-radius:20px 20px 0 0;">
                <h5 class="fw-bold mb-0"><i class="bi bi-pencil-fill text-primary me-2"></i>تعديل بيانات الدين</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('debts.update', $debt) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">نوع الدين <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required style="border-radius:10px;">
                                <option value="owed_to_us" {{ $debt->type == 'owed_to_us' ? 'selected' : '' }}>دين لنا</option>
                                <option value="owed_by_us" {{ $debt->type == 'owed_by_us' ? 'selected' : '' }}>دين علينا</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">تاريخ الدين <span class="text-danger">*</span></label>
                            <input type="date" name="debt_date" class="form-control" value="{{ $debt->debt_date ? $debt->debt_date->format('Y-m-d') : date('Y-m-d') }}" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">اسم الجهة / الشخص <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" class="form-control" value="{{ $debt->party_name }}" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">إجمالي المبلغ <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="total_amount" class="form-control fw-bold text-primary" value="{{ $debt->total_amount }}" required style="border-radius:10px;">
                            <small class="text-danger">تنبيه: تغيير الإجمالي سيؤثر على المتبقي بناءً على ما تم سداده.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted fw-bold">ملاحظات والتفاصيل</label>
                            <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;">{{ $debt->notes }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5">تحديث الدين</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Pay Installment Modal ── --}}
<div class="modal fade" id="payInstallmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0fdf4,#fff); border-bottom:2px solid #bbf7d0; border-radius:20px 20px 0 0;">
                <h5 class="fw-bold mb-0"><i class="bi bi-cash-stack text-success me-2"></i>سداد قسط مستحق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="payInstallmentForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bolder text-success mb-1" id="payInstAmountDisplay"></h4>
                        <span class="badge bg-light border text-dark fw-bold rounded-pill px-3 py-2" id="payInstDueDateDisplay"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">تاريخ السداد الفعلي</label>
                        <input type="date" name="paid_date" value="{{ date('Y-m-d') }}" class="form-control" required style="border-radius:10px;">
                    </div>
                    <div>
                        <label class="form-label fw-bold text-muted">ملاحظات (تظهر في الخزينة)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="اختياري..." style="border-radius:10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4"><i class="bi bi-check-circle me-1"></i> تأكيد السداد</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Pay Partial Modal ── --}}
<div class="modal fade" id="payPartialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0fdf4,#fff); border-bottom:2px solid #bbf7d0; border-radius:20px 20px 0 0;">
                <h5 class="fw-bold mb-0"><i class="bi bi-cash-stack text-success me-2"></i>سداد دفعة حرة (غير مرتبطة بقسط)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('debts.payPartial', $debt) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 text-center fw-bold bg-info bg-opacity-10 mb-4">
                        الدفعة ستخصم من إجمالي الدين دون تسديد أقساط بعينها.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">المبلغ المراد سداده</label>
                        <input type="number" step="0.01" name="amount" max="{{ $debt->remaining_amount }}" class="form-control form-control-lg fs-5 fw-bold text-center text-success" required placeholder="المتبقي: {{ $debt->remaining_amount }}" style="border-radius:12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">تاريخ السداد</label>
                        <input type="date" name="paid_date" value="{{ date('Y-m-d') }}" class="form-control" required style="border-radius:10px;">
                    </div>
                    <div>
                        <label class="form-label fw-bold text-muted">ملاحظات</label>
                        <input type="text" name="notes" class="form-control" placeholder="اختياري" style="border-radius:10px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-5"><i class="bi bi-check-circle me-1"></i> تأكيد الدفع</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openPayInstallmentModal(installmentId, amount, dueDate) {
        document.getElementById('payInstallmentForm').action = '/debts/{{ $debt->id }}/pay-installment/' + installmentId;
        document.getElementById('payInstAmountDisplay').textContent = Number(amount).toLocaleString() + ' ج.م';
        document.getElementById('payInstDueDateDisplay').innerHTML = '<i class="bi bi-calendar me-1"></i> استحقاق: ' + dueDate;
        
        var modal = new bootstrap.Modal(document.getElementById('payInstallmentModal'));
        modal.show();
    }
</script>
@endpush
