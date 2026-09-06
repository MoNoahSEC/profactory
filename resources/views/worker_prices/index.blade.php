@extends('layouts.app')
@section('title', 'أسعار مصنعيات العمال')
@section('page_title', 'أسعار المصنعيات')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-tags-fill text-orange me-2"></i>أسعار مصنعيات العمال</h4>
        <p class="text-muted mb-0 small">تحديد سعر القطعة (القفص) المخصص لكل عامل إنتاج على حدة</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div class="position-relative">
            <i class="bi bi-search position-absolute top-50 translate-middle-y text-muted" style="right:12px; pointer-events:none;"></i>
            <input type="text" id="workerSearch" class="form-control" style="padding-right:38px; border-radius:12px; border: 1.5px solid #e2e8f0; min-width:220px;" placeholder="ابحث عن عامل...">
        </div>
        <button type="submit" form="pricesForm" class="btn btn-orange rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-save me-2"></i> حفظ الأسعار
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert border-0 rounded-3 shadow-sm mb-4 fw-bold" style="background:var(--primary-light);color:var(--primary-dark);">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close float-start" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Workers Table --}}
<form id="pricesForm" action="{{ route('worker-prices.store') }}" method="POST">
    @csrf
    <div class="content-card">
        <div class="content-card-header">
            <h6 class="fw-bold mb-0"><i class="bi bi-people-fill text-orange me-2"></i>عمال الإنتاج بالقطعة — اضغط على اسم العامل لتعديل أسعاره</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-clean mb-0" id="workersTable">
                <thead>
                    <tr>
                        <th>العامل</th>
                        <th>الدور</th>
                        <th class="text-center">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workers as $worker)
                        <tr class="worker-item" data-worker-name="{{ $worker->name }}" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#workerModal-{{ $worker->id }}">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:40px;height:40px;font-size:1.1rem;flex-shrink:0;">
                                        <i class="bi bi-person-fill text-orange"></i>
                                    </span>
                                    <span class="fw-bold text-dark fs-6">{{ $worker->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-muted rounded-pill px-3 py-1">
                                    <i class="bi bi-tools me-1"></i>{{ $worker->production_role == 'scissors' ? 'مقصدار' : 'مكنجي' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-orange rounded-pill btn-sm px-4">
                                    <i class="bi bi-pencil me-1"></i> تعديل الأسعار
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <i class="bi bi-people text-muted display-4 d-block mb-2 opacity-25"></i>
                                <span class="text-muted fw-bold">لا يوجد عمال إنتاج بنظام القطعة</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Worker Price Modals --}}
    @foreach($workers as $worker)
    <div class="modal fade" id="workerModal-{{ $worker->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 40px rgba(0,0,0,.12);">
                <div class="modal-header" style="border-bottom: 2px solid var(--primary-light); background:#fffbf8; border-radius:20px 20px 0 0;">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">
                            <i class="bi bi-tags-fill text-orange me-2"></i>أسعار: {{ $worker->name }}
                        </h5>
                        <small class="text-muted">{{ $worker->production_role == 'scissors' ? 'مقصدار' : 'مكنجي' }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                    <div class="table-responsive" style="overflow-x: hidden;">
                        <table class="table table-clean mb-0">
                            <thead>
                                <tr style="position: sticky; top: 0; background: #fff; z-index: 5;">
                                    <th>المنتج (القفص)</th>
                                    <th class="text-center" style="width:220px;">سعر المصنعية المخصص (ج.م)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    @php
                                        $key = $worker->id . '_' . $product->id;
                                        $price = isset($prices[$key]) ? $prices[$key]->price : '';
                                        $defaultPrice = ($worker->production_role == 'scissors')
                                            ? ($product->piece_wage_scissors > 0 ? $product->piece_wage_scissors : $product->scissors_cost)
                                            : ($product->piece_wage > 0 ? $product->piece_wage : $product->labor_cost);
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $product->name }}</span>
                                            <small class="text-muted d-block">السعر الافتراضي: {{ number_format((float)$defaultPrice, 2) }} ج.م</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group input-group-sm" style="max-width:180px; margin:auto;">
                                                <input type="number"
                                                    name="prices[{{ $key }}]"
                                                    class="form-control text-center fw-bold"
                                                    style="border-radius:10px 0 0 10px; border: 1.5px solid #e2e8f0; color: var(--primary);"
                                                    step="0.01" min="0"
                                                    value="{{ $price }}"
                                                    placeholder="{{ number_format((float)$defaultPrice, 2) }}">
                                                <span class="input-group-text text-muted small" style="background:#f8fafc; border: 1.5px solid #e2e8f0; border-right: none; border-radius:0 10px 10px 0;">ج.م</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 2px solid var(--primary-light); background:#fffbf8; border-radius:0 0 20px 20px;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-4 fw-bold">
                        <i class="bi bi-save me-2"></i> حفظ الأسعار
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</form>

@push('scripts')
<script>
    document.getElementById('workerSearch').addEventListener('input', function() {
        let term = this.value.toLowerCase();
        document.querySelectorAll('.worker-item').forEach(row => {
            let name = row.getAttribute('data-worker-name').toLowerCase();
            row.style.display = name.includes(term) ? '' : 'none';
        });
    });
</script>
@endpush
@endsection
