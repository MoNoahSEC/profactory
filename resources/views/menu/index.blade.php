<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>قائمة المنتجات | {{ $globalSettings['company_name'] ?? config('app.name') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- منع تخزين الصفحة المؤقت في متصفح العميل (Cache) لضمان ظهور أحدث الأسعار -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- منع تخزين الصفحة المؤقت في متصفح العميل (Cache) لضمان ظهور أحدث الأسعار -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <style>
        :root {
            --primary: #FF6B00;
            --bg-main: #0a0a0c;
            --bg-card: #121318;
            --text-light: #ffffff;
            --text-dim: #8b8d98;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-light);
            min-height: 100vh;
            margin: 0;
            padding-bottom: 80px;
            /* Disable heavy scrolling effects on body */
            overscroll-behavior-y: none; 
            -webkit-tap-highlight-color: transparent;
        }

        /* 
         * FAST BACKGROUND 
         * Replaced heavy blur animations with a static, highly optimized radial gradient 
         */
        .premium-bg {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1;
            background: 
                radial-gradient(circle at 15% 50%, rgba(255, 107, 0, 0.04), transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(255, 255, 255, 0.02), transparent 50%);
        }

        /* Header */
        .menu-header {
            padding: 2.5rem 0 1.5rem;
            text-align: center;
        }
        .menu-title {
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--text-light);
            margin-bottom: 0.2rem;
            letter-spacing: -1px;
        }
        .menu-title span {
            color: var(--primary);
        }
        .menu-subtitle {
            font-size: 1.1rem;
            color: var(--text-dim);
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Categories Filter - Super Smooth */
        .category-filter {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 5px 15px 20px;
            margin: 0 -15px 1rem;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }
        .category-filter::-webkit-scrollbar { display: none; }
        
        .cat-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #1a1b23;
            border: 1px solid #2a2b36;
            color: var(--text-dim);
            padding: 10px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease-out;
        }
        .cat-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
            transform: scale(1.02);
        }

        /* 
         * ULTRA FAST SLIDE-UP CARD 
         * Uses only hardware accelerated properties (transform, opacity)
         */
        .product-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid #232530;
            overflow: hidden;
            position: relative;
            height: 340px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            /* Fast tap response */
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .product-card:active {
            transform: scale(0.97);
        }

        .product-img-wrapper {
            height: 60%;
            background: #0f1015;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }
        .product-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.4s ease;
        }
        .product-code {
            position: absolute;
            top: 12px; right: 12px;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 800;
            border: 1px solid #333;
            z-index: 2;
        }

        .product-info {
            height: 40%;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-name {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-category {
            font-size: 0.85rem;
            color: var(--text-dim);
            margin-top: 2px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
        }
        .price-val {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--primary);
            line-height: 1;
        }
        .price-curr {
            font-size: 0.9rem;
            color: var(--text-dim);
            margin-right: 2px;
        }

        /* The Slide-Up Overlay for Details */
        .details-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(18, 19, 24, 0.98);
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            transform: translateY(101%);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1);
            z-index: 10;
        }
        .product-card.show-details .details-overlay {
            transform: translateY(0);
        }

        .details-overlay .product-name {
            white-space: normal;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--primary);
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #2a2b36;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-dim);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-value {
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
        }

        .close-details-btn {
            margin-top: auto;
            background: #2a2b36;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Minimal Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
        }
        .empty-icon {
            font-size: 3.5rem;
            color: #2a2b36;
            margin-bottom: 1rem;
        }

        /* Floating Refresh Button */
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            font-weight: bold;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.4);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .refresh-btn:active {
            transform: scale(0.95);
        }

    </style>

</head>
<body>

    <div class="premium-bg"></div>

    <div class="container px-4 pb-5">
        
        <header class="menu-header">
            <h1 class="menu-title">المينيو <span>.</span></h1>
            <div class="menu-subtitle">{{ $globalSettings['company_name'] ?? config('app.name') }}</div>
            
            @if(auth()->check() && empty($isStaticExport))
            <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
                <form action="{{ route('menu.publish') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold shadow-lg" onclick="this.innerHTML='<i class=\'bi bi-hourglass-split me-2\'></i> جاري النشر...'; this.disabled=true; this.form.submit();">
                        <i class="bi bi-cloud-upload me-2"></i> نشر المنيو على الإنترنت
                    </button>
                </form>
                <button type="button" onclick="openPdfModal(false)" class="btn btn-warning rounded-3 px-4 fw-bold shadow-lg">
                    <i class="bi bi-printer me-2"></i> طباعة
                </button>
            </div>
            
            @if(session('success'))
            <div class="alert alert-success mt-3 rounded-3 fw-bold border-0" style="background: rgba(34, 197, 94, 0.2); color: #4ade80;">
                {!! session('success') !!}
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger mt-3 rounded-3 fw-bold border-0" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">
                {{ session('error') }}
            </div>
            @endif
            @endif
        </header>

        @if(auth()->check() && empty($isStaticExport))
        <!-- QR Code Section (Admin Only) -->
        <div class="mb-4 p-3 rounded-4" style="background: #1a1b23; border: 1px solid #2a2b36;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white fw-bold mb-1"><i class="bi bi-share text-warning me-2"></i>مشاركة المنيو</h6>
                    <span class="text-muted small">للعملاء على الواتساب</span>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="copyMenuLink()" class="btn btn-sm btn-dark border-secondary rounded-3 px-3"><i class="bi bi-copy"></i></button>
                    <button onclick="shareOnWhatsApp()" class="btn btn-sm btn-success rounded-3 px-3"><i class="bi bi-whatsapp"></i></button>
                </div>
            </div>
        </div>
        @endif

        <!-- Categories Filter -->
        <div class="category-filter" id="catFilter">
            @if(empty($isStaticExport))
                <a href="{{ route('menu.index') }}" class="cat-btn {{ !$selectedCategoryId ? 'active' : '' }}">الكل</a>
                @foreach($categories as $cat)
                    <a href="{{ route('menu.index', ['category' => $cat->id]) }}" class="cat-btn {{ $selectedCategoryId == $cat->id ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            @else
                <a href="javascript:void(0)" onclick="filterCategory(event, 'all')" class="cat-btn active">الكل</a>
                @foreach($categories as $cat)
                    <a href="javascript:void(0)" onclick="filterCategory(event, '{{ $cat->id }}')" class="cat-btn">
                        {{ $cat->name }}
                    </a>
                @endforeach
            @endif
        </div>

        <!-- Products Grid -->
        @if($products->isEmpty())
            <div class="empty-state">
                <i class="bi bi-box-seam empty-icon"></i>
                <h5 class="text-white fw-bold">لا توجد منتجات</h5>
            </div>
        @else
            <div class="row g-3" id="productsContainer">
                @foreach($products as $product)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 product-item" data-category="{{ $product->category_id }}">
                    <div class="product-card" onclick="this.classList.add('show-details')">
                        
                        <div class="product-img-wrapper">
                            <div class="product-code">{{ $product->code }}</div>
                            @if($product->image_path)
                                @if(!empty($isStaticExport))
                                    @php
                                        $imgSrc = isset($staticImageUrlMap) ? ($staticImageUrlMap[$product->image_path] ?? asset($product->image_path)) : asset($product->image_path);
                                    @endphp
                                    <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="product-img" loading="lazy">
                                @else
                                    <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" class="product-img" loading="lazy">
                                @endif
                            @else
                                <i class="bi bi-camera text-muted" style="font-size: 2.5rem;"></i>
                            @endif
                        </div>
                        
                        <div class="product-info">
                            <div>
                                <h3 class="product-name">{{ $product->name }}</h3>
                                <div class="product-category">{{ $product->category->name ?? 'تصنيف عام' }}</div>
                            </div>
                            <div class="price-row">
                                <div>
                                    <span class="price-val">{{ number_format($product->selling_price, 0) }}</span>
                                    <span class="price-curr">ج.م</span>
                                </div>
                                <i class="bi bi-plus-circle-fill text-primary" style="font-size: 1.5rem; color: var(--primary) !important;"></i>
                            </div>
                        </div>

                        <!-- Fast Slide-Up Details Overlay -->
                        <div class="details-overlay">
                            <h3 class="product-name">{{ $product->name }}</h3>
                            
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-rulers"></i> الأبعاد</span>
                                <span class="detail-value">{{ $product->dimensions ?: 'غير متوفر' }}</span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-box-seam"></i> التعبئة</span>
                                <span class="detail-value">{{ $product->cages_per_carton ?? 1 }} منتج / كرتونة</span>
                            </div>

                            <div class="detail-item border-0">
                                <span class="detail-label"><i class="bi bi-tag"></i> السعر النهائي</span>
                                <span class="detail-value text-warning fs-4">{{ number_format($product->selling_price, 0) }} ج.م</span>
                            </div>

                            <button class="close-details-btn" onclick="event.stopPropagation(); this.closest('.product-card').classList.remove('show-details')">
                                <i class="bi bi-chevron-down"></i> إخفاء التفاصيل
                            </button>
                        </div>

                    </div>
                </div>
                @endforeach
            </div>
        @endif
        
    </div>

    <!-- PDF Settings Modal (For Admin) -->
    @if(auth()->check() && empty($isStaticExport))
    <div class="modal fade" id="pdfModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-white" style="background: #1a1b23; border: 1px solid #2a2b36; border-radius: 16px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">إعدادات الطباعة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('menu.pdf') }}" method="GET" target="_blank" onsubmit="setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('pdfModal')).hide(), 500)">
                    <div class="modal-body py-0">
                        <input type="hidden" name="hide_prices" id="pdfHidePrices" value="0">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small text-muted">الرقم الأول (اختياري)</label>
                                <input type="text" name="phones[]" class="form-control bg-dark border-secondary text-white">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">الرقم الثاني (اختياري)</label>
                                <input type="text" name="phones[]" class="form-control bg-dark border-secondary text-white">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 mt-3">
                        <button type="submit" class="btn btn-warning w-100 rounded-3 fw-bold">إصدار الكتالوج</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterCategory(event, categoryId) {
            // Update active button
            document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');

            // Filter products
            const products = document.querySelectorAll('.product-item');
            products.forEach(product => {
                if (categoryId === 'all' || product.getAttribute('data-category') == categoryId) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }
        // Horizontal Scroll for Categories using Touch/Mouse
        const slider = document.getElementById('catFilter');
        let isDown = false, startX, scrollLeft;
        if(slider) {
            slider.addEventListener('mousedown', (e) => {
                isDown = true; 
                slider.style.cursor = 'grabbing';
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
            });
            slider.addEventListener('mouseleave', () => { isDown = false; slider.style.cursor = 'grab'; });
            slider.addEventListener('mouseup', () => { isDown = false; slider.style.cursor = 'grab'; });
            slider.addEventListener('mousemove', (e) => {
                if(!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 2;
                slider.scrollLeft = scrollLeft - walk;
            });
        }

        @if(auth()->check() && empty($isStaticExport))
        function copyMenuLink() {
            navigator.clipboard.writeText(window.location.origin + '/menu');
            alert('تم النسخ!');
        }
        function shareOnWhatsApp() {
            const msg = encodeURIComponent('شاهد المنيو الخاص بنا: ' + window.location.origin + '/menu');
            window.open('https://wa.me/?text=' + msg, '_blank');
        }
        function openPdfModal(hidePrices) {
            document.getElementById('pdfHidePrices').value = hidePrices ? '1' : '0';
            new bootstrap.Modal(document.getElementById('pdfModal')).show();
        }
        @endif

        // Force a fresh fetch by appending timestamp to the URL when clicked
        function forceRefresh() {
            var btn = document.getElementById('refreshBtn');
            if (btn) {
                btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> جاري التحديث...';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
            }
            window.location.href = window.location.pathname + '?v=' + new Date().getTime();
        }
    </script>
    
    @if(!empty($isStaticExport))
    <button id="refreshBtn" onclick="forceRefresh()" class="refresh-btn">
        <i class="bi bi-arrow-clockwise"></i> تحديث الأسعار
    </button>
    <style>
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
    @endif
</body>
</html>

