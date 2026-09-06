@extends('layouts.app')

@section('title', 'المعاملات المالية للموظفين | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'المعاملات المالية للموظفين')

@section('content')

{{-- ── Header ── --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-wallet2 text-orange me-2"></i>سجل المعاملات المالية</h4>
        <p class="text-muted mb-0 small">إدارة السلف، الخصومات، والمكافآت لجميع الموظفين</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-orange rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#financialModal">
            <i class="bi bi-plus-lg me-1"></i> تسجيل معاملة جديدة
        </button>
    </div>
</div>

{{-- ── Table ── --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>قائمة المعاملات المالية</h6>
        <input type="text" id="financialsSearch" class="form-control form-control-sm w-auto" placeholder="🔍 بحث عن موظف أو معاملة..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:250px;">
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0" id="financialsTable">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th class="text-center">النوع</th>
                    <th class="text-center">المبلغ</th>
                    <th class="text-center">التاريخ</th>
                    <th class="text-center">الحالة</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td>
                        <a href="{{ route('workers.statement', $tx->worker_id) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-person text-orange"></i>
                            </span>
                            {{ $tx->worker->name }}
                        </a>
                    </td>
                    <td class="text-center">
                        @if($tx->record_type === 'advance')
                            <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning px-3"><i class="bi bi-cash-coin me-1"></i> سلفة</span>
                        @elseif($tx->record_type === 'penalty')
                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger px-3"><i class="bi bi-exclamation-triangle me-1"></i> خصم</span>
                        @elseif($tx->record_type === 'bonus')
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success px-3"><i class="bi bi-gift me-1"></i> مكافأة</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="fw-bold fs-6 {{ $tx->record_type === 'bonus' ? 'text-success' : 'text-danger' }}">
                            {{ number_format($tx->amount, 2) }} <small class="fw-normal">ج.م</small>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">{{ $tx->date->format('Y-m-d') }}</span>
                    </td>
                    <td class="text-center">
                        @if($tx->is_deducted)
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success px-3"><i class="bi bi-check-circle me-1"></i> مُغلقة (مسددة)</span>
                        @else
                            <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary px-3"><i class="bi bi-clock me-1"></i> مفتوحة (جارية)</span>
                        @endif
                    </td>
                    <td class="text-muted fw-bold">{{ $tx->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="bi bi-wallet2 text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا توجد معاملات مالية مسجلة حالياً.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Add Transaction Modal ── --}}
<div class="modal fade" id="financialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <form action="{{ route('financials.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-orange me-2"></i>تسجيل معاملة مالية جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold">نوع المعاملة <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_advance" value="advance" checked>
                                <label class="form-check-label text-warning fw-bold" for="type_advance">سلفة (تسحب نقداً فوراً)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_penalty" value="penalty">
                                <label class="form-check-label text-danger fw-bold" for="type_penalty">خصم (جزاء)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_bonus" value="bonus">
                                <label class="form-check-label text-success fw-bold" for="type_bonus">مكافأة (تضاف للراتب)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">الموظف <span class="text-danger">*</span></label>
                        <select name="worker_id" class="form-select" required style="border-radius:10px;">
                            <option value="">-- اختر الموظف --</option>
                            @foreach($workers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold">المبلغ (ج.م) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control fw-bold fs-5 text-primary" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold">التاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">ملاحظات أو سبب (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;"></textarea>
                    </div>

                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-5 fw-bold shadow-sm">حفظ المعاملة <i class="bi bi-check-circle ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('financialsSearch')?.addEventListener('keyup', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('#financialsTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
        });
    });
</script>
@endpush
