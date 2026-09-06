@extends('layouts.app')
@section('title', 'المنتجات والمخزون | مصنع المنتجات')
@section('page_title', 'المنتجات والمخزون')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-box-seam-fill text-orange me-2"></i>المنتجات والمخزون</h4>
        <p class="text-muted mb-0 small">شجرة التصنيفات والمنتجات مع إدارة المخزون</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#qrModal">
            <i class="bi bi-qr-code-scan me-1"></i> QR المنيو
        </button>
        <a href="{{ route('products.inventory-print') }}" target="_blank" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-printer me-1"></i> ورقة الجرد
        </a>
        <button class="btn btn-outline-orange rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="bi bi-tags-fill me-1"></i> تصنيف جديد
        </button>
        <button class="btn btn-orange rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus-lg me-1"></i> منتج جديد
        </button>
    </div>
</div>

{{-- Stats --}}
@php
    $totalProducts   = $categories->sum(fn($c) => $c->products->count());
    $totalStock      = $categories->sum(fn($c) => $c->products->sum(fn($p) => $p->inventory->current_stock ?? 0));
    $activeProducts  = $categories->sum(fn($c) => $c->products->where('is_active', true)->count());
    $lowStockCount   = $categories->sum(fn($c) => $c->products->filter(fn($p) => ($p->inventory->current_stock ?? 0) <= 0)->count());
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-box-seam stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">إجمالي المنتجات</p>
            <div class="stat-amount">{{ $totalProducts }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <i class="bi bi-check-circle stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">منتجات نشطة</p>
            <div class="stat-amount" style="color:#16a34a !important;">{{ $activeProducts }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-archive stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">إجمالي المخزون</p>
            <div class="stat-amount">{{ number_format($totalStock) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card {{ $lowStockCount > 0 ? 'stat-danger' : '' }}">
            <i class="bi bi-exclamation-triangle stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">نفذ من المخزن</p>
            <div class="stat-amount" style="{{ $lowStockCount > 0 ? 'color:#dc2626 !important;' : '' }}">{{ $lowStockCount }}</div>
        </div>
    </div>
</div>

{{-- Categories Accordion --}}
<div class="accordion" id="productsTree">
    @forelse($categories as $category)
    <div class="content-card mb-3 overflow-hidden">
        <div class="accordion-item border-0 bg-transparent">
            <h2 class="accordion-header" id="heading{{ $category->id }}">
                <button class="accordion-button collapsed fw-bold" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $category->id }}"
                    aria-expanded="false"
                    style="background:#fffbf8; color:#1e293b; border-radius:16px 16px 0 0; border-bottom: 2px solid var(--primary-light);">
                    <div class="d-flex align-items-center w-100 me-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft me-3" style="width:40px;height:40px;flex-shrink:0;">
                            <i class="bi bi-folder-fill text-orange"></i>
                        </span>
                        <span class="fs-5 fw-bold">{{ $category->name }}</span>
                        <span class="badge badge-orange ms-3 rounded-pill">{{ $category->products->count() }} منتج</span>
                    </div>
                </button>
            </h2>
            <div id="collapse{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $category->id }}" data-bs-parent="#productsTree">
                {{-- Category Actions --}}
                <div class="d-flex justify-content-end p-2 px-3" style="background:#fafaf9; border-bottom:1px solid #f1f5f9;">
                    <button class="btn btn-sm btn-outline-orange rounded-pill me-2 px-3" onclick="editCategory({{ $category->id }}, '{{ $category->name }}', '{{ $category->description }}')">
                        <i class="bi bi-pencil me-1"></i> تعديل التصنيف
                    </button>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline-block" onsubmit="return confirm('حذف التصنيف نهائياً؟');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" {{ $category->products->count() > 0 ? 'disabled' : '' }}>
                            <i class="bi bi-trash me-1"></i> حذف
                        </button>
                    </form>
                </div>

                {{-- Products Table --}}
                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead>
                            <tr>
                                <th>الكود</th>
                                <th>اسم المنتج</th>
                                <th class="text-center">سعر البيع</th>
                                <th class="text-center">الرصيد الحالي</th>
                                <th class="text-center">تعديل المخزون</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($category->products as $product)
                            <tr>
                                <td>
                                    <span class="badge badge-muted rounded-pill px-3 py-1">{{ $product->code }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('products.cost', $product) }}" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:32px;height:32px;font-size:.9rem;flex-shrink:0;">
                                            <i class="bi bi-box-seam text-orange"></i>
                                        </span>
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td class="text-center fw-bold text-orange">{{ number_format($product->selling_price, 2) }} <small class="text-muted fw-normal">ج.م</small></td>
                                <td class="text-center">
                                    @php $stock = $product->inventory->current_stock ?? 0; @endphp
                                    @if($stock > 0)
                                        <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;">{{ $stock }}</span>
                                    @else
                                        <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;">{{ $stock }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('inventory.adjust') }}" method="POST" class="d-inline-flex align-items-center justify-content-center gap-1 m-0">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <div class="input-group input-group-sm" style="max-width:130px; border-radius:10px; overflow:hidden; border:1.5px solid #e2e8f0;">
                                            <button type="submit" name="type" value="out" class="btn btn-sm" style="background:#fee2e2; color:#dc2626; border:none; padding:4px 10px;" title="خصم">
                                                <i class="bi bi-dash-lg"></i>
                                            </button>
                                            <input type="number" name="quantity" class="form-control border-0 text-center fw-bold" value="1" min="1" style="background:#f8fafc;">
                                            <button type="submit" name="type" value="in" class="btn btn-sm" style="background:#dcfce7; color:#16a34a; border:none; padding:4px 10px;" title="إضافة">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-3 text-muted">لا توجد منتجات في هذا التصنيف.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="content-card text-center py-5">
        <i class="bi bi-box-seam text-muted display-4 d-block mb-2 opacity-25"></i>
        <p class="text-muted fw-bold">شجرة التصنيفات فارغة. ابدأ بإضافة تصنيف جديد.</p>
    </div>
    @endforelse
</div>

{{-- Modal: QR --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none;">
            <div class="modal-header" style="border-bottom: 2px solid var(--primary-light); background:#fffbf8; border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-qr-code-scan text-orange me-2"></i>QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white rounded-bottom">
                @php
                    $localIp = gethostbyname(gethostname());
                    $port = request()->getPort();
                    $systemUrl = "http://{$localIp}:{$port}/";
                    $menuUrl   = "http://{$localIp}:{$port}/menu";
                @endphp
                <div class="row g-3">
                    <div class="col-md-6 text-center border-end">
                        <h6 class="text-orange fw-bold mb-3">النظام (الإدارة)</h6>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($systemUrl) }}" class="img-fluid mb-2 rounded" style="border: 2px solid var(--primary); padding:4px;">
                        <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-orange btn-sm w-100 rounded-pill">فتح الرابط</a>
                    </div>
                    <div class="col-md-6 text-center">
                        <h6 class="fw-bold mb-3" style="color:#ca8a04;">المنيو (للعملاء)</h6>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($menuUrl) }}" class="img-fluid mb-2 rounded" style="border: 2px solid #ca8a04; padding:4px;">
                        <a href="{{ route('menu.index') }}" target="_blank" class="btn btn-outline-warning btn-sm w-100 rounded-pill">فتح الرابط</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Category --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 40px rgba(0,0,0,.12);">
            <div class="modal-header" style="border-bottom: 2px solid var(--primary-light); background:#fffbf8; border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="catModalTitle">
                    <i class="bi bi-tags-fill text-orange me-2"></i>إضافة تصنيف جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm" action="{{ route('categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="catFormMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">اسم التصنيف <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">الوصف</label>
                        <textarea name="description" id="catDesc" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-4">حفظ التصنيف</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add/Edit Product --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 40px rgba(0,0,0,.12);">
            <div class="modal-header" style="border-bottom: 2px solid var(--primary-light); background:#fffbf8; border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="pModalTitle">
                    <i class="bi bi-plus-circle text-orange me-2"></i>إضافة منتج جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="pFormMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark small">كود المنتج <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="pCode" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" placeholder="مثال: CAGE-001" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark small">اسم المنتج <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="pName" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">التصنيف <span class="text-danger">*</span></label>
                            <select name="category_id" id="pCategory" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" required>
                                <option value="">اختر تصنيف</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">الأبعاد (سم)</label>
                            <input type="text" name="dimensions" id="pDimensions" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" placeholder="مثال: 30x20x25">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">سعر البيع الافتراضي (ج.م) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="selling_price" id="pPrice" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">عدد المنتجات في الكرتونة</label>
                            <input type="number" name="cages_per_carton" id="pCages" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" placeholder="مثال: 12">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">صورة المنتج</label>
                            <input type="file" name="image_file" id="pImage" class="form-control" style="border-radius:10px; border: 1.5px solid #e2e8f0;" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">الحالة</label>
                            <select name="is_active" id="pActive" class="form-control no-search" style="border-radius:10px; border: 1.5px solid #e2e8f0;">
                                <option value="1">نشط</option>
                                <option value="0">متوقف</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-4">
                        <i class="bi bi-check-lg me-1"></i> حفظ المنتج
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function editCategory(id, name, desc) {
        document.getElementById('catModalTitle').innerHTML = '<i class="bi bi-pencil text-orange me-2"></i>تعديل التصنيف';
        document.getElementById('catName').value = name;
        document.getElementById('catDesc').value = desc;
        document.getElementById('categoryForm').action = `/categories/${id}`;
        document.getElementById('catFormMethod').value = 'PUT';
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('catModalTitle').innerHTML = '<i class="bi bi-tags-fill text-orange me-2"></i>إضافة تصنيف جديد';
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryForm').action = "{{ route('categories.store') }}";
        document.getElementById('catFormMethod').value = 'POST';
    });

    function editProduct(product) {
        document.getElementById('pModalTitle').innerHTML = '<i class="bi bi-pencil text-orange me-2"></i>تعديل المنتج';
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

    // ═══ حفظ واستعادة حالة الـ Accordion ═══
    const ACCORDION_KEY = 'products_open_accordions';
    function saveAccordionState() {
        const open = [];
        document.querySelectorAll('.accordion-collapse.show').forEach(el => open.push(el.id));
        sessionStorage.setItem(ACCORDION_KEY, JSON.stringify(open));
    }
    function restoreAccordionState() {
        const saved = sessionStorage.getItem(ACCORDION_KEY);
        if (!saved) return;
        JSON.parse(saved).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('show');
                const btn = document.querySelector(`[data-bs-target="#${id}"]`);
                if (btn) btn.classList.remove('collapsed');
            }
        });
    }
    document.querySelectorAll('.accordion-collapse').forEach(el => {
        el.addEventListener('shown.bs.collapse', saveAccordionState);
        el.addEventListener('hidden.bs.collapse', saveAccordionState);
    });
    document.addEventListener('submit', saveAccordionState, true);
    @if(session()->has('success') || session()->has('error'))
    restoreAccordionState();
    @endif

    // Accordion arrow styling fix for white background
    document.querySelectorAll('.accordion-button').forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('span.d-inline-flex');
        });
    });
</script>
<style>
    .accordion-button::after { filter: none !important; }
    .accordion-button:not(.collapsed) {
        background: #fffbf8 !important;
        color: #ea580c !important;
        box-shadow: none !important;
    }
</style>
@endpush
@endsection
