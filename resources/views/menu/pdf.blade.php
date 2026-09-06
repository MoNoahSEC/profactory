<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>كتالوج المنتجات</title>
    <style>
        /* استيراد خط Cairo الراقي */
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: normal;
            src: url("https://raw.githubusercontent.com/google/fonts/main/ofl/cairo/Cairo-Regular.ttf") format('truetype');
        }
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: bold;
            src: url("https://raw.githubusercontent.com/google/fonts/main/ofl/cairo/Cairo-Bold.ttf") format('truetype');
        }
        
        body {
            font-family: 'Cairo', 'DejaVu Sans', sans-serif;
            text-align: right;
            margin: 0; 
            padding: 20px;
            background-color: #ffffff;
            color: #1e293b;
        }

        /* ═══ الغلاف الفخم ═══ */
        .cover-page {
            page-break-after: always;
            text-align: center;
            padding-top: 150px;
            position: relative;
        }
        .cover-border {
            border: 2px solid #d97706; /* لون ذهبي/برتقالي راقي */
            position: absolute;
            top: 20px;
            bottom: 20px;
            left: 20px;
            right: 20px;
            z-index: -1;
        }
        .cover-title {
            font-size: 60px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 20px;
            padding: 20px;
        }
        .cover-subtitle {
            font-size: 26px;
            color: #64748b;
            font-weight: normal;
            margin-bottom: 80px;
            border-bottom: 1px solid #e2e8f0;
            display: inline-block;
            padding-bottom: 20px;
            width: 60%;
        }
        .cover-phones {
            margin-top: 100px;
        }
        .cover-phones-title {
            font-size: 20px;
            font-weight: normal;
            color: #94a3b8;
            margin-bottom: 15px;
        }
        .phone-box {
            font-size: 24px;
            font-weight: bold;
            display: inline-block;
            margin: 8px;
            padding: 10px 30px;
            color: #d97706;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
        }

        /* ═══ التصنيف ═══ */
        .category-title {
            background-color: transparent;
            color: #0f172a;
            padding: 10px 0;
            font-size: 28px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #d97706;
            display: inline-block;
        }

        /* ═══ نظام الشبكة (4 منتجات في الورقة) ═══ */
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 20px; 
            margin-left: -20px;
            margin-right: -20px;
        }
        .grid-table tr {
            page-break-inside: avoid;
        }
        .grid-table td {
            width: 50%;
            vertical-align: top;
        }

        /* ═══ البطاقة الفنية الراقية ═══ */
        .card {
            background-color: #fdfdfd;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            height: 440px; 
            box-sizing: border-box;
        }
        .card-img-container {
            width: 100%;
            height: 250px;
            background-color: #ffffff;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            line-height: 250px;
            overflow: hidden;
        }
        .card-img {
            max-width: 100%;
            max-height: 250px;
            width: auto;
            height: auto;
            vertical-align: middle;
            border-radius: 8px;
        }
        .no-img {
            color: #cbd5e1;
            font-size: 16px;
            font-weight: normal;
            line-height: 250px;
        }
        
        .card-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-code {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: normal;
        }

        /* تنسيق الأبعاد والتعبئة */
        .card-meta {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .card-meta span {
            color: #334155;
            font-weight: bold;
        }

        .card-price {
            font-size: 26px;
            font-weight: bold;
            color: #d97706; /* لون ذهبي/برتقالي راقي للسعر */
        }
        .card-price span {
            font-size: 15px;
            font-weight: normal;
            color: #64748b;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #cbd5e1;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

    <!-- Cover Page -->
    <div class="cover-page">
        <div class="cover-border"></div>
        <div class="cover-title">
            {!! $Arabic->utf8Glyphs($globalSettings['company_name'] ?? config('app.name'), 100, false) !!}
        </div>
        <div class="cover-subtitle">
            {!! $Arabic->utf8Glyphs('كتالوج المنتجات والأسعار', 100, false) !!}
            <br><br>
            <span style="font-size: 18px; color:#94a3b8;">{{ now()->format('Y') }}</span>
        </div>

        @if(isset($phones) && count($phones) > 0)
        <div class="cover-phones">
            <div class="cover-phones-title">
                {!! $Arabic->utf8Glyphs('للتواصل والطلبات', 50, false) !!}
            </div>
            <div>
                @foreach($phones as $phone)
                    <div class="phone-box">{{ $phone }}</div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Catalog Content -->
    <div class="content">
        @foreach($categories as $category)
            @if($category->products->count() > 0)
                <div class="category-title">{!! $Arabic->utf8Glyphs($category->name) !!}</div>
                
                <table class="grid-table">
                    @php
                        // Chunk products into groups of 2 for rows
                        $rows = $category->products->chunk(2);
                    @endphp
                    
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $product)
                                <td>
                                    <div class="card">
                                        <div class="card-img-container">
                                            @if($product->image_path && file_exists(public_path($product->image_path)))
                                                <a href="{{ asset($product->image_path) }}" target="_blank">
                                                    <img src="{{ public_path($product->image_path) }}" class="card-img">
                                                </a>
                                            @else
                                                <div class="no-img">{!! $Arabic->utf8Glyphs('بدون صورة') !!}</div>
                                            @endif
                                        </div>
                                        
                                        @php
                                            $cleanName = str_replace(['×', '*'], 'x', $product->name);
                                            $cleanDim = str_replace(['×', '*'], 'x', $product->dimensions ?: 'غير محدد');
                                        @endphp
                                        <div class="card-title">{!! $Arabic->utf8Glyphs($cleanName, 100, false) !!}</div>
                                        <div class="card-code">Code: {{ $product->code }}</div>
                                        
                                        <div class="card-meta">
                                            {!! $Arabic->utf8Glyphs('الأبعاد:') !!} <span>{!! $Arabic->utf8Glyphs($cleanDim, 50, false) !!}</span>
                                            &nbsp;|&nbsp;
                                            {!! $Arabic->utf8Glyphs('التعبئة:') !!} <span>{{ $product->cages_per_carton ?? 1 }}</span>
                                        </div>
                                        
                                        @if(!isset($hidePrices) || !$hidePrices)
                                        <div class="card-price">
                                            {{ number_format($product->selling_price, 0) }} <span>{!! $Arabic->utf8Glyphs('ج.م', 50, false) !!}</span>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            
                            {{-- Add empty td if odd number of products in row --}}
                            @if($row->count() == 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach

        <div class="footer">
            {!! $Arabic->utf8Glyphs('تم إصدار هذا الكتالوج آلياً بتاريخ') !!} {{ now()->format('Y-m-d') }}
        </div>
    </div>

</body>
</html>
