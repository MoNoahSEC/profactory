@extends('layouts.app')

@section('title', 'لوحة الإدارة | ' . ($globalSettings['company_name'] ?? 'مصنع المنتجات'))
@section('page_title', 'لوحة القيادة')

@push('styles')
<style>
    /* ══════════════════════════════════════════════════════
       ✨ LUXURY DASHBOARD & ANIMATED APP GRID
       ══════════════════════════════════════════════════════ */
    
    /* 1. Welcome Hero Banner */
    .dashboard-hero {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 50%, #9a3412 100%);
        border-radius: 22px;
        color: #ffffff;
        padding: 1.75rem 2rem;
        box-shadow: 0 15px 35px rgba(234, 88, 12, 0.22);
        position: relative;
        overflow: hidden;
        animation: heroFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .dashboard-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        left: -30%;
        width: 160%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
        animation: heroGlow 12s linear infinite;
        pointer-events: none;
    }
    
    @keyframes heroGlow {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes heroFadeIn {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .hero-stat-pill {
        background: rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1.5px solid rgba(255, 255, 255, 0.3);
        border-radius: 18px;
        padding: 0.85rem 1rem;
        text-align: center;
        transition: transform 0.25s ease, background 0.25s ease;
    }
    .hero-stat-pill:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-3px);
    }
    .hero-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 2px;
    }
    .hero-stat-val {
        font-size: 1.35rem;
        font-weight: 900;
        color: #ffffff;
        letter-spacing: -0.5px;
    }

    /* 2. iOS-Style Centered App Grid */
    .apps-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 20px 14px;
        padding: 0.5rem 0;
    }

    .app-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        text-decoration: none;
        cursor: pointer;
        user-select: none;
        transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .app-tile-icon {
        width: 62px;
        height: 62px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.85rem;
        color: #ffffff !important;
        position: relative;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .app-tile-icon::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 20px;
        background: linear-gradient(180deg, rgba(255,255,255,0.3) 0%, transparent 60%);
        pointer-events: none;
    }

    .app-tile:hover .app-tile-icon {
        transform: translateY(-6px) scale(1.08);
    }
    .app-tile:active .app-tile-icon {
        transform: scale(0.88);
    }

    .app-tile-label {
        font-size: 0.82rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 8px;
        text-align: center;
        line-height: 1.25;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease;
    }
    .app-tile:hover .app-tile-label {
        color: #ea580c;
    }

    /* Vibrant Shadows for Icons */
    .icon-glow-orange  { background: linear-gradient(135deg, #f97316, #ea580c); box-shadow: 0 10px 22px rgba(234, 88, 12, 0.38); }
    .icon-glow-sky     { background: linear-gradient(135deg, #0ea5e9, #0284c7); box-shadow: 0 10px 22px rgba(2, 132, 199, 0.38); }
    .icon-glow-green   { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 10px 22px rgba(22, 163, 74, 0.38); }
    .icon-glow-purple  { background: linear-gradient(135deg, #a855f7, #7c3aed); box-shadow: 0 10px 22px rgba(124, 58, 237, 0.38); }
    .icon-glow-rose    { background: linear-gradient(135deg, #f43f5e, #e11d48); box-shadow: 0 10px 22px rgba(225, 29, 72, 0.38); }
    .icon-glow-amber   { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 10px 22px rgba(217, 119, 6, 0.38); }
    .icon-glow-yellow  { background: linear-gradient(135deg, #eab308, #ca8a04); box-shadow: 0 10px 22px rgba(202, 138, 4, 0.38); }
    .icon-glow-teal    { background: linear-gradient(135deg, #14b8a6, #0d9488); box-shadow: 0 10px 22px rgba(13, 148, 136, 0.38); }
    .icon-glow-indigo  { background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 10px 22px rgba(79, 70, 229, 0.38); }
    .icon-glow-pink    { background: linear-gradient(135deg, #ec4899, #db2777); box-shadow: 0 10px 22px rgba(219, 39, 119, 0.38); }
    .icon-glow-emerald { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 10px 22px rgba(5, 150, 105, 0.38); }
    .icon-glow-slate   { background: linear-gradient(135deg, #64748b, #475569); box-shadow: 0 10px 22px rgba(71, 85, 105, 0.38); }
    .icon-glow-red     { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 10px 22px rgba(220, 38, 38, 0.38); }
    .icon-glow-whatsapp{ background: linear-gradient(135deg, #25d366, #128c7e); box-shadow: 0 10px 22px rgba(37, 211, 102, 0.38); }

    /* 3. Modern KPI Cards */
    .kpi-card {
        background: #ffffff;
        border: 2px solid #e2e8f0;
        border-radius: 18px;
        padding: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(0,0,0,0.03);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.07);
    }
    .kpi-icon-circle {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }

    /* Mobile Responsive Optimizations */
    @media (max-width: 768px) {
        .dashboard-hero {
            padding: 1.25rem 1rem;
            border-radius: 18px;
            margin-bottom: 1rem !important;
        }
        .dashboard-hero h2 {
            font-size: 1.3rem !important;
        }
        .hero-stat-pill {
            padding: 0.6rem 0.75rem;
            border-radius: 14px;
        }
        .hero-stat-val {
            font-size: 1.15rem;
        }
        .apps-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 14px 6px;
        }
        .app-tile-icon {
            width: 54px;
            height: 54px;
            font-size: 1.55rem;
            border-radius: 16px;
        }
        .app-tile-label {
            font-size: 0.74rem;
            height: 28px;
            margin-top: 6px;
        }
        .kpi-card {
            padding: 0.85rem;
            border-radius: 14px;
        }
        .kpi-icon-circle {
            width: 44px;
            height: 44px;
            font-size: 1.3rem;
            border-radius: 12px;
        }
        .chart-container {
            height: 240px !important;
        }
    }
</style>
@endpush

@section('content')

<!-- 1. Welcome Hero Section -->
<div class="dashboard-hero mb-4">
    <div class="row align-items-center position-relative" style="z-index: 2;">
        <div class="col-md-7 mb-3 mb-md-0">
            <h2 class="fw-bold mb-1">مرحباً بعودتك، {{ Auth::user()->name }}! 🌅</h2>
            <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
                <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::now()->translatedFormat('l, j F Y') }}
            </p>
        </div>
        <div class="col-md-5">
            <div class="row g-2">
                <div class="col-6">
                    <div class="hero-stat-pill">
                        <div class="hero-stat-label">رصيد الخزينة</div>
                        <div class="hero-stat-val">{{ number_format($cashSummary['balance'], 0) }} <small style="font-size:0.7rem;">ج.م</small></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="hero-stat-pill">
                        <div class="hero-stat-label">مبيعات الشهر</div>
                        <div class="hero-stat-val">{{ number_format($currentMonthSales, 0) }} <small style="font-size:0.7rem;">ج.م</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Apps & Quick Actions Grid -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-grid-fill text-orange me-2"></i>الوصول السريع للأقسام</h6>
    </div>
    <div class="p-3">
        <div class="apps-grid">
            
            <!-- 1. Orders -->
            <a href="{{ route('orders.index', ['open' => 'create']) }}" class="app-tile">
                <div class="app-tile-icon icon-glow-orange"><i class="bi bi-cart-plus-fill"></i></div>
                <span class="app-tile-label">طلب جديد</span>
            </a>
            
            <!-- 2. Production -->
            <a href="{{ route('production.daily') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-sky"><i class="bi bi-gear-wide-connected"></i></div>
                <span class="app-tile-label">الإنتاج</span>
            </a>

            <!-- 3. Purchases -->
            <a href="{{ route('purchases.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-green"><i class="bi bi-box-seam-fill"></i></div>
                <span class="app-tile-label">المشتريات</span>
            </a>

            <!-- 4. Salaries -->
            <a href="{{ route('salaries.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-purple"><i class="bi bi-calendar-check-fill"></i></div>
                <span class="app-tile-label">الرواتب</span>
            </a>

            <!-- 5. Treasury / Cash -->
            <a href="{{ route('expenses.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-rose"><i class="bi bi-wallet-fill"></i></div>
                <span class="app-tile-label">الخزينة</span>
            </a>

            <!-- 6. Workshops -->
            <a href="{{ route('workshops.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-amber"><i class="bi bi-tools"></i></div>
                <span class="app-tile-label">الورش</span>
            </a>

            <!-- 7. Debts -->
            <a href="{{ route('debts.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-yellow"><i class="bi bi-journal-bookmark-fill"></i></div>
                <span class="app-tile-label">الديون</span>
            </a>

            <!-- 8. Workers -->
            <a href="{{ route('workers.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-teal"><i class="bi bi-people-fill"></i></div>
                <span class="app-tile-label">الموظفين</span>
            </a>

            <!-- 9. Inventory -->
            <a href="{{ route('inventory.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-indigo"><i class="bi bi-boxes"></i></div>
                <span class="app-tile-label">المخزون</span>
            </a>

            <!-- 10. Products -->
            <a href="{{ route('products.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-pink"><i class="bi bi-tags-fill"></i></div>
                <span class="app-tile-label">المنتجات</span>
            </a>

            <!-- 11. Reports -->
            <a href="{{ route('reports.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-emerald"><i class="bi bi-pie-chart-fill"></i></div>
                <span class="app-tile-label">التقارير</span>
            </a>
            
            <!-- 12. Advances & Penalties -->
            <a href="{{ route('advances.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-red"><i class="bi bi-cash-stack"></i></div>
                <span class="app-tile-label">السلف</span>
            </a>

            <!-- 13. Invoices -->
            <a href="{{ route('invoices.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-sky"><i class="bi bi-receipt-cutoff"></i></div>
                <span class="app-tile-label">الفواتير</span>
            </a>

            <!-- 14. Chat -->
            <a href="{{ route('chat.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-whatsapp"><i class="bi bi-whatsapp"></i></div>
                <span class="app-tile-label">الشات</span>
            </a>

            <!-- 15. Loading System -->
            <a href="{{ route('loading.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-amber"><i class="bi bi-truck-flatbed"></i></div>
                <span class="app-tile-label">التحميل</span>
            </a>

            <!-- 16. Settings -->
            <a href="{{ route('settings.index') }}" class="app-tile">
                <div class="app-tile-icon icon-glow-slate"><i class="bi bi-sliders"></i></div>
                <span class="app-tile-label">الإعدادات</span>
            </a>

        </div>
    </div>
</div>

<!-- 3. Key Performance Indicators (KPIs) -->
<h5 class="fw-bold text-dark mb-3"><i class="bi bi-speedometer2 text-orange me-2"></i>مؤشرات الأداء اللحظية</h5>
<div class="row g-2 mb-4">
    <!-- Pending Orders -->
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ $pendingOrdersCount }}</h4>
                <small class="text-secondary fw-bold">طلبيات جارية</small>
            </div>
            <div class="kpi-icon-circle" style="background:#fffbeb; color:#d97706;">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>
    
    <!-- Stock Products -->
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalProductsInStock, 0) }}</h4>
                <small class="text-secondary fw-bold">منتج بالمخزن</small>
            </div>
            <div class="kpi-icon-circle" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-boxes"></i>
            </div>
        </div>
    </div>

    <!-- Debts (Owed to Us) -->
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ number_format(\App\Models\ExternalDebt::where('type', 'owed_to_us')->where('status', '!=', 'paid')->sum('amount') - \App\Models\ExternalDebt::where('type', 'owed_to_us')->where('status', '!=', 'paid')->sum('paid_amount'), 0) }}</h4>
                <small class="text-secondary fw-bold">ديون لنا بالسوق</small>
            </div>
            <div class="kpi-icon-circle" style="background:#f0f9ff; color:#0284c7;">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>

    <!-- Absences -->
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ $absentWorkersToday }}</h4>
                <small class="text-secondary fw-bold">غياب اليوم</small>
            </div>
            <div class="kpi-icon-circle" style="background:#fef2f2; color:#dc2626;">
                <i class="bi bi-person-x-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- 4. Sales Chart & Activities Section -->
<div class="row g-3 mb-4">
    <!-- Chart Section -->
    <div class="col-lg-8">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up text-orange me-2"></i>تحليل المبيعات (6 شهور)</h6>
            </div>
            <div class="p-3">
                <div class="chart-container" style="position: relative; height: 280px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Timeline & Alerts -->
    <div class="col-lg-4">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bell-fill text-orange me-2"></i>أحدث التنبيهات</h6>
            </div>
            <div class="p-3">
                <div class="d-flex flex-column gap-2">
                    @forelse($awaitingApprovalOrders as $order)
                        <div class="p-2 rounded-3" style="background:#fef2f2; border: 1px solid #fca5a5;">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-danger small"><i class="bi bi-shield-exclamation"></i> طلبية #{{ $order->order_number }}</strong>
                                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.75rem;">اعتماد</a>
                            </div>
                            <small class="text-secondary d-block mt-1">انتهى التحميل — بانتظار اعتماد الفاتورة.</small>
                        </div>
                    @empty
                    @endforelse

                    @forelse($loadingOrders as $order)
                        <div class="p-2 rounded-3" style="background:#fefce8; border: 1px solid #fde047;">
                            <strong class="text-warning small"><i class="bi bi-truck"></i> طلبية #{{ $order->order_number }}</strong>
                            <small class="text-secondary d-block mt-1">العميل: {{ $order->customer_name }} — جاري التحميل</small>
                        </div>
                    @empty
                    @endforelse

                    @if($lowStockMaterials->count() > 0)
                        <div class="p-2 rounded-3" style="background:#fff7ed; border: 1px solid #fdba74;">
                            <strong class="text-orange small"><i class="bi bi-tools"></i> {{ $lowStockMaterials->count() }} خامات ناقصة</strong>
                            <a href="{{ route('raw-materials.index') }}" class="btn btn-sm btn-outline-orange py-0 px-2 d-inline-block mt-1" style="font-size:0.75rem;">عرض المخزون</a>
                        </div>
                    @endif

                    @if($lowStockProducts->count() > 0)
                        <div class="p-2 rounded-3" style="background:#fff7ed; border: 1px solid #fdba74;">
                            <strong class="text-orange small"><i class="bi bi-box-seam"></i> {{ $lowStockProducts->count() }} منتجات ناقصة</strong>
                            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-orange py-0 px-2 d-inline-block mt-1" style="font-size:0.75rem;">عرض المخزون</a>
                        </div>
                    @endif

                    @if($awaitingApprovalOrders->isEmpty() && $loadingOrders->isEmpty() && $lowStockMaterials->isEmpty() && $lowStockProducts->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle-fill fs-2 d-block mb-1 text-success"></i>
                            <span class="small fw-bold">جميع العمليات والمخزون في وضع ممتاز!</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxEl = document.getElementById('salesChart');
        if (!ctxEl) return;
        const ctx = ctxEl.getContext('2d');
        
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(234, 88, 12, 0.35)');
        gradient.addColorStop(1, 'rgba(234, 88, 12, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($salesChartLabels) !!},
                datasets: [{
                    label: 'المبيعات (ج.م)',
                    data: {!! json_encode($salesChartData) !!},
                    borderColor: '#ea580c',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#ea580c',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#fb923c',
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
                        grid: { color: 'rgba(0,0,0,0.06)' },
                        ticks: { font: { family: 'Tajawal', weight: '700' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Tajawal', weight: '700' } }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
