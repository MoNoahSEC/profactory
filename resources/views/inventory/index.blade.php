@extends('layouts.app')

@section('title', 'المخزون | مصنع المنتجات')
@section('page_title', 'إدارة المخزون')

@section('content')
<!-- Summary Card -->
<div class="glass-card mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1 fw-bold">قيمة المخزون الإجمالية</h5>
            <p class="text-muted mb-0">القيمة الحالية لجميع المنتجات في المخزون</p>
        </div>
        <div class="text-end">
            <h2 class="fw-bold text-success mb-0">{{ number_format($totalValue, 2) }} <small class="fs-5 text-muted">ج.م</small></h2>
        </div>
    </div>
</div>

<div class="glass-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold">حركة المخزون</h5>
        <div>
            <button class="btn-glass-secondary me-2" data-bs-toggle="modal" data-bs-target="#minimumStockModal">
                <i class="bi bi-bell me-1"></i> إعدادات التنبيه (الحد الأدنى)
            </button>
            <button class="btn-glass" data-bs-toggle="modal" data-bs-target="#adjustModal">
                <i class="bi bi-arrow-left-right me-1"></i> تعديل مخزون يدوي
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-glass table-hover align-middle text-center">
            <thead>
                <tr>
                    <th class="text-start">المنتج</th>
                    <th>الفئة</th>
                    <th>سعر البيع (ج.م)</th>
                    <th>الوارد</th>
                    <th>الصادر</th>
                    <th>المخزون الحالي</th>
                    <th>الحد الأدنى</th>
                    <th>آخر تحديث</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventory as $inv)
                <tr>
                    <td class="text-start fw-bold text-white">{{ $inv->product->name ?? '-' }}</td>
                    <td class="text-muted">{{ $inv->product->category->name ?? '-' }}</td>
                    
                    <!-- Selling Price Inline Edit -->
                    <td style="width: 140px;">
                        <div class="input-group input-group-sm bg-dark border border-secondary rounded-pill overflow-hidden position-relative">
                            <span class="input-group-text bg-transparent border-0 text-muted ps-2 pe-1"><i class="bi bi-tag"></i></span>
                            <input type="number" class="form-control bg-transparent text-success fw-bold border-0 text-center px-1 quick-edit-input" 
                                   data-id="{{ $inv->product_id }}" data-field="selling_price" 
                                   value="{{ $inv->product->selling_price ?? 0 }}" min="0" step="0.5">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-2 save-indicator" style="display: none;"><i class="bi bi-check-circle-fill text-success"></i></span>
                        </div>
                    </td>

                    <td class="text-success" id="qty_in_{{ $inv->product_id }}">+{{ $inv->quantity_in }}</td>
                    <td class="text-danger" id="qty_out_{{ $inv->product_id }}">-{{ $inv->quantity_out }}</td>
                    
                    <!-- Current Stock Inline Edit -->
                    <td style="width: 140px;">
                        <div class="input-group input-group-sm bg-dark border border-secondary rounded-pill overflow-hidden position-relative">
                            <span class="input-group-text bg-transparent border-0 text-muted ps-2 pe-1"><i class="bi bi-box-seam"></i></span>
                            <input type="number" class="form-control bg-transparent text-white fw-bold border-0 text-center px-1 quick-edit-input" 
                                   data-id="{{ $inv->product_id }}" data-field="current_stock" 
                                   value="{{ $inv->current_stock }}" min="0">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-2 save-indicator" style="display: none;"><i class="bi bi-check-circle-fill text-success"></i></span>
                        </div>
                    </td>

                    <td class="text-muted">{{ $inv->minimum_stock }}</td>
                    <td class="text-muted" id="updated_at_{{ $inv->product_id }}">{{ $inv->last_updated ? $inv->last_updated->diffForHumans() : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">لا يوجد مخزون حتى الآن. سيتم تحديث المخزون تلقائياً عند إكمال أوامر الإنتاج.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Adjust Inventory (Kept for fallback/bulk operations if needed, but no longer the primary way) -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white">
            <div class="modal-header border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                <h5 class="modal-title fw-bold">تعديل المخزون يدوياً</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('inventory.adjust') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted">المنتج <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-control form-control-glass" required>
                                <option value="">اختر منتج</option>
                                @foreach($inventory as $inv)
                                    <option value="{{ $inv->product_id }}">{{ $inv->product->name ?? '-' }} (المخزون: {{ $inv->current_stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">نوع الحركة <span class="text-danger">*</span></label>
                            <select name="type" class="form-control form-control-glass" required>
                                <option value="in">وارد (إضافة)</option>
                                <option value="out">صادر (خصم)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">الكمية <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control form-control-glass" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                    <button type="button" class="btn-glass-secondary px-4 py-2 rounded-3 me-2" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn-glass px-4 py-2 rounded-3">تنفيذ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Update Minimum Stock -->
<div class="modal fade" id="minimumStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white">
            <div class="modal-header border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                <h5 class="modal-title fw-bold">تحديد الحد الأدنى للمنتجات (تنبيهات النقص)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('inventory.minimum') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted">المنتج <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-control form-control-glass" required>
                                <option value="">اختر منتج</option>
                                @foreach($inventory as $inv)
                                    <option value="{{ $inv->product_id }}">{{ $inv->product->name ?? '-' }} (الحد الحالي: {{ $inv->minimum_stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted">الحد الأدنى الجديد للمخزون <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_stock" class="form-control form-control-glass" min="0" required>
                            <small class="text-muted mt-1 d-block">سيظهر التنبيه في الشاشة الرئيسية عندما يقل المخزون عن هذا الرقم.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                    <button type="button" class="btn-glass-secondary px-4 py-2 rounded-3 me-2" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn-glass px-4 py-2 rounded-3">تحديث الحد الأدنى</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.quick-edit-input');
        
        inputs.forEach(input => {
            let timeout = null;
            let originalValue = input.value;

            input.addEventListener('focus', function() {
                originalValue = this.value;
                this.classList.add('border', 'border-primary');
            });

            input.addEventListener('blur', function() {
                this.classList.remove('border', 'border-primary');
                if (this.value !== originalValue) {
                    saveQuickUpdate(this);
                }
            });

            // Also save on Enter key
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.blur(); // Triggers the blur event which saves
                }
            });
        });

        function saveQuickUpdate(inputElement) {
            const productId = inputElement.getAttribute('data-id');
            const field = inputElement.getAttribute('data-field');
            const newValue = inputElement.value;
            const container = inputElement.closest('.input-group');
            const indicator = container.querySelector('.save-indicator');

            // Show saving state
            inputElement.style.opacity = '0.5';

            let formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('product_id', productId);
            formData.append(field, newValue);

            fetch('{{ route("inventory.quick-update") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                inputElement.style.opacity = '1';
                if (data.success) {
                    // Show checkmark briefly
                    indicator.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                    indicator.style.display = 'block';
                    setTimeout(() => { indicator.style.display = 'none'; }, 2000);

                    // Update UI if stock was changed
                    if (field === 'current_stock' && data.data) {
                        document.getElementById('qty_in_' + productId).innerText = '+' + data.data.quantity_in;
                        document.getElementById('qty_out_' + productId).innerText = '-' + data.data.quantity_out;
                        document.getElementById('updated_at_' + productId).innerText = data.data.last_updated;
                    }
                } else {
                    alert(data.message);
                    inputElement.value = inputElement.defaultValue; // Revert
                }
            })
            .catch(error => {
                inputElement.style.opacity = '1';
                alert('حدث خطأ في الاتصال بالسيرفر.');
                inputElement.value = inputElement.defaultValue; // Revert
                console.error(error);
            });
        }
    });
</script>
<style>
    /* Hide arrows from number inputs for a cleaner look */
    .quick-edit-input::-webkit-outer-spin-button,
    .quick-edit-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .quick-edit-input[type=number] {
        -moz-appearance: textfield;
    }
    .quick-edit-input:focus {
        outline: none;
        box-shadow: none;
    }
    .save-indicator {
        font-size: 1rem;
        animation: fadeInOut 2s ease-in-out;
    }
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(-50%) scale(0.5); }
        20% { opacity: 1; transform: translateY(-50%) scale(1.1); }
        80% { opacity: 1; transform: translateY(-50%) scale(1); }
        100% { opacity: 0; transform: translateY(-50%) scale(0.5); }
    }
</style>
@endpush


