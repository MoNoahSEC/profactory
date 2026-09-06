@extends('layouts.app')

@section('title', 'إعدادات النظام')
@section('page_title', 'لوحة التحكم والإعدادات')

@section('content')
<div class="row fade-in-up">
    <div class="col-12">
        <div class="content-card mb-4 p-0">
            <div class="content-card-header bg-white d-flex align-items-center">
                <div class="treasury-card-icon mb-0 me-3 shadow-sm bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-sliders"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark">إعدادات النظام الشاملة</h4>
            </div>
            
            <div class="card-body p-0">
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    
                    <div class="d-flex align-items-start p-4 flex-column flex-lg-row">
                        
                        <!-- Tabs Navigation -->
                        <div class="nav flex-column nav-pills w-100 w-lg-25 mb-4 mb-lg-0 border-end border-light pe-lg-3 gap-2" id="settings-tabs" role="tablist" aria-orientation="vertical" style="min-width: 250px;">
                            <button class="nav-link text-end active fw-bold px-4 py-3 rounded-4" id="tab-basic" data-bs-toggle="pill" data-bs-target="#pane-basic" type="button" role="tab">
                                <i class="bi bi-building me-2"></i> بيانات الشركة
                            </button>
                            <button class="nav-link text-end fw-bold px-4 py-3 rounded-4" id="tab-print" data-bs-toggle="pill" data-bs-target="#pane-print" type="button" role="tab">
                                <i class="bi bi-printer me-2"></i> تخطيط الطباعة
                            </button>
                            <button class="nav-link text-end fw-bold px-4 py-3 rounded-4" id="tab-labels" data-bs-toggle="pill" data-bs-target="#pane-labels" type="button" role="tab">
                                <i class="bi bi-translate me-2"></i> تخصيص المسميات
                            </button>
                            <button class="nav-link text-end fw-bold px-4 py-3 rounded-4" id="tab-toggles" data-bs-toggle="pill" data-bs-target="#pane-toggles" type="button" role="tab">
                                <i class="bi bi-toggle-on me-2"></i> خيارات الظهور
                            </button>
                            <button class="nav-link text-end fw-bold px-4 py-3 rounded-4" id="tab-factories" data-bs-toggle="pill" data-bs-target="#pane-factories" type="button" role="tab">
                                <i class="bi bi-diagram-3 me-2"></i> الفروع والمصانع
                            </button>
                            <button class="nav-link text-end fw-bold px-4 py-3 rounded-4" id="tab-treasuries" data-bs-toggle="pill" data-bs-target="#pane-treasuries" type="button" role="tab">
                                <i class="bi bi-safe me-2"></i> الخزائن
                            </button>
                            
                            <hr class="my-2 border-secondary opacity-10">
                            
                            <button class="nav-link text-end text-danger fw-bold px-4 py-3 rounded-4 bg-danger bg-opacity-10" id="tab-danger" data-bs-toggle="pill" data-bs-target="#pane-danger" type="button" role="tab">
                                <i class="bi bi-shield-exclamation me-2"></i> منطقة الخطر والأمان
                            </button>
                        </div>
                        
                        <!-- Tabs Content -->
                        <div class="tab-content w-100 ps-lg-4" id="settings-tabContent">
                            
                            <!-- Basic Data -->
                            <div class="tab-pane fade show active" id="pane-basic" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                                    <h5 class="fw-bold mb-0 text-primary">البيانات الأساسية والرسمية</h5>
                                </div>
                                
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">اسم المصنع/الشركة <span class="text-danger">*</span></label>
                                        <input type="text" name="company_name" class="form-control form-control-lg bg-light border-0 fw-bold shadow-none" value="{{ $settings['company_name'] ?? 'شركة مصنع سالم علي' }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">رقم الهاتف للتواصل</label>
                                        <input type="text" name="company_phone" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['company_phone'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">العنوان الرسمي</label>
                                        <input type="text" name="company_address" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['company_address'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">رقم التسجيل الضريبي</label>
                                        <input type="text" name="tax_id" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['tax_id'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">السجل التجاري</label>
                                        <input type="text" name="commercial_record" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['commercial_record'] ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Print Layout -->
                            <div class="tab-pane fade" id="pane-print" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                                    <h5 class="fw-bold mb-0 text-primary">تخطيط وتصميم الطباعة</h5>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">مقاس ورقة الطباعة الافتراضي</label>
                                        <select name="print_size" class="form-select form-select-lg bg-light border-0 shadow-none">
                                            <option value="A4" {{ ($settings['print_size'] ?? 'A4') == 'A4' ? 'selected' : '' }}>A4 - حجم كبير (للطلبيات الكبيرة)</option>
                                            <option value="A5" {{ ($settings['print_size'] ?? '') == 'A5' ? 'selected' : '' }}>A5 - نصف ورقة (للفواتير السريعة)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">موضع ترويسة الفاتورة</label>
                                        <select name="address_position" class="form-select form-select-lg bg-light border-0 shadow-none">
                                            <option value="header_left" {{ ($settings['address_position'] ?? 'header_left') == 'header_left' ? 'selected' : '' }}>أعلى اليسار</option>
                                            <option value="header_center" {{ ($settings['address_position'] ?? '') == 'header_center' ? 'selected' : '' }}>أعلى المنتصف</option>
                                            <option value="footer" {{ ($settings['address_position'] ?? '') == 'footer' ? 'selected' : '' }}>أسفل الفاتورة</option>
                                            <option value="hidden" {{ ($settings['address_position'] ?? '') == 'hidden' ? 'selected' : '' }}>إخفاء تماماً</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">شروط الفاتورة (تطبع أسفل الجدول)</label>
                                        <textarea name="invoice_terms" class="form-control bg-light border-0 shadow-none" rows="3">{{ $settings['invoice_terms'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Labels -->
                            <div class="tab-pane fade" id="pane-labels" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                                    <h5 class="fw-bold mb-0 text-primary">تخصيص المسميات في الفواتير</h5>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">عنوان المستند</label>
                                        <input type="text" name="invoice_title" class="form-control bg-light border-0 shadow-none" value="{{ $settings['invoice_title'] ?? 'فاتورة مبيعات' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">رقم الفاتورة</label>
                                        <input type="text" name="lbl_invoice_no" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_invoice_no'] ?? 'رقم الفاتورة' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">تاريخ الفاتورة</label>
                                        <input type="text" name="lbl_date" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_date'] ?? 'تاريخ الفاتورة' }}">
                                    </div>
                                    
                                    <div class="col-12 mt-4"><h6 class="fw-bold text-muted">جدول الأصناف</h6></div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">عمود الصنف</label>
                                        <input type="text" name="lbl_item" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_item'] ?? 'البيان / الصنف' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">عمود الكمية</label>
                                        <input type="text" name="lbl_qty" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_qty'] ?? 'الكمية' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">عمود السعر</label>
                                        <input type="text" name="lbl_price" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_price'] ?? 'السعر' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">عمود الإجمالي</label>
                                        <input type="text" name="lbl_total" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_total'] ?? 'الإجمالي' }}">
                                    </div>
                                    
                                    <div class="col-12 mt-4"><h6 class="fw-bold text-muted">ملخص الحساب</h6></div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">إجمالي الفاتورة</label>
                                        <input type="text" name="lbl_subtotal" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_subtotal'] ?? 'إجمالي الفاتورة' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">حساب سابق</label>
                                        <input type="text" name="lbl_prev_bal" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_prev_bal'] ?? 'حساب سابق' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">المدفوع نقداً</label>
                                        <input type="text" name="lbl_paid" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_paid'] ?? 'المدفوع نقداً' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-muted small">الرصيد المتبقي</label>
                                        <input type="text" name="lbl_net" class="form-control bg-light border-0 shadow-none" value="{{ $settings['lbl_net'] ?? 'الرصيد المتبقي' }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Toggles -->
                            <div class="tab-pane fade" id="pane-toggles" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                                    <h5 class="fw-bold mb-0 text-primary">خيارات الإظهار والإخفاء للطباعة</h5>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-4 bg-light rounded-4 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-check-label fw-bold text-dark d-block mb-1" for="show_prev_bal">إظهار الحساب السابق والنهائي</label>
                                                <small class="text-muted">طباعة كشف حساب مصغر أسفل الفاتورة</small>
                                            </div>
                                            <input class="form-check-input fs-3 m-0" type="checkbox" name="show_prev_bal" id="show_prev_bal" value="1" {{ ($settings['show_prev_bal'] ?? '1') == '1' ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-4 bg-light rounded-4 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-check-label fw-bold text-dark d-block mb-1" for="show_signatures">منطقة التوقيعات</label>
                                                <small class="text-muted">إظهار (توقيع المستلم - توقيع المحاسب)</small>
                                            </div>
                                            <input class="form-check-input fs-3 m-0" type="checkbox" name="show_signatures" id="show_signatures" value="1" {{ ($settings['show_signatures'] ?? '1') == '1' ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Factories -->
                            <div class="tab-pane fade" id="pane-factories" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                                    <h5 class="fw-bold mb-0 text-primary">إعدادات المصانع والفروع</h5>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">اسم المصنع 1 (الافتراضي)</label>
                                        <input type="text" name="factory_1_name" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['factory_1_name'] ?? 'مصنع التقفيل' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">اسم المصنع 2</label>
                                        <input type="text" name="factory_2_name" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['factory_2_name'] ?? 'مصنع الاقفاص' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">اسم المصنع 3</label>
                                        <input type="text" name="factory_3_name" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $settings['factory_3_name'] ?? 'مصنع المسامير' }}">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Treasuries -->
                            <div class="tab-pane fade" id="pane-treasuries" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light justify-content-between">
                                    <h5 class="fw-bold mb-0 text-primary">أسماء الخزائن (Treasuries)</h5>
                                </div>
                                <div class="row g-3">
                                    @foreach($treasuries ?? [] as $t)
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" name="treasury_name" form="updateTreasuryForm{{ $t->id }}" class="form-control form-control-lg bg-light border-0 shadow-none" value="{{ $t->name }}">
                                            <button type="submit" form="updateTreasuryForm{{ $t->id }}" class="btn btn-primary px-4 fw-bold">حفظ</button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Danger Zone -->
                            <div class="tab-pane fade" id="pane-danger" role="tabpanel">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-danger">
                                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-lock-fill me-2"></i> الصيانة والأمان (Danger Zone)</h5>
                                </div>
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 shadow-sm bg-success bg-opacity-10 rounded-4">
                                            <div class="card-body p-4">
                                                <h5 class="fw-bold text-success mb-3"><i class="bi bi-cloud-arrow-down-fill me-2"></i> النسخ الاحتياطي للأمان</h5>
                                                <p class="text-muted small mb-4">احرص على أخذ نسخة احتياطية من جميع بياناتك بشكل دوري للحفاظ عليها من الضياع، النسخة تحمل صيغة SQLite ومحمية تماماً.</p>
                                                <a href="{{ route('settings.backup') }}" class="btn btn-success w-100 fw-bold rounded-pill">
                                                    تحميل نسخة احتياطية (Backup)
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 shadow-sm bg-warning bg-opacity-10 rounded-4">
                                            <div class="card-body p-4">
                                                <h5 class="fw-bold text-warning mb-3"><i class="bi bi-arrow-counterclockwise me-2"></i> تصفير حركات الخزينة</h5>
                                                <p class="text-muted small mb-4">سيتم حذف جميع الحركات المالية المسجلة في الخزينة مع الحفاظ على الأرصدة الحالية للعملاء والموردين.</p>
                                                <form action="{{ route('settings.zeroTreasury') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تصفير حركات الخزينة؟');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill text-dark">
                                                        تصفير حركات الخزينة
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 shadow-sm bg-warning bg-opacity-10 rounded-4">
                                            <div class="card-body p-4">
                                                <h5 class="fw-bold text-warning mb-3"><i class="bi bi-person-dash-fill me-2"></i> تصفير سلف العمال</h5>
                                                <p class="text-muted small mb-4">سيتم مسح جميع السلف المسجلة للعمال وتصفير أرصدتهم لبداية فترة جديدة.</p>
                                                <form action="{{ route('settings.zeroAdvances') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من مسح جميع سلف العمال؟');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill text-dark">
                                                        تصفير سلف العمال
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 rounded-4">
                                            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                                                <div>
                                                    <h5 class="fw-bold text-danger mb-1"><i class="bi bi-x-octagon-fill me-2"></i> مسح جميع بيانات المصنع تماماً (Factory Reset)</h5>
                                                    <p class="text-muted small mb-0">سيتم حذف الفواتير، الحسابات، العمال، الخزائن.. وكل شيء. لا يمكن التراجع عن هذه الخطوة أبداً!</p>
                                                </div>
                                                <button type="button" class="btn btn-danger px-4 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#wipeModal">
                                                    ضبط المصنع (مسح شامل)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white border-top border-light p-4 text-start">
                        <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill fw-bold fs-5 shadow-sm">
                            <i class="bi bi-check-lg me-2"></i> حفظ وتطبيق الإعدادات بنجاح
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Wipe Database Modal -->
<div class="modal fade" id="wipeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> تحذير أمني خطير جداً</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex p-4 mb-4">
                    <i class="bi bi-trash3-fill text-danger" style="font-size: 3rem;"></i>
                </div>
                <h4 class="fw-bold mb-3 text-dark">هل أنت متأكد من مسح النظام بالكامل؟</h4>
                <p class="text-muted mb-4">جميع الفواتير، الحسابات، الديون، وأرصدة العمال سيتم مسحها بالكامل ولا يمكن استرجاعها إلا بوجود نسخة احتياطية (Backup) مسبقة.</p>
                <div class="bg-light p-3 rounded-3 text-start mb-4 border">
                    <label class="form-label fw-bold text-danger">للتأكيد، يرجى كتابة كلمة "تأكيد" في المربع أدناه:</label>
                    <input type="text" id="wipeConfirmInput" class="form-control form-control-lg text-center fw-bold" placeholder="تأكيد" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer justify-content-between border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">تراجع وإلغاء</button>
                <form action="{{ route('settings.wipe') }}" method="POST">
                    @csrf
                    <button type="submit" id="wipeSubmitBtn" class="btn btn-danger px-4 fw-bold rounded-pill shadow-sm" disabled>نعم، قم بمسح النظام!</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('wipeConfirmInput').addEventListener('input', function() {
        document.getElementById('wipeSubmitBtn').disabled = (this.value !== 'تأكيد');
    });
</script>

@foreach($treasuries ?? [] as $t)
<form id="updateTreasuryForm{{ $t->id }}" action="{{ route('treasury.update', $t->id) }}" method="POST" style="display:none;">
    @csrf
    @method('PUT')
</form>
@endforeach

<style>
    /* Styling for the modern nav pills */
    .nav-pills .nav-link {
        color: var(--text-muted);
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }
    .nav-pills .nav-link:hover {
        background-color: #f8fafc;
        color: var(--primary);
    }
    .nav-pills .nav-link.active {
        background-color: var(--primary) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
    }
    .nav-pills .nav-link.text-danger.active {
        background-color: #dc3545 !important;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
    }
</style>
@endsection
