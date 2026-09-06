@extends('layouts.app')
@section('title', 'تحميل طلبية حرة | مصنع المنتجات')
@section('page_title', 'تحميل سيارة (نقل حر بدون طلبية مسبقة)')

@section('content')
<div class="glass-card water-card mb-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1">تسجيل سيارة محملة (بدون طلبية)</h4>
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i> سيتم إنشاء طلبية جديدة وإرسالها للإدارة للفوترة</p>
        </div>
        <a href="{{ route('loading.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> رجوع</a>
    </div>

    <form action="{{ route('loading.store-adhoc') }}" method="POST" id="loadingForm">
        @csrf
        
        <div class="alert alert-info py-2 mb-4">
            <i class="bi bi-truck me-1"></i> أدخل الكميات التي تم تحميلها فعلياً في السيارة من المخزن.
        </div>

        <div class="mb-4">
            <label class="form-label text-muted small fw-bold">ملاحظات أو اسم السائق المؤقت (اختياري)</label>
            <textarea name="notes" class="form-control form-control-glass text-dark fw-bold" rows="2" placeholder="اكتب هنا أي ملاحظة للإدارة بخصوص هذه السيارة..."></textarea>
        </div>

        <div class="list-group border-0 shadow-sm rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px);">
            @foreach($products as $index => $product)
            @php
                $available = $product->inventory ? $product->inventory->current_stock : 0;
            @endphp
            <div class="list-group-item bg-transparent p-3 border-bottom border-secondary border-opacity-10">
                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    
                    <!-- Product Info -->
                    <div class="flex-grow-1">
                        <h6 class="fw-bold text-dark mb-2 fs-5"><i class="bi bi-box-seam-fill text-primary me-2"></i>{{ $product->name }}</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge {{ $available == 0 ? 'bg-danger text-white' : 'bg-success bg-opacity-10 text-success border border-success' }} px-2 py-1"><i class="bi bi-archive"></i> متاح في المخزن: <span class="fs-6">{{ $available }}</span></span>
                        </div>
                    </div>

                    <!-- Counter -->
                    <div class="d-flex flex-column align-items-center">
                        <small class="text-muted fw-bold mb-1 d-block">تم تحميله فعلياً</small>
                        <div class="input-group" style="width: 250px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden;">
                            <button type="button" class="btn btn-light border px-4 py-4 text-danger fw-bold fs-2" onclick="decrement('loaded_{{ $product->id }}')">-</button>
                            <input type="number" 
                                   name="items[{{ $index }}][loaded_quantity]" 
                                   id="loaded_{{ $product->id }}" 
                                   class="form-control text-center fw-bold text-primary fs-2 border-light py-4 save-state" 
                                   value="0" 
                                   min="0" 
                                   inputmode="numeric"
                                   onchange="saveToStorage()"
                                   onkeyup="saveToStorage()"
                                   required style="background: #fff;">
                            <button type="button" class="btn btn-light border px-4 py-4 text-success fw-bold fs-2" onclick="increment('loaded_{{ $product->id }}')">+</button>
                        </div>
                    </div>

                </div>
            </div>
            @endforeach
        </div>

        <!-- New Product Section -->
        <div class="glass-card water-card mb-4 p-3 border border-success border-opacity-25">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-plus-square-dotted me-2 text-success fs-5"></i>منتج غير موجود بالقائمة؟</h6>
            <div id="newProductsContainer">
                <!-- Javascript will add rows here -->
            </div>
            <button type="button" class="btn btn-outline-success btn-sm mt-2 fw-bold" onclick="addNewProductRow()">
                <i class="bi bi-plus"></i> إضافة منتج جديد
            </button>
        </div>

        <button type="submit" class="btn btn-glass w-100 py-3 fs-4 fw-bold mt-2" onclick="return confirmSubmit()">
            <i class="bi bi-send-fill me-2"></i> تأكيد وإنشاء الطلبية
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function increment(id) {
        let el = document.getElementById(id);
        let val = parseInt(el.value) || 0;
        el.value = val + 1;
        saveToStorage();
    }
    
    function decrement(id) {
        let el = document.getElementById(id);
        let val = parseInt(el.value) || 0;
        if(val > 0) {
            el.value = val - 1;
            saveToStorage();
        }
    }

    // --- New Products Logic ---
    let newProductIndex = 0;
    function addNewProductRow() {
        const container = document.getElementById('newProductsContainer');
        const html = `
            <div class="row g-2 mb-3 align-items-end new-product-row" id="new_prod_${newProductIndex}">
                <div class="col-8">
                    <label class="form-label text-muted small fw-bold">اسم المنتج الجديد</label>
                    <input type="text" name="new_products[${newProductIndex}][name]" class="form-control form-control-glass save-state-new-name" placeholder="مثال: منتج أحمر كبير" onchange="saveToStorage()" onkeyup="saveToStorage()">
                </div>
                <div class="col-4">
                    <label class="form-label text-muted small fw-bold">الكمية المحملة</label>
                    <input type="number" name="new_products[${newProductIndex}][quantity]" class="form-control form-control-glass text-center save-state-new-qty" value="0" min="0" onchange="saveToStorage()" onkeyup="saveToStorage()">
                </div>
                <div class="col-12 mt-1">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="removeProductRow('new_prod_${newProductIndex}')"><small><i class="bi bi-trash"></i> حذف هذا المنتج</small></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        newProductIndex++;
        saveToStorage();
    }

    function removeProductRow(id) {
        document.getElementById(id).remove();
        saveToStorage();
    }

    // --- Auto Save Logic ---
    function saveToStorage() {
        const data = {};
        
        // Save known products
        document.querySelectorAll('.save-state').forEach(input => {
            if(input.id && input.value > 0) {
                data[input.id] = input.value;
            }
        });

        // Save new products
        const newProductsData = [];
        document.querySelectorAll('.new-product-row').forEach(row => {
            const name = row.querySelector('.save-state-new-name').value;
            const qty = row.querySelector('.save-state-new-qty').value;
            if(name || qty > 0) {
                newProductsData.push({name, qty});
            }
        });
        
        data['newProducts'] = newProductsData;
        data['notes'] = document.querySelector('textarea[name="notes"]').value;

        localStorage.setItem('adhoc_draft', JSON.stringify(data));
    }

    function loadFromStorage() {
        const savedData = localStorage.getItem('adhoc_draft');
        if(savedData) {
            try {
                const data = JSON.parse(savedData);
                
                // Restore known products
                for(const key in data) {
                    if(key !== 'newProducts' && key !== 'notes') {
                        const el = document.getElementById(key);
                        if(el) el.value = data[key];
                    }
                }
                
                // Restore notes
                if(data.notes) {
                    document.querySelector('textarea[name="notes"]').value = data.notes;
                }

                // Restore new products
                if(data.newProducts && data.newProducts.length > 0) {
                    data.newProducts.forEach(prod => {
                        addNewProductRow();
                        const lastRow = document.querySelector('.new-product-row:last-child');
                        if(lastRow) {
                            lastRow.querySelector('.save-state-new-name').value = prod.name;
                            lastRow.querySelector('.save-state-new-qty').value = prod.qty;
                        }
                    });
                }
            } catch(e) {
                console.error("Error loading draft", e);
            }
        }
    }

    window.addEventListener('load', loadFromStorage);
    document.querySelector('textarea[name="notes"]').addEventListener('change', saveToStorage);
    document.querySelector('textarea[name="notes"]').addEventListener('keyup', saveToStorage);

    function confirmSubmit() {
        if(confirm('هل أنت متأكد من حفظ الحمولة وتأكيدها للإدارة؟')) {
            // Clear storage on successful submission intention
            localStorage.removeItem('adhoc_draft');
            return true;
        }
        return false;
    }
</script>
@endpush

