@extends('layouts.app')

@section('title', 'التقارير الشاملة | مصنع المنتجات')
@section('page_title', 'التقارير والتحليلات')

@section('content')
<!-- Time Filters -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-group glass-panel rounded-pill p-1" role="group">
        <a href="{{ route('reports.index', ['filter' => 'daily']) }}" class="btn btn-sm {{ ($filter ?? 'all') === 'daily' ? 'btn-glass text-white' : 'text-muted' }} rounded-pill px-4">اليوم</a>
        <a href="{{ route('reports.index', ['filter' => 'weekly']) }}" class="btn btn-sm {{ ($filter ?? 'all') === 'weekly' ? 'btn-glass text-white' : 'text-muted' }} rounded-pill px-4">هذا الأسبوع</a>
        <a href="{{ route('reports.index', ['filter' => 'monthly']) }}" class="btn btn-sm {{ ($filter ?? 'all') === 'monthly' ? 'btn-glass text-white' : 'text-muted' }} rounded-pill px-4">هذا الشهر</a>
        <a href="{{ route('reports.index', ['filter' => 'yearly']) }}" class="btn btn-sm {{ ($filter ?? 'all') === 'yearly' ? 'btn-glass text-white' : 'text-muted' }} rounded-pill px-4">هذا العام</a>
        <a href="{{ route('reports.index', ['filter' => 'all']) }}" class="btn btn-sm {{ ($filter ?? 'all') === 'all' ? 'btn-glass text-white' : 'text-muted' }} rounded-pill px-4">الكل</a>
    </div>
    
    <div>
        <a href="{{ route('reports.customer-invoices', ['filter' => $filter ?? 'all']) }}" class="btn btn-primary btn-sm rounded-pill px-4">
            <i class="bi bi-receipt me-1"></i> كشف فواتير العملاء
        </a>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="glass-card h-100 kpi-card">
            <div class="icon-box primary mb-3"><i class="bi bi-cart-check-fill"></i></div>
            <h6 class="text-muted mb-2">إجمالي المبيعات</h6>
            <h3 class="fw-bold text-dark mb-2">{{ number_format($totalSales, 0) }} <small class="fs-6 text-muted">ج.م</small></h3>
            <div class="d-flex justify-content-between small">
                <span class="text-success fw-bold">محصّل: {{ number_format($paidSales, 0) }}</span>
                <span class="text-danger fw-bold">متبقي: {{ number_format($remainingSales, 0) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="glass-card h-100 kpi-card">
            <div class="icon-box warning mb-3"><i class="bi bi-gear-fill"></i></div>
            <h6 class="text-muted mb-2">الإنتاج</h6>
            <h3 class="fw-bold text-dark mb-2">{{ $completedProductionOrders }} <small class="fs-6 text-muted">/ {{ $totalProductionOrders }}</small></h3>
            <span class="text-danger fw-bold small">تكلفة: {{ number_format($totalProductionCost, 0) }} ج.م</span>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="glass-card h-100 kpi-card">
            <div class="icon-box success mb-3"><i class="bi bi-archive-fill"></i></div>
            <h6 class="text-muted mb-2">قيمة المخزون</h6>
            <h3 class="fw-bold text-dark mb-2">{{ number_format($productsValue + $rawMaterialsValue, 0) }} <small class="fs-6 text-muted">ج.م</small></h3>
            <div class="d-flex justify-content-between small">
                <span>منتجات: {{ number_format($productsValue, 0) }}</span>
                <span>خام: {{ number_format($rawMaterialsValue, 0) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="glass-card h-100 kpi-card">
            <div class="icon-box {{ $netProfit >= 0 ? 'success' : 'danger' }} mb-3"><i class="bi bi-graph-up-arrow"></i></div>
            <h6 class="text-muted mb-2">صافي الربح التقديري</h6>
            <h3 class="fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }} mb-2">{{ number_format($netProfit, 0) }} <small class="fs-6">ج.م</small></h3>
            <span class="small text-muted">إيرادات − (إنتاج + رواتب + مصاريف)</span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>المبيعات الشهرية (آخر 12 شهر)</h5>
            <canvas id="salesReportChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>توزيع التكاليف</h5>
            <canvas id="costPieChart" height="200"></canvas>
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">رواتب</span><span class="fw-bold text-danger">{{ number_format($totalSalaries, 0) }} ج.م</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">مصاريف</span><span class="fw-bold text-danger">{{ number_format($totalExpenses, 0) }} ج.م</span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">إنتاج</span><span class="fw-bold text-danger">{{ number_format($totalProductionCost, 0) }} ج.م</span></div>
            </div>
        </div>
    </div>
</div>

<div class="text-end mt-4">
    <a href="{{ route('reports.weekly-production') }}" class="btn btn-glass-secondary me-2"><i class="bi bi-scissors me-1"></i> تقرير المكنجي والمقص</a>
    <button onclick="window.print()" class="btn-glass"><i class="bi bi-printer-fill me-2"></i> طباعة التقرير</button>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const salesLabels = {!! json_encode(collect($salesByMonth)->pluck('label')) !!};
    const salesData = {!! json_encode(collect($salesByMonth)->pluck('total')) !!};

    new Chart(document.getElementById('salesReportChart'), {
        type: 'bar',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'المبيعات (ج.م)',
                data: salesData,
                backgroundColor: 'rgba(234, 88, 12, 0.7)',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(234,88,12,0.08)' }, ticks: { color: '#9a3412' } },
                x: { grid: { display: false }, ticks: { color: '#9a3412', maxRotation: 45 } }
            }
        }
    });

    new Chart(document.getElementById('costPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['رواتب', 'مصاريف', 'إنتاج'],
            datasets: [{
                data: [{{ $totalSalaries }}, {{ $totalExpenses }}, {{ $totalProductionCost }}],
                backgroundColor: ['#dc2626', '#f97316', '#ea580c'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { family: 'Cairo' } } } }
        }
    });
});
</script>
@endpush

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
    .no-print, form, nav, header, footer, .sidebar, .btn-group, .text-end.mt-4 {
        display: none !important;
    }
    .icon-box {
        display: none !important;
    }
    h3, h5, h6 {
        color: #000 !important;
        page-break-after: avoid;
    }
    .col-md-6.col-lg-3 {
        width: 25% !important;
        float: right !important;
    }
    .col-lg-8 {
        width: 66% !important;
        float: right !important;
    }
    .col-lg-4 {
        width: 33% !important;
        float: right !important;
    }
    canvas {
        max-width: 100% !important;
    }
    /* Simple grid fallback for print if flex fails */
    .row {
        display: flex !important;
        flex-wrap: wrap !important;
    }
}
</style>
@endpush

