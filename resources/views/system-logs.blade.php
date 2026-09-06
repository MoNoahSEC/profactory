@extends('layouts.app')

@section('title', 'سجل أخطاء النظام | ' . config('app.name'))
@section('page_title', 'مكتشف الأخطاء والإصلاح الذكي')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="glass-card p-4 rounded-4 shadow-sm border-0 position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
            <div style="position: absolute; left: -20px; top: -30px; font-size: 10rem; opacity: 0.05; color: white;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center position-relative z-1">
                <div>
                    <h4 class="fw-bolder mb-2 text-white"><i class="bi bi-tools text-orange me-2"></i> مركز الصيانة وإصلاح الأخطاء</h4>
                    <p class="text-light opacity-75 mb-0">يمكنك هنا اكتشاف أي أخطاء برمجية واجهت النظام، والقيام بعملية تنظيف ذاتية للملفات المؤقتة لإصلاح المشاكل.</p>
                </div>
                <div class="mt-3 mt-md-0 d-flex gap-2">
                    <form action="{{ route('system.fix') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-orange fw-bold rounded-pill px-4 shadow-sm">
                            <i class="bi bi-magic me-1"></i> إصلاح ذكي (مسح الكاش)
                        </button>
                    </form>
                    <form action="{{ route('system.logs.clear') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من مسح السجل؟')">
                        @csrf
                        <button type="submit" class="btn btn-outline-light fw-bold rounded-pill px-4">
                            <i class="bi bi-trash me-1"></i> مسح السجل
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-code text-primary me-2"></i> تفاصيل السجل (آخر 500 سطر)</h5>
    </div>
    <div class="card-body p-0">
        <div class="bg-dark text-light p-3" style="max-height: 60vh; overflow-y: auto; font-family: monospace; direction: ltr; font-size: 0.85rem; border-radius: 0 0 16px 16px;">
            @forelse($logs as $log)
                @php
                    $isError = str_contains($log, 'ERROR') || str_contains($log, 'Exception');
                    $isStack = str_starts_with(trim($log), '#');
                @endphp
                <div style="padding: 2px 0; {{ $isError ? 'color: #ff6b6b; font-weight: bold;' : ($isStack ? 'color: #6c757d;' : 'color: #a9b7c6;') }} border-bottom: 1px solid rgba(255,255,255,0.05);">
                    {{ $log }}
                </div>
            @empty
                <div class="text-center py-5 text-success fw-bold" style="font-size: 1.1rem;">
                    <i class="bi bi-check-circle-fill fs-2 d-block mb-2"></i>
                    لا توجد أخطاء حالياً. النظام يعمل بشكل مثالي!
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
