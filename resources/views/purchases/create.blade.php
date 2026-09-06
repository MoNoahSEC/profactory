@extends('layouts.app')

@section('title', 'تسجيل فاتورة شراء | ' . config('app.name'))
@section('page_title', 'تسجيل فاتورة شراء جديدة')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="page-header-card d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bolder mb-1"><i class="bi bi-cart-plus-fill text-orange me-2"></i>تسجيل فاتورة شراء خام</h4>
                <p class="text-muted mb-0 small">سيتم إضافة الكميات للمخزون فور الحفظ</p>
            </div>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary shadow-sm rounded-pill px-4">
                <i class="bi bi-arrow-right me-1"></i> رجوع للمشتريات
            </a>
        </div>

        <div class="content-card p-4">
            <form action="{{ route('purchases.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">تاريخ الفاتورة <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">المورد <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_id" list="supplierList" class="form-control" placeholder="اختر من القائمة أو اكتب اسم مورد جديد" required style="border-radius:10px;">
                        <datalist id="supplierList">
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->name }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-12"><hr style="border-color:#f1f5f9; margin: 0.5rem 0;"></div>

                    <div class="col-md-12">
                        <label class="form-label text-muted fw-bold">المادة الخام <span class="text-danger">*</span></label>
                        <select name="raw_material_id" class="form-select form-select-lg" required style="border-radius:10px;">
                            <option value="">-- اختر المادة الخام --</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->unit }}) - الرصيد الحالي: {{ $material->current_stock }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">الكمية <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-box"></i></span>
                            <input type="number" step="0.01" name="quantity" id="pQty" class="form-control form-control-lg border-start-0" required oninput="calcTotal()" style="border-radius:0 10px 10px 0;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold">سعر الوحدة <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-cash"></i></span>
                            <input type="number" step="0.01" name="unit_price" id="pPrice" class="form-control form-control-lg border-start-0" required oninput="calcTotal()" style="border-radius:0 10px 10px 0;">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-4 rounded-4 text-center mt-2 h-100 d-flex flex-column justify-content-center" style="background:rgba(234,88,12,0.05); border:2px dashed #fdba74;">
                            <p class="text-muted small fw-bold mb-2">إجمالي الفاتورة</p>
                            <h2 class="fw-bolder text-orange mb-0" id="pTotalDisplay">0.00 <span class="fs-6 text-muted">ج.م</span></h2>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-4 rounded-4 mt-2" style="background:#f8fafc; border:1px solid #e2e8f0;">
                            <label class="form-label text-muted fw-bold">المبلغ المدفوع نقداً للمورد <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="paid_amount" class="form-control form-control-lg fw-bolder text-success text-center mb-2" value="0" required style="border-radius:10px;">
                            <small class="text-danger d-block text-center"><i class="bi bi-info-circle"></i> سيتم تسجيل الباقي كمديونية تلقائياً.</small>
                        </div>
                    </div>

                    <div class="col-12 text-center mt-5">
                        <button type="submit" class="btn btn-orange btn-lg px-5 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-check-circle me-2"></i> حفظ وتسجيل الفاتورة
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
function calcTotal() {
    let q = parseFloat(document.getElementById('pQty').value) || 0;
    let p = parseFloat(document.getElementById('pPrice').value) || 0;
    document.getElementById('pTotalDisplay').innerHTML = (q * p).toFixed(2) + ' <span class="fs-6 text-muted">ج.م</span>';
}
</script>
@endpush
