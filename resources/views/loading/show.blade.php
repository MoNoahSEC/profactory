@extends('layouts.app')
@section('title', 'تحميل طلبية | مصنع المنتجات')
@section('page_title', 'شاشة التحميل (موظف التحميل)')

@section('content')
<div class="glass-card water-card mb-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1">طلبية #{{ $order->order_number }}</h4>
            <p class="text-muted mb-0"><i class="bi bi-person me-1"></i> {{ $order->customer_name ?? $order->customer->name ?? '-' }}</p>
        </div>
        <a href="{{ route('loading.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> رجوع</a>
    </div>

    <form action="{{ route('loading.confirm', $order) }}" method="POST" id="loadingForm">
        @csrf
        
        <div class="alert alert-info py-2 mb-4">
            <i class="bi bi-info-circle-fill me-1"></i> يرجى إدخال الكمية <strong>الفعلية</strong> التي تم تحميلها في السيارة.
        </div>

        <div class="list-group border-0 shadow-sm rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px);">
            @foreach($order->items as $index => $item)
            @php
                $inventory = \App\Models\Inventory::where('product_id', $item->product_id)->first();
                $available = $inventory ? $inventory->current_stock : 0;
                $alreadyShipped = (int) $item->shipmentItems->sum('quantity');
                $maxQty = min($item->quantity, $available + $alreadyShipped);
                $defaultValue = max($item->loaded_quantity ?? 0, $alreadyShipped);
            @endphp
            <div class="list-group-item bg-transparent p-3 border-bottom border-secondary border-opacity-10">
                <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    
                    <!-- Product Info -->
                    <div class="flex-grow-1">
                        <h6 class="fw-bold text-dark mb-2 fs-5"><i class="bi bi-box-seam-fill text-primary me-2"></i>{{ $item->product->name }}</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-cart"></i> مطلوب: <span class="fs-6">{{ $item->quantity }}</span></span>
                            <span class="badge {{ $available < $item->quantity ? 'bg-danger text-white' : 'bg-success bg-opacity-10 text-success border border-success' }} px-2 py-1"><i class="bi bi-archive"></i> متاح: <span class="fs-6">{{ $available }}</span></span>
                        </div>
                    </div>

                    <!-- Counter -->
                    <div class="d-flex flex-column align-items-center">
                        <small class="text-muted fw-bold mb-1 d-block">تم تحميله فعلياً</small>
                        <div class="input-group" style="width: 250px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden;">
                            <button type="button" class="btn btn-light border px-4 py-4 text-danger fw-bold fs-2" onclick="decrement('loaded_{{ $item->id }}', {{ $item->id }})">-</button>
                            <input type="number" 
                                   name="items[{{ $index }}][loaded_quantity]" 
                                   id="loaded_{{ $item->id }}" 
                                   class="form-control text-center fw-bold text-primary fs-2 border-light py-4" 
                                   value="{{ $defaultValue }}" 
                                   min="0" 
                                   inputmode="numeric"
                                   onchange="saveProgress({{ $item->id }}, this.value)"
                                   onkeyup="saveProgressDebounced({{ $item->id }}, this.value)"
                                   required style="background: #fff;">
                            <button type="button" class="btn btn-light border px-4 py-4 text-success fw-bold fs-2" onclick="increment('loaded_{{ $item->id }}', 99999, {{ $item->id }})">+</button>
                        </div>
                        <small id="status_{{ $item->id }}" class="text-success mt-1" style="display:none; font-size: 0.8rem;"><i class="bi bi-cloud-check"></i> محفوظ</small>
                    </div>

                </div>
            </div>
            @endforeach
        </div>

        @hasrole('Admin')
        <div class="glass-card water-card mb-4 p-3 border border-primary border-opacity-25">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-truck me-2 text-primary fs-5"></i>بيانات السائق والرحلة (تحديد الإدارة فقط)</h6>
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted small fw-bold">اختر السائق (لإرسال الرحلة للموبايل الخاص به)</label>
                    <select name="driver_id" class="form-select form-control-glass text-dark fw-bold">
                        <option value="">-- بدون سائق مسجل بالبرنامج --</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted small fw-bold">أو اسم سائق خارجي (نقل حر)</label>
                    <input type="text" name="driver_name" class="form-control form-control-glass text-dark fw-bold" placeholder="مثال: محمد نقل">
                </div>
                
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted small fw-bold">رقم اللوحة / نوع السيارة</label>
                    <input type="text" name="truck_details" class="form-control form-control-glass text-dark" placeholder="مثال: د ب أ 1234">
                </div>
                
                <div class="col-12 col-md-6 d-flex align-items-end pb-2">
                    <div class="form-check form-switch fs-5">
                        <input class="form-check-input" type="checkbox" role="switch" id="showInvoiceSwitch" name="show_invoice" value="1">
                        <label class="form-check-label ms-2 text-dark fw-bold" for="showInvoiceSwitch">السماح للسائق برؤية الفاتورة وتحصيل المبلغ</label>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning mt-3 mb-0 small py-2">
                <i class="bi bi-broadcast"></i> عند اختيار سائق مسجل، ستظهر هذه الرحلة فوراً في <strong>(لوحة قيادة السائق)</strong> على هاتفه ليبدأ التتبع המباشر (GPS).
            </div>
        </div>
        @endhasrole

        <button type="submit" class="btn btn-glass w-100 py-3 fs-4 fw-bold mt-2" onclick="return confirmSubmit()">
            <i class="bi bi-check-circle-fill me-2"></i> تأكيد وإنهاء التحميل
        </button>
    </form>

    {{-- Cancel Loading --}}
    <form action="{{ route('loading.cancel', $order) }}" method="POST" class="mt-3"
          onsubmit="return confirm('إلغاء عملية التحميل وإرجاع الطلبية للمسؤول؟');">
        @csrf
        <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-bold rounded-pill">
            <i class="bi bi-x-circle me-2"></i> إلغاء التحميل (إرجاع للإدارة)
        </button>
    </form>
</div>

{{-- Extra Products Section (outside original form, appended via JS) --}}
<div class="glass-card water-card mb-4 mt-2" id="extraProductsCard">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-plus-square-fill text-success me-2 fs-5"></i>إضافة منتج إضافي (غير مطلوب في الطلبية)</h6>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="addExtraRow()">
            <i class="bi bi-plus-lg me-1"></i> إضافة صنف
        </button>
    </div>
    <div id="extraItemsContainer"></div>
    <div class="alert alert-info py-2 small mt-2 mb-0">
        <i class="bi bi-info-circle me-1"></i> الأصناف المضافة هنا ستُضاف تلقائياً للطلبية عند تأكيد التحميل.
    </div>
</div>
@endsection

@push('scripts')
<script>
    let debounceTimer;

    function saveProgressDebounced(orderItemId, value) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            saveProgress(orderItemId, value);
        }, 800); // Save after 800ms of no typing
    }

    function saveProgress(orderItemId, value) {
        let val = parseInt(value) || 0;
        
        fetch('{{ route('loading.save-progress') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                order_item_id: orderItemId,
                loaded_quantity: val
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                let statusEl = document.getElementById('status_' + orderItemId);
                statusEl.style.display = 'block';
                setTimeout(() => { statusEl.style.display = 'none'; }, 2000);
            }
        });
    }

    function increment(id, maxVal, orderItemId) {
        let el = document.getElementById(id);
        let val = parseInt(el.value) || 0;
        if(val < maxVal) {
            el.value = val + 1;
            saveProgress(orderItemId, el.value);
        }
    }
    
    function decrement(id, orderItemId) {
        let el = document.getElementById(id);
        let val = parseInt(el.value) || 0;
        if(val > 0) {
            el.value = val - 1;
            saveProgress(orderItemId, el.value);
        }
    }

        function confirmSubmit() {
        // Inject any extra rows into the main loading form before submit
        const extraRows = document.querySelectorAll('#extraItemsContainer .extra-item-row');
        const form = document.getElementById('loadingForm');
        let extraIndex = 10000; // use high index to avoid collision
        extraRows.forEach(row => {
            const productId = row.querySelector('.extra-product-select').value;
            const qty       = row.querySelector('.extra-qty').value;
            const price     = row.querySelector('.extra-price').value;
            if (!productId || !qty || parseInt(qty) <= 0) return;

            const hiddenPid   = document.createElement('input');
            hiddenPid.type    = 'hidden';
            hiddenPid.name    = `items[${extraIndex}][product_id]`;
            hiddenPid.value   = productId;

            const hiddenQty   = document.createElement('input');
            hiddenQty.type    = 'hidden';
            hiddenQty.name    = `items[${extraIndex}][loaded_quantity]`;
            hiddenQty.value   = qty;

            const hiddenPrice = document.createElement('input');
            hiddenPrice.type  = 'hidden';
            hiddenPrice.name  = `items[${extraIndex}][unit_price]`;
            hiddenPrice.value = price || 0;

            form.appendChild(hiddenPid);
            form.appendChild(hiddenQty);
            form.appendChild(hiddenPrice);
            extraIndex++;
        });
        return confirm('تأكيد تسجيل الحمولة وإرسالها للسائق؟');
    }

    // Products data for extra items
    const allProducts = {!! json_encode($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->selling_price ?? 0, 'stock' => optional($p->inventory)->current_stock ?? 0])) !!};
    let extraRowCount = 0;

    function addExtraRow() {
        extraRowCount++;
        const idx = extraRowCount;
        const container = document.getElementById('extraItemsContainer');
        const div = document.createElement('div');
        div.className = 'extra-item-row d-flex gap-2 align-items-start mb-3 p-3 rounded border border-success border-opacity-25';
        div.style.background = 'rgba(25,135,84,0.04)';
        div.innerHTML = `
            <div style="flex:3; position:relative;">
                <input type="text" class="form-control form-control-glass fw-bold text-success extra-search-${idx}" placeholder="ابحث عن المنتج..." oninput="filterExtra(${idx}, this.value)" onfocus="showExtraDropdown(${idx})" autocomplete="off">
                <input type="hidden" class="extra-product-select">
                <ul class="list-group position-absolute w-100 shadow mt-1 extra-dropdown-${idx}" style="display:none; max-height:200px; overflow-y:auto; z-index:9999; top:100%; left:0;"></ul>
            </div>
            <div style="flex:1; min-width:80px;">
                <input type="number" class="form-control form-control-glass text-center fw-bold text-success extra-qty" placeholder="الكمية" min="1" value="1">
            </div>
            <div style="flex:1; min-width:90px;">
                <input type="number" class="form-control form-control-glass text-center fw-bold extra-price" placeholder="السعر" min="0" step="0.01" value="0">
            </div>
            <div style="flex:0 0 auto;">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle border-0" onclick="this.closest('.extra-item-row').remove()"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        renderExtraDropdown(idx, allProducts.slice(0, 10));
    }

    function filterExtra(idx, term) {
        const filtered = term.length < 1
            ? allProducts.slice(0, 10)
            : allProducts.filter(p => p.name.toLowerCase().includes(term.toLowerCase())).slice(0, 10);
        renderExtraDropdown(idx, filtered);
        showExtraDropdown(idx);
    }

    function renderExtraDropdown(idx, products) {
        const ul = document.querySelector(`.extra-dropdown-${idx}`);
        if (!ul) return;
        ul.innerHTML = '';
        if (products.length === 0) {
            ul.innerHTML = '<li class="list-group-item text-muted text-center">لا توجد نتائج</li>';
            return;
        }
        products.forEach(p => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action cursor-pointer p-2 d-flex justify-content-between align-items-center';
            li.innerHTML = `<span class="fw-bold">${p.name}</span><small class="text-muted">مخزون: ${p.stock}</small>`;
            li.onclick = () => selectExtraProduct(idx, p);
            ul.appendChild(li);
        });
    }

    function showExtraDropdown(idx) {
        const ul = document.querySelector(`.extra-dropdown-${idx}`);
        if (ul) ul.style.display = 'block';
    }

    function selectExtraProduct(idx, product) {
        const row = document.querySelector(`.extra-dropdown-${idx}`).closest('.extra-item-row');
        row.querySelector('.extra-search-' + idx).value = product.name;
        row.querySelector('.extra-product-select').value = product.id;
        row.querySelector('.extra-price').value = product.price;
        const ul = document.querySelector(`.extra-dropdown-${idx}`);
        if (ul) ul.style.display = 'none';
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        document.querySelectorAll('[class*="extra-dropdown-"]').forEach(ul => {
            if (!ul.contains(e.target) && !e.target.classList.contains('extra-search-' + ul.className.match(/extra-dropdown-(\d+)/)?.[1])) {
                ul.style.display = 'none';
            }
        });
    });
</script>
@endpush

