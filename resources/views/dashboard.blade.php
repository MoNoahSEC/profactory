@extends('layouts.app')

@section('title', 'لوحة الإدارة | مصنع المنتجات')
@section('page_title', 'لوحة القيادة')

@push('styles')
<style>
    /* Premium Dashboard Styles */
    .welcome-hero {
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.95), rgba(249, 115, 22, 0.85));
        border-radius: 20px;
        color: white;
        padding: 2rem;
        box-shadow: 0 15px 35px rgba(234, 88, 12, 0.2);
        position: relative;
        overflow: hidden;
    }
    .welcome-hero::after {
        content: '';
        position: absolute;
        top: -50%; left: -50%; width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        animation: rotateGlow 15s linear infinite;
        pointer-events: none;
    }
    @keyframes rotateGlow {
        100% { transform: rotate(360deg); }
    }
    .stat-glass {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 16px;
        padding: 0.75rem;
    }
    
    @media (max-width: 768px) {
        .welcome-hero { padding: 1.25rem; }
        .welcome-hero h2 { font-size: 1.5rem; }
        .stat-glass h4 { font-size: 1.25rem; }
        .chart-container { height: 250px; position: relative; }
    }
    
    .timeline {
        border-right: 2px solid rgba(234, 88, 12, 0.15);
        padding-right: 1.5rem;
        margin-right: 0.5rem;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        right: -1.8rem;
        top: 0.3rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.2);
    }
    
    .app-icon-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: transform 0.2s ease, filter 0.2s ease;
    }
    .app-icon-wrapper:hover {
        transform: translateY(-5px) scale(1.05);
        filter: brightness(1.1);
    }
    .app-icon {
        width: 64px;
        height: 64px;
        border-radius: 22px; /* iOS style squircles */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        margin-bottom: 8px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.12), inset 0 2px 4px rgba(255,255,255,0.3);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .app-icon-text {
        color: var(--text-dark);
        font-size: 0.8rem;
        font-weight: 800;
        text-align: center;
        line-height: 1.2;
    }
    @media (max-width: 768px) {
        .app-icon {
            width: 56px;
            height: 56px;
            font-size: 1.7rem;
            border-radius: 18px;
        }
        .app-icon-text {
            font-size: 0.75rem;
        }
    }
</style>
@endpush

@section('content')

<!-- Welcome Hero Section -->
<div class="welcome-hero mb-4">
    <div class="row align-items-center relative z-1">
        <div class="col-md-7 mb-3 mb-md-0">
            <h2 class="fw-bold mb-2">مرحباً بعودتك، {{ Auth::user()->name }}! 🌅</h2>
            <p class="mb-0 text-white-50 fs-5">{{ \Carbon\Carbon::now()->translatedFormat('l, j F Y') }}</p>
        </div>
        <div class="col-md-5">
            <div class="row g-2">
                <div class="col-6">
                    <div class="stat-glass text-center h-100 d-flex flex-column justify-content-center">
                        <small class="d-block mb-1 opacity-75" style="font-size: 0.8rem;">رصيد الخزينة</small>
                        <h4 class="fw-bold mb-0 text-wrap">{{ number_format($cashSummary['balance'], 0) }} <small style="font-size: 0.7rem;">ج.م</small></h4>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-glass text-center h-100 d-flex flex-column justify-content-center">
                        <small class="d-block mb-1 opacity-75" style="font-size: 0.8rem;">مبيعات الشهر</small>
                        <h4 class="fw-bold mb-0 text-wrap">{{ number_format($currentMonthSales, 0) }} <small style="font-size: 0.7rem;">ج.م</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Premium Quick Actions (Shortcuts) -->
<h5 class="fw-bold text-dark mb-3 mt-4"><i class="bi bi-grid-fill me-2 text-primary"></i>الوصول السريع</h5>
<div class="row g-3 mb-4 justify-content-center">
    
    <!-- 1. Orders -->
    <div class="col-3 col-md-2">
        <a href="{{ route('orders.index', ['open' => 'create']) }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #ea580c, #f97316); box-shadow: 0 8px 20px rgba(234,88,12,0.3);"><i class="bi bi-cart-plus-fill"></i></div>
            <div class="app-icon-text">طلب جديد</div>
        </a>
    </div>
    
    <!-- 2. Production -->
    <div class="col-3 col-md-2">
        <a href="{{ route('production.daily') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #0284c7, #38bdf8); box-shadow: 0 8px 20px rgba(2,132,199,0.3);"><i class="bi bi-gear-fill"></i></div>
            <div class="app-icon-text">الإنتاج</div>
        </a>
    </div>

    <!-- 3. Purchases -->
    <div class="col-3 col-md-2">
        <a href="{{ route('purchases.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #16a34a, #4ade80); box-shadow: 0 8px 20px rgba(22,163,74,0.3);"><i class="bi bi-box-seam-fill"></i></div>
            <div class="app-icon-text">المشتريات</div>
        </a>
    </div>

    <!-- 4. Salaries -->
    <div class="col-3 col-md-2">
        <a href="{{ route('salaries.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #7c3aed, #a78bfa); box-shadow: 0 8px 20px rgba(124,58,237,0.3);"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="app-icon-text">الرواتب</div>
        </a>
    </div>

    <!-- 5. Cash -->
    <div class="col-3 col-md-2">
        <a href="{{ route('expenses.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #dc2626, #f87171); box-shadow: 0 8px 20px rgba(220,38,38,0.3);"><i class="bi bi-wallet-fill"></i></div>
            <div class="app-icon-text">الخزينة</div>
        </a>
    </div>

    <!-- 6. Workshops -->
    <div class="col-3 col-md-2">
        <a href="{{ route('workshops.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #eab308, #fde047); box-shadow: 0 8px 20px rgba(234,179,8,0.3);"><i class="bi bi-tools text-dark"></i></div>
            <div class="app-icon-text">الورش</div>
        </a>
    </div>

    <!-- 6. Debts -->
    <div class="col-3 col-md-2">
        <a href="{{ route('debts.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #d97706, #fbbf24); box-shadow: 0 8px 20px rgba(217,119,6,0.3);"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="app-icon-text">الديون</div>
        </a>
    </div>

    <!-- 7. Workers -->
    <div class="col-3 col-md-2">
        <a href="{{ route('workers.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #0d9488, #2dd4bf); box-shadow: 0 8px 20px rgba(13,148,136,0.3);"><i class="bi bi-people-fill"></i></div>
            <div class="app-icon-text">الموظفين</div>
        </a>
    </div>

    <!-- 8. Inventory -->
    <div class="col-3 col-md-2">
        <a href="{{ route('inventory.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #4f46e5, #818cf8); box-shadow: 0 8px 20px rgba(79,70,229,0.3);"><i class="bi bi-boxes"></i></div>
            <div class="app-icon-text">المخزون</div>
        </a>
    </div>

    <!-- 9. Products -->
    <div class="col-3 col-md-2">
        <a href="{{ route('products.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #be185d, #f472b6); box-shadow: 0 8px 20px rgba(190,24,93,0.3);"><i class="bi bi-tags-fill"></i></div>
            <div class="app-icon-text">المنتجات</div>
        </a>
    </div>

    <!-- 10. Reports -->
    <div class="col-3 col-md-2">
        <a href="{{ route('reports.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #0f766e, #2dd4bf); box-shadow: 0 8px 20px rgba(15,118,110,0.3);"><i class="bi bi-pie-chart-fill"></i></div>
            <div class="app-icon-text">التقارير</div>
        </a>
    </div>
    
    <!-- 12. Advances & Penalties -->
    <div class="col-3 col-md-2">
        <a href="{{ route('advances.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #b91c1c, #ef4444); box-shadow: 0 8px 20px rgba(185,28,28,0.3);"><i class="bi bi-cash-stack"></i></div>
            <div class="app-icon-text" style="font-size: 0.7rem;">السلف والديون</div>
        </a>
    </div>

    <!-- 13. Invoices -->
    <div class="col-3 col-md-2">
        <a href="{{ route('invoices.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #0ea5e9, #38bdf8); box-shadow: 0 8px 20px rgba(14,165,233,0.3);"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="app-icon-text">الفواتير</div>
        </a>
    </div>

    <!-- 12. Settings -->
    <div class="col-3 col-md-2">
        <a href="{{ route('settings.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #475569, #cbd5e1); box-shadow: 0 8px 20px rgba(71,85,105,0.3);"><i class="bi bi-sliders"></i></div>
            <div class="app-icon-text">الإعدادات</div>
        </a>
    </div>

    <!-- 15. Loading System -->
    <div class="col-3 col-md-2">
        <a href="{{ route('loading.index') }}" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 8px 20px rgba(217,119,6,0.35);"><i class="bi bi-truck-flatbed"></i></div>
            <div class="app-icon-text">التحميل</div>
        </a>
    </div>

    <!-- 14. Public Menu + QR -->
    <div class="col-3 col-md-2">
        <a href="{{ route('menu.index') }}" target="_blank" class="app-icon-wrapper">
            <div class="app-icon" style="background: linear-gradient(135deg, #d97706, #fbbf24); box-shadow: 0 8px 20px rgba(217,119,6,0.3);"><i class="bi bi-qr-code"></i></div>
            <div class="app-icon-text">القائمة العامة</div>
        </a>
    </div>


</div>

<!-- Main KPIs (Smart Cards) -->
<h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>نظرة سريعة (مؤشرات)</h5>
<div class="row g-2 mb-4">
    <!-- Pending Orders -->
    <div class="col-6">
        <div class="glass-card h-100 p-3" style="position: relative; overflow: hidden;">
            <i class="bi bi-hourglass-split position-absolute text-warning opacity-25" style="font-size: 4rem; right: -10px; bottom: -10px;"></i>
            <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.8rem;">{{ $pendingOrdersCount }}</h4>
            <small class="text-muted fw-bold">طلبيات جارية</small>
        </div>
    </div>
    
    <!-- Stock Products -->
    <div class="col-6">
        <div class="glass-card h-100 p-3" style="position: relative; overflow: hidden;">
            <i class="bi bi-boxes position-absolute text-success opacity-25" style="font-size: 4rem; right: -10px; bottom: -10px;"></i>
            <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.8rem;">{{ number_format($totalProductsInStock, 0) }}</h4>
            <small class="text-muted fw-bold">منتج جاهز بالمخزن</small>
        </div>
    </div>

    <!-- Debts (Owed to Us) -->
    <div class="col-6">
        <div class="glass-card h-100 p-3" style="position: relative; overflow: hidden;">
            <i class="bi bi-cash-coin position-absolute text-primary opacity-25" style="font-size: 4rem; right: -10px; bottom: -10px;"></i>
            <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.8rem;">{{ number_format(\App\Models\ExternalDebt::where('type', 'owed_to_us')->where('status', '!=', 'paid')->sum('amount') - \App\Models\ExternalDebt::where('type', 'owed_to_us')->where('status', '!=', 'paid')->sum('paid_amount'), 0) }}</h4>
            <small class="text-muted fw-bold">ديون في السوق (لنا)</small>
        </div>
    </div>

    <!-- Absences -->
    <div class="col-6">
        <div class="glass-card h-100 p-3" style="position: relative; overflow: hidden;">
            <i class="bi bi-person-x-fill position-absolute text-danger opacity-25" style="font-size: 4rem; right: -10px; bottom: -10px;"></i>
            <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.8rem;">{{ $absentWorkersToday }}</h4>
            <small class="text-muted fw-bold">موظفين غائبون اليوم</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart Section -->
    <div class="col-lg-8">
        <div class="glass-card h-100">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-graph-up me-2 text-primary"></i>تحليل المبيعات (6 شهور)</h5>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Timeline & Alerts -->
    <div class="col-lg-4">
        <div class="glass-card h-100">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-activity me-2 text-primary"></i>أحدث النشاطات والتنبيهات</h5>
            <div class="timeline">
                @forelse($awaitingApprovalOrders as $order)
                <div class="timeline-item">
                    <small class="text-danger fw-bold"><i class="bi bi-shield-exclamation"></i> مراجعة مطلوبة</small>
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger rounded-4 mt-1">
                        <h6 class="fw-bold text-danger mb-1">طلبية #{{ $order->order_number }}</h6>
                        <small class="text-dark">انتهى التحميل — بانتظار اعتماد الأدمن وإصدار الفاتورة.</small>
                        <a href="{{ route('orders.index') }}" class="d-block mt-1 small">عرض الطلبيات</a>
                    </div>
                </div>
                @empty
                @endforelse

                @forelse($loadingOrders as $order)
                <div class="timeline-item">
                    <small class="text-warning fw-bold"><i class="bi bi-truck"></i> قيد التحميل</small>
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning rounded-4 mt-1">
                        <h6 class="fw-bold text-dark mb-1">طلبية #{{ $order->order_number }}</h6>
                        <small class="text-muted">العميل: {{ $order->customer_name }}</small>
                    </div>
                </div>
                @empty
                @endforelse

                @if($lowStockMaterials->count() > 0)
                <div class="timeline-item">
                    <small class="text-danger fw-bold"><i class="bi bi-tools"></i> خامات ناقصة</small>
                    <div class="p-3 bg-light border border-secondary rounded-4 mt-1">
                        <h6 class="fw-bold text-dark mb-1">{{ $lowStockMaterials->count() }} خامة تحت الحد الأدنى</h6>
                        <a href="{{ route('raw-materials.index') }}" class="small">عرض المخزون</a>
                    </div>
                </div>
                @endif

                @if($lowStockProducts->count() > 0)
                <div class="timeline-item">
                    <small class="text-danger fw-bold"><i class="bi bi-box-seam"></i> منتجات ناقصة</small>
                    <div class="p-3 bg-light border border-secondary rounded-4 mt-1">
                        <h6 class="fw-bold text-dark mb-1">{{ $lowStockProducts->count() }} منتج تحت الحد الأدنى</h6>
                        <a href="{{ route('inventory.index') }}" class="small">عرض المخزون</a>
                    </div>
                </div>
                @endif

                @forelse($overdueInvoices as $invoice)
                <div class="timeline-item">
                    <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle-fill"></i> تأخر سداد</small>
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger rounded-4 mt-1">
                        <h6 class="fw-bold text-danger mb-1">فاتورة #{{ $invoice->invoice_number }}</h6>
                        <small class="text-dark">العميل {{ $invoice->customer->name }} تأخر عن السداد.</small>
                    </div>
                </div>
                @empty
                @endforelse

                @if($awaitingApprovalOrders->isEmpty() && $loadingOrders->isEmpty() && $lowStockMaterials->isEmpty() && $lowStockProducts->isEmpty() && $overdueInvoices->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                    لا توجد نشاطات حديثة أو تنبيهات.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Gradient for the line chart
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(234, 88, 12, 0.4)');
    gradient.addColorStop(1, 'rgba(234, 88, 12, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($salesChartLabels) !!},
            datasets: [{
                label: 'إجمالي المبيعات (ج.م)',
                data: {!! json_encode($salesChartData) !!},
                borderColor: '#ea580c',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#ea580c',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#000',
                    bodyColor: '#ea580c',
                    borderColor: 'rgba(234, 88, 12, 0.2)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('en-US').format(context.raw) + ' ج.م';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: { font: { family: 'Cairo' } }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: 'Cairo' } }
                }
            }
        }
    });
</script>
@endpush
@endsection

