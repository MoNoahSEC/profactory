<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'مصنع أقفاص العصافير'))</title>
    
    <!-- PWA Setup -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#ea580c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ProFactory">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="ProFactory">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('icons/icon-72.png') }}">
    <link rel="apple-touch-icon" sizes="96x96" href="{{ asset('icons/icon-96.png') }}">
    <link rel="apple-touch-icon" sizes="128x128" href="{{ asset('icons/icon-128.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">
    
    <!-- Preload Fonts to show icons/text immediately -->
    <link rel="preload" href="{{ asset('libs/fonts/bootstrap-icons.woff2?2820a3852bdb9a5832199cc61cec4e65') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('libs/fonts/tajawal-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('libs/fonts/tajawal-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    
    <link rel="stylesheet" href="{{ asset('libs/css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/css/bootstrap-icons.css') }}">
    
    <!-- TomSelect for searchable dropdowns -->
    <link href="{{ asset('libs/css/tom-select.min.css') }}" rel="stylesheet">
    <style>
        .ts-control { background-color: rgba(255, 255, 255, 0.7); border-radius: 8px; border-color: rgba(234, 88, 12, 0.3); }
        .ts-dropdown { border-radius: 8px; border-color: rgba(234, 88, 12, 0.3); z-index: 9999 !important; } /* Above everything */
        /* Fix for TomSelect clipping inside modals */
        .modal-body { overflow: visible !important; }
        .modal-dialog { overflow: visible !important; }
        /* Fix scroll-panel hiding content */
        .scroll-panel { max-height: none !important; overflow: visible !important; }
        .scroll-inner { max-height: 75vh; overflow-y: auto; overflow-x: auto; }
    </style>
    
    <!-- Local Fonts -->
    <link rel="stylesheet" href="{{ asset('libs/css/tajawal.css') }}">
    {{-- Vite keeps the source styles and JavaScript in sync with the production build. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('libs/js/turbo.js') }}"></script>

    @stack('styles')
    <style>
        /* ═══════════════════════════════════════════════
           🏦 GLOBAL DESIGN SYSTEM & COLOR CONTRAST RULES
           ═══════════════════════════════════════════════ */
        :root {
            --primary: #ea580c;
            --primary-light: #ffedd5;
            --primary-dark: #c2410c;
            --bg-body: #f8fafc;
            --text-main: #0f172a;
            --text-secondary: #334155;
            --text-muted-color: #475569;
            --border-color: #e2e8f0;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .logo-circle {
            width: 48px !important;
            height: 48px !important;
            min-width: 48px !important;
            border-radius: 50% !important;
            overflow: hidden !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: #ffffff !important;
            flex-shrink: 0 !important;
            border: 1px solid #e2e8f0;
        }
        .logo-circle img {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
            max-width: 100% !important;
            max-height: 100% !important;
        }

        /* ── Desktop Layout Rules (> 992px) ── */
        @media (min-width: 993px) {
            .sidebar {
                width: 280px !important;
                position: fixed !important;
                top: 1rem !important;
                bottom: 1rem !important;
                right: 1rem !important;
                z-index: 1000 !important;
                display: flex !important;
                flex-direction: column !important;
                transform: none !important;
            }
            .main-content {
                margin-right: calc(280px + 2rem) !important;
                margin-left: 0 !important;
                padding: 1rem 1.5rem !important;
                min-height: 100vh !important;
                width: calc(100% - 280px - 2rem) !important;
                max-width: calc(100% - 280px - 2rem) !important;
            }
            .bottom-nav {
                display: none !important;
            }
        }

        /* ── Mobile/Tablet Layout Rules (<= 992px) ── */
        @media (max-width: 992px) {
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                right: 0 !important;
                width: 280px !important;
                z-index: 1060 !important;
                transform: translateX(100%) !important;
                transition: transform 0.3s ease !important;
            }
            .sidebar.show {
                transform: translateX(0) !important;
                box-shadow: -10px 0 30px rgba(0,0,0,0.15) !important;
            }
            .main-content {
                margin-right: 0 !important;
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100vw !important;
            }
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
            overflow-x: hidden;
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* ── Universal High-Contrast Badges & Pills ── */
        .badge, [class*="badge-"], .wcm-badge {
            font-weight: 800 !important;
            padding: 0.35rem 0.65rem !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.3rem !important;
            font-size: 0.82rem !important;
            line-height: 1.25 !important;
        }
        .badge i, [class*="badge-"] i {
            color: inherit !important;
            opacity: 1 !important;
        }

        .badge-orange, .badge.bg-orange, .badge-prod {
            background-color: #ffedd5 !important;
            color: #9a3412 !important;
            border: 1.5px solid #fed7aa !important;
        }
        .badge.bg-orange-solid {
            background-color: #ea580c !important;
            color: #ffffff !important;
            border: 1.5px solid #c2410c !important;
        }
        .badge-success, .badge.bg-success, .badge.bg-success-subtle, .badge-active, .badge-daily {
            background-color: #dcfce7 !important;
            color: #14532d !important;
            border: 1.5px solid #86efac !important;
        }
        .badge-danger, .badge.bg-danger, .badge.bg-danger-subtle, .badge-inactive {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
            border: 1.5px solid #fca5a5 !important;
        }
        .badge-warning, .badge.bg-warning, .badge.bg-warning-subtle {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border: 1.5px solid #fde68a !important;
        }
        .badge-info, .badge.bg-info, .badge.bg-info-subtle {
            background-color: #e0f2fe !important;
            color: #075985 !important;
            border: 1.5px solid #7dd3fc !important;
        }
        .badge-primary, .badge.bg-primary, .badge.bg-primary-subtle {
            background-color: #e0e7ff !important;
            color: #3730a3 !important;
            border: 1.5px solid #c7d2fe !important;
        }
        .badge-secondary, .badge.bg-secondary, .badge-muted, .badge.bg-muted, .badge.bg-secondary-subtle {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1.5px solid #cbd5e1 !important;
        }
        .badge-light, .badge.bg-light, .badge-white, .badge.bg-white {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1.5px solid #cbd5e1 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06) !important;
        }
        .badge-dark, .badge.bg-dark {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border: 1.5px solid #334155 !important;
        }

        /* ── Typography & Legibility Overhaul (No text matches its background) ── */
        .text-orange  { color: #ea580c !important; }
        .text-muted   { color: #475569 !important; font-weight: 600 !important; }
        .text-secondary { color: #334155 !important; font-weight: 600 !important; }
        .text-dark    { color: #0f172a !important; font-weight: 700 !important; }
        .text-danger  { color: #b91c1c !important; font-weight: 700 !important; }
        .text-success { color: #15803d !important; font-weight: 700 !important; }
        .text-warning { color: #b45309 !important; font-weight: 700 !important; }
        .text-info    { color: #0369a1 !important; font-weight: 700 !important; }
        .text-primary { color: #ea580c !important; font-weight: 700 !important; }

        .bg-orange      { background-color: var(--primary) !important; color: #ffffff !important; }
        .bg-orange-soft { background-color: var(--primary-light) !important; color: var(--primary-dark) !important; }
        .border-orange  { border-color: var(--primary) !important; }

        /* ── High-Contrast Alert Callouts ── */
        .alert {
            font-weight: 700 !important;
            border-radius: 14px !important;
            padding: 0.9rem 1.2rem !important;
        }
        .alert-warning {
            background-color: #fef3c7 !important;
            color: #78350f !important;
            border: 1.5px solid #fcd34d !important;
        }
        .alert-info {
            background-color: #e0f2fe !important;
            color: #075985 !important;
            border: 1.5px solid #7dd3fc !important;
        }
        .alert-success {
            background-color: #dcfce7 !important;
            color: #14532d !important;
            border: 1.5px solid #86efac !important;
        }
        .alert-danger {
            background-color: #fee2e2 !important;
            color: #7f1d1d !important;
            border: 1.5px solid #fca5a5 !important;
        }
        .alert-secondary {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1.5px solid #cbd5e1 !important;
        }

        /* ── Primary & Outline Buttons ── */
        .btn-orange {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff !important;
            border: none;
            font-weight: 800;
            border-radius: 12px;
            padding: .6rem 1.4rem;
            transition: all .25s ease;
            box-shadow: 0 4px 12px rgba(234,88,12,.25);
        }
        .btn-orange:hover, .btn-orange:focus {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(234,88,12,.35);
        }
        .btn-outline-orange {
            background: #ffffff;
            color: var(--primary) !important;
            border: 2px solid var(--primary);
            font-weight: 800;
            border-radius: 12px;
            padding: .6rem 1.4rem;
            transition: all .25s ease;
        }
        .btn-outline-orange:hover {
            background: var(--primary);
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(234,88,12,.25);
        }

        /* ── Clean Tables (High Contrast & Clear Grid) ── */
        .table-clean thead th {
            font-weight: 800;
            font-size: 0.92rem;
            color: #0f172a;
            padding: 0.9rem 1.2rem;
            background: #f1f5f9;
            border-bottom: 2px solid #cbd5e1;
            white-space: nowrap;
        }
        .table-clean tbody td {
            padding: 0.85rem 1.2rem;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 0.92rem;
            font-weight: 600;
            vertical-align: middle;
        }
        .table-clean tbody tr:hover td {
            background-color: #fff8f4;
        }
        .table-clean tbody tr:last-child td {
            border-bottom: none;
        }

        /* ── Stat Cards ── */
        /* ── Stat Cards Design System ── */
        .stat-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 0.85rem 1.1rem;
            transition: all .25s ease;
            position: relative;
            overflow: hidden;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        .stat-card .stat-label, .stat-card p, .stat-card h6, .stat-card div.small {
            font-size: 0.84rem !important;
            margin-bottom: 0.25rem !important;
            font-weight: 800 !important;
            color: #475569 !important;
            z-index: 2;
            position: relative;
        }
        .stat-card .stat-amount {
            font-size: 1.35rem !important;
            font-weight: 900 !important;
            color: var(--primary) !important;
            direction: ltr;
            text-align: right;
            margin-bottom: 0;
            z-index: 2;
            position: relative;
        }
        .stat-card .stat-icon {
            font-size: 2.2rem;
            color: var(--primary);
            opacity: .12;
            position: absolute;
            bottom: -6px;
            left: 6px;
            z-index: 1;
        }

        .stat-card.stat-primary {
            background: #fff7ed !important;
            border-color: #fdba74 !important;
        }
        .stat-card.stat-primary .stat-label, .stat-card.stat-primary p, .stat-card.stat-primary h6 { color: #9a3412 !important; }
        .stat-card.stat-primary .stat-amount { color: #ea580c !important; }
        .stat-card.stat-primary .stat-icon { color: #ea580c !important; opacity: .2 !important; }

        .stat-card.stat-warning {
            background: #fefce8 !important;
            border-color: #fde047 !important;
        }
        .stat-card.stat-warning .stat-label, .stat-card.stat-warning p, .stat-card.stat-warning h6 { color: #854d0e !important; }
        .stat-card.stat-warning .stat-amount { color: #ca8a04 !important; }
        .stat-card.stat-warning .stat-icon { color: #ca8a04 !important; opacity: .2 !important; }

        .stat-card.stat-success {
            background: #f0fdf4 !important;
            border-color: #86efac !important;
        }
        .stat-card.stat-success .stat-label, .stat-card.stat-success p, .stat-card.stat-success h6 { color: #166534 !important; }
        .stat-card.stat-success .stat-amount { color: #16a34a !important; }
        .stat-card.stat-success .stat-icon { color: #16a34a !important; opacity: .2 !important; }

        .stat-card.stat-info {
            background: #f0f9ff !important;
            border-color: #7dd3fc !important;
        }
        .stat-card.stat-info .stat-label, .stat-card.stat-info p, .stat-card.stat-info h6 { color: #075985 !important; }
        .stat-card.stat-info .stat-amount { color: #0284c7 !important; }
        .stat-card.stat-info .stat-icon { color: #0284c7 !important; opacity: .2 !important; }

        .stat-card.stat-danger {
            background: #fef2f2 !important;
            border-color: #fca5a5 !important;
        }
        .stat-card.stat-danger .stat-label, .stat-card.stat-danger p, .stat-card.stat-danger h6 { color: #991b1b !important; }
        .stat-card.stat-danger .stat-amount { color: #dc2626 !important; }
        .stat-card.stat-danger .stat-icon { color: #dc2626 !important; opacity: .2 !important; }

        .stat-card.stat-dark {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            border-color: #475569 !important;
        }
        .stat-card.stat-dark .stat-label, .stat-card.stat-dark p, .stat-card.stat-dark h6 { color: #f8fafc !important; }
        .stat-card.stat-dark .stat-amount { color: #fb923c !important; }
        .stat-card.stat-dark .stat-icon { color: #ffffff !important; opacity: .2 !important; }

        /* ── Page Header Card ── */
        .page-header-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1rem 1.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.03);
            margin-bottom: 1rem;
        }

        /* ── Action Quick-Tiles ── */
        .action-tile-sm {
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 1rem .8rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #ffffff;
        }
        .action-tile-sm:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(234,88,12,.15);
        }
        .action-tile-sm .tile-icon {
            font-size: 1.8rem;
            color: var(--primary);
            display: block;
            margin-bottom: .4rem;
        }
        .action-tile-sm .tile-label {
            font-weight: 800;
            font-size: .88rem;
            color: #0f172a;
        }

        /* ── Content Cards ── */
        .content-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0,0,0,.04);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .content-card-header {
            padding: 1rem 1.4rem;
            border-bottom: 2px solid #ffedd5;
            background: #fffbf8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .content-card-header h5, .content-card-header h6 {
            color: #0f172a;
            font-weight: 800;
            margin-bottom: 0;
        }

        /* ── Glass Panel ── */
        .glass-panel {
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* ── Number Inputs (High-Visibility & Touch-Optimized) ── */
        input[type="number"],
        input[type="tel"],
        input[inputmode="numeric"],
        input[inputmode="decimal"] {
            font-size: 1.2rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px;
            direction: ltr;
            text-align: right;
            color: #0f172a !important;
            background-color: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
        }
        input[type="number"]:focus,
        input[type="tel"]:focus,
        input[inputmode="numeric"]:focus,
        input[inputmode="decimal"]:focus {
            background-color: #fffbf5 !important;
            border-color: #ea580c !important;
            box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.18) !important;
        }

        /* Remove spinner arrows */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; }

        /* Key inputs centered */
        input[name*="quantity"], input[name*="loaded"],
        input[name*="price"], input[name*="amount"],
        input[name*="total"], input[name*="cost"],
        .qty-input {
            font-size: 1.3rem !important;
            font-weight: 900 !important;
            text-align: center !important;
            color: #0f172a !important;
            border: 2px solid rgba(234,88,12,0.3) !important;
            border-radius: 12px !important;
            background: #fffcfa !important;
        }

        /* ═══════════════════════════════════════════════
           📱 ULTRA-RESPONSIVE MOBILE ENGINE (< 768px)
           ═══════════════════════════════════════════════ */
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden !important;
                max-width: 100vw;
            }

            .main-content {
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                padding-top: 0.5rem !important;
                padding-bottom: calc(85px + env(safe-area-inset-bottom, 16px)) !important;
                max-width: 100vw !important;
            }

            /* Mobile Glass Cards & Content Cards */
            .glass-card, .content-card {
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 16px !important;
                box-shadow: 0 4px 14px rgba(0,0,0,0.03) !important;
                padding: 1rem !important;
                margin-bottom: 0.85rem !important;
            }

            /* Responsive Stat Cards: Clean 2-column or 3-column wrap */
            .stat-card {
                padding: 0.65rem 0.85rem !important;
                min-height: 72px !important;
                border-radius: 14px !important;
            }
            .stat-card .stat-amount {
                font-size: 1.15rem !important;
            }
            .stat-card .stat-icon {
                font-size: 1.6rem !important;
                bottom: -3px;
                left: 2px;
            }
            .stat-card .stat-label, .stat-card p, .stat-card h6, .stat-card div.small {
                font-size: 0.76rem !important;
                line-height: 1.25 !important;
            }

            /* Page Header Card on Mobile: Title + Full Width Buttons */
            .page-header-card {
                padding: 0.85rem 1rem !important;
                margin-bottom: 0.75rem !important;
                border-radius: 16px !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 0.6rem !important;
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
            }
            .page-header-card h4, .page-header-card h5, .page-header-card .page-title {
                font-size: 1.1rem !important;
                font-weight: 900 !important;
                margin-bottom: 0 !important;
                color: #0f172a !important;
            }
            .page-header-card .btn, .page-header-card a.btn {
                min-height: 44px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.92rem !important;
                font-weight: 800 !important;
                padding: 0.6rem 1rem !important;
                border-radius: 12px !important;
            }

            /* Tables: Smooth Touch Scroll */
            .table-responsive {
                border-radius: 14px !important;
                -webkit-overflow-scrolling: touch;
                border: 1.5px solid #e2e8f0 !important;
                background: #ffffff;
                margin-bottom: 0.75rem;
            }
            .table-clean th {
                font-size: 0.82rem !important;
                padding: 0.75rem 0.8rem !important;
                white-space: nowrap;
            }
            .table-clean td {
                font-size: 0.86rem !important;
                padding: 0.75rem 0.8rem !important;
            }

            /* Inputs on Mobile */
            input[type="number"],
            input[type="tel"],
            input[inputmode="numeric"],
            input[inputmode="decimal"] {
                font-size: 1.35rem !important;
                font-weight: 900 !important;
                min-height: 52px !important;
                padding: 10px 14px !important;
            }
            .form-control, .form-select {
                font-size: 0.95rem !important;
                min-height: 46px !important;
                border-radius: 10px !important;
            }
            label.form-label {
                font-size: 0.9rem !important;
                font-weight: 800 !important;
                margin-bottom: 0.3rem !important;
                color: #1e293b !important;
            }

            /* Modals on Mobile */
            .modal-dialog {
                margin: 0.5rem !important;
                max-width: calc(100vw - 1rem) !important;
            }
            .modal-content {
                border-radius: 18px !important;
                border: none !important;
            }
            .modal-body {
                padding: 1rem !important;
                max-height: 70vh !important;
                overflow-y: auto !important;
            }
            .modal-footer {
                padding: 0.75rem 1rem !important;
                display: flex !important;
                gap: 0.5rem !important;
            }
            .modal-footer .btn {
                flex: 1 !important;
                min-height: 46px !important;
            }

            /* Bottom Nav (Ultra-Sleek & Accessible) */
            .bottom-nav {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: calc(60px + env(safe-area-inset-bottom, 0px)) !important;
                padding-bottom: env(safe-area-inset-bottom, 0px) !important;
                background: rgba(255, 255, 255, 0.98) !important;
                backdrop-filter: blur(16px) !important;
                -webkit-backdrop-filter: blur(16px) !important;
                border-top: 1px solid #e2e8f0 !important;
                box-shadow: 0 -4px 20px rgba(0,0,0,0.06) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-around !important;
                z-index: 1040 !important;
            }
            .bottom-nav .nav-link {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.72rem !important;
                font-weight: 800 !important;
                color: #64748b !important;
                padding: 4px 6px !important;
                flex: 1 !important;
                text-align: center !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
            }
            .bottom-nav .nav-link i {
                font-size: 1.3rem !important;
                margin-bottom: 2px !important;
            }
            .bottom-nav .nav-link.active {
                color: #ea580c !important;
            }
            .bottom-nav .nav-link.active i {
                transform: scale(1.15) !important;
                color: #ea580c !important;
            }

            /* Top Header on Mobile */
            .top-header {
                padding: 0.4rem 0.6rem !important;
                margin-bottom: 0.6rem !important;
                border-radius: 14px !important;
            }
            .top-header h5.page-heading {
                font-size: 0.95rem !important;
                font-weight: 800 !important;
            }
            .breadcrumb {
                display: none !important;
            }
        }
    </style>
    {{-- Loaded last deliberately: this is the single visual system shared by every screen. --}}
    <link rel="stylesheet" href="{{ asset('css/profactory-ui.css') }}">
</head>
<body x-data="appShell()" @keydown.window="handleKeydown($event)">

    <!-- Sidebar -->
    <nav class="sidebar glass-panel" id="sidebar" :class="{ 'show': sidebarOpen }">
        <div class="sidebar-brand p-3 text-center border-bottom mb-3">
            <a href="{{ url('/') }}" class="text-decoration-none">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <div class="brand-icon p-0 border-0 bg-transparent">
                        <div class="logo-circle shadow-sm">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" width="48" height="48">
                        </div>
                    </div>
                    <div class="text-start">
                        <h5 class="mb-0 fw-bold text-dark brand-title">{{ $globalSettings['company_name'] ?? 'مصنع المنتجات' }}</h5>
                        <small class="text-muted">نظام إدارة المصنع</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- Sidebar Search -->
        <div class="px-3 mb-3">
            <button type="button" class="sidebar-search-btn w-100" @click="openSearch()">
                <i class="bi bi-search"></i>
                <span>بحث سريع...</span>
                <kbd>Ctrl+K</kbd>
            </button>
        </div>

        <div class="sidebar-scroll">
            <ul class="nav flex-column gap-1 w-100 px-2">
                @hasrole('Admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2-fill"></i> لوحة التحكم
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('audit.index') ? 'active' : '' }}" href="{{ route('audit.index') }}">
                        <i class="bi bi-shield-check text-warning"></i> المراجعة الصارمة
                    </a>
                </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('debts.*') ? 'active' : '' }}" href="{{ route('debts.index') }}">
                            <i class="bi bi-journal-bookmark me-2"></i> الديون الخارجية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workshops.*') ? 'active' : '' }}" href="{{ route('workshops.index') }}">
                            <i class="bi bi-tools me-2 text-warning"></i> الورش الخارجية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workers.*', 'attendances.*', 'salaries.*', 'advances.*') ? 'active' : '' }}" href="#hrSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('workers.*', 'attendances.*', 'salaries.*', 'advances.*') ? 'true' : 'false' }}" aria-controls="hrSubmenu">
                            <i class="bi bi-people me-2"></i> الموارد البشرية
                        </a>
                    </li>
                @endhasrole
                
                @hasanyrole('Supervisor|HR')

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('salaries.index*') ? 'active' : '' }}" href="{{ route('salaries.index') }}">
                        <i class="bi bi-calendar3"></i> جدول التحضير التفاعلي
                    </a>
                </li>
                @endhasanyrole


                @hasrole('Admin')
                <li class="nav-section">إدارة المصنع</li>
                <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"><i class="bi bi-box-seam-fill"></i> المنتجات</a></li>
                <li><a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}"><i class="bi bi-archive-fill"></i> مخزون المنتجات</a></li>
                <li><a href="{{ route('raw-materials.index') }}" class="nav-link {{ request()->routeIs('raw-materials.*') ? 'active' : '' }}"><i class="bi bi-tools"></i> المواد الخام</a></li>
                <li><a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}"><i class="bi bi-gear-fill"></i> الطلبيات @if($pendingOrders > 0)<span class="nav-badge">{{ $pendingOrders }}</span>@endif</a></li>

                <li class="nav-section">الموردين والمشتريات</li>
                <li><a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><i class="bi bi-truck"></i> الموردين</a></li>
                <li><a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}"><i class="bi bi-cart-plus-fill"></i> مشتريات الخام</a></li>


                <li class="nav-section">المبيعات والمصروفات</li>
                <li><a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i> العملاء</a></li>
                <li><a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}"><i class="bi bi-receipt-cutoff"></i> فواتير المبيعات</a></li>
                <li><a href="{{ route('loading.index') }}" class="nav-link {{ request()->routeIs('loading.*') ? 'active' : '' }}"><i class="bi bi-truck-flatbed"></i> نظام التحميل والتسليم</a></li>
                
                <li class="nav-section">المالية والخزينة</li>
                <li><a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') || request()->routeIs('treasury.*') ? 'active' : '' }}"><i class="bi bi-wallet2"></i> المالية والخزينة الشاملة</a></li>


                <li class="nav-section">شؤون الموظفين والإنتاج</li>
                <li><a href="{{ route('workers.index') }}" class="nav-link {{ request()->routeIs('workers.*') ? 'active' : '' }}"><i class="bi bi-person-badge-fill"></i> الموظفين</a></li>
                <li><a href="{{ route('worker-prices.index') }}" class="nav-link {{ request()->routeIs('worker-prices.*') ? 'active' : '' }}"><i class="bi bi-tags-fill"></i> أسعار المصنعيات</a></li>
                <li><a href="{{ route('salaries.index') }}" class="nav-link {{ request()->routeIs('salaries.*') ? 'active' : '' }}"><i class="bi bi-calendar-check-fill"></i> دفتر التحضير والرواتب</a></li>
                <li><a href="{{ route('production.daily') }}" class="nav-link {{ request()->routeIs('production.daily') ? 'active' : '' }}"><i class="bi bi-display"></i> شاشة المصنع (اليوم)</a></li>

                <li class="nav-section">النظام</li>
                <li><a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-graph-up"></i> التقارير</a></li>
                <li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> المستخدمين والصلاحيات</a></li>
                <li><a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"><i class="bi bi-sliders"></i> الإعدادات</a></li>
                <li><a href="{{ route('system.logs') }}" class="nav-link {{ request()->routeIs('system.*') ? 'active' : '' }}"><i class="bi bi-shield-check"></i> مكتشف الأخطاء</a></li>
                @endhasrole
                <li class="nav-section">التواصل والمراسلة</li>
                <li>
                    <a href="{{ route('chat.index') }}" class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }} d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-whatsapp text-success fs-5 me-2"></i> شات الإدارة (واتساب)</span>
                        <span id="sidebarChatBadge" class="badge bg-success rounded-pill d-none">0</span>
                    </a>
                </li>

                <li class="nav-section">الحساب الشخصي</li>
                <li>
                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person-circle"></i> الملف الشخصي</a>
                </li>
                <li class="mt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link text-danger fw-bold w-100 border-0 bg-transparent text-start">
                            <i class="bi bi-box-arrow-right"></i> تسجيل خروج
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak></div>

    <!-- Main Content -->
    <main class="main-content fade-in-up" id="main-content">
        <!-- Top Header -->
        <div class="top-header glass-card mb-4">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
                <!-- Global Back Button -->
                <button onclick="window.history.back()" class="btn btn-glass-secondary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; min-width: 42px;" title="الرجوع للخلف">
                    <i class="bi bi-arrow-right-short fs-3 text-dark"></i>
                </button>
                
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-glass-secondary d-lg-none" @click="sidebarOpen = !sidebarOpen">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="logo-circle shadow-sm d-lg-none">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" width="40" height="40">
                    </div>
                </div>
                <div>
                    @hasSection('page_title')
                        <nav aria-label="breadcrumb" class="mb-1">
                            <ol class="breadcrumb mb-0 small">
                                <li class="breadcrumb-item"><a href="{{ url('/') }}">الرئيسية</a></li>
                                <li class="breadcrumb-item active">@yield('page_title')</li>
                            </ol>
                        </nav>
                    @endif
                    <h5 class="mb-0 text-dark fw-bold page-heading" style="font-size:1.05rem;line-height:1.3;">@yield('page_title', 'لوحة الإدارة')</h5>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- WhatsApp Chat Header Button -->
                <a href="{{ route('chat.index') }}" class="header-icon-btn position-relative text-decoration-none" title="شات الإدارة الفوري (واتساب)">
                    <i class="bi bi-whatsapp text-success fs-5"></i>
                    <span id="headerChatBadge" class="notification-dot bg-success d-none">0</span>
                </a>

                <button type="button" class="header-icon-btn" onclick="toggleTheme()" title="تغيير المظهر">
                    <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                </button>
                <button type="button" class="header-icon-btn d-none d-md-flex" @click="openSearch()" title="بحث (Ctrl+K)">
                    <i class="bi bi-search"></i>
                </button>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="header-icon-btn position-relative" id="notifDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" type="button">
                        <i class="bi bi-bell-fill"></i>
                        @if($alertCount > 0)
                            <span class="notification-dot">{{ $alertCount > 9 ? '9+' : $alertCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-panel p-0" aria-labelledby="notifDropdownBtn">
                        <div class="notification-header p-3 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold">التنبيهات</h6>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" id="enableNotifBtn" class="btn btn-sm btn-outline-orange py-0 px-2 small fw-bold" style="font-size:0.75rem;" onclick="requestNotificationPermission()">
                                    🔔 تفعيل الإشعارات
                                </button>
                                @if($alertCount > 0)<span class="badge bg-danger rounded-pill">{{ $alertCount }}</span>@endif
                            </div>
                        </div>
                        <div class="notification-body">
                            @if(isset($systemAlerts) && $systemAlerts->count() > 0)
                                @foreach($systemAlerts as $alert)
                                    <div class="notification-item p-2 mb-1 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                                        <i class="bi bi-bell-fill text-{{ $alert->type }}"></i>
                                        <div class="flex-grow-1">
                                            <strong class="text-dark">{{ $alert->title }}</strong>
                                            <div class="small text-secondary">{{ $alert->message }}</div>
                                        </div>
                                        <form action="{{ route('alerts.read', $alert->id) }}" method="POST" class="ms-2">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light rounded-circle" title="تحديد كمقروء"><i class="bi bi-check2"></i></button>
                                        </form>
                                    </div>
                                @endforeach
                            @endif
                            @if($overdueInstallments > 0)
                                <a href="{{ route('debts.index') }}" class="notification-item">
                                    <i class="bi bi-calendar-x-fill text-danger"></i>
                                    <div><strong>{{ $overdueInstallments }} قسط</strong> متأخر عن السداد</div>
                                </a>
                            @endif
                            @if($lowStockMaterials > 0)
                                <a href="{{ route('raw-materials.index') }}" class="notification-item">
                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                    <div><strong>{{ $lowStockMaterials }} خامة</strong> تحت الحد الأدنى</div>
                                </a>
                            @endif
                            @if($lowStockProducts > 0)
                                <a href="{{ route('inventory.index') }}" class="notification-item">
                                    <i class="bi bi-box-seam-fill text-warning"></i>
                                    <div><strong>{{ $lowStockProducts }} منتج</strong> ناقص من المخزن</div>
                                </a>
                            @endif
                            @if($overdueInvoices > 0)
                                <a href="{{ route('invoices.index') }}" class="notification-item">
                                    <i class="bi bi-receipt text-primary"></i>
                                    <div><strong>{{ $overdueInvoices }} فاتورة</strong> متأخرة السداد</div>
                                </a>
                            @endif
                            @if($pendingOrders > 0)
                                <a href="{{ route('orders.index') }}" class="notification-item">
                                    <i class="bi bi-gear text-info"></i>
                                    <div><strong>{{ $pendingOrders }} طلبية</strong> قيد التنفيذ</div>
                                </a>
                            @endif
                            @if($absentToday > 0)
                                <a href="{{ route('attendance.index') }}" class="notification-item">
                                    <i class="bi bi-person-x text-danger"></i>
                                    <div><strong>{{ $absentToday }} موظف</strong> غائب اليوم</div>
                                </a>
                            @endif
                            @if($alertCount === 0)
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-shield-check fs-2 text-success d-block mb-2"></i>
                                    لا توجد تنبيهات
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <a href="{{ route('profile.edit') }}" class="user-chip text-decoration-none">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'Admin') }}&background=ea580c&color=fff" class="rounded-circle" width="40" height="40" alt="">
                    <div class="d-none d-md-block">
                        <h6 class="mb-0 fw-bold text-dark">{{ Auth::user()->name ?? 'مدير النظام' }}</h6>
                        <small class="text-muted">المدير العام</small>
                    </div>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success glass-panel border-success mb-4 d-flex align-items-center alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                <div class="fw-bold text-success">{{ session('success') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger glass-panel border-danger mb-4 d-flex align-items-center alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div class="fw-bold text-danger">{{ session('error') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Global Search Modal -->
    <div class="search-modal" x-show="searchOpen" x-cloak @keydown.escape.window="searchOpen = false">
        <div class="search-modal-backdrop" @click="searchOpen = false"></div>
        <div class="search-modal-content glass-panel" @click.stop>
            <div class="search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" x-model="searchQuery" x-ref="searchInput" @input.debounce.300ms="doSearch()" placeholder="ابحث عن منتج، عميل، طلبية، فاتورة، موظف..." autocomplete="off">
                <kbd>ESC</kbd>
            </div>
            <div class="search-results">
                <template x-if="searchLoading">
                    <div class="text-center py-4 text-muted"><i class="bi bi-hourglass-split me-2"></i>جاري البحث...</div>
                </template>
                <template x-if="!searchLoading && searchQuery.length >= 2 && searchResults.length === 0">
                    <div class="text-center py-4 text-muted">لا توجد نتائج لـ "<span x-text="searchQuery"></span>"</div>
                </template>
                <template x-if="!searchLoading && searchQuery.length < 2">
                    <div class="search-hints py-3">
                        <div class="search-hint-title">اختصارات سريعة</div>
                        <div class="search-shortcuts">
                            <a href="{{ route('salaries.index') }}" class="search-shortcut"><i class="bi bi-calendar-check"></i> تحضير ورواتب</a>
                            <a href="{{ route('orders.index') }}" class="search-shortcut"><i class="bi bi-gear"></i> طلبيات</a>
                            <a href="{{ route('invoices.create') }}" class="search-shortcut"><i class="bi bi-receipt"></i> فاتورة جديدة</a>
                            <a href="{{ route('purchases.index') }}" class="search-shortcut"><i class="bi bi-cart-plus"></i> مشتريات</a>
                        </div>
                    </div>
                </template>
                <template x-for="(item, i) in searchResults" :key="i">
                    <a :href="item.url" class="search-result-item">
                        <div class="search-result-icon"><i class="bi" :class="item.icon"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" x-text="item.title"></div>
                            <small class="text-muted" x-text="item.subtitle"></small>
                        </div>
                        <span class="search-result-type" x-text="item.type"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    @hasrole('Admin')
    <nav class="bottom-nav" id="bottomNav">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i><span>الرئيسية</span>
        </a>
        <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
            <i class="bi bi-cart-check-fill"></i><span>الطلبيات</span>
        </a>
        <a href="{{ route('salaries.index') }}" class="nav-link {{ request()->routeIs('salaries.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check-fill"></i><span>الرواتب</span>
        </a>
        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') || request()->routeIs('treasury.*') ? 'active' : '' }}">
            <i class="bi bi-wallet-fill"></i><span>الخزينة</span>
        </a>
        <button type="button" class="nav-link border-0 bg-transparent" @click="sidebarOpen = true">
            <i class="bi bi-list"></i><span>القائمة</span>
        </button>
    </nav>
    @endhasrole
    
    {{-- PWA Install Floating Button for Mobile (Hidden by default, shown via JS if installable) --}}
    <div id="pwaInstallMobileBtn" style="display: none; position: fixed; bottom: 85px; right: 15px; z-index: 1040;">
        <button onclick="installPWA()" class="btn btn-warning rounded-pill shadow-lg fw-bold px-4 py-2" style="border: 2px solid #ea580c;">
            <i class="bi bi-download me-2 text-dark"></i>تثبيت التطبيق على الهاتف
        </button>
    </div>

    
    @hasanyrole('Supervisor|HR')
    <nav class="bottom-nav">
        <a href="{{ route('salaries.index') }}" class="nav-link {{ request()->routeIs('salaries.index*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i><span>جدول التحضير</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i><span>حسابي</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="flex: 1; display: flex;">
            @csrf
            <button type="submit" class="nav-link text-danger fw-bold border-0 bg-transparent w-100">
                <i class="bi bi-box-arrow-right"></i><span>خروج</span>
            </button>
        </form>
    </nav>
    @endhasanyrole

    @hasrole('Driver')
    <nav class="bottom-nav">
        <form method="POST" action="{{ route('logout') }}" style="flex: 1; display: flex;">
            @csrf
            <button type="submit" class="nav-link text-danger fw-bold border-0 bg-transparent w-100">
                <i class="bi bi-box-arrow-right"></i><span>تسجيل خروج</span>
            </button>
        </form>
    </nav>
    @endhasrole

    <script src="{{ asset('libs/js/bootstrap.bundle.min.js') }}" defer></script>
    @if(request()->is('/') || request()->is('dashboard') || request()->is('reports*'))
    <script src="{{ asset('libs/js/chart.min.js') }}" defer></script>
    @endif
    <script defer src="{{ asset('libs/js/alpine.min.js') }}"></script>
    
    <script>
        function appShell() {
            return {
                sidebarOpen: false,
                searchOpen: false,
                searchQuery: '',
                searchResults: [],
                searchLoading: false,

                openSearch() {
                    this.searchOpen = true;
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                },

                handleKeydown(e) {
                    // Global Search: Ctrl+K or Cmd+K
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                        e.preventDefault();
                        this.openSearch();
                    }

                    // Alt Shortcuts for quick navigation and actions
                    if (e.altKey) {
                        switch(e.key.toLowerCase()) {
                            case 'h': // Home
                                e.preventDefault();
                                window.location.href = "{{ route('dashboard') }}";
                                break;
                            case 'i': // New Invoice
                                e.preventDefault();
                                window.location.href = "{{ route('invoices.create') }}";
                                break;
                            case 'o': // Orders
                                e.preventDefault();
                                window.location.href = "{{ route('orders.index') }}";
                                break;
                            case 'w': // Workers
                                e.preventDefault();
                                window.location.href = "{{ route('workers.index') }}";
                                break;
                            case 'e': // Treasury/Expenses
                                e.preventDefault();
                                window.location.href = "{{ route('expenses.index') }}";
                                break;
                            case 'r': // Reports
                                e.preventDefault();
                                window.location.href = "{{ route('reports.index') }}";
                                break;
                            case 's': // Save/Submit Form
                                e.preventDefault();
                                const submitBtn = document.querySelector('button[type="submit"]');
                                if(submitBtn && !submitBtn.disabled) submitBtn.click();
                                break;
                            case 'a': // Add Item (Requires a button with specific class)
                                e.preventDefault();
                                const addBtn = document.querySelector('.shortcut-add');
                                if(addBtn) addBtn.click();
                                break;
                        }
                    }
                    
                    // Ctrl+S to save form
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        const submitBtn = document.querySelector('button[type="submit"]');
                        if(submitBtn && !submitBtn.disabled) submitBtn.click();
                    }
                },

                async doSearch() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    this.searchLoading = true;
                    try {
                        const res = await fetch(`{{ route('search') }}?q=${encodeURIComponent(this.searchQuery)}`);
                        const data = await res.json();
                        this.searchResults = data.results || [];
                    } catch (e) {
                        this.searchResults = [];
                    }
                    this.searchLoading = false;
                }
            };
        }

        // Global Double Submit Prevention - Auto-reset after 8s
        function resetForm(form) {
            if (!form) return;
            delete form.dataset.submitted;
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = false;
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                    delete btn.dataset.originalHtml;
                }
            }
        }

        document.addEventListener('submit', function (e) {
            if (e.target.tagName !== 'FORM') return;
            if (e.defaultPrevented) return;
            if (e.target.method && e.target.method.toUpperCase() === 'GET') return;

            // If already submitted and still pending, block
            if (e.target.dataset.submitted === 'true') {
                e.preventDefault();
                return false;
            }

            e.target.dataset.submitted = 'true';

            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                setTimeout(() => {
                    submitBtn.disabled = true;
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.dataset.originalHtml = originalHtml;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري المعالجة...';
                }, 50);
            }

            // Auto-reset after 8 seconds in case of network error or stuck
            setTimeout(() => resetForm(e.target), 8000);
        });

        // Reset ALL modal forms when any modal closes
        document.addEventListener('hidden.bs.modal', function (e) {
            const forms = e.target.querySelectorAll('form');
            forms.forEach(f => resetForm(f));
        });
    </script>
    
    <script>
        // Hide Bottom Nav on Scroll Down
        let lastScrollTop = 0;
        const bottomNav = document.getElementById('bottomNav');
        
        window.addEventListener('scroll', function() {
            if(!bottomNav) return;
            let st = window.pageYOffset || document.documentElement.scrollTop;
            if (st > lastScrollTop && st > 50) {
                // Downscroll
                bottomNav.classList.add('nav-hidden');
            } else {
                // Upscroll
                bottomNav.classList.remove('nav-hidden');
            }
            lastScrollTop = st <= 0 ? 0 : st;
        }, { passive: true });

        // ===== PWA Install Prompt =====
        let deferredPrompt;
        const installBtn = document.getElementById('pwaInstallBtn');
        const installBanner = document.getElementById('pwaInstallBanner');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installBanner) installBanner.style.display = 'flex';
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    if (installBanner) installBanner.style.display = 'none';
                }
                deferredPrompt = null;
            });
        }

        window.addEventListener('appinstalled', () => {
            if (installBanner) installBanner.style.display = 'none';
            deferredPrompt = null;
        });

        // Check if already installed (standalone mode)
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
            if (installBanner) installBanner.style.display = 'none';
        }

        // ===== Service Worker =====
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('SW registered:', registration.scope);
                }).catch(function(err) {
                    console.log('SW registration failed:', err);
                });
            });
        }
    </script>
    <!-- TomSelect JS -->
    <script src="{{ asset('libs/js/tom-select.min.js') }}" defer></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function initTomSelect(el) {
                if (el.tomselect || el.classList.contains('no-search')) return;
                // Only init if it has more than 3 options, or if it explicitly has 'searchable' class
                if (el.options.length < 4 && !el.classList.contains('searchable')) return;
                
                new TomSelect(el, {
                    create: false,
                    dropdownParent: 'body',
                    sortField: { field: "text", direction: "asc" },
                    render: {
                        no_results: function(data, escape) {
                            return '<div class="no-results p-2 text-muted">لا توجد نتائج مطابقة</div>';
                        }
                    }
                });
                // Fix alpine.js x-model sync if present
                el.addEventListener('change', function() {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }

            // Init existing
            document.querySelectorAll('select:not(.no-search)').forEach(initTomSelect);

            // Watch for dynamically added selects (Alpine, Modals, etc.)
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            if (node.tagName === 'SELECT') initTomSelect(node);
                            const selects = node.querySelectorAll ? node.querySelectorAll('select:not(.no-search)') : [];
                            selects.forEach(initTomSelect);
                        }
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>

    {{-- ═══ حفظ واستعادة موضع الشاشة والتبويبات (Tabs/Accordions) ═══ --}}
    <script>
        (function () {
            const PATH_KEY = window.location.pathname;
            const SCROLL_KEY = 'page_scroll_' + PATH_KEY;
            const TAB_KEY = 'active_tab_' + PATH_KEY;
            const ACCORDION_KEY = 'open_accordions_' + PATH_KEY;

            // 1. Save scroll position on form submit
            document.addEventListener('submit', function () {
                sessionStorage.setItem(SCROLL_KEY, window.scrollY);
            }, true);

            // 2. Save Active Tab
            document.addEventListener('shown.bs.tab', function (e) {
                if (e.target && e.target.id) {
                    sessionStorage.setItem(TAB_KEY, '#' + e.target.id);
                } else if (e.target && e.target.dataset.bsTarget) {
                    sessionStorage.setItem(TAB_KEY, e.target.dataset.bsTarget);
                } else if (e.target && e.target.getAttribute('href') && e.target.getAttribute('href').startsWith('#')) {
                    sessionStorage.setItem(TAB_KEY, e.target.getAttribute('href'));
                }
            });

            // 3. Save Accordion State
            function saveAccordions() {
                const openItems = Array.from(document.querySelectorAll('.accordion-collapse.show'))
                                       .map(el => el.id)
                                       .filter(id => id);
                sessionStorage.setItem(ACCORDION_KEY, JSON.stringify(openItems));
            }
            document.addEventListener('shown.bs.collapse', saveAccordions);
            document.addEventListener('hidden.bs.collapse', saveAccordions);

            // 4. Restore everything on load
            window.addEventListener('DOMContentLoaded', function () {
                // Restore Tab
                const savedTab = sessionStorage.getItem(TAB_KEY);
                if (savedTab) {
                    const tabElement = document.querySelector(`[data-bs-target="${savedTab}"]`) || 
                                       document.querySelector(`[href="${savedTab}"]`) || 
                                       document.querySelector(savedTab);
                    if (tabElement && typeof bootstrap !== 'undefined') {
                        try {
                            const tabInstance = new bootstrap.Tab(tabElement);
                            tabInstance.show();
                        } catch (e) {
                            tabElement.click();
                        }
                    }
                }

                // Restore Accordions
                const savedAccordions = JSON.parse(sessionStorage.getItem(ACCORDION_KEY) || '[]');
                savedAccordions.forEach(id => {
                    const el = document.getElementById(id);
                    if (el && !el.classList.contains('show')) {
                        el.classList.add('show');
                        const btn = document.querySelector(`[data-bs-target="#${id}"]`) || document.querySelector(`[aria-controls="${id}"]`);
                        if (btn) {
                            btn.classList.remove('collapsed');
                            btn.setAttribute('aria-expanded', 'true');
                        }
                    }
                });

                // Restore Scroll (always try to restore if it exists for this path)
                const savedY = sessionStorage.getItem(SCROLL_KEY);
                if (savedY !== null) {
                    requestAnimationFrame(function () {
                        setTimeout(function () {
                            window.scrollTo({ top: parseInt(savedY), behavior: 'instant' });
                            sessionStorage.removeItem(SCROLL_KEY);
                        }, 100); // Slight delay for DOM rendering
                    });
                }
            });
        })();

        // ════ Live Chat Unread Badge Poller ════
        function updateChatUnreadBadges() {
            @auth
            fetch('{{ route('chat.unread-count') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const count = data.unread_count || 0;
                const headerBadge = document.getElementById('headerChatBadge');
                const sidebarBadge = document.getElementById('sidebarChatBadge');

                if (headerBadge) {
                    if (count > 0) {
                        headerBadge.textContent = count > 9 ? '9+' : count;
                        headerBadge.classList.remove('d-none');
                    } else {
                        headerBadge.classList.add('d-none');
                    }
                }
                if (sidebarBadge) {
                    if (count > 0) {
                        sidebarBadge.textContent = count;
                        sidebarBadge.classList.remove('d-none');
                    } else {
                        sidebarBadge.classList.add('d-none');
                    }
                }
            })
            .catch(() => {});
            @endauth
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateChatUnreadBadges();
            setInterval(updateChatUnreadBadges, 12000);
        });
    </script>

    {{-- ═══ PWA Notification & Push Manager ═══ --}}
    <script>
        function requestNotificationPermission() {
            if (!('Notification' in window)) {
                alert('عذراً، هذا المتصفح لا يدعم نظام إشعارات الويب.');
                return;
            }
            if (Notification.permission === 'granted') {
                showAppNotification('الإشعارات مفعلة بالفعل! 🔔', 'تطبيق مصنع سالم علي جاهز لإرسال كافة التنبيهات الفورية.');
                const btn = document.getElementById('enableNotifBtn');
                if (btn) btn.innerHTML = '✅ الإشعارات مفعلة';
                return;
            }
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    showAppNotification('تم تفعيل التنبيهات بنجاح! 🎉', 'ستتلقى الآن إشعارات الفواتير، الشات الفوري، وأرصدة المخزون.');
                    const btn = document.getElementById('enableNotifBtn');
                    if (btn) btn.innerHTML = '✅ الإشعارات مفعلة';
                } else if (permission === 'denied') {
                    alert('تم رفض الإشعارات. يرجى تفعيلها من إعدادات المتصفح/الهاتف لتلقي التنبيهات.');
                }
            });
        }

        function showAppNotification(title, body, url = '/dashboard') {
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                        navigator.serviceWorker.controller.postMessage({
                            type: 'SHOW_NOTIFICATION',
                            title: title,
                            body: body,
                            url: url
                        });
                    } else {
                        new Notification(title, {
                            body: body,
                            icon: '/icons/icon-192.png',
                            badge: '/icons/icon-96.png'
                        });
                    }
                } catch(e) {
                    console.log('Notification trigger error:', e);
                }
            }
        }
    </script>

    <!-- Global Instant Feedback -->
    <style>
        html { background-color: #f8fafc !important; } /* Prevent black flash on iOS */
        .btn, .btn-action { transition: transform 0.1s ease, filter 0.1s; }
        .btn:active, .btn-action:active { transform: scale(0.96) !important; filter: brightness(0.9); }
        /* Make Turbo progress bar visible and orange */
        .turbo-progress-bar {
            height: 4px;
            background-color: var(--primary);
            box-shadow: 0 0 10px rgba(234, 88, 12, 0.7);
        }
    </style>

    @stack('scripts')

</body>
</html>

