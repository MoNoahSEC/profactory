@extends('layouts.app')
@section('title', 'إدارة الفئات | مصنع المنتجات')
@section('page_title', 'فئات المنتجات')

@section('content')

{{-- ── Header ── --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-tags text-orange me-2"></i>قائمة فئات المنتجات</h4>
        <p class="text-muted mb-0 small">إدارة وتصنيف المنتجات في فئات لسهولة الوصول إليها</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-orange rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة فئة جديدة
        </button>
    </div>
</div>

{{-- ── Table ── --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>الفئات المسجلة</h6>
        <input type="text" id="categorySearch" class="form-control form-control-sm w-auto" placeholder="🔍 بحث عن فئة..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:250px;">
    </div>
    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0" id="categoryTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الفئة</th>
                    <th>الوصف</th>
                    <th class="text-center">عدد المنتجات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr class="cursor-pointer" onclick="openCategoryActionModal({{ $category->id }}, '{{ $category->name }}', '{{ $category->description }}', {{ $category->products_count }})" title="انقر لعرض خيارات التعديل والحذف">
                    <td><span class="badge bg-light text-dark border rounded-pill px-3 py-1">{{ $category->id }}</span></td>
                    <td class="fw-bold text-primary">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-tag text-orange"></i>
                            </span>
                            {{ $category->name }}
                        </div>
                    </td>
                    <td class="text-muted fw-bold">{{ $category->description ?? 'لا يوجد وصف' }}</td>
                    <td class="text-center">
                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info px-3 py-1">
                            {{ $category->products_count }} منتج
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <i class="bi bi-tags text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا توجد فئات مضافة حتى الآن.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

{{-- ── Action Modal ── --}}
<div class="modal fade" id="actionCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-gear text-orange me-2"></i>خيارات الفئة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h5 class="fw-bolder text-primary mb-1" id="actCatName"></h5>
                <p class="text-muted small mb-4" id="actCatProducts"></p>
                
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-outline-primary fw-bold rounded-pill w-100 py-2" id="editCatBtn">
                        <i class="bi bi-pencil me-1"></i> تعديل بيانات الفئة
                    </button>

                    <form id="deleteCatForm" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفئة؟');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill w-100 py-2" id="deleteCatBtn">
                            <i class="bi bi-trash me-1"></i> حذف الفئة
                        </button>
                    </form>
                    <div id="deleteCatWarning" class="text-danger small fw-bold" style="display:none;">
                        <i class="bi bi-exclamation-triangle"></i> لا يمكن الحذف، الفئة مرتبطة بمنتجات.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Add/Edit Category Modal ── --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle"><i class="bi bi-plus-circle-fill text-orange me-2"></i>إضافة فئة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm" action="{{ route('categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">اسم الفئة <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="categoryName" class="form-control" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">وصف الفئة (اختياري)</label>
                        <textarea name="description" id="categoryDesc" class="form-control" rows="3" style="border-radius:10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-5 fw-bold shadow-sm">حفظ الفئة <i class="bi bi-check-circle ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Search
    document.getElementById('categorySearch')?.addEventListener('keyup', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('#categoryTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
        });
    });

    // Action Modal
    let actionModal;
    let categoryModal;
    
    document.addEventListener("DOMContentLoaded", () => {
        actionModal = new bootstrap.Modal(document.getElementById('actionCategoryModal'));
        categoryModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    });

    function openCategoryActionModal(id, name, desc, count) {
        document.getElementById('actCatName').textContent = name;
        document.getElementById('actCatProducts').textContent = `مرتبطة بـ ${count} منتج`;
        
        // Delete setup
        const deleteBtn = document.getElementById('deleteCatBtn');
        const deleteWarning = document.getElementById('deleteCatWarning');
        const deleteForm = document.getElementById('deleteCatForm');
        
        if (count > 0) {
            deleteBtn.style.display = 'none';
            deleteWarning.style.display = 'block';
        } else {
            deleteBtn.style.display = 'block';
            deleteWarning.style.display = 'none';
            deleteForm.action = `/categories/${id}`;
        }

        // Edit setup
        const editBtn = document.getElementById('editCatBtn');
        editBtn.onclick = function() {
            actionModal.hide();
            // wait for fade out then show edit modal
            setTimeout(() => {
                document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-fill text-orange me-2"></i>تعديل الفئة';
                document.getElementById('categoryName').value = name;
                document.getElementById('categoryDesc').value = desc !== 'null' ? desc : '';
                document.getElementById('categoryForm').action = `/categories/${id}`;
                document.getElementById('formMethod').value = 'PUT';
                categoryModal.show();
            }, 400);
        };
        
        actionModal.show();
    }

    // Reset add modal on close
    document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle-fill text-orange me-2"></i>إضافة فئة جديدة';
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryForm').action = "{{ route('categories.store') }}";
        document.getElementById('formMethod').value = 'POST';
    });
</script>
<style>
    .cursor-pointer { cursor: pointer; transition: background-color 0.2s; }
    .cursor-pointer:hover { background-color: rgba(234, 88, 12, 0.03) !important; }
</style>
@endpush
