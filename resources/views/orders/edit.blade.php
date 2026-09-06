@extends('layouts.app')
@section('title', 'تعديل الطلبية ' . $order->order_number)

@push('styles')
<style>
.inv-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:16px;
    padding:1.4rem;margin-bottom:1.2rem;box-shadow:0 2px 10px rgba(0,0,0,.05);
    overflow:visible !important;
}
.inv-card-title {
    font-size:1rem;font-weight:800;color:#1e293b;
    border-bottom:2px solid #f1f5f9;padding-bottom:.6rem;
    margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;
}
.item-row {
    display:flex;flex-wrap:wrap;gap:8px;align-items:center;
    background:#f8fafc;border:1.5px solid #e2e8f0;
    border-radius:12px;padding:10px;margin-bottom:8px;
    position:relative;z-index:1;overflow:visible !important;
}
.item-row:focus-within { border-color:#ea580c;background:#fffbf8;z-index:10; }
.product-wrap { flex:1 1 100%;min-width:0;position:relative; }
.r-col { flex: 1 1 calc(33.333% - 8px); min-width:60px; }
.r-col-action { flex: 1 1 100%; text-align:center; margin-top: 8px; border-top:1px dashed #e2e8f0; padding-top:8px;}
@media (min-width: 768px) {
    .item-row { flex-wrap:nowrap; }
    .product-wrap { flex:3 1 auto; }
    .r-col { flex: 1 1 0%; }
    .r-col-action { flex: 0 0 34px; border:none; margin:0; padding:0; }
}
.pdrop {
    display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
    background:#fff;border:1.5px solid #ea580c;border-radius:12px;
    box-shadow:0 8px 24px rgba(0,0,0,.15);max-height:220px;
    overflow-y:auto;z-index:99999;list-style:none;padding:0;margin:0;
}
.pdrop li {
    padding:10px 14px;cursor:pointer;display:flex;
    justify-content:space-between;align-items:center;
    border-bottom:1px solid #f1f5f9;font-weight:600;
}
.pdrop li:last-child { border-bottom:none; }
.pdrop li:hover { background:#fff8f4;color:#ea580c; }
.cdrop {
    display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
    background:#fff;border:1.5px solid #0d6efd;border-radius:12px;
    box-shadow:0 8px 24px rgba(0,0,0,.15);max-height:250px;
    overflow-y:auto;z-index:99999;list-style:none;padding:0;margin:0;
}
.cdrop li {
    padding:12px 16px;cursor:pointer;display:flex;
    justify-content:space-between;border-bottom:1px solid #f1f5f9;font-weight:600;
}
.cdrop li:last-child { border-bottom:none; }
.cdrop li:hover { background:#eff6ff;color:#0d6efd; }
.ni {
    font-size:1.05rem !important;font-weight:800 !important;
    text-align:center !important;border:1.5px solid #cbd5e1;
    border-radius:10px;padding:7px 5px;width:100%;background:#fff;
}
.ni:focus { border-color:#ea580c;outline:none;box-shadow:0 0 0 3px rgba(234,88,12,.12);background:#fffbf8; }
.summary-box {
    background:linear-gradient(135deg,#1e293b,#0f172a);
    color:#fff;border-radius:16px;padding:1.4rem;position:sticky;top:80px;
}
.srow {
    display:flex;justify-content:space-between;align-items:center;
    padding:8px 0;border-bottom:1px solid rgba(255,255,255,.08);
}
.slabel { color:rgba(255,255,255,.7);font-weight:600;font-size:.9rem; }
.sval { font-weight:800;font-size:1rem; }
.grand-box {
    background:rgba(234,88,12,.15);border-radius:12px;
    padding:14px 16px;margin-top:12px;
    display:flex;justify-content:space-between;align-items:center;
}
.grand-label { color:#fff;font-weight:700;font-size:1.1rem; }
.grand-val { color:#f97316;font-size:1.8rem;font-weight:900; }
.save-btn {
    background:linear-gradient(135deg,#ea580c,#c2410c);color:#fff;
    border:none;border-radius:14px;padding:16px;font-size:1.2rem;
    font-weight:800;width:100%;cursor:pointer;margin-top:16px;transition:all .2s;
}
.save-btn:hover { transform:translateY(-2px);box-shadow:0 8px 20px rgba(234,88,12,.35); }
.sin { background:#1e3a5f;border:1px solid #334155;border-radius:8px;color:#f87171;width:100%; }
.sin:focus { outline:none;border-color:#f97316; }
</style>
@endpush

@section('content')

@if($errors->any())
<div class="alert alert-danger border-0 rounded-3 mb-4 shadow-sm">
    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>يوجد أخطاء:</strong>
    <ul class="mb-0 mt-2 ps-3">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@if($order->converted_to_invoice)
    <div class="alert alert-warning bg-opacity-10 border-warning text-warning fw-bold mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i> تم تحويل هذه الطلبية إلى فاتورة — التعديل غير متاح.
    </div>
@else
<form action="{{ route('orders.update', $order) }}" method="POST" id="ORD_FORM">
@csrf
@method('PUT')

    <div class="inv-card mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-gear-fill text-primary me-2"></i>إجراءات الطلبية</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('orders.print', $order) }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i> طباعة</a>
            
            @if($order->status === 'pending' || $order->status === 'in_progress')
                <button type="button" class="btn btn-outline-success" onclick="document.getElementById('completeForm').submit();"><i class="bi bi-check-circle"></i> مكتملة</button>
            @endif
            
            @if(($order->status === 'pending' || $order->status === 'in_progress') && $order->loading_status !== 'loading' && $order->loading_status !== 'loaded' && !$order->converted_to_invoice)
                <button type="button" class="btn btn-outline-warning fw-bold" onclick="document.getElementById('loadingForm').submit();"><i class="bi bi-truck-flatbed"></i> إرسال للتحميل</button>
            @endif
            
            @if($order->loading_status === 'loading')
                <a href="{{ route('loading.show', $order) }}" class="btn btn-warning fw-bold"><i class="bi bi-truck-flatbed"></i> قيد التحميل</a>
            @endif
            
            @if(($order->status === 'completed' || $order->status === 'awaiting_approval') && !$order->converted_to_invoice)
                <button type="button" class="btn btn-outline-info" onclick="document.getElementById('invoiceForm').submit();"><i class="bi bi-file-earmark-arrow-up"></i> تحويل لفاتورة</button>
            @endif
            
            @if(!$order->converted_to_invoice)
                <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('deleteForm').submit();"><i class="bi bi-trash"></i> حذف الطلبية</button>
            @endif
        </div>
    </div>

<div class="row g-3">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Customer --}}
        <div class="inv-card">
            <div class="inv-card-title"><i class="bi bi-person-fill text-primary"></i> بيانات العميل والطلبية</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">اسم العميل <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="ORD_CUST_SEARCH" name="customer_name" autocomplete="off"
                               class="form-control fw-bold" style="border-radius:10px;"
                               value="{{ $order->customer_name ?? ($order->customer->name ?? '') }}"
                               placeholder="ابحث أو اكتب اسم العميل الجديد..." required>
                        <input type="hidden" name="customer_id" id="ORD_CUST_ID" value="{{ $order->customer_id ?? '' }}">
                        <ul class="cdrop" id="ORD_CDROP"></ul>
                    </div>
                    <div id="ORD_CUST_BAL" class="mt-2 p-2 rounded-3 small fw-bold d-none"></div>
                    <div id="ORD_CUST_NEW" class="mt-2 small text-success fw-bold d-none">
                        <i class="bi bi-stars me-1"></i> سيتم إضافة عميل جديد تلقائياً
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">تاريخ الطلبية <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" value="{{ $order->order_date->format('Y-m-d') }}" class="form-control" style="border-radius:10px;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">التسليم المتوقع</label>
                    <input type="date" name="expected_date" class="form-control" style="border-radius:10px;" value="{{ $order->expected_date?->format('Y-m-d') }}">
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">تاريخ التسليم الفعلي</label>
                    <input type="date" name="delivery_date" class="form-control" style="border-radius:10px;" value="{{ $order->delivery_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">عنوان التوصيل</label>
                    <input type="text" name="address" class="form-control" style="border-radius:10px;" placeholder="أدخل العنوان للتوصيل..." value="{{ $order->address }}">
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="inv-card" style="overflow:visible !important;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="inv-card-title mb-0"><i class="bi bi-cart-check-fill text-success"></i> الأصناف المطلوبة</div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3" onclick="ORD.addRow()">
                    <i class="bi bi-plus-lg me-1"></i> إضافة صنف
                </button>
            </div>
            <div class="d-none d-md-flex gap-2 mb-2" style="font-size:.72rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;">
                <div style="flex:3;" class="ps-2">المنتج</div>
                <div style="flex:1;" class="text-center">كراتين</div>
                <div style="flex:1;" class="text-center">عبوة</div>
                <div style="flex:1;" class="text-center">الكمية</div>
                <div style="flex:1;" class="text-center">السعر</div>
                <div style="flex:1;" class="text-center">الإجمالي</div>
                <div style="flex:0 0 36px;"></div>
            </div>
            <div id="ORD_ROWS"></div>
        </div>

        {{-- Notes --}}
        <div class="inv-card">
            <div class="inv-card-title"><i class="bi bi-card-text text-secondary"></i> ملاحظات</div>
            <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;" placeholder="ملاحظات تظهر على الطلبية...">{{ $order->notes }}</textarea>
        </div>
    </div>

    {{-- RIGHT --}}
        <div class="summary-box">
            <h6 class="fw-bold text-white mb-4" style="font-size:1rem;">
                <i class="bi bi-calculator me-2" style="color:#f97316;"></i>الملخص المالي
            </h6>

            <div class="srow">
                <span class="slabel">إجمالي الأصناف</span>
                <span class="sval" id="S_SUB">٠٫٠٠</span>
            </div>

            <div class="grand-box mt-3">
                <span class="grand-label">الإجمالي الكلي</span>
                <span class="grand-val" id="S_GRAND">٠٫٠٠</span>
            </div>

            <div class="mt-4">
                <label class="slabel small mb-1">مبلغ العربون المدفوع</label>
                <input type="number" name="paid_deposit" id="ORD_PAID" min="0" step="0.01"
                       class="form-control fw-bold text-center sin"
                       style="font-size:1.4rem;font-weight:900;border-radius:12px;color:#fff;text-align:center;"
                       value="{{ $order->paid_deposit }}" placeholder="0.00" readonly>
                <div class="small mt-2 text-warning"><i class="bi bi-info-circle"></i> العربون المدفوع مسجل مسبقاً. لإضافة دفعات جديدة، يرجى استخدام سندات القبض.</div>
            </div>
            <div class="srow mt-3">
                <span class="slabel">المتبقي</span>
                <span class="sval text-danger" id="S_DUE">٠٫٠٠</span>
            </div>
            <button type="button" id="ORD_SUBMIT_BTN" onclick="ORD.submit()" class="save-btn mt-4">
                <i class="bi bi-check2-circle me-2"></i>
                <span id="ORD_BTN_TXT">تحديث الطلبية</span>
                <span class="spinner-border spinner-border-sm d-none ms-2" id="ORD_SPIN"></span>
            </button>
        </div>
    </div>

</div>
</form>
@endif

@if($order->status === 'pending' || $order->status === 'in_progress')
<form id="completeForm" action="{{ route('orders.status', $order) }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="status" value="completed">
</form>
@endif

@if(($order->status === 'pending' || $order->status === 'in_progress') && $order->loading_status !== 'loading' && $order->loading_status !== 'loaded' && !$order->converted_to_invoice)
<form id="loadingForm" action="{{ route('orders.send-to-loading', $order) }}" method="POST" class="d-none">
    @csrf
</form>
@endif

@if(($order->status === 'completed' || $order->status === 'awaiting_approval') && !$order->converted_to_invoice)
<form id="invoiceForm" action="{{ route('orders.invoice', $order) }}" method="POST" class="d-none">
    @csrf
</form>
@endif

@if(!$order->converted_to_invoice)
<form id="deleteForm" action="{{ route('orders.destroy', $order) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endif

@push('scripts')
<script>
const ORD_INIT_ITEMS = {!! json_encode($order->items->map(function($i) {
    return [
        'product_id' => $i->product_id,
        'search'     => $i->product_name ?? ($i->product->name ?? ''),
        'cartons'    => '', 
        'cpk'        => $i->product ? ($i->product->cages_per_carton ?? 1) : 1,
        'qty'        => $i->quantity,
        'price'      => $i->unit_price,
    ];
})) !!};
const ORD_PRODUCTS  = {!! json_encode($products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code,'price'=>(float)($p->selling_price??0),'cpk'=>(float)($p->cages_per_carton??1)])) !!};
const ORD_CUSTOMERS = {!! json_encode($customers->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'type'=>$c->type])) !!};

const ORD = (() => {
    let rowCount = 0;

    function fmt(v) {
        return Number(parseFloat(v)||0).toLocaleString('ar-EG',{minimumFractionDigits:2,maximumFractionDigits:2});
    }

    function calc() {
        let sub = 0;
        document.querySelectorAll('.ord-item-row').forEach(row => {
            const qty   = parseFloat(row.querySelector('.r-qty').value)  || 0;
            const price = parseFloat(row.querySelector('.r-price').value) || 0;
            const rt    = Math.max(0, qty * price);
            row.querySelector('.r-total').textContent = fmt(rt);
            sub += rt;
        });

        const paidNow  = parseFloat(document.getElementById('ORD_PAID').value) || 0;
        const grand = sub;
        const due   = Math.max(0, grand - paidNow);

        document.getElementById('S_SUB').textContent   = fmt(sub);
        document.getElementById('S_GRAND').textContent = fmt(grand);
        document.getElementById('S_DUE').textContent   = fmt(due);
    }

    function addRow(data) {
        rowCount++;
        const idx = rowCount;
        const d   = data || {};

        const row = document.createElement('div');
        row.className = 'item-row ord-item-row';
        row.dataset.idx = idx;
        row.innerHTML = `
            <div class="product-wrap">
                <input type="text" class="ni r-search" placeholder="ابحث عن منتج..." autocomplete="off"
                       value="${d.search||''}"
                       onfocus="ORD.openDrop(this)"
                       onblur="ORD.closeDrop(this)"
                       oninput="ORD.filterProds(this)" required>
                <input type="hidden" class="r-pid" name="items[${idx}][product_id]" value="${d.product_id||''}">
                <ul class="pdrop r-drop"></ul>
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">كراتين</label>
                <input type="number" class="ni text-info r-cartons" placeholder="0" min="0" step="any" value="${d.cartons||''}"
                       oninput="ORD.calcFromPackaging(this)">
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">عبوة</label>
                <input type="number" class="ni text-secondary r-cpk" placeholder="1" min="0.01" step="any" value="${d.cpk||1}"
                       oninput="ORD.calcFromPackaging(this)">
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">كمية</label>
                <input type="number" class="ni r-qty" name="items[${idx}][quantity]" placeholder="1" min="0.01" step="any" value="${d.qty||1}"
                       oninput="ORD.calcFromQty(this)" required>
            </div>
            <div class="r-col">
                <label class="d-md-none small text-muted mb-1">سعر</label>
                <input type="number" class="ni text-success r-price" name="items[${idx}][unit_price]" placeholder="0" min="0" step="0.01" value="${d.price||0}"
                       oninput="ORD.calc()">
            </div>
            <div class="r-col d-flex flex-column justify-content-center align-items-center">
                <label class="d-md-none small text-muted mb-1">الإجمالي</label>
                <span class="fw-bold text-dark r-total" style="font-size:1.1rem;">٠٫٠٠</span>
            </div>
            <div class="r-col-action">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill border-0 px-3 w-100 d-md-none"
                        onclick="ORD.removeRow(this)">
                    <i class="bi bi-trash"></i> حذف الصنف
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle border-0 p-1 d-none d-md-inline-block"
                        onclick="ORD.removeRow(this)" title="حذف">
                    <i class="bi bi-trash" style="font-size:.8rem;"></i>
                </button>
            </div>`;
        document.getElementById('ORD_ROWS').appendChild(row);
        calc();
        return row;
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.ord-item-row');
        if (rows.length <= 1) {
            const row = rows[0];
            row.querySelector('.r-search').value  = '';
            row.querySelector('.r-pid').value      = '';
            row.querySelector('.r-cartons').value  = '';
            row.querySelector('.r-cpk').value      = '1';
            row.querySelector('.r-qty').value      = '1';
            row.querySelector('.r-price').value    = '0';
            row.querySelector('.r-total').textContent = '٠٫٠٠';
            calc();
            return;
        }
        btn.closest('.ord-item-row').remove();
        calc();
    }

    function filterProds(input) {
        const q    = input.value.toLowerCase().trim();
        const drop = input.closest('.product-wrap').querySelector('.r-drop');
        const pid  = input.closest('.product-wrap').querySelector('.r-pid');
        pid.value  = ''; 

        const results = q.length < 1
            ? ORD_PRODUCTS.slice(0, 12)
            : ORD_PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || (p.code && p.code.toLowerCase().includes(q))).slice(0, 12);

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
        const row = searchInput.closest('.ord-item-row');
        searchInput.value = p.name;
        row.querySelector('.r-pid').value   = p.id;
        row.querySelector('.r-price').value = p.price;
        row.querySelector('.r-cpk').value   = p.cpk || 1;
        
        row.querySelector('.r-qty').value     = 1;
        row.querySelector('.r-cartons').value = p.cpk > 0 ? (1/p.cpk).toFixed(3) : 0;
        row.querySelector('.r-drop').style.display = 'none';
        calc();

        const allRows = document.querySelectorAll('.ord-item-row');
        if (row === allRows[allRows.length - 1]) {
            addRow();
            setTimeout(() => {
                const newRows = document.querySelectorAll('.ord-item-row');
                newRows[newRows.length - 1].querySelector('.r-search').focus();
            }, 50);
        }
    }

    function calcFromPackaging(input) {
        const row     = input.closest('.ord-item-row');
        const cpk     = parseFloat(row.querySelector('.r-cpk').value) || 1;
        const cartons = parseFloat(row.querySelector('.r-cartons').value) || 0;
        row.querySelector('.r-qty').value = (cartons * cpk).toFixed(2);
        calc();
    }

    function calcFromQty(input) {
        const row = input.closest('.ord-item-row');
        const cpk = parseFloat(row.querySelector('.r-cpk').value) || 1;
        const qty = parseFloat(input.value) || 0;
        row.querySelector('.r-cartons').value = cpk > 0 ? (qty / cpk).toFixed(3) : 0;
        calc();
    }

    function submit() {
        const form = document.getElementById('ORD_FORM');
        if (!form.reportValidity()) return;
        const btn = document.getElementById('ORD_SUBMIT_BTN');
        document.getElementById('ORD_BTN_TXT').textContent = 'جاري الحفظ...';
        document.getElementById('ORD_SPIN').classList.remove('d-none');
        btn.disabled = true;
        form.submit();
    }

    const custSearch = document.getElementById('ORD_CUST_SEARCH');
    const custIdEl   = document.getElementById('ORD_CUST_ID');
    const custDrop   = document.getElementById('ORD_CDROP');
    const custBal    = document.getElementById('ORD_CUST_BAL');
    const custNew    = document.getElementById('ORD_CUST_NEW');

    custSearch.addEventListener('input', () => {
        custIdEl.value = '';
        custBal.classList.add('d-none');
        const q = custSearch.value.toLowerCase().trim();
        const results = q.length < 1 ? [] : ORD_CUSTOMERS.filter(c => c.name.toLowerCase().includes(q)).slice(0, 10);
        custDrop.innerHTML = '';
        if (results.length > 0) {
            results.forEach(c => {
                const li = document.createElement('li');
                li.innerHTML = `<span>${c.name}</span><span class="badge bg-light text-dark border small">${c.type==='wholesale'?'جملة':'قطاعي'}</span>`;
                li.onmousedown = (e) => { e.preventDefault(); pickCustomer(c); };
                custDrop.appendChild(li);
            });
            custDrop.style.display = 'block';
        } else {
            custDrop.style.display = 'none';
        }
        custNew.classList.toggle('d-none', !(q.length > 1 && results.length === 0));
    });
    custSearch.addEventListener('focus', () => { if (custDrop.children.length) custDrop.style.display = 'block'; });
    custSearch.addEventListener('blur', () => { setTimeout(() => custDrop.style.display='none', 250); });

    function pickCustomer(c) {
        custSearch.value   = c.name;
        custIdEl.value     = c.id;
        custDrop.style.display = 'none';
        custNew.classList.add('d-none');
        fetch(`/customers/${c.id}/balance`)
            .then(r => r.json())
            .then(d => {
                const bal = d.balance;
                if (bal > 0) {
                    custBal.className = 'mt-2 p-2 rounded-3 small fw-bold bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                    custBal.innerHTML = `<i class="bi bi-wallet2 me-1"></i>مديونية: ${Number(bal).toLocaleString('ar-EG')} ج.م`;
                } else {
                    custBal.className = 'mt-2 p-2 rounded-3 small fw-bold bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                    custBal.innerHTML = `<i class="bi bi-wallet2 me-1"></i>رصيد له: ${Number(Math.abs(bal)).toLocaleString('ar-EG')} ج.م`;
                }
                custBal.classList.remove('d-none');
            })
            .catch(() => {});
    }

    return { addRow, removeRow, filterProds, openDrop, closeDrop, calcFromPackaging, calcFromQty, calc, submit };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function _ordInit() {
        document.removeEventListener('DOMContentLoaded', _ordInit);
        if (!document.getElementById('ORD_ROWS')._initialized) {
            document.getElementById('ORD_ROWS')._initialized = true;
            ORD_INIT_ITEMS.forEach(item => {
                const row = ORD.addRow(item);
                const cartonsInput = row.querySelector('.r-cartons');
                ORD.calcFromQty(row.querySelector('.r-qty')); // Automatically calculate cartons from qty
            });
            if(ORD_INIT_ITEMS.length === 0) ORD.addRow();
        }
    });
} else {
    if (!document.getElementById('ORD_ROWS')._initialized) {
        document.getElementById('ORD_ROWS')._initialized = true;
        ORD_INIT_ITEMS.forEach(item => {
            const row = ORD.addRow(item);
            ORD.calcFromQty(row.querySelector('.r-qty'));
        });
        if(ORD_INIT_ITEMS.length === 0) ORD.addRow();
    }
}

document.addEventListener('keydown', function(e) {
    if ((e.altKey && e.code === 'KeyA') || e.code === 'Insert' || (e.altKey && e.code === 'Equal')) {
        e.preventDefault();
        ORD.addRow();
    }
});

</script>
@endpush

@endsection
