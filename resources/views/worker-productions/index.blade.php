@extends('layouts.app')

@section('title', 'يومية الإنتاج بالقطعة | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'تسجيل إنتاج الموظفين بالقطعة')

@section('content')

{{-- ── Header ── --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-hammer text-orange me-2"></i>يومية إنتاج القطعة</h4>
        <p class="text-muted mb-0 small">تسجيل أجور العمال بناءً على كميات الإنتاج المنجزة</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-orange rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#productionModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة إنتاج لموظف
        </button>
    </div>
</div>

{{-- ── Table ── --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>سجل الإنتاج اليومي</h6>
        <input type="text" id="workerProdSearch" class="form-control form-control-sm w-auto" placeholder="🔍 بحث عن موظف أو منتج..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:250px;">
    </div>
    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0" id="workerProdTable">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الموظف</th>
                    <th>المنتج</th>
                    <th class="text-center">الكمية المصنعة</th>
                    <th class="text-center">أجرة القطعة</th>
                    <th class="text-center text-success">إجمالي الأجر</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productions as $prod)
                <tr class="cursor-pointer" onclick="openProdActionModal({{ $prod->id }}, '{{ $prod->worker->name }}', '{{ $prod->product->name }}', '{{ $prod->quantity }}', '{{ number_format($prod->total_pay, 2) }}')" title="انقر لعرض الخيارات">
                    <td><span class="badge bg-light text-dark border rounded-pill px-3 py-1">{{ $prod->date->format('Y-m-d') }}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-person text-orange"></i>
                            </span>
                            <span class="fw-bold text-dark">{{ $prod->worker->name }}</span>
                        </div>
                    </td>
                    <td class="fw-bold text-primary">{{ $prod->product->name }}</td>
                    <td class="text-center fw-bolder fs-5">{{ $prod->quantity }}</td>
                    <td class="text-center text-muted fw-bold">{{ number_format($prod->labor_cost_per_piece, 2) }} <small>ج.م</small></td>
                    <td class="text-center fw-bolder fs-5 text-success">{{ number_format($prod->total_pay, 2) }} <small class="text-muted fs-6">ج.م</small></td>
                    <td class="text-muted fw-bold">{{ $prod->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-hammer text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا يوجد إنتاج مسجل حتى الآن.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

{{-- ── Action Modal ── --}}
<div class="modal fade" id="prodActionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-gear text-orange me-2"></i>خيارات السجل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h6 class="fw-bold mb-1" id="actWorker"></h6>
                <p class="text-muted mb-1 small" id="actProduct"></p>
                <div class="text-success fw-bolder fs-5 mb-4" id="actTotal"></div>
                
                <form id="deleteProdForm" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟ قد يؤثر ذلك على راتب الموظف إذا لم يتم صرفه بعد.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill w-100 py-2">
                        <i class="bi bi-trash me-1"></i> حذف السجل
                    </button>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Add Modal ── --}}
<div class="modal fade" id="productionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <form action="{{ route('worker-productions.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-orange me-2"></i>تسجيل إنتاج موظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">الموظف <span class="text-danger">*</span></label>
                        <select name="worker_id" class="form-select" required style="border-radius:10px;">
                            <option value="">-- اختر الموظف --</option>
                            @foreach($workers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">المنتج المُصنع <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required style="border-radius:10px;">
                            <option value="">-- اختر المنتج --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} (تكلفة المصنعية: {{ number_format($product->labor_cost, 2) }} ج.م)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold">الكمية المصنعة <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" min="1" class="form-control fw-bold fs-5 text-primary" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold">التاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">ملاحظات (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-5 fw-bold shadow-sm">حفظ الإنتاج <i class="bi bi-check-circle ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Search
    document.getElementById('workerProdSearch')?.addEventListener('keyup', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('#workerProdTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
        });
    });

    // Modal Details
    function openProdActionModal(id, workerName, productName, qty, totalPay) {
        document.getElementById('actWorker').textContent = workerName;
        document.getElementById('actProduct').textContent = productName + ' (' + qty + ' قطعة)';
        document.getElementById('actTotal').textContent = totalPay + ' ج.م';
        
        document.getElementById('deleteProdForm').action = `/worker-productions/${id}`;
        
        var modal = new bootstrap.Modal(document.getElementById('prodActionModal'));
        modal.show();
    }
</script>
<style>
    .cursor-pointer { cursor: pointer; transition: background-color 0.2s; }
    .cursor-pointer:hover { background-color: rgba(234, 88, 12, 0.03) !important; }
</style>
@endpush
