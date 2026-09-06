@extends('layouts.app')
@section('title', 'الحضور والانصراف | مصنع المنتجات')
@section('page_title', 'تسجيل الحضور والانصراف')

@section('content')
<div class="glass-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0 fw-bold">حضور يوم</h5>
            <form action="{{ route('attendance.index') }}" method="GET" class="d-flex gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-glass" style="width: 180px;" onchange="this.form.submit();">
            </form>
        </div>
        <button class="btn-glass" data-bs-toggle="modal" data-bs-target="#attendModal"><i class="bi bi-plus-lg me-1"></i> تسجيل حضور</button>
    </div>
    <div class="row g-4">
        <!-- Attendance Table -->
        <div class="col-12 col-lg-7">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-check text-primary me-2"></i>الحضور والغياب</h6>
            <div class="table-responsive">
                <table class="table table-glass table-hover align-middle">
                    <thead><tr><th>الموظف</th><th>حضور</th><th>انصراف</th><th>الحالة</th><th>ملاحظات</th></tr></thead>
                    <tbody>
                        @forelse($attendances as $a)
                        @php $statusLabels=['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','half_day'=>'نصف يوم','holiday'=>'إجازة'];$statusColors=['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info','holiday'=>'secondary']; @endphp
                        <tr>
                            <td class="fw-bold text-white">{{ $a->worker->name ?? '-' }}</td>
                            <td>{{ $a->check_in ?? '-' }}</td>
                            <td>{{ $a->check_out ?? '-' }}</td>
                            <td><span class="badge bg-{{ $statusColors[$a->status] ?? 'secondary' }} bg-opacity-25 text-{{ $statusColors[$a->status] ?? 'secondary' }} border border-{{ $statusColors[$a->status] ?? 'secondary' }} rounded-pill px-2 py-1">{{ $statusLabels[$a->status] ?? $a->status }}</span></td>
                            <td class="text-muted">{{ $a->notes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">لا توجد بيانات حضور لهذا اليوم</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Production Table -->
        <div class="col-12 col-lg-5">
            <h6 class="fw-bold mb-3"><i class="bi bi-hammer text-success me-2"></i>إنتاج اليوم بالقطعة</h6>
            <div class="table-responsive">
                <table class="table table-glass table-hover align-middle">
                    <thead><tr><th>موظف الإنتاج</th><th>المنتج / المنتج</th><th>الكمية</th><th>ملاحظات</th></tr></thead>
                    <tbody>
                        @forelse($productions as $p)
                        <tr>
                            <td class="fw-bold text-white">{{ $p->worker->name ?? '-' }} <br><small class="text-muted">{{ $p->worker->role_label ?? '-' }}</small></td>
                            <td class="text-warning fw-bold">{{ $p->product->name ?? '-' }}</td>
                            <td class="text-success fw-bold">{{ $p->quantity }}</td>
                            <td class="text-muted" style="font-size: 0.8rem;">{{ $p->notes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">لم يتم تسجيل أي إنتاج بالقطعة اليوم</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="attendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel text-white">
            <div class="modal-header border-secondary" style="border-color:rgba(255,255,255,0.1)!important;"><h5 class="modal-title fw-bold">تسجيل حضور</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('attendance.store') }}" method="POST">@csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label text-muted">الموظف <span class="text-danger">*</span></label><select name="worker_id" class="form-control form-control-glass" required><option value="">اختر موظف</option>@foreach($workers as $w)<option value="{{$w->id}}">{{$w->code}} — {{$w->name}}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label text-muted">الوردية</label><select name="shift_id" class="form-control form-control-glass"><option value="">بدون</option>@foreach($shifts as $s)<option value="{{$s->id}}">{{$s->name}}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label text-muted">التاريخ <span class="text-danger">*</span></label><input type="date" name="date" value="{{ $date }}" class="form-control form-control-glass" required></div>
                        <div class="col-md-4"><label class="form-label text-muted">وقت الحضور</label><input type="time" name="check_in" class="form-control form-control-glass"></div>
                        <div class="col-md-4"><label class="form-label text-muted">وقت الانصراف</label><input type="time" name="check_out" class="form-control form-control-glass"></div>
                        <div class="col-md-4"><label class="form-label text-muted">الحالة <span class="text-danger">*</span></label><select name="status" class="form-control form-control-glass" required><option value="present">حاضر</option><option value="absent">غائب</option><option value="late">متأخر</option><option value="half_day">نصف يوم</option><option value="holiday">إجازة</option></select></div>
                        <div class="col-md-4"><label class="form-label text-muted">ساعات أوفرتايم</label><input type="number" step="0.5" name="overtime_hours" value="0" class="form-control form-control-glass"></div>
                        <div class="col-md-4"><label class="form-label text-muted">ملاحظات</label><input type="text" name="notes" class="form-control form-control-glass"></div>
                    </div>
                </div>
                <div class="modal-footer border-secondary" style="border-color:rgba(255,255,255,0.1)!important;"><button type="button" class="btn-glass-secondary px-4 py-2 rounded-3 me-2" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn-glass px-4 py-2 rounded-3">تسجيل</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

