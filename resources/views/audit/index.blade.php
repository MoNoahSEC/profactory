@extends('layouts.app')
@section('title', 'المراجعة الشاملة للنظام')
@section('page_title', 'المراجعة الشاملة للنظام')

@section('content')
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card water-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>نظام المراجعة الصارم</h4>
                    <p class="text-muted small mb-0">مراجعة شاملة لجميع الحركات المالية في النظام وتدقيقها</p>
                </div>
                
                <form action="{{ route('audit.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                    <div>
                        <select name="period" class="form-select form-select-glass form-select-sm" onchange="this.form.submit()">
                            <option value="today" {{ $period == 'today' ? 'selected' : '' }}>اليوم</option>
                            <option value="this_week" {{ $period == 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                            <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                            <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>الشهر الماضي</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="mt-3 text-muted small">
                الفترة قيد المراجعة: <strong>{{ \Carbon\Carbon::parse($startStr)->translatedFormat('l d F Y') }}</strong> إلى <strong>{{ \Carbon\Carbon::parse($endStr)->translatedFormat('l d F Y') }}</strong>
            </div>
        </div>
    </div>
</div>

@if(count($alerts) > 0)
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <h5 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i>تنبيهات التدقيق</h5>
        @foreach($alerts as $alert)
        <div class="alert alert-{{ $alert['type'] }} d-flex align-items-center mb-2" role="alert">
            <i class="bi {{ $alert['icon'] }} fs-4 me-3"></i>
            <div>
                {{ $alert['msg'] }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="row g-4 mb-4 fade-in-up">
    <!-- الفواتير -->
    <div class="col-lg-4">
        <div class="glass-card water-card h-100 border-top border-4 border-info">
            <h5 class="fw-bold mb-4 text-info"><i class="bi bi-receipt me-2"></i>إحصائيات الفواتير</h5>
            
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">عدد الفواتير</span>
                <span class="fw-bold fs-5">{{ $invoiceStats['total_count'] }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">إجمالي المطالبات</span>
                <span class="fw-bold fs-5 text-primary">{{ number_format($invoiceStats['total_amount'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">المُحصّل الفعلي</span>
                <span class="fw-bold fs-5 text-success">{{ number_format($invoiceStats['total_collected'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">المتبقي (الديون)</span>
                <span class="fw-bold fs-5 text-danger">{{ number_format($invoiceStats['total_remaining'], 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <!-- الرواتب والسلف -->
    <div class="col-lg-4">
        <div class="glass-card water-card h-100 border-top border-4 border-warning">
            <h5 class="fw-bold mb-4 text-warning"><i class="bi bi-people me-2"></i>الرواتب والسلف</h5>
            
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">إجمالي الصافي المنصرف</span>
                <span class="fw-bold fs-5 text-success">{{ number_format($salaryStats['total_paid'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">السلف المخصومة</span>
                <span class="fw-bold fs-5 text-warning">{{ number_format($salaryStats['total_advances_deducted'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">الجزاءات المخصومة</span>
                <span class="fw-bold fs-5 text-danger">{{ number_format($salaryStats['total_penalties'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">السلف المنصرفة (جديدة)</span>
                <span class="fw-bold fs-5 text-danger">{{ number_format($advanceStats['total_amount'], 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <!-- الخزينة -->
    <div class="col-lg-4">
        <div class="glass-card water-card h-100 border-top border-4 border-success">
            <h5 class="fw-bold mb-4 text-success"><i class="bi bi-safe me-2"></i>حركة الخزينة</h5>
            
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">إجمالي الوارد</span>
                <span class="fw-bold fs-5 text-success"><i class="bi bi-arrow-down-circle me-1"></i> {{ number_format($cashStats['total_in'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.05) !important;">
                <span class="text-muted">إجمالي الصادر</span>
                <span class="fw-bold fs-5 text-danger"><i class="bi bi-arrow-up-circle me-1"></i> {{ number_format($cashStats['total_out'], 2) }} ج.م</span>
            </div>
            <div class="d-flex justify-content-between mt-4 p-3 rounded" style="background: rgba(255,255,255,0.05);">
                <span class="text-muted fw-bold">الصافي للفترة</span>
                <span class="fw-bold fs-3 {{ $cashStats['net'] >= 0 ? 'text-success' : 'text-danger' }}" style="text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                    {{ number_format($cashStats['net'], 2) }} ج.م
                </span>
            </div>
        </div>
    </div>
</div>

@endsection
