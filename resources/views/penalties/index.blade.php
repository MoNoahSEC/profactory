@extends('layouts.app')

@section('title', 'الخصومات والجزاءات | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'إدارة الخصومات والجزاءات')

@section('content')
<div class="row mb-4 fade-in-up">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>سجل الخصومات للموظفين</h5>
        <button class="btn-glass" data-bs-toggle="modal" data-bs-target="#penaltyModal">
            <i class="bi bi-plus-lg me-1"></i> توقيع جزاء / خصم جديد
        </button>
    </div>
</div>

<div class="glass-panel fade-in-up">
    <div class="table-responsive">
        <table class="table table-borderless table-hover align-middle mb-0">
            <thead class="text-muted border-bottom border-secondary">
                <tr>
                    <th>الموظف</th>
                    <th>المبلغ</th>
                    <th>سبب الخصم</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>ملاحظات</th>
                    <th class="text-center">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penalties as $pen)
                <tr class="border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                    <td class="fw-bold text-dark"><i class="bi bi-person-circle me-2 text-danger"></i>{{ $pen->worker->name }}</td>
                    <td class="text-danger fw-bold">{{ number_format($pen->amount, 2) }} ج.م</td>
                    <td>
                        @if($pen->reason_type == 'absent') غياب بدون إذن
                        @elseif($pen->reason_type == 'damage') إتلاف خامات/منتجات
                        @elseif($pen->reason_type == 'behavior') سوء سلوك أو تأخير
                        @else أخرى
                        @endif
                    </td>
                    <td>{{ $pen->date->format('Y-m-d') }}</td>
                    <td>
                        @if($pen->is_deducted)
                            <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3">تم التطبيق/الخصم</span>
                        @else
                            <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3">لم يخصم بعد (قيد الانتظار)</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $pen->notes ?? '-' }}</td>
                    <td class="text-center">
                        @if(!$pen->is_deducted)
                        <form action="{{ route('penalties.destroy', $pen) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الخصم؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger rounded-circle border-0" title="إلغاء الخصم">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد خصومات مسجلة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="penaltyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered border-0">
        <div class="modal-content glass-panel">
            <form action="{{ route('penalties.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary bg-danger bg-opacity-10">
                    <h5 class="modal-title fw-bold text-danger">توقيع خصم / جزاء جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label text-muted">الموظف</label>
                        <select name="worker_id" class="form-select form-control-glass" required>
                            <option value="">اختر الموظف...</option>
                            @foreach($workers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">مبلغ الخصم (ج.م)</label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-glass text-danger fw-bold" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">التاريخ</label>
                            <input type="date" name="date" class="form-control form-control-glass" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">سبب الخصم</label>
                        <select name="reason_type" class="form-select form-control-glass" required>
                            <option value="absent">غياب بدون إذن</option>
                            <option value="damage">إتلاف خامات أو منتجات</option>
                            <option value="behavior">سوء سلوك أو تأخير</option>
                            <option value="other">سبب آخر</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">تفاصيل إضافية (ملاحظات)</label>
                        <textarea name="notes" class="form-control form-control-glass" rows="2" placeholder="اكتب تفاصيل المخالفة هنا..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">اعتماد الخصم</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
