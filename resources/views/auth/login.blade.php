<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#0d1b2a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>تسجيل الدخول - مصنع سالم علي</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/libs/css/bootstrap-icons.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #ea580c;
            --primary-dark: #c2410c;
            --primary-glow: rgba(234, 88, 12, 0.5);
            --gold: #d4a84b;
            --navy: #0d1b2a;
            --navy-mid: #1a2d42;
            --navy-light: #243447;
            --glass: rgba(13, 27, 42, 0.75);
            --text-white: #f0f4f8;
            --text-muted-light: rgba(240, 244, 248, 0.6);
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Cairo', sans-serif;
        }

        /* ═══ Full-Screen Mural Background ═══ */
        .login-scene {
            position: fixed;
            inset: 0;
            background: url('/images/login_bg.jpg') center center / cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dark overlay for readability */
        .login-scene::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(13,27,42,0.88) 0%, rgba(13,27,42,0.65) 100%);
            z-index: 0;
        }

        /* ═══ Floating geometric decorators ═══ */
        .geo-decor {
            position: absolute;
            pointer-events: none;
            z-index: 0;
        }
        .geo-decor-1 {
            top: -60px; left: -60px;
            width: 220px; height: 220px;
            border: 2px solid rgba(212, 168, 75, 0.15);
            border-radius: 50%;
            animation: rotateSlowCW 30s linear infinite;
        }
        .geo-decor-1::after {
            content:'';
            position: absolute;
            inset: 20px;
            border: 1px solid rgba(234,88,12,0.12);
            border-radius: 50%;
        }
        .geo-decor-2 {
            bottom: -40px; right: -40px;
            width: 180px; height: 180px;
            border: 1.5px solid rgba(234,88,12,0.18);
            transform: rotate(45deg);
            animation: rotateSlowCCW 25s linear infinite;
        }
        .geo-decor-3 {
            top: 50%; left: 50%;
            transform: translate(-50%,-50%) rotate(0deg);
            width: 350px; height: 350px;
            border: 1px solid rgba(212,168,75,0.05);
            border-radius: 50%;
            animation: rotateSlowCW 60s linear infinite;
            display: none;
        }
        @keyframes rotateSlowCW  { to { transform: rotate(360deg); } }
        @keyframes rotateSlowCCW { to { transform: rotate(-360deg); } }

        /* ═══ Login Card ═══ */
        .login-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
            margin: 16px;
            background: var(--glass);
            backdrop-filter: blur(28px) saturate(1.4);
            -webkit-backdrop-filter: blur(28px) saturate(1.4);
            border: 1px solid rgba(212, 168, 75, 0.2);
            border-radius: 28px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 30px 80px rgba(0,0,0,0.6),
                0 0 60px rgba(234,88,12,0.06);
            animation: cardIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0)   scale(1); }
        }

        /* Card Header */
        .card-header-area {
            background: linear-gradient(135deg, rgba(234,88,12,0.25) 0%, rgba(13,27,42,0.1) 100%);
            border-bottom: 1px solid rgba(212,168,75,0.15);
            padding: 28px 28px 20px;
            text-align: center;
            position: relative;
        }
        .card-header-area::before {
            content: '';
            position: absolute;
            bottom: 0; left: 20%; right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .brand-icon-wrap {
            width: 68px; height: 68px;
            margin: 0 auto 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 30px var(--primary-glow), 0 0 0 1px rgba(255,255,255,0.1) inset;
            position: relative;
        }
        .brand-icon-wrap::after {
            content: '';
            position: absolute;
            inset: -4px;
            border: 1px solid rgba(234,88,12,0.3);
            border-radius: 24px;
        }
        .brand-icon-wrap i { font-size: 2rem; color: #fff; }

        .brand-name {
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--text-white);
            letter-spacing: -0.3px;
            margin-bottom: 2px;
        }
        .brand-sub {
            font-size: 0.8rem;
            color: var(--text-muted-light);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Card Body */
        .card-body-area {
            padding: 24px 28px 28px;
        }

        /* Gold separator line */
        .gold-line {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), var(--primary), var(--gold), transparent);
            border-radius: 2px;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        /* Form inputs */
        .input-field-wrap {
            position: relative;
            margin-bottom: 14px;
        }
        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(212,168,75,0.7);
            font-size: 1.1rem;
            pointer-events: none;
            transition: color 0.3s;
            z-index: 2;
        }
        .input-field {
            width: 100%;
            padding: 13px 42px 13px 14px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(212,168,75,0.2);
            border-radius: 14px;
            color: var(--text-white);
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s;
            -webkit-appearance: none;
        }
        .input-field::placeholder { color: rgba(240,244,248,0.35); font-weight: 600; }
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(234,88,12,0.08);
            box-shadow: 0 0 0 3px rgba(234,88,12,0.15);
            color: #fff;
        }
        .input-field:focus + .input-icon { color: var(--primary); }
        .input-field:-webkit-autofill,
        .input-field:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 100px rgba(13,27,42,0.9) inset !important;
            -webkit-text-fill-color: #fff !important;
            caret-color: #fff;
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }
        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .remember-row label {
            color: var(--text-muted-light);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        /* Login button */
        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 24px rgba(234,88,12,0.4);
            position: relative;
            overflow: hidden;
            -webkit-appearance: none;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.4s;
        }
        .btn-login:hover::before { left: 100%; }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 32px rgba(234,88,12,0.55);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        /* Error */
        .alert-error {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success-msg {
            background: rgba(22,163,74,0.12);
            border: 1px solid rgba(22,163,74,0.3);
            color: #86efac;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }

        /* Footer */
        .card-footer-area {
            text-align: center;
            padding: 12px 28px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            color: rgba(240,244,248,0.3);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .secure-badge {
            display: inline-flex; align-items: center; gap: 5px;
            color: rgba(212,168,75,0.5);
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* Shimmer loading pulse */
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        .btn-login.loading {
            background: linear-gradient(90deg, #c2410c 0%, #ea580c 40%, #f97316 50%, #ea580c 60%, #c2410c 100%);
            background-size: 200% auto;
            animation: shimmer 1.5s linear infinite;
        }

        /* iOS safe area padding */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .login-card {
                padding-bottom: env(safe-area-inset-bottom);
            }
        }

        /* Very small screens */
        @media (max-height: 680px) {
            .card-header-area { padding: 18px 24px 14px; }
            .brand-icon-wrap { width: 54px; height: 54px; margin-bottom: 10px; }
            .brand-icon-wrap i { font-size: 1.6rem; }
            .brand-name { font-size: 1.1rem; }
            .card-body-area { padding: 16px 24px 20px; }
            .input-field { padding: 11px 40px 11px 12px; }
            .btn-login { padding: 12px; }
        }
    </style>
</head>
<body>
<div class="login-scene">
    <!-- Geometric decorators -->
    <div class="geo-decor geo-decor-1"></div>
    <div class="geo-decor geo-decor-2"></div>

    <div class="login-card">
        <!-- Header -->
        <div class="card-header-area">
            <div class="brand-icon-wrap">
                <i class="bi bi-buildings-fill"></i>
            </div>
            <div class="brand-name">مصنع سالم علي</div>
            <div class="brand-sub">نظام إدارة المصنع الذكي</div>
        </div>

        <!-- Body -->
        <div class="card-body-area">
            <div class="gold-line"></div>

            @if(session('status'))
                <div class="alert-success-msg">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-error">
                    <i class="bi bi-shield-exclamation"></i>
                    اسم المستخدم أو كلمة المرور غير صحيحة
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" autocomplete="off">
                @csrf

                <div class="input-field-wrap">
                    <input
                        id="name"
                        class="input-field"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        placeholder="اسم المستخدم"
                        autocomplete="username"
                    >
                    <i class="bi bi-person-fill input-icon"></i>
                </div>

                <div class="input-field-wrap">
                    <input
                        id="password"
                        class="input-field"
                        type="password"
                        name="password"
                        required
                        placeholder="كلمة المرور"
                        autocomplete="current-password"
                        dir="ltr"
                        style="text-align: right;"
                    >
                    <i class="bi bi-lock-fill input-icon"></i>
                </div>

                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember_me" checked>
                    <label for="remember_me">تذكرني</label>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <span id="btnContent">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        دخول
                    </span>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="card-footer-area">
            <div class="secure-badge">
                <i class="bi bi-shield-lock-fill"></i>
                محمي ومشفر بالكامل
            </div>
            <div style="margin-top:4px;">© {{ date('Y') }} مصنع سالم علي</div>
        </div>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        const parentBtn = document.getElementById('submitBtn');
        parentBtn.disabled = true;
        parentBtn.classList.add('loading');
        document.getElementById('btnContent').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري التحقق...';
    });
</script>
</body>
</html>
