@extends('layouts.app')
@section('title', 'تفاصيل فاتورة الورشة | مصنع المنتجات')

@push('styles')
<style>
.action-bar { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.action-bar .action-title { color: #fff; font-weight: 700; font-size: 1.1rem; }
.action-bar .btn-action { border-radius: 12px; font-weight: 700; padding: 10px 20px; font-size: 0.9rem; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; cursor: pointer; text-decoration: none; }
.action-bar .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.btn-print { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; }
.btn-print-pc { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.btn-whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); color: #fff; }
.btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.btn-back { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
@media (max-width: 767px) { .action-bar { flex-direction: column; align-items: stretch; padding: 12px; } .action-bar .btn-action { justify-content: center; padding: 12px; font-size: 1rem; } }
</style>
@endpush

@section('content')
<div class="action-bar">
    <div>
        <div class="action-title"><i class="bi bi-receipt me-2"></i>فاتورة ورشة #{{ $invoice->invoice_number }}</div>
        <small style="color:rgba(255,255,255,0.6)">{{ $invoice->workshop->name }} @if($invoice->workshop->phone) — {{ $invoice->workshop->phone }} @endif</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('workshops.invoices.print', $invoice) }}?t={{ time() }}" target="_blank" class="btn-action btn-print">
            <i class="bi bi-printer-fill"></i> طباعة / مشاركة
        </a>
        <form action="{{ route('system.direct-print') }}" method="POST" class="m-0 p-0">
            @csrf <input type="hidden" name="print_url" value="{{ route('workshops.invoices.print', $invoice) }}">
            <button type="submit" class="btn-action btn-print-pc"><i class="bi bi-pc-display"></i> طباعة على الكمبيوتر</button>
        </form>
        @if($invoice->workshop && $invoice->workshop->phone)
            <a href="https://wa.me/2{{ ltrim($invoice->workshop->phone, '0') }}" target="_blank" class="btn-action btn-whatsapp">
                <i class="bi bi-whatsapp"></i> واتساب مباشر
            </a>
        @endif
        <a href="{{ route('workshops.invoices.edit', $invoice) }}" class="btn-action btn-edit">
            <i class="bi bi-pencil-fill"></i> تعديل
        </a>
        <a href="{{ route('workshops.show', $invoice->workshop_id) }}" class="btn-action btn-back">
            <i class="bi bi-arrow-right"></i> عودة
        </a>
    </div>
</div>

<div class="container-fluid">

    <div class="card glass-panel border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted fw-bold">اسم الورشة</h6>
                    <h5 class="fw-bold text-primary">{{ $invoice->workshop->name }}</h5>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted fw-bold">تاريخ الفاتورة</h6>
                    <h5 class="fw-bold">{{ $invoice->invoice_date->format('Y-m-d') }}</h5>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted fw-bold">مُنشئ الفاتورة</h6>
                    <h5 class="fw-bold">{{ $invoice->creator->name ?? 'النظام' }}</h5>
                </div>
            </div>
            @if($invoice->notes)
                <hr>
                <h6 class="text-muted fw-bold">ملاحظات</h6>
                <p class="mb-0">{{ $invoice->notes }}</p>
            @endif
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-danger bg-opacity-10 border-0 py-3">
                    <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-box-arrow-up-right me-2"></i> منصرف للورشة (مبيعاتنا)</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>الخامة</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $hasMaterials = false; @endphp
                            @foreach($invoice->items as $item)
                                @if($item->item_type === 'raw_material')
                                    @php $hasMaterials = true; @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $item->rawMaterial->name ?? 'غير معروف' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="fw-bold text-danger">{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            @if(!$hasMaterials)
                                <tr><td colspan="4" class="text-muted py-3">لا توجد خامات منصرفة في هذه الفاتورة.</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="table-light fw-bold text-danger">
                            <tr>
                                <td colspan="3" class="text-end">إجمالي المنصرف:</td>
                                <td>{{ number_format($invoice->total_materials_sold, 2) }} ج.م</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="bi bi-box-arrow-in-down-left me-2"></i> وارد من الورشة (مشترياتنا)</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية (بالحبة)</th>
                                <th>الكراتين</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $hasProducts = false; @endphp
                            @foreach($invoice->items as $item)
                                @if($item->item_type === 'product')
                                    @php 
                                        $hasProducts = true; 
                                        $cages = $item->product->cages_per_carton ?? 1;
                                        $cartons = $cages > 0 ? ($item->quantity / $cages) : 0;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $item->product->name ?? 'غير معروف' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            <span class="badge bg-secondary"><i class="bi bi-box"></i> {{ number_format($cartons, 2) }}</span>
                                        </td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="fw-bold text-success">{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            @if(!$hasProducts)
                                <tr><td colspan="5" class="text-muted py-3">لا توجد منتجات واردة في هذه الفاتورة.</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="table-light fw-bold text-success">
                            <tr>
                                <td colspan="4" class="text-end">إجمالي الوارد:</td>
                                <td>{{ number_format($invoice->total_products_bought, 2) }} ج.م</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ملخص الإجماليات -->
    <div class="card bg-dark text-white border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row text-center">
                <div class="col-md-3 border-end border-secondary">
                    <h6 class="text-white-50 fw-bold">إجمالي المنصرف (لهم)</h6>
                    <h3 class="text-danger fw-bold mb-0">{{ number_format($invoice->total_materials_sold, 2) }}</h3>
                </div>
                <div class="col-md-3 border-end border-secondary">
                    <h6 class="text-white-50 fw-bold">إجمالي الوارد (منهم)</h6>
                    <h3 class="text-success fw-bold mb-0">{{ number_format($invoice->total_products_bought, 2) }}</h3>
                </div>
                <div class="col-md-3 border-end border-secondary">
                    <h6 class="text-white-50 fw-bold">الصافي</h6>
                    @if($invoice->net_amount > 0)
                        <h3 class="text-warning fw-bold mb-0">{{ number_format($invoice->net_amount, 2) }} <small class="fs-6">(الورشة مديونة)</small></h3>
                    @elseif($invoice->net_amount < 0)
                        <h3 class="text-info fw-bold mb-0">{{ number_format(abs($invoice->net_amount), 2) }} <small class="fs-6">(نحن مديونون)</small></h3>
                    @else
                        <h3 class="text-white fw-bold mb-0">0.00</h3>
                    @endif
                </div>
                <div class="col-md-3">
                    <h6 class="text-white-50 fw-bold">المدفوع نقداً بالورقة</h6>
                    <h3 class="text-white fw-bold mb-0">{{ number_format($invoice->paid_amount, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
