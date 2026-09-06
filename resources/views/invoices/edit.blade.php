@extends('layouts.app')
@section('title', 'تعديل الفاتورة #' . $invoice->invoice_number . ' | ' . ($globalSettings['company_name'] ?? config('app.name')))

@push('styles')
<style>
.inv-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:16px; padding:1.4rem; margin-bottom:1.2rem; box-shadow:0 2px 10px rgba(0,0,0,.05); overflow:visible !important; }
.inv-card-title { font-size:1rem; font-weight:800; color:#1e293b; border-bottom:2px solid #f1f5f9; padding-bottom:.6rem; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }
.item-row { display:flex; flex-wrap:wrap; gap:8px; align-items:center; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:10px; margin-bottom:8px; position:relative; z-index:1; overflow:visible !important; }
.item-row:focus-within { border-color:#ea580c; background:#fffbf8; z-index:10; }
.product-wrap { flex:1 1 100%; min-width:0; position:relative; }
.r-col { flex: 1 1 calc(33.333% - 8px); min-width:60px; }
.r-col-action { flex: 1 1 100%; text-align:center; margin-top: 8px; border-top:1px dashed #e2e8f0; padding-top:8px;}
@media (min-width: 768px) {
    .item-row { flex-wrap:nowrap; }
    .product-wrap { flex:3 1 auto; }
    .r-col { flex: 1 1 0%; }
    .r-col-action { flex: 0 0 34px; border:none; margin:0; padding:0; }
}
.pdrop { display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1.5px solid #ea580c; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.15); max-height:220px; overflow-y:auto; z-index:99999; list-style:none; padding:0; margin:0; }
.pdrop li { padding:10px 14px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f1f5f9; font-weight:600; }
.pdrop li:last-child { border-bottom:none; }
.pdrop li:hover { background:#fff8f4; color:#ea580c; }
.cdrop { display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1.5px solid #0d6efd; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.15); max-height:250px; overflow-y:auto; z-index:99999; list-style:none; padding:0; margin:0; }
.cdrop li { padding:12px 16px; cursor:pointer; display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; font-weight:600; }
.cdrop li:last-child { border-bottom:none; }
.cdrop li:hover { background:#eff6ff; color:#0d6efd; }
.ni { font-size:1.05rem !important; font-weight:800 !important; text-align:center !important; border:1.5px solid #cbd5e1; border-radius:10px; padding:7px 5px; width:100%; background:#fff; }
.ni:focus { border-color:#ea580c; outline:none; box-shadow:0 0 0 3px rgba(234,88,12,.12); background:#fffbf8; }
.summary-box { background:linear-gradient(135deg,#1e293b,#0f172a); color:#fff; border-radius:16px; padding:1.4rem; position:sticky; top:80px; }
.srow { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.08); }
.slabel { color:rgba(255,255,255,.7); font-weight:600; font-size:.9rem; }
.sval { font-weight:800; font-size:1rem; }
.grand-box { background:rgba(234,88,12,.15); border-radius:12px; padding:14px 16px; margin-top:12px; display:flex; justify-content:space-between; align-items:center; }
.grand-label { color:#fff; font-weight:700; font-size:1.1rem; }
.grand-val { color:#f97316; font-size:1.8rem; font-weight:900; }
.save-btn { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border:none; border-radius:14px; padding:16px; font-size:1.2rem; font-weight:800; width:100%; cursor:pointer; margin-top:16px; transition:all .2s; }
.save-btn:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(245,158,11,.35); }
.sin { background:#1e3a5f; border:1px solid #334155; border-radius:8px; width:100%; }
.sin:focus { outline:none; border-color:#f97316; }
</style>
@endpush

@section('content')

@if($errors->any())
<div class="alert alert-danger border-0 rounded-3 mb-4 shadow-sm">
    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>يوجد أخطاء:</strong>
    <ul class="mb-0 mt-2 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('invoices.update', $invoice) }}" method="POST" id="EDIT_FORM">
@csrf
@method('PUT')
<input type="hidden" name="discount_type" id="EDIT_DISC_TYPE" value="{{ $invoice->discount_type }}">

<div class="row g-3">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Customer --}}
        <div class="inv-card">
            <div class="inv-card-title"><i class="bi bi-person-fill text-primary"></i> بيانات العميل والفاتورة</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">اسم العميل <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="EDIT_CUST_SEARCH" name="customer_name" autocomplete="off"
                               class="form-control fw-bold" style="border-radius:10px;"
                               value="{{ $invoice->customer->name ?? '' }}" required>
                        <input type="hidden" name="customer_id" id="EDIT_CUST_ID" value="{{ $invoice->customer_id }}">
                        <ul class="cdrop" id="EDIT_CDROP"></ul>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">تاريخ الفاتورة <span class="text-danger">*</span></label>
                    <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}" class="form-control" style="border-radius:10px;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">العملة</label>
                    <input type="text" name="currency" value="{{ $invoice->currency }}" class="form-control fw-bold" style="border-radius:10px;">
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="inv-card" style="overflow:visible !important;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="inv-card-title mb-0"><i class="bi bi-cart-check-fill text-success"></i> الأصناف</div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3" onclick="EDIT.addRow()">
                    <i class="bi bi-plus-lg me-1"></i> إضافة صنف
                </button>
            </div>
            <div class="d-none d-md-flex gap-2 mb-2" style="font-size:.72rem;font-weight:800;color:#94a3b8;">
                <div style="flex:3;" class="ps-2">المنتج</div>
                <div style="flex:1;" class="text-center">كراتين</div>
                <div style="flex:1;" class="text-center">عبوة</div>
                <div style="flex:1;" class="text-center">الكمية</div>
                <div style="flex:1;" class="text-center">السعر</div>
                <div style="flex:1;" class="text-center">خصم</div>
                <div style="flex:1;" class="text-center">الإجمالي</div>
                <div style="flex:0 0 36px;"></div>
            </div>
            <div id="EDIT_ROWS"></div>
        </div>

        {{-- Notes --}}
        <div class="inv-card">
            <div class="inv-card-title"><i class="bi bi-card-text text-secondary"></i> ملاحظات</div>
            <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;">{{ $invoice->notes }}</textarea>
        </div>
    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">
        <div class="summary-box">
            <h6 class="fw-bold text-white mb-4" style="font-size:1rem;">
                <i class="bi bi-calculator me-2" style="color:#f97316;"></i>الملخص المالي
            </h6>

            <div class="srow">
                <span class="slabel">إجمالي الأصناف</span>
                <span class="sval" id="ES_SUB">٠٫٠٠</span>
            </div>

            <div class="mt-3 mb-2">
                <label class="slabel small mb-1">خصم إضافي على الفاتورة</label>
                <div class="d-flex gap-2">
                    <input type="number" name="discount_value" id="EDIT_DISC_VAL" min="0" step="0.01"
                           class="form-control form-control-sm fw-bold sin" style="color:#f87171;"
                           placeholder="0" value="{{ $invoice->discount_value ?? 0 }}" oninput="EDIT.calc()">
                    <select name="discount_type" id="EDIT_DISC_SEL" onchange="EDIT.syncDiscType();EDIT.calc();"
                            class="form-select form-select-sm fw-bold sin" style="max-width:80px;color:#f87171;">
                        <option value="amount" {{ ($invoice->discount_type ?? 'amount') === 'amount' ? 'selected' : '' }}>ج.م</option>
                        <option value="percent" {{ ($invoice->discount_type ?? 'amount') === 'percent' ? 'selected' : '' }}>%</option>
                    </select>
                </div>
            </div>
            <div class="srow">
                <span class="slabel">إجمالي الخصومات</span>
                <span class="sval text-danger" id="ES_DISC">٠٫٠٠</span>
            </div>

            <div class="mt-2 mb-2">
                <label class="slabel small mb-1">ضريبة مضافة %</label>
                <input type="number" name="tax_rate" id="EDIT_TAX" min="0" step="0.01"
                       class="form-control form-control-sm fw-bold sin" style="color:#93c5fd;"
                       placeholder="0" value="{{ $invoice->tax_rate ?? 0 }}" oninput="EDIT.calc()">
            </div>
            <div class="srow">
                <span class="slabel">إجمالي الضريبة</span>
                <span class="sval text-info" id="ES_TAX">٠٫٠٠</span>
            </div>

            <div class="grand-box">
                <span class="grand-label">الإجمالي النهائي</span>
                <span class="grand-val" id="ES_GRAND">٠٫٠٠</span>
            </div>

            <div class="mt-3 p-2 rounded-3" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);">
                <small class="text-white opacity-75"><i class="bi bi-info-circle me-1"></i>لإضافة دفعات جديدة، احفظ التعديلات أولاً ثم أضفها من صفحة الفاتورة.</small>
            </div>

            <button type="button" id="EDIT_SAVE_BTN" onclick="EDIT.submit()" class="save-btn">
                <i class="bi bi-check2-circle me-2"></i>
                <span id="EDIT_SAVE_TXT">حفظ التعديلات</span>
                <span class="spinner-border spinner-border-sm d-none ms-2" id="EDIT_SPIN"></span>
            </button>
        </div>
    </div>

</div>
</form>

@push('scripts')
<script>
const EDIT_PRODUCTS  = {!! json_encode($products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code,'price'=>(float)($p->selling_price??0)])) !!};
const EDIT_CUSTOMERS = {!! json_encode($customers->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'type'=>$c->type])) !!};
const EDIT_INIT_ITEMS = {!! json_encode($invoice->items->map(fn($i) => [
    'product_id' => $i->product_id,
    'search'     => $i->product->name ?? '',
    'cpk'        => (float)($i->product->cages_per_carton ?? 1),
    'cartons'    => (float)($i->product->cages_per_carton ?? 1) > 0 ? $i->quantity / (float)($i->product->cages_per_carton ?? 1) : 0,
    'qty'        => $i->quantity,
    'price'      => $i->unit_price,
    'disc'       => $i->discount,
])) !!};

const EDIT = (() => {
    let rowCount = 0;

    function fmt(v) {
        return Number(parseFloat(v)||0).toLocaleString('ar-EG',{minimumFractionDigits:2,maximumFractionDigits:2});
    }

    function calc() {
        let sub = 0;
        document.querySelectorAll('.edit-item-row').forEach(row => {
            const qty   = parseFloat(row.querySelector('.r-qty').value)  || 0;
            const price = parseFloat(row.querySelector('.r-price').value) || 0;
            const disc  = parseFloat(row.querySelector('.r-disc').value)  || 0;
            const rt    = Math.max(0, qty * price - disc);
            row.querySelector('.r-total').textContent = fmt(rt);
            sub += rt;
        });

        const discVal  = parseFloat(document.getElementById('EDIT_DISC_VAL').value) || 0;
        const discType = document.getElementById('EDIT_DISC_SEL').value;
        const taxRate  = parseFloat(document.getElementById('EDIT_TAX').value) || 0;

        const disc  = discType === 'percent' ? sub * (discVal / 100) : Math.min(discVal, sub);
        const tax   = (sub - disc) * (taxRate / 100);
        const grand = sub - disc + tax;

        document.getElementById('ES_SUB').textContent   = fmt(sub);
        document.getElementById('ES_DISC').textContent  = fmt(disc);
        document.getElementById('ES_TAX').textContent   = fmt(tax);
        document.getElementById('ES_GRAND').textContent = fmt(grand);
    }

    function addRow(data) {
        rowCount++;
        const idx = rowCount;
        const d   = data || {};
        const row = document.createElement('div');
        row.className = 'item-row edit-item-row';
        row.innerHTML = `
            <div class="product-wrap">
                <input type="text" class="ni r-search" placeholder="ابحث عن منتج..." autocomplete="off"
                       value="${d.search||''}"
                       onfocus="EDIT.openDrop(this)"
                       onblur="EDIT.closeDrop(this)"
                       oninput="EDIT.filterProds(this)" required>
                <input type="hidden" class="r-pid" name="items[${idx}][product_id]" value="${d.product_id||''}">
                <ul class="pdrop r-drop"></ul>
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">كراتين</label>
                <input type="number" class="ni text-info r-cartons" placeholder="0" min="0" step="any" value="${d.cartons||''}"
                       oninput="EDIT.calcFromPackaging(this)">
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">عبوة</label>
                <input type="number" class="ni text-secondary r-cpk" placeholder="1" min="0.01" step="any" value="${d.cpk||1}"
                       oninput="EDIT.calcFromPackaging(this)">
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">كمية</label>
                <input type="number" class="ni r-qty" name="items[${idx}][quantity]" placeholder="1" min="0.01" step="any" value="${d.qty||1}"
                       oninput="EDIT.calcFromQty(this)" required>
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">سعر</label>
                <input type="number" class="ni text-success r-price" name="items[${idx}][unit_price]" placeholder="0" min="0" step="0.01" value="${d.price||0}"
                       oninput="EDIT.calc();">
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">خصم</label>
                <input type="number" class="ni text-danger r-disc" name="items[${idx}][discount]" placeholder="0" min="0" step="0.01" value="${d.disc||0}"
                       oninput="EDIT.calc();">
            </div>
            <div class="r-col d-flex flex-column justify-content-center align-items-center">
                <label class="d-md-none small text-muted mb-1">الإجمالي</label>
                <span class="fw-bold text-dark r-total" style="font-size:1.1rem;">٠٫٠٠</span>
            </div>
            <div class="r-col-action">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill border-0 px-3 w-100 d-md-none"
                        onclick="EDIT.removeRow(this)">
                    <i class="bi bi-trash"></i> حذف الصنف
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle border-0 p-1 d-none d-md-inline-block"
                        onclick="EDIT.removeRow(this)" title="حذف">
                    <i class="bi bi-trash" style="font-size:.8rem;"></i>
                </button>
            </div>`;
        document.getElementById('EDIT_ROWS').appendChild(row);
        calc();
        return row;
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.edit-item-row');
        if (rows.length <= 1) {
            const row = rows[0];
            row.querySelector('.r-search').value   = '';
            row.querySelector('.r-pid').value      = '';
            row.querySelector('.r-cartons').value  = '';
            row.querySelector('.r-cpk').value      = '1';
            row.querySelector('.r-qty').value      = '1';
            row.querySelector('.r-price').value    = '0';
            row.querySelector('.r-disc').value     = '0';
            row.querySelector('.r-total').textContent = '٠٫٠٠';
            calc();
            return;
        }
        btn.closest('.edit-item-row').remove();
        calc();
    }

    function filterProds(input) {
        const q    = input.value.toLowerCase().trim();
        const drop = input.closest('.product-wrap').querySelector('.r-drop');
        input.closest('.product-wrap').querySelector('.r-pid').value = '';

        const results = q.length < 1
            ? EDIT_PRODUCTS.slice(0, 12)
            : EDIT_PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || (p.code && p.code.toLowerCase().includes(q))).slice(0, 12);

        drop.innerHTML = '';
        if (results.length === 0) {
            drop.innerHTML = '<li style="color:#94a3b8;cursor:default;justify-content:center;">لا يوجد منتج مطابق</li>';
        } else {
            results.forEach(p => {
                const li = document.createElement('li');
                li.innerHTML = `<span>${p.name}</span><span class="badge bg-light text-dark border">${p.code||''}</span>`;
                li.onmousedown = (e) => { e.preventDefault(); pickProd(input, p); };
                drop.appendChild(li);
            });
        }
        drop.style.display = 'block';
        calc();
    }

    function openDrop(input) { filterProds(input); }
    function closeDrop(input) {
        setTimeout(() => {
            const drop = input.closest('.product-wrap').querySelector('.r-drop');
            drop.style.display = 'none';
        }, 250);
    }

    function pickProd(searchInput, p) {
        const row = searchInput.closest('.edit-item-row');
        searchInput.value = p.name;
        row.querySelector('.r-pid').value   = p.id;
        row.querySelector('.r-price').value = p.price;
        row.querySelector('.r-cpk').value   = p.cpk || 1;
        
        row.querySelector('.r-qty').value     = 1;
        row.querySelector('.r-cartons').value = p.cpk > 0 ? (1/p.cpk).toFixed(3) : 0;
        row.querySelector('.r-drop').style.display = 'none';
        calc();

        // Auto-add new row if this is the last row
        const allRows = document.querySelectorAll('.edit-item-row');
        if (row === allRows[allRows.length - 1]) {
            addRow();
            setTimeout(() => {
                const newRows = document.querySelectorAll('.edit-item-row');
                newRows[newRows.length - 1].querySelector('.r-search').focus();
            }, 50);
        }
    }

    function calcFromPackaging(input) {
        const row     = input.closest('.edit-item-row');
        const cpk     = parseFloat(row.querySelector('.r-cpk').value) || 1;
        const cartons = parseFloat(row.querySelector('.r-cartons').value) || 0;
        row.querySelector('.r-qty').value = (cartons * cpk).toFixed(2);
        calc();
    }

    function calcFromQty(input) {
        const row = input.closest('.edit-item-row');
        const cpk = parseFloat(row.querySelector('.r-cpk').value) || 1;
        const qty = parseFloat(input.value) || 0;
        row.querySelector('.r-cartons').value = cpk > 0 ? (qty / cpk).toFixed(3) : 0;
        calc();
    }

    function syncDiscType() {
        document.getElementById('EDIT_DISC_TYPE').value = document.getElementById('EDIT_DISC_SEL').value;
    }

    function submit() {
        const form = document.getElementById('EDIT_FORM');
        if (!form.reportValidity()) return;
        syncDiscType();
        const btn = document.getElementById('EDIT_SAVE_BTN');
        document.getElementById('EDIT_SAVE_TXT').textContent = 'جاري الحفظ...';
        document.getElementById('EDIT_SPIN').classList.remove('d-none');
        btn.disabled = true;
        form.submit();
    }

    // Customer search
    const custSearch = document.getElementById('EDIT_CUST_SEARCH');
    const custIdEl   = document.getElementById('EDIT_CUST_ID');
    const custDrop   = document.getElementById('EDIT_CDROP');

    custSearch.addEventListener('input', () => {
        custIdEl.value = '';
        const q = custSearch.value.toLowerCase().trim();
        const results = q.length < 1 ? [] : EDIT_CUSTOMERS.filter(c => c.name.toLowerCase().includes(q)).slice(0, 10);
        custDrop.innerHTML = '';
        if (results.length > 0) {
            results.forEach(c => {
                const li = document.createElement('li');
                li.innerHTML = `<span>${c.name}</span><span class="badge bg-light text-dark border small">${c.type==='wholesale'?'جملة':'تجزئة'}</span>`;
                li.onmousedown = (e) => { e.preventDefault(); custSearch.value = c.name; custIdEl.value = c.id; custDrop.style.display = 'none'; };
                custDrop.appendChild(li);
            });
            custDrop.style.display = 'block';
        } else {
            custDrop.style.display = 'none';
        }
    });
    custSearch.addEventListener('focus', () => { if (custDrop.children.length) custDrop.style.display = 'block'; });
    custSearch.addEventListener('blur',  () => { setTimeout(() => custDrop.style.display='none', 250); });

    // Init is handled outside to prevent double firing

    return { addRow, removeRow, filterProds, openDrop, closeDrop, calcFromPackaging, calcFromQty, calc, syncDiscType, submit };
})();

/* Guard: run exactly once after DOM is ready */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function _invEditInit() {
        document.removeEventListener('DOMContentLoaded', _invEditInit);
        if (!document.getElementById('EDIT_ROWS')._initialized) {
            document.getElementById('EDIT_ROWS')._initialized = true;
            EDIT_INIT_ITEMS.forEach(item => EDIT.addRow(item));
            if(EDIT_INIT_ITEMS.length === 0) EDIT.addRow();
        }
    });
} else {
    if (!document.getElementById('EDIT_ROWS')._initialized) {
        document.getElementById('EDIT_ROWS')._initialized = true;
        EDIT_INIT_ITEMS.forEach(item => EDIT.addRow(item));
        if(EDIT_INIT_ITEMS.length === 0) EDIT.addRow();
    }
}

// Keyboard shortcuts for adding rows
document.addEventListener('keydown', function(e) {
    if ((e.altKey && e.code === 'KeyA') || e.code === 'Insert' || (e.altKey && e.code === 'Equal')) {
        e.preventDefault();
        EDIT.addRow();
    }
});
</script>
@endpush

@endsection
