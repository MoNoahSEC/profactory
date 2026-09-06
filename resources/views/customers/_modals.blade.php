<!-- Modal: Edit Customer -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('customers.update', $customer) }}" method="POST" class="modal-content" style="border-radius:20px; border:none;">
            @csrf
            @method('PUT')
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil text-orange me-2"></i>تعديل بيانات العميل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-muted fw-bold">اسم العميل <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ $customer->name }}" required style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fw-bold">الشركة (اختياري)</label>
                    <input type="text" name="company_name" class="form-control" value="{{ $customer->company_name }}" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fw-bold">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fw-bold">العنوان</label>
                    <input type="text" name="address" class="form-control" value="{{ $customer->address }}" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fw-bold">نوع العميل</label>
                    <select name="customer_type" class="form-control" style="border-radius:10px;">
                        <option value="retail" {{ $customer->customer_type == 'retail' ? 'selected' : '' }}>تجزئة</option>
                        <option value="wholesale" {{ $customer->customer_type == 'wholesale' ? 'selected' : '' }}>جملة</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-orange rounded-pill px-4 fw-bold shadow-sm">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>
