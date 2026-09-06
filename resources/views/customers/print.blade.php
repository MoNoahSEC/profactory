@extends('layouts.print')
@php
    $title = 'كشف حساب عميل مفصل';
@endphp

@section('title', $title . ' - ' . $customer->name)
@section('customer_phone', $customer->phone ?? '')

@section('content')
<div class="row mb-4">
    <div class="col-6">
        <h3 class="fw-bold text-decoration-underline mb-3">{{ $title }}</h3>
        <table class="table table-sm table-borderless mb-0 fw-bold fs-5">
            <tr>
                <td class="p-0 pb-1" style="width: 140px">تاريخ الطباعة:</td>
                <td class="p-0 pb-1">{{ date('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td class="p-0 pb-1">الفترة:</td>
                <td class="p-0 pb-1">حتى تاريخه</td>
            </tr>
        </table>
    </div>
    <div class="col-6">
        <div class="border border-2 border-dark p-3 h-100 rounded-3" style="background-color: #f8f9fa;">
            <h5 class="fw-bold border-bottom border-dark pb-2 mb-3">بيانات العميل</h5>
            <p class="mb-2 fw-bold fs-4 text-primary">{{ $customer->name }}</p>
            <p class="mb-2 fs-5"><i class="bi bi-telephone me-2"></i> {{ $customer->phone ?? 'غير مسجل' }}</p>
            <p class="mb-0 fs-5"><i class="bi bi-geo-alt me-2"></i> {{ $customer->address ?? 'غير مسجل' }}</p>
        </div>
    </div>
</div>

<div class="mb-4">
    <h5 class="fw-bold mb-3">تفاصيل الحركات (طلبيات وسدادات)</h5>
    <table class="table table-bordered border-dark table-striped mb-0">
        <thead class="text-center table-light border-dark">
            <tr>
                <th style="width: 50px">م</th>
                <th style="width: 120px">التاريخ</th>
                <th>البيان (طلبية / سداد / تسوية)</th>
                <th style="width: 120px">دائن (له)</th>
                <th style="width: 120px">مدين (عليه)</th>
            </tr>
        </thead>
        <tbody class="fw-bold">
            @forelse($sorted as $index => $entry)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}</td>
                <td>{{ $entry['label'] }}</td>
                <td class="text-center text-success">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                <td class="text-center text-danger">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-3">لا توجد حركات مسجلة.</td>
            </tr>
            @endforelse
            
            <!-- Fill empty rows if items are too few to maintain layout structure -->
            @for($i = count($sorted); $i < 5; $i++)
            <tr>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>
</div>

<div class="row mt-4">
    <div class="col-6">
        <div class="border border-dark p-3 mt-auto rounded-3 bg-light">
            <h6 class="fw-bold mb-2">ملاحظات:</h6>
            <p class="mb-0">هذا الكشف يمثل ملخصاً لجميع العمليات المالية والفواتير المسجلة على النظام حتى تاريخ طباعته.</p>
        </div>
    </div>
    <div class="col-6">
        <table class="table table-bordered border-dark text-center fw-bold mb-0">
            <tr>
                <th class="w-50 fs-5 bg-light border-dark">الصافي النهائي</th>
                <td class="fs-4 bg-light border-dark {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">
                    @if($netBalance >= 0)
                        دائن بـ : {{ number_format(abs($netBalance), 2) }} ج.م
                    @else
                        مدين بـ : {{ number_format(abs($netBalance), 2) }} ج.م
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-5 pt-5 text-center fw-bold fs-5">
    <div class="col-6">
        <p>توقيع المحاسب / الإدارة</p>
        <p>.......................................</p>
    </div>
    <div class="col-6">
        <p>توقيع العميل بالمصادقة</p>
        <p>.......................................</p>
    </div>
</div>
@endsection
