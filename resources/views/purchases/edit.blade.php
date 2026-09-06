@extends('layouts.app')

@section('title', 'تعديل فاتورة مشتريات | ' . config('app.name'))
@section('page_title', 'تعديل فاتورة مشتريات')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="page-header-card d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bolder mb-1"><i class="bi bi-pencil-square text-orange me-2"></i>تعديل الفاتورة رقم #{{ $purchase->id }}</h4>
                <p class="text-muted mb-0 small">تعديل الفاتورة سيقوم بتحديث المخزون والخزينة تلقائياً</p>
            </div>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary shadow-sm rounded-pill px-4">
                <i class="bi bi-arrow-right me-1"></i> رجوع للمشتريات
            </a>
        </div>

        <div class="content-card p-4">
            <form action="{{ route('purchases.update', $purchase) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">تاريخ الفاتورة <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ $purchase->purchase_date->format('Y-m-d') }}" required style="border-radius:10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">المورد</label>
                        <input type="text" class="form-control bg-light" value="{{ $purchase->supplier->name ?? 'غير معروف' }}" readonly style="border-radius:10px;">
                        <small class="text-muted"><i class="bi bi-info-circle"></i> لا يمكن تغيير المورد بعد الحفظ. إذا كان خاطئاً، احذف الفاتورة وأنشئ جديدة.</small>
                    </div>

                    <div class="col-12"><hr style="border-color:#f1f5f9; margin: 0.5rem 0;"></div>

                    <div class="col-md-12">
                        <label class="form-label text-muted fw-bold">المادة الخام</label>
                        <input type="text" class="form-control form-control-lg bg-light" value="{{ $purchase->rawMaterial->name ?? 'غير معروف' }} ({{ $purchase->rawMaterial->unit ?? '' }})" readonly style="border-radius:10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">الكمية <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-box"></i></span>
                            <input type="number" step="0.01" name="quantity" id="pQtyEdit" class="form-control form-control-lg border-start-0" value="{{ $purchase->quantity }}" required oninput="calcTotalEdit()" style="border-radius:0 10px 10px 0;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">سعر الوحدة <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-cash"></i></span>
                            <input type="number" step="0.01" name="unit_price" id="pPriceEdit" class="form-control form-control-lg border-start-0" value="{{ $purchase->unit_price }}" required oninput="calcTotalEdit()" style="border-radius:0 10px 10px 0;">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-4 rounded-4 text-center mt-2 h-100 d-flex flex-column justify-content-center" style="background:rgba(234,88,12,0.05); border:2px dashed #fdba74;">
                            <p class="text-muted small fw-bold mb-2">إجمالي الفاتورة الجديد</p>
                            <h2 class="fw-bolder text-orange mb-0" id="pTotalDisplayEdit">{{ number_format($purchase->total_price, 2, '.', '') }} <span class="fs-6 text-muted">ج.م</span></h2>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-4 rounded-4 mt-2" style="background:#f8fafc; border:1px solid #e2e8f0;">
                            <label class="form-label text-muted fw-bold">المبلغ المدفوع نقداً للمورد <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="paid_amount" class="form-control form-control-lg fw-bolder text-success text-center mb-2" value="{{ $purchase->paid_amount }}" required style="border-radius:10px;">
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <label class="form-label text-muted fw-bold">ملاحظات الفاتورة</label>
                        <textarea name="notes" class="form-control" rows="3" style="border-radius:10px;">{{ $purchase->notes }}</textarea>
                    </div>

                    <div class="col-12 text-center mt-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-save me-2"></i> حفظ التعديلات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function calcTotalEdit() {
    let q = parseFloat(document.getElementById('pQtyEdit').value) || 0;
    let p = parseFloat(document.getElementById('pPriceEdit').value) || 0;
    document.getElementById('pTotalDisplayEdit').innerHTML = (q * p).toFixed(2) + ' <span class="fs-6 text-muted">ج.م</span>';
}
</script>
@endpush
