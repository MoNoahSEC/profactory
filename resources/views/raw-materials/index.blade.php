@extends('layouts.app')

@section('title', 'المواد الخام | مصنع المنتجات')
@section('page_title', 'إدارة المواد الخام')

@section('content')

{{-- Header --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-box-fill text-orange me-2"></i>المواد الخام</h4>
        <p class="text-muted mb-0 small">إدارة وتتبع أرصدة المواد الخام ومستويات التنبيه</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-orange rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#materialCategoriesModal">
            <i class="bi bi-tags-fill me-1"></i> تصنيفات الخامات
        </button>
        <button class="btn btn-orange rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#materialModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة خامة
        </button>
    </div>
</div>

{{-- Stats --}}
@php
    $totalStockValue = collect($materials)->sum(fn($m) => $m->current_stock * $m->unit_cost);
    $lowStockCount   = collect($materials)->filter(fn($m) => $m->current_stock <= $m->minimum_stock)->count();
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-boxes stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">إجمالي الخامات</p>
            <div class="stat-amount">{{ count($materials) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <i class="bi bi-cash-stack stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">قيمة المخزون المقدرة</p>
            <div class="stat-amount" style="color:#16a34a !important;">{{ number_format($totalStockValue, 0) }}</div>
            <small class="text-muted">ج.م</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card {{ $lowStockCount > 0 ? 'stat-danger' : '' }}">
            <i class="bi bi-exclamation-triangle stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">تحت حد التنبيه</p>
            <div class="stat-amount" style="{{ $lowStockCount > 0 ? 'color:#dc2626 !important;' : '' }}">{{ $lowStockCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <i class="bi bi-tags stat-icon"></i>
            <p class="text-muted small fw-bold mb-1">عدد التصنيفات</p>
            <div class="stat-amount">{{ count($materialCategories) }}</div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>قائمة الخامات المتاحة</h6>
        <input type="text" id="searchInput" class="form-control form-control-sm w-auto" placeholder="🔍 بحث..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:200px;">
    </div>
    <div class="table-responsive">
        <table class="table table-clean mb-0" id="materialsTable">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>اسم الخامة</th>
                    <th class="text-center">الوحدة</th>
                    <th class="text-center">التكلفة للوحدة</th>
                    <th class="text-center">المخزون الحالي</th>
                    <th class="text-center">حد التنبيه</th>
                    <th>المورد الافتراضي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                <tr>
                    <td><span class="badge badge-muted rounded-pill px-3 py-1">{{ $material->id }}</span></td>
                    <td>
                        <a href="javascript:void(0)" onclick="editMaterial({{ $material->id }}, '{{ $material->name }}', '{{ $material->unit }}', {{ $material->unit_cost }}, {{ $material->current_stock }}, {{ $material->minimum_stock }}, '{{ $material->supplier_name }}', '{{ $material->notes }}', '{{ $material->material_category_id }}')" class="fw-bold text-dark text-decoration-none d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-box text-orange"></i>
                            </span>
                            {{ $material->name }}
                        </a>
                    </td>
                    <td class="text-center"><span class="badge badge-muted rounded-pill px-3 py-1">{{ $material->unit }}</span></td>
                    <td class="text-center fw-bold">{{ number_format($material->unit_cost, 2) }} <small class="text-muted fw-normal">ج.م</small></td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-2">
                            @if($material->current_stock <= $material->minimum_stock)
                                <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;" title="نقص في المخزون">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $material->current_stock }}
                                </span>
                            @else
                                <span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#dcfce7; color:#16a34a; border:1px solid #86efac;">
                                    {{ $material->current_stock }}
                                </span>
                            @endif
                            <button class="btn btn-sm btn-outline-success rounded-circle p-0" style="width:28px;height:28px; line-height:26px;" onclick="restockMaterial({{ $material->id }}, '{{ $material->name }}', '{{ $material->unit }}')" title="توريد / إضافة للمخزون">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center text-muted">{{ $material->minimum_stock }}</td>
                    <td class="text-muted">{{ $material->supplier_name ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-box-seam text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا توجد مواد خام مسجلة</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add/Edit Material -->
<div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <div class="d-flex align-items-center gap-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:44px;height:44px;">
                        <i class="bi bi-box-fill text-orange fs-5"></i>
                    </span>
                    <h5 class="modal-title fw-bold mb-0" id="modalTitle">إضافة مادة خام جديدة</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="materialForm" action="{{ route('raw-materials.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label text-muted fw-bold">اسم الخامة <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="matName" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted fw-bold">التصنيف</label>
                            <select name="material_category_id" id="matCategory" class="form-select" style="border-radius:10px;">
                                <option value="">بدون تصنيف</option>
                                @foreach($materialCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted fw-bold">الوحدة <span class="text-danger">*</span></label>
                            <input type="text" name="unit" id="matUnit" class="form-control" placeholder="كجم، لتر.." required style="border-radius:10px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted fw-bold">التكلفة <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="unit_cost" id="matCost" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">المخزون الحالي <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" name="current_stock" id="matStock" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold">حد التنبيه (الأدنى) <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" name="minimum_stock" id="matMinStock" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted fw-bold">اسم المورد الافتراضي (اختياري)</label>
                            <input type="text" name="supplier_name" id="matSupplier" class="form-control" style="border-radius:10px;">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted fw-bold">ملاحظات</label>
                            <textarea name="notes" id="matNotes" class="form-control" rows="2" style="border-radius:10px;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center" style="border-top:1px solid #f1f5f9;">
                    <button type="button" id="deleteMatBtn" class="btn btn-outline-danger rounded-pill px-4 d-none" onclick="document.getElementById('deleteMatForm').submit();"><i class="bi bi-trash"></i> حذف</button>
                    <div>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 me-2" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-orange px-5 rounded-pill">حفظ الخامة</button>
                    </div>
                </div>
            </form>
            <form id="deleteMatForm" method="POST" class="d-none" onsubmit="return confirm('تأكيد حذف الخامة نهائياً؟');">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<!-- Modal: Restock -->
<div class="modal fade" id="restockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0fdf4,#fff); border-bottom:2px solid #bbf7d0; border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-plus-circle-fill me-2"></i>توريد خامة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="restockForm" method="POST">
                @csrf
                <div class="modal-body p-4 text-center">
                    <h6 class="fw-bold mb-3" id="restockMatName"></h6>
                    <div class="mb-3">
                        <label class="form-label text-muted">الكمية الموردة <span id="restockUnit" class="text-success fw-bold"></span></label>
                        <input type="number" step="0.001" name="quantity" class="form-control text-center fs-4 fw-bold" required min="0.1" style="border-radius:15px; border:2px solid #86efac; background:#f0fdf4; color:#16a34a;">
                    </div>
                </div>
                <div class="modal-footer justify-content-center" style="border-top:1px solid #f1f5f9;">
                    <button type="submit" class="btn btn-success rounded-pill px-5 w-100 fw-bold shadow-sm">إضافة للمخزون</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Material Categories -->
<div class="modal fade" id="materialCategoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-tags-fill text-orange me-2"></i>إدارة تصنيفات الخامات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="{{ route('material-categories.store') }}" method="POST" class="mb-4 pb-4 border-bottom border-light">
                    @csrf
                    <h6 class="mb-3 fw-bold text-primary">إضافة تصنيف جديد</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="name" class="form-control" placeholder="اسم التصنيف (مثال: دهان، حديد)" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="default_unit" class="form-control" placeholder="الوحدة الافتراضية" style="border-radius:10px;">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-orange w-100 rounded-pill">إضافة التصنيف</button>
                        </div>
                    </div>
                </form>

                <h6 class="mb-3 fw-bold text-dark">التصنيفات الحالية</h6>
                <div class="table-responsive">
                    <table class="table table-clean table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الوحدة الافتراضية</th>
                                <th class="text-center">عدد الخامات</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materialCategories as $cat)
                                <tr>
                                    <td class="fw-bold">{{ $cat->name }}</td>
                                    <td><span class="badge badge-muted rounded-pill px-3">{{ $cat->default_unit ?? '-' }}</span></td>
                                    <td class="text-center fw-bold">{{ $cat->materials_count }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('material-categories.destroy', $cat) }}" method="POST" class="d-inline-block" onsubmit="return confirm('تأكيد الحذف؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle border-0" {{ $cat->materials_count > 0 ? 'disabled' : '' }} title="حذف التصنيف"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">لا يوجد تصنيفات مضافة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function editMaterial(id, name, unit, cost, stock, minStock, supplier, notes, categoryId) {
        document.getElementById('modalTitle').innerHTML = 'تعديل الخامة: ' + name;
        document.getElementById('matName').value = name;
        document.getElementById('matUnit').value = unit;
        document.getElementById('matCost').value = cost;
        document.getElementById('matStock').value = stock;
        document.getElementById('matMinStock').value = minStock;
        document.getElementById('matSupplier').value = supplier !== 'null' ? supplier : '';
        document.getElementById('matNotes').value = notes !== 'null' ? notes : '';
        document.getElementById('matCategory').value = categoryId !== 'null' ? categoryId : '';
        
        let form = document.getElementById('materialForm');
        form.action = `/raw-materials/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        
        let deleteForm = document.getElementById('deleteMatForm');
        deleteForm.action = `/raw-materials/${id}`;
        document.getElementById('deleteMatBtn').classList.remove('d-none');

        var myModal = new bootstrap.Modal(document.getElementById('materialModal'));
        myModal.show();
    }

    document.getElementById('materialModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalTitle').innerHTML = 'إضافة مادة خام جديدة';
        document.getElementById('materialForm').reset();
        document.getElementById('materialForm').action = "{{ route('raw-materials.store') }}";
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('deleteMatBtn').classList.add('d-none');
    });

    function restockMaterial(id, name, unit) {
        document.getElementById('restockMatName').innerText = name;
        document.getElementById('restockUnit').innerText = '(' + unit + ')';
        
        let form = document.getElementById('restockForm');
        form.action = `/raw-materials/${id}/restock`;
        
        var myModal = new bootstrap.Modal(document.getElementById('restockModal'));
        myModal.show();
    }

    // Search
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('#materialsTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
        });
    });
</script>
@endpush
