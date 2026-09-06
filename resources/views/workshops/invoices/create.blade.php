@extends('layouts.app')
@section('title', 'فاتورة ورشة مزدوجة | مصنع المنتجات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="bi bi-receipt text-primary me-2"></i> إنشاء فاتورة ورشة مزدوجة (منصرف / وارد)</h3>
        <a href="{{ route('workshops.index') }}" class="btn-glass-secondary">عودة</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger fw-bold">{{ session('error') }}</div>
    @endif

    <form action="{{ route('workshops.invoices.store') }}" method="POST" id="invoiceForm">
        @csrf
        
        <div class="card glass-panel border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الورشة الخارجية <span class="text-danger">*</span></label>
                        <select name="workshop_id" class="form-select select2" required>
                            <option value="">-- اختر الورشة --</option>
                            @foreach($workshops as $workshop)
                                <option value="{{ $workshop->id }}" {{ request('workshop_id') == $workshop->id ? 'selected' : '' }}>
                                    {{ $workshop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">تاريخ الفاتورة <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- قسم المنصرف للورشة (خامات) -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-danger bg-opacity-10 border-0 py-3">
                        <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-box-arrow-up-right me-2"></i> منصرف للورشة (مبيعات خامات)</h5>
                        <small class="text-muted">هذه العناصر ستسحب من مخزوننا وتضاف كدين على الورشة</small>
                    </div>
                    <div class="card-body p-3">
                        <table class="table table-sm text-center align-middle" id="materialsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">الخامة</th>
                                    <th width="20%">الكمية</th>
                                    <th width="25%">السعر</th>
                                    <th width="10%">الإجمالي</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows appended by JS -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="addMaterialRow()">
                            <i class="bi bi-plus-lg"></i> إضافة خامة
                        </button>
                    </div>
                    <div class="card-footer bg-white border-0 text-start">
                        <h5 class="fw-bold text-danger">إجمالي المنصرف: <span id="totalMaterials">0.00</span> ج.م</h5>
                    </div>
                </div>
            </div>

            <!-- قسم الوارد من الورشة (منتجات) -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                        <h5 class="mb-0 fw-bold text-success"><i class="bi bi-box-arrow-in-down-left me-2"></i> وارد من الورشة (مشتريات منتجات)</h5>
                        <small class="text-muted">هذه العناصر ستضاف لمخزوننا وتخصم من دين الورشة</small>
                    </div>
                    <div class="card-body p-3">
                        <table class="table table-sm text-center align-middle" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">المنتج</th>
                                    <th width="20%">الكمية (بالحبة)</th>
                                    <th width="25%">السعر (للحبة)</th>
                                    <th width="10%">الإجمالي</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows appended by JS -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="addProductRow()">
                            <i class="bi bi-plus-lg"></i> إضافة منتج
                        </button>
                    </div>
                    <div class="card-footer bg-white border-0 text-start">
                        <h5 class="fw-bold text-success">إجمالي الوارد: <span id="totalProducts">0.00</span> ج.م</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="card glass-panel border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center border-end">
                        <h6 class="text-muted fw-bold">الصافي (منصرف - وارد)</h6>
                        <h2 class="fw-bold mb-0" id="netAmountDisplay">0.00</h2>
                        <span id="netAmountLabel" class="badge bg-secondary mt-2">لا يوجد فرق</span>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">المبلغ المدفوع (نقداً الآن)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control form-control-lg text-center fw-bold" value="0" oninput="calculateTotals()">
                            <span class="input-group-text">ج.م</span>
                        </div>
                        <small class="text-muted d-block mt-1">يُطرح هذا المبلغ من الصافي.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">ملاحظات الفاتورة</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-0 py-3 text-center">
                <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 rounded-pill shadow-sm" id="submitBtn">
                    <i class="bi bi-save me-2"></i> حفظ الفاتورة وترحيلها
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    const rawMaterials = @json($rawMaterials);
    const products = @json($products);
    
    let matIndex = 0;
    let prodIndex = 0;

    function addMaterialRow() {
        let options = '<option value="">اختر خامة</option>';
        rawMaterials.forEach(m => {
            options += `<option value="${m.id}" data-price="0">${m.name}</option>`;
        });

        const row = `
            <tr id="matRow${matIndex}">
                <td>
                    <select name="materials[${matIndex}][id]" class="form-select form-select-sm" required>
                        ${options}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="materials[${matIndex}][quantity]" class="form-control form-control-sm text-center mat-qty" required oninput="calculateTotals()">
                </td>
                <td>
                    <input type="number" step="0.01" name="materials[${matIndex}][price]" class="form-control form-control-sm text-center mat-price" required oninput="calculateTotals()">
                </td>
                <td class="fw-bold mat-total">0.00</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger rounded-circle" onclick="document.getElementById('matRow${matIndex}').remove(); calculateTotals();"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        document.querySelector('#materialsTable tbody').insertAdjacentHTML('beforeend', row);
        matIndex++;
    }

    function addProductRow() {
        let options = '<option value="">اختر منتج</option>';
        products.forEach(p => {
            options += `<option value="${p.id}" data-cages="${p.cages_per_carton}">${p.name}</option>`;
        });

        const row = `
            <tr id="prodRow${prodIndex}">
                <td>
                    <select name="products[${prodIndex}][id]" class="form-select form-select-sm prod-select" required onchange="updateCartonInfo(this, ${prodIndex})">
                        ${options}
                    </select>
                    <small class="text-muted carton-info d-block mt-1" id="cartonInfo${prodIndex}"></small>
                </td>
                <td>
                    <input type="number" step="1" name="products[${prodIndex}][quantity]" class="form-control form-control-sm text-center prod-qty" required oninput="updateCartonInfo(this, ${prodIndex}); calculateTotals();">
                </td>
                <td>
                    <input type="number" step="0.01" name="products[${prodIndex}][price]" class="form-control form-control-sm text-center prod-price" required oninput="calculateTotals()">
                </td>
                <td class="fw-bold prod-total">0.00</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger rounded-circle" onclick="document.getElementById('prodRow${prodIndex}').remove(); calculateTotals();"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        document.querySelector('#productsTable tbody').insertAdjacentHTML('beforeend', row);
        prodIndex++;
    }

    function updateCartonInfo(element, index) {
        const row = document.getElementById(`prodRow${index}`);
        const select = row.querySelector('.prod-select');
        const qtyInput = row.querySelector('.prod-qty');
        const infoDiv = document.getElementById(`cartonInfo${index}`);
        
        if(select.selectedIndex > 0) {
            const cagesPerCarton = parseFloat(select.options[select.selectedIndex].dataset.cages) || 1;
            const qty = parseFloat(qtyInput.value) || 0;
            const cartons = (qty / cagesPerCarton).toFixed(2);
            infoDiv.innerHTML = `<i class="bi bi-box"></i> ${cartons} كرتونة (الكرتونة = ${cagesPerCarton})`;
        } else {
            infoDiv.innerHTML = '';
        }
    }

    function calculateTotals() {
        let matSum = 0;
        document.querySelectorAll('[id^="matRow"]').forEach(row => {
            const qty = parseFloat(row.querySelector('.mat-qty').value) || 0;
            const price = parseFloat(row.querySelector('.mat-price').value) || 0;
            const total = qty * price;
            row.querySelector('.mat-total').innerText = total.toFixed(2);
            matSum += total;
        });
        document.getElementById('totalMaterials').innerText = matSum.toFixed(2);

        let prodSum = 0;
        document.querySelectorAll('[id^="prodRow"]').forEach(row => {
            const qty = parseFloat(row.querySelector('.prod-qty').value) || 0;
            const price = parseFloat(row.querySelector('.prod-price').value) || 0;
            const total = qty * price;
            row.querySelector('.prod-total').innerText = total.toFixed(2);
            prodSum += total;
        });
        document.getElementById('totalProducts').innerText = prodSum.toFixed(2);

        const netAmount = matSum - prodSum;
        const display = document.getElementById('netAmountDisplay');
        const label = document.getElementById('netAmountLabel');
        
        display.innerText = Math.abs(netAmount).toFixed(2);
        
        if (netAmount > 0) {
            display.className = 'fw-bold mb-0 text-danger';
            label.className = 'badge bg-danger mt-2';
            label.innerText = 'الورشة مديونة لنا';
        } else if (netAmount < 0) {
            display.className = 'fw-bold mb-0 text-success';
            label.className = 'badge bg-success mt-2';
            label.innerText = 'نحن مديونون للورشة';
        } else {
            display.className = 'fw-bold mb-0 text-dark';
            label.className = 'badge bg-secondary mt-2';
            label.innerText = 'لا يوجد فرق';
        }
    }

    // Add initial row for both
    addMaterialRow();
    addProductRow();
</script>
@endsection
