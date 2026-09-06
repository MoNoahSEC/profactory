@extends('layouts.app')
@section('title', 'فاتورة وهمية | مصنع المنتجات')
@section('page_title', 'إصدار فاتورة وهمية (للطباعة فقط)')

@section('content')
<form action="{{ route('invoices.printFake') }}" method="POST" target="_blank">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-receipt me-2 text-primary"></i>بيانات الفاتورة</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">العميل <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-control form-control-glass" required>
                            <option value="">اختر عميل</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->type == 'wholesale' ? 'جملة' : 'تجزئة' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">تاريخ الفاتورة <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" class="form-control form-control-glass" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">العملة</label>
                        <input type="text" id="invCurrency" name="currency" class="form-control form-control-glass" value="ج.م" oninput="calcTotals()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" class="form-control form-control-glass">
                    </div>
                </div>
            </div>

            <div class="glass-card mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-list-ul me-2 text-primary"></i>بنود الفاتورة</h5>
                <div id="invoiceItems">
                    <div class="row g-2 align-items-end mb-2 item-row">
                        <div class="col-md-4">
                            <label class="form-label text-muted text-sm">المنتج</label>
                            <select name="items[0][product_id]" class="form-control form-control-glass product-select" required onchange="setPrice(this)">
                                <option value="">اختر منتج</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-price="{{ $p->selling_price }}">{{ $p->code }} — {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted text-sm">الكمية</label>
                            <input type="number" name="items[0][quantity]" class="form-control form-control-glass" min="1" value="1" required oninput="calcRow(this)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted text-sm">سعر الوحدة</label>
                            <input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-glass" required oninput="calcRow(this)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted text-sm">خصم</label>
                            <input type="number" step="0.01" name="items[0][discount]" value="0" class="form-control form-control-glass" oninput="calcRow(this)">
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 border-0" onclick="this.closest('.item-row').remove();">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-glass-secondary px-3 py-2 rounded-3 mt-2" onclick="addItem()">
                    <i class="bi bi-plus-circle me-1"></i> إضافة بند
                </button>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-cash me-2 text-primary"></i>الملخص المالي</h5>
                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                    <span class="text-muted">المجموع الفرعي</span>
                    <span class="fw-bold" id="subtotalDisplay">0.00 ج.م</span>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-muted">نوع الخصم</label>
                        <select name="discount_type" class="form-control form-control-glass" onchange="calcTotals()">
                            <option value="amount">مبلغ ثابت</option>
                            <option value="percent">نسبة مئوية</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">قيمة الخصم</label>
                        <input type="number" step="0.01" name="discount_value" value="0" class="form-control form-control-glass" oninput="calcTotals()">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted">نسبة الضريبة %</label>
                        <input type="number" step="0.01" name="tax_rate" value="0" class="form-control form-control-glass" oninput="calcTotals()">
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">الخصم</span>
                        <span class="fw-bold text-danger" id="discountDisplay">0.00 ج.م</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">الضريبة</span>
                        <span class="fw-bold" id="taxDisplay">0.00 ج.م</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">الإجمالي</span>
                        <span class="fw-bold fs-4 text-primary" id="grandTotalDisplay">0.00 ج.م</span>
                    </div>
                </div>
            </div>
            <div class="glass-card mb-4">
                <label class="form-label text-muted">ملاحظات</label>
                <textarea name="notes" class="form-control form-control-glass" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-warning btn-glass w-100 py-3 fs-5 text-dark fw-bold"><i class="bi bi-printer me-2"></i> طباعة الفاتورة الوهمية</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    let itemIndex = 1;
    function addItem() {
        const container = document.getElementById('invoiceItems');
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end mb-2 item-row';
        row.innerHTML = `
            <div class="col-md-4"><select name="items[${itemIndex}][product_id]" class="form-control form-control-glass product-select" required onchange="setPrice(this)"><option value="">اختر منتج</option>@foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->selling_price }}">{{ $p->code }} — {{ $p->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-glass" min="1" value="1" required oninput="calcRow(this)"></div>
            <div class="col-md-2"><input type="number" step="0.01" name="items[${itemIndex}][unit_price]" class="form-control form-control-glass" required oninput="calcRow(this)"></div>
            <div class="col-md-2"><input type="number" step="0.01" name="items[${itemIndex}][discount]" value="0" class="form-control form-control-glass" oninput="calcRow(this)"></div>
            <div class="col-md-2 text-end"><button type="button" class="btn btn-sm btn-outline-danger rounded-3 border-0" onclick="this.closest('.item-row').remove();"><i class="bi bi-trash"></i></button></div>
        `;
        container.appendChild(row);
        itemIndex++;
    }

    function setPrice(select) {
        const option = select.options[select.selectedIndex];
        const price = option.getAttribute('data-price');
        const row = select.closest('.item-row');
        const priceInput = row.querySelector('input[name*="unit_price"]');
        if (price && priceInput) priceInput.value = price;
        calcTotals();
    }

    function calcRow(input) { calcTotals(); }

    function calcTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name*="quantity"]')?.value) || 0;
            const price = parseFloat(row.querySelector('input[name*="unit_price"]')?.value) || 0;
            const discount = parseFloat(row.querySelector('input[name*="discount"]')?.value) || 0;
            subtotal += (qty * price) - discount;
        });

        const discountType = document.querySelector('select[name="discount_type"]')?.value || 'amount';
        const discountValue = parseFloat(document.querySelector('input[name="discount_value"]')?.value) || 0;
        const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]')?.value) || 0;

        let invoiceDiscount = discountType === 'percent' ? subtotal * (discountValue / 100) : discountValue;
        let afterDiscount = subtotal - invoiceDiscount;
        let tax = afterDiscount * (taxRate / 100);
        let grandTotal = afterDiscount + tax;

        const curr = document.getElementById('invCurrency')?.value || 'ج.م';
        const fmt = n => n.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + curr;
        document.getElementById('subtotalDisplay').textContent = fmt(subtotal);
        document.getElementById('discountDisplay').textContent = fmt(invoiceDiscount);
        document.getElementById('taxDisplay').textContent = fmt(tax);
        document.getElementById('grandTotalDisplay').textContent = fmt(grandTotal);
    }

    document.addEventListener('DOMContentLoaded', calcTotals);
</script>
@endpush
@endsection

