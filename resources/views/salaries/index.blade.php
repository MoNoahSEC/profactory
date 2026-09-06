@extends('layouts.app')
@section('title', 'جدول التحضير الأسبوعي التفاعلي | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'جدول التحضير والإنتاج الأسبوعي (السبت ← الجمعة)')

@push('styles')
<style>
    .sticky-glass-col {
        background-color: #f8f9fa !important;
        border-left: 2px solid #ea580c !important;
        min-width: 130px;
    }
    @media (min-width: 992px) {
        .sticky-glass-col {
            position: sticky !important;
            right: 0 !important;
            z-index: 2;
            box-shadow: -3px 0 8px rgba(0,0,0,0.08) !important;
        }
    }
    @media (max-width: 991px) {
        .sticky-glass-col { position: static !important; }
    }
    .hover-bg-light:hover { background-color: rgba(255,255,255,0.4) !important; cursor: pointer; }
</style>
@endpush

@section('content')

<div class="glass-card water-card mb-4 fade-in-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-calendar-week text-success me-2"></i>جدول التحضير الأسبوعي التفاعلي</h5>
            <div class="d-flex align-items-center gap-2 mt-2">
                <a href="{{ route('salaries.index', ['start_date' => $prevWeek]) }}" class="btn btn-sm btn-glass-secondary"><i class="bi bi-chevron-right"></i> الأسبوع السابق</a>
                <span class="text-muted small mx-2">الفترة من <strong>{{ \Carbon\Carbon::parse($startDateStr)->translatedFormat('l d/m') }}</strong> إلى <strong>{{ \Carbon\Carbon::parse($endDateStr)->translatedFormat('l d/m') }}</strong></span>
                <a href="{{ route('salaries.index', ['start_date' => $nextWeek]) }}" class="btn btn-sm btn-glass-secondary">الأسبوع القادم <i class="bi bi-chevron-left"></i></a>
            </div>
        </div>
        <form action="{{ route('salaries.index') }}" method="GET" class="d-flex flex-wrap gap-2">
            <div>
                <label class="form-label text-muted small mb-0">من (السبت)</label>
                <input type="date" name="start_date" value="{{ $startDateStr }}" class="form-control form-control-glass form-control-sm">
            </div>
            <div class="d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-glass-secondary"><i class="bi bi-search"></i> عرض</button>
                @hasrole('Admin|Cashier')
                <a href="{{ route('worker-prices.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold">
                    <i class="bi bi-tags-fill"></i> أسعار المصنعيات
                </a>
                <a href="{{ route('salaries.printPaid', ['start_date' => $startDateStr, 'end_date' => $endDateStr]) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3">
                    <i class="bi bi-printer"></i> تقرير المنصرف
                </a>
                <a href="{{ route('salaries.index', ['start_date' => $startDateStr, 'print_all' => 'true']) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-printer"></i> كشف العمل والإنتاج
                </a>
                <form action="{{ route('salaries.recalculate') }}" method="POST" class="d-inline-block">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDateStr }}">
                    <input type="hidden" name="end_date" value="{{ $endDateStr }}">
                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3" onclick="return confirm('هل أنت متأكد من إعادة حساب وتحديث الأجور؟ قد يستغرق هذا بضع ثوانٍ بناءً على عدد الحركات.')">
                        <i class="bi bi-arrow-repeat"></i> إعادة حساب وتحديث الأجور
                    </button>
                </form>
                @endhasrole
            </div>
        </form>
    </div>
</div>

<div class="glass-card water-card p-0 fade-in-up border-0 bg-transparent">
    <div class="scroll-inner w-100 table-responsive glass-card border-0 shadow-sm p-0 mb-4" style="border-radius: 16px; overflow-x: auto; padding-bottom: 20px; -webkit-overflow-scrolling: touch;">
        <table class="table table-hover table-striped align-middle text-center mb-0" style="min-width: {{ auth()->user()->hasRole('Admin') ? '1200px' : '750px' }}; border-collapse: separate; border-spacing: 0;">
            <thead class="position-sticky top-0 z-3" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr class="text-secondary" style="font-size: 0.9rem;">
                    <th class="sticky-glass-col text-start ps-4 border-end" style="width: {{ auth()->user()->hasRole('Admin') ? '15%' : '20%' }}; min-width: 120px; z-index: 4;">الموظف</th>
                    @foreach($days as $day)
                        <th style="width: {{ auth()->user()->hasRole('Admin') ? '9%' : '10%' }}; min-width: 70px;" class="{{ \Carbon\Carbon::parse($day['date'])->isToday() ? 'bg-primary bg-opacity-10 border-primary' : '' }}">
                            {{ $day['name'] }}
                            @if(\Carbon\Carbon::parse($day['date'])->isToday())
                                <span class="badge bg-primary text-white rounded-pill ms-1 fw-normal" style="font-size: 0.65rem; padding: 2px 5px; box-shadow: 0 1px 3px rgba(234,88,12,0.2);">اليوم</span>
                            @endif
                            <br>
                            <small class="{{ \Carbon\Carbon::parse($day['date'])->isToday() ? 'text-primary fw-bold' : 'text-muted fw-normal' }}">{{ $day['day_month'] }}</small>
                        </th>
                    @endforeach
                    <th style="width: 8%; min-width: 80px;">الإجمالي<br><small class="text-muted fw-normal">وردية / أقفاص</small></th>
                    @hasrole('Admin')
                    <th style="width: 8%;">الاستحقاق<br><small class="text-muted fw-normal">ج.م</small></th>
                    <th style="width: 7%;">السلف<br><small class="text-muted fw-normal">ج.م</small></th>
                    <th style="width: 7%;">خصومات<br><small class="text-muted fw-normal">ج.م</small></th>
                    <th style="width: 7%;">مكافآت<br><small class="text-muted fw-normal">ج.م</small></th>
                    <th style="width: 9%;">الصافي<br><small class="text-muted fw-normal">ج.م</small></th>
                    <th style="width: 10%;">إجراءات</th>
                    @endhasrole
                </tr>
            </thead>
            <tbody>
                @php $prevFactory = null; @endphp
                @forelse($grid as $workerId => $row)
                    @php $currentFactory = $row['worker']->factory_location ?: 'أخرى'; @endphp
                    @if($currentFactory !== $prevFactory)
                        <tr>
                            <td class="fw-bold text-white text-start py-2" 
                                style="background-color: #ea580c !important; font-size: 0.95rem; padding-right: 10px;">
                                <i class="bi bi-building me-1"></i> {{ $currentFactory }}
                            </td>
                            <td colspan="{{ auth()->user()->hasRole('Admin') ? 14 : 8 }}" 
                                style="background-color: rgba(234, 88, 12, 0.08) !important;"></td>
                        </tr>
                        @php $prevFactory = $currentFactory; @endphp
                    @endif
                    <tr>
                        <td class="text-start sticky-glass-col ps-4 border-end" style="z-index: 2; background: #fff;">
                            <a href="javascript:void(0)" onclick="editWorker({{ json_encode($row['worker']) }}, '{{ url()->full() }}')" class="fw-bold text-dark text-decoration-none" title="تعديل بيانات الموظف" style="font-size: 1.05rem;">{{ $row['worker']->name }}</a>
                            <small class="text-muted d-block">
                                @hasrole('Admin')
                                    @if($row['worker']->worker_type === 'daily')
                                        يومية
                                    @else
                                        {{ $row['worker']->role_label }}
                                    @endif
                                @else
                                    {{ $row['worker']->role_label }}
                                @endhasrole
                            </small>

                        </td>

                        @foreach($days as $day)
                            @php 
                                $cell = $row['days'][$day['date']]; 
                                $isToday = \Carbon\Carbon::parse($day['date'])->isToday();
                            @endphp
                            <td class="{{ $row['is_paid'] ? 'bg-light' : 'cursor-pointer hover-bg-light' }} position-relative {{ $isToday ? 'bg-primary bg-opacity-10' : '' }}" 
                                @if(!$row['is_paid'])
                                onclick="openEditModal({{ $workerId }}, '{{ addslashes($row['worker']->name) }}', '{{ $row['worker']->worker_type }}', '{{ $row['worker']->daily_wage_type ?? 'daily' }}', '{{ $row['worker']->production_role }}', '{{ $day['date'] }}', '{{ $cell['status'] }}', '{{ $cell['time_in'] }}', '{{ $cell['time_out'] }}', {{ json_encode($cell['productions'] ?? []) }})"
                                title="{{ $row['worker']->worker_type === 'production' && $cell['display_text'] !== '-' ? '✏️ اضغط لتعديل أو حذف الإنتاج' : 'اضغط لتعديل ' . $day['name'] }}"
                                @else
                                title="تم الصرف - لا يمكن التعديل"
                                @endif
                                >
                                <span class="{{ $cell['display_color'] }} d-block" style="font-size: {{ mb_strlen($cell['display_text'] ?? '') > 15 ? '0.75rem' : '0.9rem' }}; {{ mb_strlen($cell['display_text'] ?? '') > 15 ? 'line-height: 1.3;' : '' }}">
                                    {{ $cell['display_text'] }}
                                </span>
                                @if($row['worker']->worker_type === 'production' && !$row['is_paid'] && $cell['display_text'] !== '-')
                                <span style="position:absolute; top:2px; left:3px; font-size:0.65rem; opacity:0.5;" title="قابل للتعديل">✏️</span>
                                @endif
                            </td>
                        @endforeach

                        <td class="fw-bold text-dark bg-opacity-10 bg-secondary" style="font-size: 0.85rem; line-height: 1.5; white-space: pre-line;">{{ $row['summary_text'] }}</td>
                        @hasrole('Admin')
                        <td class="fw-bold text-success" style="background-color: rgba(25, 135, 84, 0.05);">{{ number_format($row['total_pay'], 0) }}</td>
                        <td class="fw-bold" style="background-color: rgba(220, 53, 69, 0.05);">
                            @if($row['total_advances'] > 0)
                                <a href="{{ route('advances.index', ['worker_id' => $workerId]) }}"
                                   class="text-danger text-decoration-none fw-bold"
                                   title="اضغط لعرض سلف {{ $row['worker']->name }}" target="_blank">
                                    {{ number_format($row['total_advances'], 0) }}
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.65rem;"></i>
                                </a>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="fw-bold text-danger" style="background-color: rgba(220, 53, 69, 0.05);">{{ number_format($row['total_penalties'], 0) }}</td>
                        <td class="fw-bold text-primary" style="background-color: rgba(13, 110, 253, 0.05);">{{ number_format($row['total_bonuses'], 0) }}</td>
                        <td class="fw-bolder text-success" style="background-color: rgba(25, 135, 84, 0.15); font-size: 1.2rem;">{{ number_format($row['net_pay'] ?? $row['net_salary'], 0) }}</td>
                        <td>
                            @if($row['is_paid'] && $row['salary_record'])
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-success"><i class="bi bi-check-all"></i> تم الصرف</span>
                                    @hasrole('Admin')
                                    <form action="{{ route('salaries.reverse', $row['salary_record']->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirmReverse('{{ addslashes($row['worker']->name) }}', {{ $row['salary_record']->net_salary }})">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="إلغاء عملية الصرف وإرجاع السلف">
                                            <i class="bi bi-x-circle-fill fs-6"></i>
                                        </button>
                                    </form>
                                    @endhasrole
                                </div>
                                
                                @if($row['worker']->phone)
                                    @php
                                        $wPhone = ltrim($row['worker']->phone, '0');
                                        $waWPhone = '20' . $wPhone;
                                        $waWMsg = "مرحباً {$row['worker']->name}،\nتم صرف راتب الأسبوع بصافي مبلغ: " . number_format($row['net_salary'], 0) . " ج.م\nللاطلاع على تفاصيل القبض:\n" . route('salaries.print', $row['salary_record']->id);
                                        $waWUrl = "https://wa.me/{$waWPhone}?text=" . urlencode($waWMsg);
                                    @endphp
                                    <a href="{{ $waWUrl }}" target="_blank" class="btn btn-sm text-white w-100 mb-1" style="background-color: #25D366; border-radius: 8px;">
                                        <i class="bi bi-whatsapp"></i> إرسال واتساب
                                    </a>
                                @endif
                                <a href="{{ route('salaries.print', $row['salary_record']->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 mb-1"><i class="bi bi-printer"></i> طباعة</a>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-success w-100 fw-bold" 
                                    onclick="openPayModal({{ $workerId }}, '{{ addslashes($row['worker']->name) }}', {{ $row['total_pay'] }}, {{ $row['total_advances'] }}, {{ $row['total_penalties'] }}, {{ $row['total_bonuses'] }}, '{{ $startDateStr }}', '{{ $endDateStr }}', {{ json_encode($row['advances_list']->map(fn($a) => ['date' => $a->date->format('Y-m-d'), 'amount' => $a->amount, 'notes' => $a->notes ?? ''])->values()) }})">
                                    <i class="bi bi-cash me-1"></i> صرف
                                </button>
                            @endif
                        </td>
                        @endhasrole
                    </tr>
                @empty
                <tr>
                    <td colspan="14" class="py-5 text-muted">لا يوجد موظفين مسجلين.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Daily Record Modal (Smart & Compact) -->
<div class="modal fade" id="editDayModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-calendar2-check fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="editModalTitle">تعديل اليوم</h6>
                        <small class="text-muted fw-bold" id="edit_display_date"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="editDayForm">
                @csrf
                <input type="hidden" name="worker_id" id="edit_worker_id">
                <input type="hidden" name="date" id="edit_date">
                <input type="hidden" name="type" id="edit_type">
                <input type="hidden" id="edit_daily_wage_type">
                
                <div class="modal-body py-3">
                    <!-- Daily Workers (Attendance) -->
                    <div id="daily_inputs" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small mb-2">حالة الحضور</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="status" id="status_present" value="present" onchange="toggleTimes(this.value)">
                                <label class="btn btn-outline-success flex-fill fw-bold rounded-pill" for="status_present"><i class="bi bi-check-circle me-1"></i> حاضر</label>

                                <input type="radio" class="btn-check" name="status" id="status_half" value="half_day" onchange="toggleTimes(this.value)">
                                <label class="btn btn-outline-warning flex-fill fw-bold rounded-pill" for="status_half"><i class="bi bi-brightness-alt-high me-1"></i> نصف يوم</label>

                                <input type="radio" class="btn-check" name="status" id="status_absent" value="absent" onchange="toggleTimes(this.value)">
                                <label class="btn btn-outline-danger flex-fill fw-bold rounded-pill" for="status_absent"><i class="bi bi-x-circle me-1"></i> غائب</label>
                            </div>
                        </div>
                        
                        <div id="timeFields" class="row g-2" style="display: none; background: #f8f9fa; padding: 10px; border-radius: 12px;">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold mb-1"><i class="bi bi-box-arrow-in-right me-1"></i> وقت الحضور</label>
                                <input type="time" name="time_in" id="edit_time_in" class="form-control form-control-glass text-center fw-bold">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold mb-1"><i class="bi bi-box-arrow-right me-1"></i> وقت الانصراف</label>
                                <input type="time" name="time_out" id="edit_time_out" class="form-control form-control-glass text-center fw-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Shift / Production Workers -->
                    <div id="production_inputs" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-dark small mb-0">سجل الإنتاج (الورديات)</label>
                            <button type="button" id="clear_all_prod_btn" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" onclick="clearAllProduction()" title="تفريغ كل السجلات لهذا اليوم">
                                <i class="bi bi-trash3-fill me-1"></i> مسح الكل
                            </button>
                        </div>
                        
                        <div class="p-2 mb-2" style="background: rgba(13,110,253,0.03); border: 1px solid rgba(13,110,253,0.1); border-radius: 12px;">
                            <div id="dynamic_production_rows" class="d-flex flex-column gap-2">
                                <!-- Rows -->
                            </div>
                            <button type="button" id="add_prod_btn" class="btn btn-primary btn-sm w-100 mt-2 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> إضافة صنف جديد
                            </button>
                        </div>

                        <!-- Scissors Worker Summary -->
                        <div id="scissors_summary_box" class="mt-2 p-2 bg-warning bg-opacity-10 rounded border border-warning" style="display: none;">
                            <h6 class="text-warning fw-bold mb-1 fs-6"><i class="bi bi-scissors"></i> العمل كـ "مقص" خلف المكنجية:</h6>
                            <div id="scissors_summary_content" class="text-dark small">جاري التحميل...</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success rounded-pill flex-fill fw-bold shadow" id="btnSaveEdit">
                        <i class="bi bi-check2-circle me-1"></i> حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pay Salary Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg border-0">
        <div class="modal-content bg-dark border border-secondary shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-bottom border-secondary" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(25, 135, 84, 0.1));">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-25 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-white mb-0" id="payModalTitle">إتمام عملية الصرف</h4>
                        <small class="text-muted" id="pay_worker_name_sub">تأكيد تفاصيل الصرف وخصم السلف</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('salaries.pay_all') }}" method="POST" id="payForm">
                @csrf
                <input type="hidden" name="worker_id" id="pay_worker_id">
                <input type="hidden" name="start_date" id="pay_start_date">
                <input type="hidden" name="end_date" id="pay_end_date">
                <input type="hidden" name="total_pay" id="pay_total_pay">
                <input type="hidden" name="penalties" id="pay_penalties">
                
                <div class="modal-body p-4 p-md-5">
                    
                    <div class="text-center mb-4 fade-in-up">
                        <h3 class="fw-bold text-white mb-1" id="pay_worker_name"></h3>
                        <div class="badge bg-secondary bg-opacity-25 text-light rounded-pill px-3 py-2 fw-normal"><i class="bi bi-calendar3 me-1"></i> عن الأسبوع المحدد</div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="glass-panel p-3 text-center h-100 border-top border-3 border-primary" style="background: rgba(13, 110, 253, 0.03);">
                                <label class="form-label text-muted small fw-bold mb-1"><i class="bi bi-cash-stack me-1"></i> الراتب المستحق</label>
                                <input type="text" class="form-control bg-transparent border-0 fw-bold text-primary fs-3 text-center p-0" id="pay_display_total" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="glass-panel p-3 text-center h-100 border-top border-3 border-danger" style="background: rgba(220, 53, 69, 0.03);">
                                <label class="form-label text-muted small fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i> الخصومات والجزاءات</label>
                                <input type="text" class="form-control bg-transparent border-0 fw-bold text-danger fs-3 text-center p-0" id="pay_display_penalties" readonly>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="glass-panel p-3 text-center h-100 border-top border-3 border-warning" style="background: rgba(255, 193, 7, 0.03);">
                                <label class="form-label text-muted small fw-bold mb-1"><i class="bi bi-hourglass-split me-1"></i> السلف غير المسددة</label>
                                <input type="text" class="form-control bg-transparent border-0 fw-bold text-warning fs-3 text-center p-0" id="pay_display_advances" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Advances Breakdown --}}
                    <div class="col-12 mb-4" id="advances_breakdown_box" style="display:none;">
                        <div class="glass-panel border-warning p-3">
                            <h6 class="fw-bold text-warning mb-3 d-flex align-items-center"><i class="bi bi-journal-text me-2 fs-5"></i> تفاصيل السلف المتراكمة:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0" id="advances_breakdown_table">
                                    <thead class="border-bottom border-secondary">
                                        <tr class="text-muted small"><th>التاريخ</th><th>المبلغ</th><th>ملاحظات</th></tr>
                                    </thead>
                                    <tbody id="advances_breakdown_tbody"></tbody>
                                    <tfoot>
                                        <tr class="fw-bold border-top border-secondary">
                                            <td class="text-white pt-2">إجمالي السلف</td>
                                            <td class="text-warning pt-2 fs-5" id="advances_breakdown_total"></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="glass-panel p-4 h-100 position-relative" style="background: rgba(255, 193, 7, 0.05); border-color: rgba(255, 193, 7, 0.2);">
                                <div class="position-absolute top-0 end-0 p-3 opacity-25"><i class="bi bi-scissors fs-1 text-warning"></i></div>
                                <label class="form-label fw-bold text-warning mb-3">المبلغ المراد سداده من السلفة الآن (ج.م)</label>
                                <div class="input-group input-group-lg shadow-sm">
                                    <input type="number" name="advances" id="pay_deduct_advance" class="form-control fw-bold fs-4 text-center bg-dark text-white border-warning" min="0" step="1" required oninput="calculateNetPay()">
                                    <button class="btn btn-warning fw-bold px-4" type="button" onclick="setMaxAdvance()">سداد الكل</button>
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i> إذا سددت جزءاً، سيتم ترحيل الباقي تلقائياً.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="glass-panel p-4 h-100 position-relative" style="background: rgba(25, 135, 84, 0.05); border-color: rgba(25, 135, 84, 0.2);">
                                <div class="position-absolute top-0 end-0 p-3 opacity-25"><i class="bi bi-gift fs-1 text-success"></i></div>
                                <label class="form-label fw-bold text-success mb-3">مكافأة إضافية (ج.م) <span class="badge bg-secondary bg-opacity-25 text-light ms-1 fw-normal">اختياري</span></label>
                                <input type="number" name="bonus" id="pay_bonus" class="form-control form-control-lg fw-bold fs-4 text-center bg-dark text-success border-success shadow-sm" min="0" step="0.5" value="0" oninput="calculateNetPay()" placeholder="0">
                                <small class="text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i> تُضاف مباشرة إلى الصافي النهائي.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-5">
                        <div class="p-4 rounded-4 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(13, 110, 253, 0.15)); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                            <div class="position-absolute top-50 start-50 translate-middle w-100 h-100" style="background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);"></div>
                            <h5 class="text-white fw-bold mb-2 position-relative z-1" style="letter-spacing: 0.5px;">الصافي النهائي للاستلام النقدي</h5>
                            <h1 class="display-3 fw-bold text-white mb-0 position-relative z-1 text-shadow-sm" id="pay_display_net" style="text-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);"></h1>
                            <input type="hidden" name="net_salary" id="pay_net_salary">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top border-secondary p-4 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary px-5 py-2 fw-bold rounded-pill" data-bs-dismiss="modal">إلغاء التغييرات</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow-lg d-flex align-items-center gap-2" id="btnConfirmPay" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                        <span>تأكيد واعتماد الصرف</span>
                        <i class="bi bi-check2-circle fs-5"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('workers._modal')

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editDayModal'));
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const DELETE_PROD_URL = '{{ url("salaries/production") }}';

    const productsList = {!! json_encode($products) !!};
    const scissorsList = {!! json_encode($scissorsWorkers) !!};

    // ✅ تأكيد إلغاء القبض
    function confirmReverse(workerName, netAmount) {
        return confirm(
            '⚠️ تحذير: إلغاء القبض\n\n' +
            'الموظف: ' + workerName + '\n' +
            'المبلغ الذي سيُسترجع: ' + parseFloat(netAmount).toFixed(2) + ' ج.م\n\n' +
            'هذا سيؤدي إلى:\n' +
            '• إرجاع السلف المخصومة (غير مخصومة)\n' +
            '• إرجاع الجزاءات (غير مخصومة)\n' +
            '• عكس قيد الخزينة\n' +
            '• إلغاء سجل الصرف\n\n' +
            'هل أنت متأكد؟'
        );
    }

    function addProductionRow(productId, qty, scissorsId, isScissorsWorker, recordId) {
        const container = document.getElementById('dynamic_production_rows');
        let displayScissors = (isScissorsWorker || document.getElementById('edit_type').value !== 'production') ? 'none' : 'block';
        
        let html = `
        <div class="production-row position-relative bg-white p-2 rounded shadow-sm border border-light" data-record-id="${recordId || ''}">
            <input type="hidden" name="record_ids[]" value="${recordId || ''}">
            <div class="d-flex gap-2 align-items-center">
                <div class="flex-grow-1">
                    <select name="product_ids[]" class="form-select form-select-sm form-control-glass prod_select_input fw-bold" required>
                        <option value="">- الصنف -</option>`;
        productsList.forEach(p => {
            html += `<option value="${p.id}" ${productId == p.id ? 'selected' : ''}>${p.name}</option>`;
        });
        html += `   </select>
                </div>
                <div style="width: 80px;">
                    <input type="number" name="quantities[]" class="form-control form-control-sm form-control-glass text-center fw-bold text-primary fs-6 px-1" min="1" value="${qty}" placeholder="الكمية" required>
                </div>
                <button type="button" class="btn btn-sm btn-light text-danger delete-prod-row rounded-circle shadow-sm" data-record-id="${recordId || ''}" style="width: 32px; height: 32px; padding: 0;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <div class="mt-2 scissors_selector_col" style="display: ${displayScissors};">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-0"><i class="bi bi-scissors"></i></span>
                    <select name="scissors_ids[]" class="form-select form-select-sm form-control-glass scissors_select_input text-secondary border-start-0">
                        <option value="">- بدون عامل مقص -</option>`;
        scissorsList.forEach(sw => {
            html += `<option value="${sw.id}" ${scissorsId == sw.id ? 'selected' : ''}>${sw.name}</option>`;
        });
        html += `   </select>
                </div>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    // Event delegation: handle delete-prod-row buttons
    document.getElementById('dynamic_production_rows').addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-prod-row');
        if (!btn) return;

        const row = btn.closest('.production-row');
        // Read record ID from the button itself OR from the parent row div
        const recordId = btn.dataset.recordId || (row ? row.dataset.recordId : '');

        if (recordId) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch(`${DELETE_PROD_URL}/${recordId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        checkEmptyRows();
                    }, 300);
                } else {
                    alert('خطأ: ' + (data.message || 'حدث خطأ أثناء الحذف'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                }
            })
            .catch(() => {
                alert('خطأ في الاتصال بالخادم.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
            });
        } else {
            row.style.opacity = '0';
            setTimeout(() => { row.remove(); checkEmptyRows(); }, 200);
        }
    });

    function checkEmptyRows() {
        const rows = document.querySelectorAll('#dynamic_production_rows .production-row');
        if (rows.length === 0) {
            document.getElementById('clear_all_prod_btn').style.display = 'none';
        } else {
            document.getElementById('clear_all_prod_btn').style.display = 'inline-block';
        }
    }

    function openEditModal(workerId, workerName, type, dailyWageType, role, date, status, timeIn, timeOut, productions) {
        document.getElementById('editModalTitle').innerText = workerName;
        document.getElementById('edit_display_date').innerText = new Date(date).toLocaleDateString('ar-EG', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('edit_worker_id').value = workerId;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_daily_wage_type').value = dailyWageType;

        let isScissorsWorker = (role === 'scissors');

        if (type === 'daily') {
            document.getElementById('daily_inputs').style.display = 'block';
            document.getElementById('production_inputs').style.display = 'none';
            
            // Handle Radio Buttons
            let activeStatus = status || 'absent';
            let radio = document.getElementById('status_' + (activeStatus === 'half_day' ? 'half' : activeStatus));
            if(radio) radio.checked = true;

            document.getElementById('edit_time_in').value = timeIn || '';
            document.getElementById('edit_time_out').value = timeOut || '';
            toggleTimes(activeStatus);
        } else {
            document.getElementById('daily_inputs').style.display = 'none';
            document.getElementById('production_inputs').style.display = 'block';
            
            // Populate dynamic rows with record IDs for direct deletion
            const container = document.getElementById('dynamic_production_rows');
            container.innerHTML = '';
            
            if (productions && productions.length > 0) {
                productions.forEach(p => {
                    addProductionRow(p.product_id, p.quantity, p.scissors_worker_id || '', isScissorsWorker, p.id);
                });
            } else {
                addProductionRow('', '', '', isScissorsWorker, null);
            }
        }

        document.getElementById('add_prod_btn').onclick = function() {
            addProductionRow('', '', '', isScissorsWorker);
        };

        document.getElementById('scissors_summary_box').style.display = 'none';

        if (isScissorsWorker) {
            document.getElementById('scissors_summary_box').style.display = 'block';
            document.getElementById('scissors_summary_content').innerHTML = '<span class="spinner-border spinner-border-sm text-warning" role="status"></span> جاري التحميل...';
            
            fetch(`{{ route('salaries.scissors-summary') }}?worker_id=${workerId}&date=${date}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.productions.length > 0) {
                        let html = '<ul class="list-unstyled mb-0">';
                        let totalQty = 0;
                        data.productions.forEach(p => {
                            html += `<li>- عمل خلف <strong class="text-primary">${p.machinist_worker ? p.machinist_worker.name : 'مكنجي'}</strong>: ${p.quantity} منتج (${p.product ? p.product.name : ''})</li>`;
                            totalQty += p.quantity;
                        });
                        html += `</ul><div class="mt-2 pt-2 border-top border-warning fw-bold text-dark">إجمالي العمل خلفهم: <span class="text-success fs-5">${totalQty}</span> منتج</div>`;
                        html += `<div class="mt-2 small text-muted">ملحوظة: يمكنك تسجيل عمل إضافي مستقل لك في خانة (الكمية) بالأعلى، ولكنه سيُجمع مع الشغل بالأسفل في حساباتك.</div>`;
                        document.getElementById('scissors_summary_content').innerHTML = html;
                    } else {
                        document.getElementById('scissors_summary_content').innerHTML = 'لا يوجد سجلات عمل خلف مكنجية لهذا اليوم.';
                    }
                })
                .catch(err => {
                    document.getElementById('scissors_summary_content').innerHTML = 'خطأ في جلب البيانات.';
                });
        }

        editModal.show();
    }
    
    function clearAllProduction() {
        const workerId = document.getElementById('edit_worker_id').value;
        const date = document.getElementById('edit_date').value;

        if (!confirm('هل أنت متأكد من مسح كل سجلات هذا اليوم؟\nسيتم عكس المخزون فوراً.')) return;

        const clearBtn = document.getElementById('clear_all_prod_btn');
        clearBtn.disabled = true;
        const originalHtml = clearBtn.innerHTML;
        clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        // ✅ Always call server to delete from DB, regardless of DOM state
        fetch('{{ route("salaries.clear-day-production") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ worker_id: workerId, date: date })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error('خطأ في الخادم: ' + text.substring(0, 200)); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const container = document.getElementById('dynamic_production_rows');
                container.style.opacity = '0.3';
                setTimeout(() => {
                    container.innerHTML = '';
                    container.style.opacity = '1';
                    const isScissors = document.getElementById('edit_type').value === 'scissors';
                    addProductionRow('', '', '', isScissors, null);
                    checkEmptyRows();
                    clearBtn.disabled = false;
                    clearBtn.innerHTML = originalHtml;
                    
                    const modalBody = document.querySelector('#editDayModal .modal-body');
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success py-2 mb-2 text-center small fw-bold';
                    alertDiv.innerText = '✅ تم مسح جميع السجلات بنجاح';
                    modalBody.prepend(alertDiv);
                    setTimeout(() => alertDiv.remove(), 2500);
                }, 300);
            } else {
                alert('خطأ: ' + (data.message || 'حدث خطأ أثناء المسح.'));
                clearBtn.disabled = false;
                clearBtn.innerHTML = originalHtml;
            }
        })
        .catch(err => {
            alert('خطأ: ' + err.message);
            clearBtn.disabled = false;
            clearBtn.innerHTML = originalHtml;
        });
    }

    function toggleTimes(status) {
        let dailyWageType = document.getElementById('edit_daily_wage_type').value;
        if (status === 'present' && dailyWageType === 'hourly') {
            document.getElementById('timeFields').style.display = 'flex';
            // Set default times for convenience if they are empty
            let timeInInput = document.getElementById('edit_time_in');
            let timeOutInput = document.getElementById('edit_time_out');
            if (!timeInInput.value) timeInInput.value = '08:00';
            if (!timeOutInput.value) timeOutInput.value = '18:00';
        } else {
            document.getElementById('timeFields').style.display = 'none';
        }
    }

    document.getElementById('editDayForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveEdit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري...';

        const type = document.getElementById('edit_type').value;
        const formData = new FormData(this);

        // If production type and no rows left, explicitly send empty markers
        if (type === 'production') {
            const rows = document.querySelectorAll('#dynamic_production_rows .production-row');
            if (rows.length === 0) {
                // Send a special flag to signal "delete all production"
                formData.append('delete_all_production', '1');
            }
        }

        fetch('{{ route("salaries.update-day") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Save scroll positions to prevent jumping to top
                sessionStorage.setItem('salaries_scroll_y', window.scrollY);
                const tableContainer = document.querySelector('.table-responsive');
                if (tableContainer) {
                    sessionStorage.setItem('salaries_table_scroll_x', tableContainer.scrollLeft);
                    sessionStorage.setItem('salaries_table_scroll_y', tableContainer.scrollTop);
                }
                
                // Reload the page to refresh the grid calculations
                window.location.reload();
            } else {
                alert('خطأ: ' + (data.message || 'حدث خطأ أثناء الحفظ.'));
                btn.disabled = false;
                btn.innerHTML = 'حفظ <i class="bi bi-save ms-1"></i>';
            }
        })
        .catch(err => {
            console.error(err);
            alert('خطأ في الاتصال بالخادم. تحقق من الكونسول.');
            btn.disabled = false;
            btn.innerHTML = 'حفظ <i class="bi bi-save ms-1"></i>';
        });
    });

    // Pay Salary Logic
    const payModal = new bootstrap.Modal(document.getElementById('payModal'));
    let currentTotalPay = 0;
    let currentTotalAdvances = 0;
    let currentTotalPenalties = 0;

    function openPayModal(workerId, workerName, totalPay, advances, penalties, bonuses, startDate, endDate, advancesList = []) {
        currentTotalPay = parseFloat(totalPay) || 0;
        currentTotalAdvances = parseFloat(advances) || 0;
        currentTotalPenalties = parseFloat(penalties) || 0;
        let currentTotalBonuses = parseFloat(bonuses) || 0;
        
        document.getElementById('pay_worker_id').value = workerId;
        document.getElementById('pay_worker_name').innerText = workerName;
        document.getElementById('pay_start_date').value = startDate;
        document.getElementById('pay_end_date').value = endDate;
        document.getElementById('pay_total_pay').value = currentTotalPay;
        document.getElementById('pay_penalties').value = currentTotalPenalties;
        
        document.getElementById('pay_display_total').value = currentTotalPay.toFixed(2);
        document.getElementById('pay_display_advances').value = currentTotalAdvances.toFixed(2);
        document.getElementById('pay_display_penalties').value = currentTotalPenalties.toFixed(2);

        // Populate advances breakdown
        const breakdownBox = document.getElementById('advances_breakdown_box');
        const tbody = document.getElementById('advances_breakdown_tbody');
        tbody.innerHTML = '';
        if (advancesList && advancesList.length > 0) {
            breakdownBox.style.display = 'block';
            let totalAmt = 0;
            advancesList.forEach(adv => {
                totalAmt += parseFloat(adv.amount);
                tbody.innerHTML += `<tr>
                    <td class="text-muted">${adv.date}</td>
                    <td class="text-danger fw-bold">${parseFloat(adv.amount).toFixed(2)} ج</td>
                    <td class="text-muted small">${adv.notes || '-'}</td>
                </tr>`;
            });
            document.getElementById('advances_breakdown_total').innerText = totalAmt.toFixed(2) + ' ج.م';
        } else {
            breakdownBox.style.display = 'none';
        }
        
        let availableForDeduction = currentTotalPay - currentTotalPenalties;
        if (availableForDeduction < 0) availableForDeduction = 0;
        
        let defaultDeduction = Math.min(availableForDeduction, currentTotalAdvances);
        document.getElementById('pay_deduct_advance').value = defaultDeduction;
        document.getElementById('pay_deduct_advance').max = currentTotalAdvances;
        // Set predefined bonus to what's uncollected, allowing them to add more manually if they want
        document.getElementById('pay_bonus').value = currentTotalBonuses;
        
        calculateNetPay();
        payModal.show();
    }
    
    function setMaxAdvance() {
        document.getElementById('pay_deduct_advance').value = currentTotalAdvances;
        calculateNetPay();
    }
    
    function calculateNetPay() {
        let deduct = parseFloat(document.getElementById('pay_deduct_advance').value) || 0;
        // Don't allow deducting more than they actually owe
        if (deduct > currentTotalAdvances) {
            deduct = currentTotalAdvances;
            document.getElementById('pay_deduct_advance').value = deduct;
        }
        
        let bonus = parseFloat(document.getElementById('pay_bonus').value) || 0;
        if (bonus < 0) bonus = 0;
        
        let net = currentTotalPay + bonus - currentTotalPenalties - deduct;
        document.getElementById('pay_display_net').innerText = net.toFixed(2) + ' ج.م';
        document.getElementById('pay_net_salary').value = net;
        
        if (net < 0) {
            document.getElementById('pay_display_net').classList.remove('text-success');
            document.getElementById('pay_display_net').classList.add('text-danger');
        } else {
            document.getElementById('pay_display_net').classList.remove('text-danger');
            document.getElementById('pay_display_net').classList.add('text-success');
        }
    }
    
    document.getElementById('payForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnConfirmPay');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الصرف...';
    });

    // Restore scroll positions after reload
    document.addEventListener("DOMContentLoaded", function() {
        const scrollY = sessionStorage.getItem('salaries_scroll_y');
        if (scrollY !== null) {
            window.scrollTo(0, parseInt(scrollY));
            sessionStorage.removeItem('salaries_scroll_y');
        }
        
        const tableContainer = document.querySelector('.scroll-inner');
        if (tableContainer) {
            const scrollX = sessionStorage.getItem('salaries_table_scroll_x');
            const scrollTableY = sessionStorage.getItem('salaries_table_scroll_y');
            
            if (scrollX !== null) {
                tableContainer.scrollLeft = parseInt(scrollX);
                sessionStorage.removeItem('salaries_table_scroll_x');
            }
            if (scrollTableY !== null) {
                tableContainer.scrollTop = parseInt(scrollTableY);
                sessionStorage.removeItem('salaries_table_scroll_y');
            }
        }
    });
</script>
@endpush
@endsection

