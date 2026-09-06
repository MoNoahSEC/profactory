@extends('layouts.app')

@section('title', 'حاسبة التكلفة | {{ $product->name }}')
@section('page_title', 'حاسبة التكلفة والربح — ' . $product->name)

@section('content')
@php
    $laborCost = ($product->labor_cost ?? 0) + ($product->scissors_cost ?? 0);
    $plasticCost = ($product->plastic_weight ?? 0) * ($product->plastic_price_per_kg ?? 0);
    $overheadCost = $product->overhead_cost ?? 0;
    $paintCost = $product->paint_cost ?? 0;
    $totalCost = $laborCost + $plasticCost + $overheadCost + $paintCost;
    $sellingPrice = $product->selling_price ?? 0;
    $profitAmount = $sellingPrice - $totalCost;
    $profitMargin = $sellingPrice > 0 ? ($profitAmount / $sellingPrice) * 100 : 0;
@endphp

<div class="row g-4">
    <!-- Cost Summary Cards -->
    <div class="col-lg-8">
        <div class="glass-card mb-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-calculator me-2 text-primary"></i>تحليل التكلفة الفعلي للمنتج</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="glass-card text-center p-2">
                        <div class="icon-box warning mx-auto mb-2" style="width: 40px; height: 40px;"><i class="bi bi-people"></i></div>
                        <small class="text-muted d-block">إجمالي المصنعية</small>
                        <h5 class="fw-bold mb-0 text-warning">{{ number_format($laborCost, 2) }} <small class="text-muted fs-7">ج.م</small></h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card text-center p-2">
                        <div class="icon-box primary mx-auto mb-2" style="width: 40px; height: 40px;"><i class="bi bi-box"></i></div>
                        <small class="text-muted d-block">تكلفة البلاستيك</small>
                        <h5 class="fw-bold mb-0 text-primary">{{ number_format($plasticCost, 2) }} <small class="text-muted fs-7">ج.م</small></h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card text-center p-2">
                        <div class="icon-box info mx-auto mb-2" style="width: 40px; height: 40px;"><i class="bi bi-brush"></i></div>
                        <small class="text-muted d-block">تكلفة الدهان</small>
                        <h5 class="fw-bold mb-0 text-info">{{ number_format($paintCost, 2) }} <small class="text-muted fs-7">ج.م</small></h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card text-center p-2">
                        <div class="icon-box danger mx-auto mb-2" style="width: 40px; height: 40px;"><i class="bi bi-lightning"></i></div>
                        <small class="text-muted d-block">مصاريف أخرى</small>
                        <h5 class="fw-bold mb-0 text-danger">{{ number_format($overheadCost, 2) }} <small class="text-muted fs-7">ج.م</small></h5>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="p-3 rounded-3 text-center" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <small class="text-muted d-block mb-1">إجمالي التكلفة</small>
                        <h5 class="fw-bold text-danger mb-0">{{ number_format($totalCost, 2) }} ج.م</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded-3 text-center" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2);">
                        <small class="text-muted d-block mb-1">سعر البيع</small>
                        <h5 class="fw-bold text-primary mb-0">{{ number_format($sellingPrice, 2) }} ج.م</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded-3 text-center" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2);">
                        <small class="text-muted d-block mb-1">صافي الربح</small>
                        <h5 class="fw-bold text-success mb-0">{{ number_format($profitAmount, 2) }} ج.م</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded-3 text-center" style="background: {{ $profitMargin < 20 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)' }}; border: 1px solid {{ $profitMargin < 20 ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)' }};">
                        <small class="text-muted d-block mb-1">نسبة الربح</small>
                        <h5 class="fw-bold mb-0 {{ $profitMargin < 20 ? 'text-danger' : 'text-success' }}">{{ number_format($profitMargin, 1) }}%</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Costs Entry Form -->
        <div class="glass-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2 text-success"></i>تحديث بنود التكلفة للمنتج الواحد</h5>
            <form action="{{ route('products.update', $product) }}" method="POST">
                @csrf
                @method('PUT')
                <!-- Keep other required product fields hidden -->
                <input type="hidden" name="category_id" value="{{ $product->category_id }}">
                <input type="hidden" name="code" value="{{ $product->code }}">
                <input type="hidden" name="name" value="{{ $product->name }}">
                <input type="hidden" name="dimensions" value="{{ $product->dimensions }}">
                <input type="hidden" name="is_active" value="{{ $product->is_active }}">
                
                <h6 class="fw-bold text-warning mb-3">1. أجور الموظفين (بالقطعة)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label text-info fw-bold">الكمية المستهدفة للوردية</label>
                        <input type="number" name="shift_target_quantity" class="form-control form-control-glass border-info" value="{{ $product->shift_target_quantity ?? '' }}">
                        <small class="text-muted">تُستخدم لحساب رواتب الموظفين الذين يعملون بنظام "الورديات".</small>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label text-warning fw-bold">سعر القطعة - مكنجي (ج.م)</label>
                        <input type="number" step="0.01" name="piece_wage" class="form-control form-control-glass text-warning border-warning" value="{{ $product->piece_wage ?: $product->labor_cost }}" required>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label text-warning fw-bold">سعر القطعة - مقص (ج.م)</label>
                        <input type="number" step="0.01" name="piece_wage_scissors" class="form-control form-control-glass text-warning border-warning" value="{{ $product->piece_wage_scissors ?: $product->scissors_cost }}" required>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3">2. البلاستيك</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">وزن المنتج (بالكيلو جرام)</label>
                        <input type="number" step="0.001" name="plastic_weight" class="form-control form-control-glass text-primary fw-bold" value="{{ $product->plastic_weight ?? 0 }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">سعر كيلو البلاستيك (ج.م)</label>
                        <input type="number" step="0.01" name="plastic_price_per_kg" class="form-control form-control-glass text-primary fw-bold" value="{{ $product->plastic_price_per_kg ?? 0 }}" required>
                    </div>
                </div>

                <h6 class="fw-bold text-info mb-3">3. الدهان والمصاريف</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">تكلفة الدهان للمنتج (ج.م)</label>
                        <input type="number" step="0.01" name="paint_cost" class="form-control form-control-glass text-info fw-bold" value="{{ $product->paint_cost ?? 0 }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">مصاريف أخرى (ج.م)</label>
                        <input type="number" step="0.01" name="overhead_cost" class="form-control form-control-glass text-danger fw-bold" value="{{ $product->overhead_cost ?? 0 }}" required>
                    </div>
                </div>

                <hr class="border-secondary mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label text-muted">تحديث سعر البيع (ج.م)</label>
                        <input type="number" step="0.01" name="selling_price" class="form-control form-control-glass fs-5 text-success fw-bold" value="{{ $product->selling_price }}" required>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="submit" class="btn-glass w-100 py-2"><i class="bi bi-save me-1"></i> حفظ التفاصيل</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Info Sidebar -->
    <div class="col-lg-4">
        <div class="glass-card mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-box-seam me-2"></i>بيانات المنتج</h5>
            <table class="table table-borderless text-white mb-0">
                <tr><td class="text-muted py-2">الكود</td><td class="fw-bold py-2">{{ $product->code }}</td></tr>
                <tr><td class="text-muted py-2">الاسم</td><td class="fw-bold py-2">{{ $product->name }}</td></tr>
                <tr><td class="text-muted py-2">الفئة</td><td class="py-2">{{ $product->category->name ?? '-' }}</td></tr>
                <tr><td class="text-muted py-2">الأبعاد</td><td class="py-2">{{ $product->dimensions ?? '-' }}</td></tr>
                <tr><td class="text-muted py-2">سعر البيع</td><td class="fw-bold text-success py-2">{{ number_format($product->selling_price, 2) }} ج.م</td></tr>
            </table>
            <div class="mt-3 pt-3 d-flex justify-content-center gap-2" style="border-top: 1px solid rgba(255,255,255,0.05);">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="editProduct({{ json_encode($product) }})">
                    <i class="bi bi-pencil me-1"></i> تعديل بيانات المنتج
                </button>
                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف المنتج نهائياً؟');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                        <i class="bi bi-trash me-1"></i> حذف
                    </button>
                </form>
            </div>
        </div>

        <div class="glass-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2"></i>توزيع التكلفة</h5>
            <canvas id="costChart" height="200"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('costChart'), {
            type: 'doughnut',
            data: {
                labels: ['المصنعية', 'البلاستيك', 'الدهان', 'مصاريف أخرى'],
                datasets: [{
                    data: [{{ $laborCost }}, {{ $plasticCost }}, {{ $paintCost }}, {{ $overheadCost }}],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#0ea5e9', '#ef4444'],
                    borderColor: 'transparent',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#cbd5e1', padding: 15 }
                    }
                },
                cutout: '65%'
            }
        });
    });

    function editProduct(product) {
        document.getElementById('pModalTitle').innerText = 'تعديل بيانات المنتج';
        document.getElementById('pCode').value = product.code;
        document.getElementById('pName').value = product.name;
        document.getElementById('pCategory').value = product.category_id;
        document.getElementById('pDimensions').value = product.dimensions || '';
        document.getElementById('pPrice').value = product.selling_price;
        document.getElementById('pCages').value = product.cages_per_carton || '';
        document.getElementById('pActive').value = product.is_active ? '1' : '0';
        
        document.getElementById('productForm').action = `/products/${product.id}`;
        document.getElementById('pFormMethod').value = 'PUT';
        
        new bootstrap.Modal(document.getElementById('productModal')).show();
    }
</script>
@endpush

<!-- Modal: Edit Product -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel text-white">
            <div class="modal-header border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                <h5 class="modal-title fw-bold" id="pModalTitle">تعديل بيانات المنتج</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="pFormMethod" value="PUT">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">كود المنتج <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="pCode" class="form-control form-control-glass" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-muted">اسم المنتج <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="pName" class="form-control form-control-glass" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">التصنيف <span class="text-danger">*</span></label>
                            <select name="category_id" id="pCategory" class="form-control form-control-glass" required>
                                <option value="">اختر تصنيف</option>
                                @foreach(\App\Models\Category::all() as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">الأبعاد (سم)</label>
                            <input type="text" name="dimensions" id="pDimensions" class="form-control form-control-glass">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">سعر البيع (ج.م) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="selling_price" id="pPrice" class="form-control form-control-glass" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">عدد المنتجات في الكرتونة</label>
                            <input type="number" name="cages_per_carton" id="pCages" class="form-control form-control-glass">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">صورة المنتج</label>
                            <input type="file" name="image_file" id="pImage" class="form-control form-control-glass" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">الحالة</label>
                            <select name="is_active" id="pActive" class="form-control form-control-glass">
                                <option value="1">نشط</option>
                                <option value="0">متوقف</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary" style="border-color: rgba(255,255,255,0.1) !important;">
                    <button type="submit" class="btn-glass px-4 py-2 w-100">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

