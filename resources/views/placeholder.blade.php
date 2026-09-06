@extends('layouts.app')

@section('title', 'قريباً | مصنع المنتجات')
@section('page_title', 'جاري التطوير')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="col-md-8 col-lg-6 text-center">
        <div class="glass-card p-5">
            <div class="icon-box primary mx-auto mb-4" style="width: 80px; height: 80px; font-size: 2.5rem;">
                <i class="bi bi-tools"></i>
            </div>
            <h3 class="fw-bold mb-3">هذه الصفحة قيد التطوير</h3>
            <p class="text-muted mb-4 fs-5">نحن نعمل حالياً على برمجة وتطوير هذا القسم (المرحلة القادمة). شكراً لتفهمك!</p>
            <a href="{{ route('dashboard') }}" class="btn-glass">العودة للوحة التحكم</a>
        </div>
    </div>
</div>
@endsection

