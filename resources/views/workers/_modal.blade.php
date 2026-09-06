<div class="modal fade" id="workerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-dark" id="wModalTitle">إضافة موظف جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="workerForm" action="{{ route('workers.store') }}" method="POST" data-turbo="false">
                @csrf
                <input type="hidden" name="_method" id="wMethod" value="POST">
                <input type="hidden" name="return_url" id="wReturnUrl" value="">
                <div class="modal-body p-4">
                    <div class="modal-body p-4">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label text-muted">الاسم <span class="text-danger">*</span></label><input type="text" name="name" id="wName" class="form-control form-control-glass" required value="{{ old('name') }}"></div>
                        <div class="col-md-6"><label class="form-label text-muted">الوظيفة <span class="text-danger">*</span></label><input type="text" name="job_title" id="wJob" class="form-control form-control-glass" placeholder="حداد / دهان / مجمع / مشرف" required></div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted">نوع الموظف وتصنيفه <span class="text-danger">*</span></label>
                            <select name="worker_type" id="wType" class="form-select form-control-glass" required onchange="toggleWageInputs()">
                                <option value="daily">راتب/يومية ثابتة</option>
                                <option value="production">موظف إنتاج (قطعة/ورديات)</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="divProductionRole" style="display:none;">
                            <label class="form-label text-muted">دور الإنتاج <span class="text-danger">*</span></label>
                            <select name="production_role" id="wProdRole" class="form-select form-control-glass">
                                <option value="machinist">مكنجي (تجميع)</option>
                                <option value="scissors">مقص</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">موقع المصنع <span class="text-danger">*</span></label>
                            <select name="factory_location" id="wFactory" class="form-select form-control-glass" required>
                                <option value="{{ \App\Models\Setting::get('factory_1_name', 'مصنع 1') }}">{{ \App\Models\Setting::get('factory_1_name', 'مصنع 1') }}</option>
                                <option value="{{ \App\Models\Setting::get('factory_2_name', 'مصنع 2') }}">{{ \App\Models\Setting::get('factory_2_name', 'مصنع 2') }}</option>
                                <option value="{{ \App\Models\Setting::get('factory_3_name', 'مصنع 3') }}">{{ \App\Models\Setting::get('factory_3_name', 'مصنع 3') }}</option>
                                <option value="{{ \App\Models\Setting::get('factory_4_name', 'مصنع 4') }}">{{ \App\Models\Setting::get('factory_4_name', 'مصنع 4') }}</option>
                            </select>
                        </div>
                        
                        @hasrole('Admin')
                        <div class="col-12" id="divDailyWageType" style="display:block;">
                            <label class="form-label text-muted fw-bold">نظام الدفع (للموظفين الثابتين) <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="daily_wage_type" id="wWageTypeDaily" value="daily" checked>
                                    <label class="form-check-label fw-bold text-success" for="wWageTypeDaily">باليومية</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="daily_wage_type" id="wWageTypeHourly" value="hourly">
                                    <label class="form-check-label fw-bold text-info" for="wWageTypeHourly">بالساعة</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3" id="divHourlyWage">
                            <label class="form-label text-muted text-success fw-bold">أجر الساعة (ج.م)</label>
                            <input type="number" step="0.01" name="hourly_wage" id="wHourlyWage" class="form-control form-control-glass text-success fw-bold" value="0">
                        </div>
                        <div class="col-md-3" id="divDailyWage">
                            <label class="form-label text-muted text-success fw-bold">اليومية الثابتة (ج.م)</label>
                            <input type="number" step="0.01" name="daily_wage" id="wDailyWage" class="form-control form-control-glass text-success fw-bold" value="0">
                        </div>

                        {{-- نظام الأجر للموظفين الإنتاجيين --}}
                        <div class="col-12" id="divWageSystem" style="display:none;">
                            <label class="form-label text-muted fw-bold">نظام الأجر <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="wage_system" id="wWageShift" value="shift" checked onchange="toggleWageSystemInputs()">
                                    <label class="form-check-label fw-bold text-info" for="wWageShift">ورديات (بعدد المنتجات في الوردية)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="wage_system" id="wWagePiece" value="piece" onchange="toggleWageSystemInputs()">
                                    <label class="form-check-label fw-bold text-warning" for="wWagePiece">قطعة (السعر من إعدادات كل منتج)</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="divShiftWage" style="display:none;">
                            <label class="form-label text-muted text-warning fw-bold">أجر الوردية (ج.م)</label>
                            <input type="number" step="0.01" name="shift_wage" id="wShiftWage" class="form-control form-control-glass text-warning fw-bold" value="0">
                            <small class="text-muted">مثال: 120 ج.م للوردية الواحدة</small>
                        </div>
                        @endhasrole

                        <div class="col-md-6"><label class="form-label text-muted">الوردية <span class="text-danger">*</span></label><select name="shift_type" id="wShift" class="form-control form-control-glass" required><option value="morning">صباحي</option><option value="evening">مسائي</option><option value="night">ليلي</option></select></div>
                        <div class="col-md-6"><label class="form-label text-muted">تاريخ التعيين <span class="text-danger">*</span></label><input type="date" name="hire_date" id="wHire" class="form-control form-control-glass" required></div>
                        
                        <div class="col-md-4"><label class="form-label text-muted">الرقم القومي</label><input type="text" name="national_id" id="wNID" class="form-control form-control-glass"></div>
                        <div class="col-md-4"><label class="form-label text-muted">الهاتف</label><input type="text" name="phone" id="wPhone" class="form-control form-control-glass"></div>
                        <div class="col-md-4"><label class="form-label text-muted">العنوان</label><input type="text" name="address" id="wAddress" class="form-control form-control-glass"></div>
                        
                        <div class="col-md-9"><label class="form-label text-muted">ملاحظات</label><textarea name="notes" id="wNotes" class="form-control form-control-glass" rows="2"></textarea></div>
                        <div class="col-md-3 d-flex align-items-center mt-4">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="wActive" value="1" checked>
                                <label class="form-check-label fw-bold ms-2" for="wActive">موظف نشط</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">حفظ بيانات الموظف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleWageInputs() {
    let type = document.getElementById('wType').value;
    let divDaily = document.getElementById('divDailyWage');
    let divHourly = document.getElementById('divHourlyWage');
    let divWageSystem = document.getElementById('divWageSystem');
    let divShiftWage = document.getElementById('divShiftWage');
    let divProdRole = document.getElementById('divProductionRole');

    if (type === 'production') {
        if (divDaily) divDaily.style.display = 'none';
        if (divHourly) divHourly.style.display = 'none';
        if (document.getElementById('divDailyWageType')) document.getElementById('divDailyWageType').style.display = 'none';
        if (divWageSystem) divWageSystem.style.display = 'block';
        if (divProdRole) divProdRole.style.display = 'block';
        toggleWageSystemInputs();
    } else {
        if (divDaily) divDaily.style.display = 'block';
        if (divHourly) divHourly.style.display = 'block';
        if (document.getElementById('divDailyWageType')) document.getElementById('divDailyWageType').style.display = 'block';
        if (divWageSystem) divWageSystem.style.display = 'none';
        if (divShiftWage) divShiftWage.style.display = 'none';
        if (divProdRole) divProdRole.style.display = 'none';
    }
}

function toggleWageSystemInputs() {
    let type = document.getElementById('wType').value;
    if (type !== 'production') return;
    
    let isShift = document.getElementById('wWageShift').checked;
    let divShiftWage = document.getElementById('divShiftWage');
    
    if (isShift) {
        if (divShiftWage) divShiftWage.style.display = 'block';
    } else {
        if (divShiftWage) divShiftWage.style.display = 'none';
    }
}

function editWorker(w, returnUrl = '') {
    document.getElementById('wModalTitle').innerText = 'تعديل بيانات الموظف';
    document.getElementById('wName').value = w.name;
    document.getElementById('wJob').value = w.job_title;
    document.getElementById('wNID').value = w.national_id || '';
    document.getElementById('wPhone').value = w.phone || '';
    document.getElementById('wHire').value = w.hire_date ? w.hire_date.substring(0, 10) : '';
    document.getElementById('wShift').value = w.shift_type;
    document.getElementById('wType').value = w.worker_type || 'daily';
    document.getElementById('wProdRole').value = w.production_role || 'machinist';
    document.getElementById('wFactory').value = w.factory_location || "{{ \App\Models\Setting::get('factory_1_name', 'مصنع 1') }}";
    
    if (document.getElementById('wDailyWage')) document.getElementById('wDailyWage').value = w.daily_wage || 0;
    if (document.getElementById('wHourlyWage')) document.getElementById('wHourlyWage').value = w.hourly_wage || 0;
    
    // Set daily wage type
    let dwt = w.daily_wage_type || 'daily';
    if (document.getElementById('wWageTypeDaily')) {
        document.getElementById('wWageTypeDaily').checked = (dwt === 'daily');
        document.getElementById('wWageTypeHourly').checked = (dwt === 'hourly');
    }
    
    // Set wage system
    let sys = w.wage_system || 'shift';
    if (document.getElementById('wWageShift')) {
        document.getElementById('wWageShift').checked = (sys === 'shift');
        document.getElementById('wWagePiece').checked = (sys === 'piece');
    }
    
    if (document.getElementById('wShiftWage')) document.getElementById('wShiftWage').value = w.shift_wage || 0;
    
    document.getElementById('wAddress').value = w.address || '';
    document.getElementById('wNotes').value = w.notes || '';
    document.getElementById('wActive').checked = w.is_active ? true : false;
    
    document.getElementById('workerForm').action = `/workers/${w.id}`;
    document.getElementById('wMethod').value = 'PUT';
    document.getElementById('wReturnUrl').value = returnUrl;
    
    toggleWageInputs();
    
    new bootstrap.Modal(document.getElementById('workerModal')).show();
}

document.getElementById('workerModal')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('wModalTitle').innerText = 'إضافة موظف جديد';
    document.getElementById('workerForm').reset();
    document.getElementById('workerForm').action = "{{ route('workers.store') }}";
    document.getElementById('wMethod').value = 'POST';
    document.getElementById('wReturnUrl').value = '';
    document.getElementById('wType').value = 'daily';
    document.getElementById('wFactory').value = "{{ \App\Models\Setting::get('factory_1_name', 'مصنع 1') }}";
    if (document.getElementById('wWageShift')) {
        document.getElementById('wWageShift').checked = true;
    }
    if (document.getElementById('wWageTypeDaily')) {
        document.getElementById('wWageTypeDaily').checked = true;
    }
    document.getElementById('wActive').checked = true;
    toggleWageInputs();
});
</script>

