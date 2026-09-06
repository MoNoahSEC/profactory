@extends('layouts.app')

@section('title', 'كشف فواتير العملاء | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'كشف فواتير العملاء')

@section('content')

{{-- Filters --}}
<div class="glass-panel p-3 mb-4 d-print-none">
    <form method="GET" action="{{ route('reports.customer-invoices') }}" class="row g-3 align-items-center">
        <div class="col-md-auto">
            <label class="form-label fw-bold mb-0 me-2">الفترة:</label>
        </div>
        <div class="col-md-3">
            <select name="filter" class="form-select" onchange="this.form.submit()">
                <option value="daily" {{ $filter === 'daily' ? 'selected' : '' }}>اليوم</option>
                <option value="weekly" {{ $filter === 'weekly' ? 'selected' : '' }}>هذا الأسبوع</option>
                <option value="monthly" {{ $filter === 'monthly' ? 'selected' : '' }}>هذا الشهر</option>
                <option value="yearly" {{ $filter === 'yearly' ? 'selected' : '' }}>هذا العام</option>
                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>كل الفترات</option>
            </select>
        </div>
        <div class="col-md-auto ms-auto">
            <button type="button" class="btn btn-info text-white" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> طباعة الكشف
            </button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary ms-2">
                رجوع للتقارير
            </a>
        </div>
    </form>
</div>

{{-- Printable Area --}}
<div class="glass-panel p-4" id="printableArea">
    <div class="text-center mb-4 d-none d-print-block">
        <h3>كشف فواتير العملاء</h3>
        <p>الفترة: 
            @if($filter === 'all') كل الفترات 
            @else {{ $startDate?->format('Y-m-d') }} إلى {{ $endDate?->format('Y-m-d') }}
            @endif
        </p>
    </div>

    @if($invoices->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-receipt" style="font-size:3rem;"></i>
            <p class="mt-3">لا توجد فواتير في هذه الفترة</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>اسم العميل</th>
                        <th>إجمالي الفاتورة</th>
                        <th>المدفوع</th>
                        <th>المتبقي من الفاتورة</th>
                        <th>إجمالي حساب العميل (الحالي)</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalAmount = 0;
                        $totalPaid = 0;
                        $totalRemaining = 0;
                    @endphp
                    @foreach($invoices as $invoice)
                    @php
                        $totalAmount += $invoice->total_amount;
                        $totalPaid += $invoice->paid_amount;
                        $totalRemaining += $invoice->remaining_amount;
                    @endphp
                    <tr>
                        <td class="fw-bold">#{{ $invoice->id }}</td>
                        <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                        <td class="fw-bold">{{ $invoice->customer->name ?? 'عميل محذوف' }}</td>
                        <td>{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="text-success">{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="text-danger">{{ number_format($invoice->remaining_amount, 2) }}</td>
                        <td class="fw-bold bg-light">
                            @if($invoice->customer)
                                @if($invoice->customer->outstanding_balance > 0)
                                    <span class="text-danger">{{ number_format($invoice->customer->outstanding_balance, 2) }} (عليه)</span>
                                @elseif($invoice->customer->outstanding_balance < 0)
                                    <span class="text-success">{{ number_format(abs($invoice->customer->outstanding_balance), 2) }} (له)</span>
                                @else
                                    <span class="text-muted">مسدد 0.00</span>
                                @endif
                            @else
                                ---
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">الإجماليات:</td>
                        <td>{{ number_format($totalAmount, 2) }}</td>
                        <td class="text-success">{{ number_format($totalPaid, 2) }}</td>
                        <td class="text-danger">{{ number_format($totalRemaining, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printableArea, #printableArea * {
            visibility: visible;
        }
        #printableArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .d-print-none {
            display: none !important;
        }
        .d-print-block {
            display: block !important;
        }
        .table {
            border-color: #000 !important;
        }
        .table th, .table td {
            border: 1px solid #000 !important;
            padding: 4px !important;
            font-size: 14px;
        }
        .glass-panel {
            box-shadow: none !important;
            border: none !important;
        }
        .table-dark th {
            background-color: #eee !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>
@endpush
@endsection
