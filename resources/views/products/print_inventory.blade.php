@extends('layouts.print')
@section('title', 'نموذج جرد المخزن')

@push('styles')
<style>
    body {
        font-family: 'Cairo', sans-serif;
        background-color: #fff;
    }
    .print-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 3px double #000;
        padding-bottom: 15px;
    }
    .print-header h2 {
        margin: 0;
        font-weight: 800;
        font-size: 28px;
    }
    .meta-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 25px;
        font-weight: bold;
        font-size: 16px;
        padding: 10px 20px;
        background-color: #f8f9fa;
        border: 1px solid #000;
        border-radius: 8px;
    }
    .table {
        width: 100%;
        border-collapse: collapse !important;
        margin-bottom: 30px;
        font-size: 16px;
    }
    .table th, .table td {
        border: 2px solid #000 !important;
        padding: 12px 15px;
        text-align: center;
        vertical-align: middle;
    }
    .table th {
        background-color: #f0f0f0 !important;
        font-weight: 800;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .table tbody tr {
        border: 2px solid #000 !important;
    }
    .category-header {
        background-color: #d9d9d9 !important;
        font-weight: 900 !important;
        font-size: 18px;
        text-align: center !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    @media print {
        .table th, .table td {
            border: 2px solid #000 !important;
        }
        .table {
            border: 2px solid #000 !important;
        }
    }
    .signature-area {
        margin-top: 60px;
        display: flex;
        justify-content: space-around;
        font-weight: bold;
        font-size: 16px;
    }
    .signature-box {
        text-align: center;
        width: 250px;
    }
    .signature-line {
        margin-top: 50px;
        border-top: 2px dashed #000;
    }
</style>
@endpush

@section('content')

<div class="print-header">
    <h2>نموذج جرد المخزن (المنتجات)</h2>
</div>

<div class="meta-info">
    <div>تاريخ الجرد: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
    <div>أمين المخزن: ................................................</div>
</div>

<table class="table">
    <thead>
        <tr>
            <th style="width: 5%;">م</th>
            <th style="width: 45%;">اسم المنتج</th>
            <th style="width: 20%;">الرصيد الفعلي (جرد)</th>
            <th style="width: 30%;">ملاحظات / العجز أو الزيادة</th>
        </tr>
    </thead>
    <tbody>
        @php 
            $counter = 1; 
            $currentCategory = null;
        @endphp
        @foreach($products as $product)
            @if($currentCategory !== $product->category_id)
                @php $currentCategory = $product->category_id; @endphp
                <tr>
                    <td colspan="4" class="category-header">
                        <i class="bi bi-folder-fill me-2"></i> {{ $product->category->name ?? 'بدون تصنيف' }}
                    </td>
                </tr>
            @endif
            <tr>
                <td style="font-weight: bold;">{{ $counter++ }}</td>
                <td style="text-align: right; font-weight: bold; font-size: 16px;">{{ $product->name }}</td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="signature-area">
    <div class="signature-box">
        توقيع أمين المخزن
        <div class="signature-line"></div>
    </div>
    <div class="signature-box">
        توقيع المراجع / المدير
        <div class="signature-line"></div>
    </div>
</div>

@endsection
