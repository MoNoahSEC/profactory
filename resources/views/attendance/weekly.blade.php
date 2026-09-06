@extends('layouts.app')

@section('page_title', 'جدول التحضير الأسبوعي')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-calendar-week text-primary fs-3"></i>
                جدول التحضير الأسبوعي
            </h4>
            <p class="text-muted mb-0">عرض ومتابعة أيام الحضور لجميع الموظفين</p>
        </div>
        <div class="col-md-6 mt-3 mt-md-0 text-md-end">
            <form action="{{ route('attendance.weekly') }}" method="GET" class="d-flex align-items-center justify-content-md-end gap-2">
                <label class="form-label mb-0 fw-bold text-nowrap">اختر الأسبوع (يبدأ من السبت):</label>
                <input type="date" name="start_date" class="form-control form-control-sm w-auto" value="{{ $startDateStr }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="glass-card mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" style="min-width: 150px; position: sticky; right: 0; z-index: 10;">اسم الموظف</th>
                        <th style="min-width: 80px;">النوع</th>
                        @foreach($days as $day)
                            <th style="min-width: 80px;">
                                <div class="fw-bold">{{ $day['name'] }}</div>
                                <div class="small text-muted">{{ $day['day_month'] }}</div>
                            </th>
                        @endforeach
                        <th style="min-width: 100px;">إجمالي الأيام</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grid as $workerId => $row)
                    <tr>
                        <td class="text-start fw-bold" style="position: sticky; right: 0; background: inherit; z-index: 10;">
                            {{ $row['worker']->name }}
                        </td>
                        <td>
                            @if($row['worker']->worker_type === 'daily')
                                <span class="badge bg-secondary">يومية</span>
                            @else
                                <span class="badge bg-info text-dark">إنتاج ({{ $row['worker']->production_role == 'scissors' ? 'مقصدار' : 'مكينة' }})</span>
                            @endif
                        </td>
                        
                        @foreach($row['days'] as $dayData)
                            <td>
                                @if($dayData['status'] === 'present')
                                    <div class="text-success"><i class="bi bi-check-circle-fill fs-5"></i></div>
                                    @if($dayData['has_production'])
                                        <div class="small text-muted mt-1"><i class="bi bi-box-seam"></i> إنتاج</div>
                                    @endif
                                @else
                                    <div class="text-danger opacity-50"><i class="bi bi-x-circle fs-5"></i></div>
                                @endif
                            </td>
                        @endforeach
                        
                        <td class="fw-bold fs-5 text-primary">
                            {{ $row['total_days'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
