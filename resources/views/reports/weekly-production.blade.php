@extends('layouts.app')
@section('title', 'تقرير الإنتاج الأسبوعي | مصنع المنتجات')
@section('page_title', 'تقرير المكنجي والمقص — أسبوعي')

@section('content')
<div class="glass-card water-card mb-4 p-3">
    <form class="row g-3 align-items-end" method="GET">
        <div class="col-md-4"><label class="form-label text-muted">من</label><input type="date" name="start" value="{{ $start }}" class="form-control form-control-glass"></div>
        <div class="col-md-4"><label class="form-label text-muted">إلى</label><input type="date" name="end" value="{{ $end }}" class="form-control form-control-glass"></div>
        <div class="col-md-4"><button type="submit" class="btn-glass w-100"><i class="bi bi-funnel me-1"></i> عرض التقرير</button></div>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="glass-card water-card text-center"><small class="text-muted">إجمالي الإنتاج</small><h3 class="fw-bold text-primary">{{ $report['total_produced'] }} قطعة</h3></div></div>
    <div class="col-md-4"><div class="glass-card water-card text-center"><small class="text-muted">عمليات المكنجي</small><h3 class="fw-bold">{{ $report['machinist_records']->count() }}</h3></div></div>
    <div class="col-md-4"><div class="glass-card water-card text-center"><small class="text-muted">موظفين المقص النشطين</small><h3 class="fw-bold">{{ $report['scissors_summary']->count() }}</h3></div></div>
</div>

<!-- Scissors Summary: who worked behind whom -->
<div class="glass-card water-card mb-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-scissors text-danger me-2"></i>تقرير المقص — من اشتغل ورا مين</h5>
    <p class="text-muted small mb-4">هذا التقرير محفوظ في قاعدة البيانات ويمكن الرجوع إليه في أي وقت — حتى بعد سنة</p>

    @forelse($report['scissors_summary'] as $item)
    <div class="pairing-card mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-scissors text-danger me-1"></i> {{ $item['scissors'] }}</h6>
            <span class="smart-badge">{{ $item['total_qty'] }} قطعة — {{ number_format($item['total_pay'], 0) }} ج</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-glass mb-0">
                <thead><tr><th>المكنجي</th><th>الكمية</th><th>المبلغ</th><th>الأيام</th></tr></thead>
                <tbody>
                    @foreach($item['machinists'] as $m)
                    <tr>
                        <td class="fw-bold"><i class="bi bi-person-gear me-1 text-primary"></i> {{ $m['machinist'] }}</td>
                        <td>{{ $m['total_qty'] }}</td>
                        <td class="text-success fw-bold">{{ number_format($m['total_pay'], 0) }} ج</td>
                        <td><small class="text-muted">{{ $m['days']->implode('، ') }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>لا توجد بيانات في هذه الفترة</div>
    @endforelse
</div>

<!-- Detailed Machinist Log -->
<div class="glass-card water-card">
    <h5 class="fw-bold mb-4"><i class="bi bi-list-check me-2"></i>سجل تفصيلي — المكنجي</h5>
    <div class="scroll-panel">
        <div class="table-responsive scroll-inner">
            <table class="table table-glass align-middle mb-0">
                <thead><tr><th>التاريخ</th><th>المكنجي</th><th>المنتج</th><th>الكمية</th><th>المقص</th><th>أجر المكنجي</th></tr></thead>
                <tbody>
                    @forelse($report['machinist_records'] as $r)
                    <tr>
                        <td>{{ $r->date->format('Y-m-d') }}</td>
                        <td class="fw-bold">{{ $r->worker->name ?? '-' }}</td>
                        <td>{{ $r->product->name ?? '-' }}</td>
                        <td><span class="smart-badge">{{ $r->quantity }}</span></td>
                        <td>{{ $r->scissorsWorker->name ?? '—' }}</td>
                        <td class="text-success fw-bold">{{ number_format($r->total_pay, 0) }} ج</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد سجلات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="text-end mt-4 no-print">
    <button onclick="window.print()" class="btn-glass"><i class="bi bi-printer me-1"></i> طباعة التقرير</button>
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
    .smart-badge, .badge, .btn, .form-control {
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
    h3, h5, h6 {
        color: #000 !important;
        page-break-after: avoid;
    }
    .scroll-panel, .scroll-inner, .table-responsive {
        overflow: visible !important;
        max-height: none !important;
    }
    .pairing-card {
        page-break-inside: avoid;
        border: 1px solid #000 !important;
        padding: 10px !important;
        margin-bottom: 20px !important;
    }
}
</style>
@endpush

