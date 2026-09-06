<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', \App\Models\Setting::get('company_name', 'الشركة'))</title>
    <link href="{{ asset('libs/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <!-- Use a serious, strict font -->
    <link href="{{ asset('libs/css/tajawal.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #fff;
            color: #000;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        /* Strict Corporate Styling */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .table {
            margin-bottom: 0.5rem;
            color: #000;
        }
        .table-bordered {
            border: 2px solid #000;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
            padding: 4px 8px; /* High density */
            vertical-align: middle;
        }
        .table-bordered th {
            background-color: #e9ecef !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            font-weight: 700;
        }
        .print-header {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .print-footer {
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 20px;
            font-size: 11px;
            text-align: center;
            font-weight: 500;
        }
        /* Paper Sizes */
        .page-A4 { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm; }
        .page-A5 { width: 148mm; min-height: 210mm; margin: 0 auto; padding: 10mm; font-size: 12px; }
        .page-A5 .table-bordered th, .page-A5 .table-bordered td { padding: 2px 4px; }
        .page-A5 h4 { font-size: 1.2rem; }
        @media print {
            .page-A4 { padding: 12mm; }
            .page-A5 { padding: 8mm; }
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                background: transparent; 
                font-size: 11px !important;
            }
            .page-A4, .page-A5 { 
                width: 100% !important; 
                min-height: auto !important; 
                margin: 0 !important; 
                padding: 8mm !important;
                box-shadow: none !important; 
            }
            h1 { font-size: 1.4rem !important; }
            h2 { font-size: 1.2rem !important; }
            h3, h4 { font-size: 1rem !important; }
            h5, h6 { font-size: 0.9rem !important; }
            .table-bordered th, .table-bordered td {
                padding: 3px 5px !important;
                font-size: 10px !important;
            }
            .table { font-size: 10px !important; }
            .print-header { margin-bottom: 8px !important; padding-bottom: 6px !important; }
            .print-footer { margin-top: 8px !important; padding-top: 3px !important; font-size: 9px !important; }
            /* Force everything to one page if possible */
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
        @page {
            size: {{ \App\Models\Setting::get('print_size', 'A4') }};
            margin: 8mm;
        }
    </style>
</head>
<body>
    @php
        $paperSize = \App\Models\Setting::get('print_size', 'A4');
        $addressPos = \App\Models\Setting::get('address_position', 'header_left');
        $companyName = \App\Models\Setting::get('company_name', 'الشركة');
        $companyPhone = \App\Models\Setting::get('company_phone', '');
        $companyAddress = \App\Models\Setting::get('company_address', '');
        $taxId = \App\Models\Setting::get('tax_id', '');
        $cr = \App\Models\Setting::get('commercial_record', '');
    @endphp

    <!-- Print Tools -->
    <div class="container mt-3 no-print">
        <div class="d-flex justify-content-end gap-2 border-bottom pb-2 mb-3 flex-wrap">
            <button onclick="shareInvoice()" id="shareBtn" class="btn btn-success fw-bold shadow-sm">
                <i class="bi bi-whatsapp"></i> مشاركة / إرسال واتساب
            </button>
            <button onclick="window.print()" class="btn btn-dark fw-bold shadow-sm"><i class="bi bi-printer"></i> طباعة الآن</button>
            <form action="{{ route('system.direct-print') }}" method="POST" class="m-0 p-0">
                @csrf
                <input type="hidden" name="print_url" value="{{ url()->current() }}">
                <button type="submit" class="btn btn-outline-success fw-bold shadow-sm"><i class="bi bi-pc-display"></i> طباعة على الكمبيوتر</button>
            </form>
            <button onclick="window.close()" class="btn btn-outline-dark fw-bold shadow-sm">إغلاق</button>
        </div>

    </div>

    <div class="page-{{ $paperSize }}" id="printArea">
        <!-- Header -->
        <div class="print-header d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-3">
                <div style="position: relative; width: 100px; height: 100px; border-radius: 50%; overflow: hidden; background: white; border: 2px solid #000; flex-shrink: 0;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="position: absolute; top: 50%; left: 50%; width: 180%; height: auto; transform: translate(-50%, -50%); filter: hue-rotate(35deg) saturate(1.5) brightness(0.85); /* Darker for print */">
                </div>
                <div class="{{ $addressPos === 'header_center' ? 'text-center w-100' : '' }}">
                    <h2 class="mb-1">{{ $companyName }}</h2>
                    @if($taxId || $cr)
                        <div class="fw-bold fs-6 mt-2 mb-1">
                            @if($taxId) <span class="me-3">البطاقة الضريبية: {{ $taxId }}</span> @endif
                            @if($cr) <span>السجل التجاري: {{ $cr }}</span> @endif
                        </div>
                    @endif
                </div>
            </div>

            @if(in_array($addressPos, ['header_left', 'header_center']))
                <div class="text-{{ $addressPos === 'header_center' ? 'center mt-2' : 'end' }}">
                    @if($companyPhone) <p class="mb-0 fw-bold">هاتف: <span dir="ltr">{{ $companyPhone }}</span></p> @endif
                    @if($companyAddress) <p class="mb-0">{{ $companyAddress }}</p> @endif
                </div>
            @endif
        </div>

        <!-- Content -->
        @yield('content')

        <!-- Footer -->
        <div class="print-footer">
            @if($addressPos === 'footer')
                <div class="mb-2 fw-bold">
                    @if($companyAddress) <span class="me-3">{{ $companyAddress }}</span> @endif
                    @if($companyPhone) <span>هاتف: <span dir="ltr">{{ $companyPhone }}</span></span> @endif
                </div>
            @endif
            <p class="mb-0">تم الإصدار آلياً بواسطة نظام المصنع الموحد - {{ date('Y-m-d H:i') }}</p>
        </div>
    </div>

    <!-- html-to-image for sharing (Better RTL support) -->
    <script src="{{ asset('libs/js/html-to-image.min.js') }}"></script>
    <script>
        async function shareInvoice() {
            const btn = document.getElementById('shareBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> جاري تجهيز الصورة...';
            btn.disabled = true;

            try {
                const element = document.getElementById('printArea');
                
                // Wrapper div creation to encapsulate and fix width independent of viewport
                const wrapper = document.createElement('div');
                wrapper.style.width = '800px';
                wrapper.style.padding = '20px';
                wrapper.style.backgroundColor = '#ffffff';
                wrapper.style.position = 'absolute';
                wrapper.style.top = '-9999px';
                wrapper.style.left = '-9999px';
                document.body.appendChild(wrapper);
                
                // Clone the print area into the wrapper
                const clone = element.cloneNode(true);
                wrapper.appendChild(clone);
                
                const blob = await htmlToImage.toBlob(wrapper, { 
                    backgroundColor: '#ffffff',
                    pixelRatio: 2,
                    quality: 0.95,
                    width: 800,
                    style: { transform: 'none' } // prevent parent scale issues
                });
                
                // Cleanup
                document.body.removeChild(wrapper);
                
                const file = new File([blob], 'invoice.png', { type: 'image/png' });
                    
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                files: [file],
                                title: 'الفاتورة',
                                text: 'مرفق صورة الفاتورة'
                            });
                            btn.innerHTML = '<i class="bi bi-check-circle"></i> تمت المشاركة';
                        } catch (e) {
                            console.error('Share failed', e);
                            fallbackShare(blob);
                        }
                    } else {
                        // Fallback for desktop Chrome/Windows
                        fallbackShare(blob);
                    }
                    setTimeout(() => { btn.innerHTML = originalText; btn.disabled = false; }, 3000);
                } catch (e) {
                tools.style.display = 'flex';
                alert('حدث خطأ أثناء إنشاء الصورة');
                btn.innerHTML = originalText;
                btn.disabled = false;
                console.error(e);
            }
        }

        async function fallbackShare(blob) {
            let phone = "{{ View::hasSection('customer_phone') ? trim(View::getSection('customer_phone')) : '' }}";
            // Clean phone number (remove leading 0 and add country code if needed, assuming Egypt +20)
            if (phone.startsWith('01')) {
                phone = '2' + phone;
            }

            try {
                await navigator.clipboard.write([new ClipboardItem({ "image/png": blob })]);
                if (phone) {
                    alert("تم نسخ صورة الفاتورة بنجاح! 📋\nسيتم فتح محادثة الواتساب الآن للعميل. فقط اضغط (لصق / Paste) داخل المحادثة.");
                    window.open(`https://web.whatsapp.com/send?phone=${phone}`, '_blank');
                } else {
                    alert("تم نسخ صورة الفاتورة بنجاح! 📋\nالعميل ليس لديه رقم مسجل، يمكنك الذهاب لبرنامج الواتساب وعمل (لصق / Paste) للملف مباشرة.");
                }
            } catch (e) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'invoice.png';
                a.click();
                URL.revokeObjectURL(url);
                if (phone) {
                    alert("تم تحميل صورة الفاتورة لعدم دعم النسخ التلقائي. سيتم فتح محادثة الواتساب للعميل.");
                    window.open(`https://web.whatsapp.com/send?phone=${phone}`, '_blank');
                } else {
                    alert("تم تحميل صورة الفاتورة على جهازك، يمكنك إرسالها الآن.");
                }
            }
        }

        @if(request()->has('share'))
        window.addEventListener('load', function() {
            setTimeout(() => {
                shareInvoice();
            }, 800);
        });
        @else
        window.addEventListener('load', function() {
            setTimeout(() => {
                window.print();
            }, 600);
        });
        @endif
    </script>
</body>
</html>
