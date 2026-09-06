@extends('layouts.app')
@section('title', 'الإنتاج اليومي | مصنع المنتجات')
@section('page_title', 'شاشة المصنع اليومية')

@section('content')
<div class="row mb-4">
    <div class="col-12 text-center">
        <h3 class="fw-bold text-white mb-2"><i class="bi bi-display me-2 text-primary"></i> شاشة إنتاج اليوم</h3>
        <p class="text-info fs-5 mb-0">{{ \Carbon\Carbon::parse($date)->locale('ar')->translatedFormat('l, d F Y') }}</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="glass-card text-center p-4 h-100">
            <h1 class="display-4 fw-bold text-warning">{{ $totalCages }}</h1>
            <h5 class="text-muted">إجمالي المنتجات (إنتاج اليوم)</h5>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card text-center p-4 h-100">
            <h1 class="display-4 fw-bold text-success">{{ number_format($totalHourlyWages + $totalProductionWages, 2) }} <small class="fs-5">ج.م</small></h1>
            <h5 class="text-muted">إجمالي الأجور المستحقة لليوم</h5>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card text-center p-4 h-100">
            <h1 class="display-4 fw-bold text-info">{{ $hourlyAttendances->count() + ($productions->count() * 2) }}</h1>
            <h5 class="text-muted">عدد الموظفين الحاضرين تقريباً</h5>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- قسم موظفين الإنتاج -->
    <div class="col-md-7">
        <div class="glass-card h-100">
            <h5 class="fw-bold mb-4 text-warning border-bottom border-secondary pb-2"><i class="bi bi-hammer me-2"></i> سجل الإنتاج والورديات</h5>
            
            <div class="table-responsive">
                <table class="table table-glass align-middle">
                    <thead>
                        <tr>
                            <th>المكنجي</th>
                            <th>المقص (المرتبط)</th>
                            <th>الصنف</th>
                            <th class="text-center">الكمية</th>
                            <th class="text-end">أجر المكنجي</th>
                            <th class="text-end">أجر المقص</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productions as $prod)
                        <tr>
                            <td class="fw-bold text-white">{{ $prod->worker->name }}</td>
                            <td class="text-info">{{ $prod->scissorsWorker ? $prod->scissorsWorker->name : '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $prod->product->name }}</span></td>
                            <td class="text-center fw-bold fs-5 text-warning">{{ $prod->quantity }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($prod->machinist_wage, 2) }} ج</td>
                            <td class="text-end text-success fw-bold">{{ number_format($prod->scissors_wage, 2) }} ج</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">لا يوجد إنتاج مسجل لهذا اليوم حتى الآن</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- قسم موظفين اليوميات -->
    <div class="col-md-5">
        <div class="glass-card h-100">
            <h5 class="fw-bold mb-4 text-primary border-bottom border-secondary pb-2"><i class="bi bi-clock-history me-2"></i> حضور اليوميات والساعات</h5>
            
            <div class="table-responsive">
                <table class="table table-glass align-middle">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th class="text-center">من - إلى</th>
                            <th class="text-center">الساعات</th>
                            <th class="text-end">الأجر المستحق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hourlyAttendances as $att)
                        <tr>
                            <td class="fw-bold text-white">{{ $att->worker->name }}</td>
                            <td class="text-center text-muted small">
                                @if($att->time_in && $att->time_out)
                                    {{ date('h:i A', strtotime($att->time_in)) }} - {{ date('h:i A', strtotime($att->time_out)) }}
                                @else
                                    يوم كامل
                                @endif
                            </td>
                            <td class="text-center fw-bold text-info">{{ $att->worked_hours > 0 ? $att->worked_hours : '-' }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($att->calculated_wage, 2) }} ج</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">لا يوجد موظفين يومية مسجلين اليوم</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="text-end mt-4 no-print">
    <button onclick="window.print()" class="btn-glass"><i class="bi bi-printer me-1"></i> طباعة شاشة اليوم</button>
</div>
@endsection

@push('styles')
<style>
@media print {
    body, .glass-card, .water-card, .glass-panel {
        background: #fff !important;
        color: #000 !important;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .text-white, .text-muted, .text-info, .text-warning, .text-primary, .text-danger, .text-success {
        color: #000 !important;
    }
    .badge, .btn, .form-control {
        background: transparent !important;
        color: #000 !important;
        border: 1px solid #000 !important;
    }
    .no-print, form, nav, header, footer, .sidebar {
        display: none !important;
    }
    table, th, td {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: #fff !important;
    }
    h1, h3, h5, h6, p {
        color: #000 !important;
        page-break-after: avoid;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .col-md-4, .col-md-7, .col-md-5 {
        width: 100% !important;
        margin-bottom: 20px !important;
        page-break-inside: avoid;
    }
}
</style>
@endpush

