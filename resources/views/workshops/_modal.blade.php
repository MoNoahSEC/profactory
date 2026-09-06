{{-- Modal موحد للإضافة والتعديل --}}
<div class="modal fade" id="workshopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel text-white">
            <div class="modal-header border-secondary" style="border-color:rgba(255,255,255,0.1)!important;">
                <h5 class="modal-title fw-bold" id="workshopModalTitle">إضافة ورشة جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="workshopForm" action="{{ route('workshops.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="workshopMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted">اسم الورشة <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="wName" class="form-control form-control-glass" required placeholder="مثال: ورشة أبو أحمد">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">رقم الهاتف</label>
                            <input type="text" name="phone" id="wPhone" class="form-control form-control-glass" placeholder="اختياري">
                        </div>
                        <div class="col-md-6" id="initialBalanceRow">
                            <label class="form-label text-muted">الرصيد الافتتاحي</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="initial_balance" id="wInitialBalance" class="form-control form-control-glass" value="0">
                                <span class="input-group-text bg-transparent text-muted border-secondary">ج.م</span>
                            </div>
                            <small class="text-muted opacity-75 mt-1 d-block">موجب = الورشة مديونة لنا | سالب = نحن مديونون لهم</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted">العنوان</label>
                            <input type="text" name="address" id="wAddress" class="form-control form-control-glass" placeholder="اختياري">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary" style="border-color:rgba(255,255,255,0.1)!important;">
                    <button type="button" class="btn-glass-secondary px-4 py-2 rounded-3 me-2" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn-glass px-4 py-2 rounded-3">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editWorkshop(w) {
    document.getElementById('workshopModalTitle').innerText = 'تعديل بيانات الورشة';
    document.getElementById('wName').value = w.name;
    document.getElementById('wPhone').value = w.phone || '';
    document.getElementById('wAddress').value = w.address || '';
    document.getElementById('initialBalanceRow').style.display = 'none';
    document.getElementById('workshopForm').action = '/workshops/' + w.id;
    document.getElementById('workshopMethod').value = 'PUT';
    new bootstrap.Modal(document.getElementById('workshopModal')).show();
}

document.getElementById('workshopModal')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('workshopModalTitle').innerText = 'إضافة ورشة جديدة';
    document.getElementById('workshopForm').reset();
    document.getElementById('workshopForm').action = "{{ route('workshops.store') }}";
    document.getElementById('workshopMethod').value = 'POST';
    document.getElementById('initialBalanceRow').style.display = '';
    document.getElementById('wInitialBalance').value = '0';
});
</script>
@endpush
