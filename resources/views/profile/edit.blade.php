@extends('layouts.app')

@section('title', 'الملف الشخصي | مصنع المنتجات')
@section('page_title', 'الملف الشخصي')

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="glass-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-circle me-2 text-primary"></i>بيانات الحساب</h5>
            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')
                <div class="mb-3">
                    <label class="form-label text-muted">الاسم</label>
                    <input type="text" name="name" class="form-control form-control-glass" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control form-control-glass" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn-glass"><i class="bi bi-check-circle me-1"></i> حفظ التغييرات</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-primary"></i>تغيير كلمة المرور</h5>
            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')
                <div class="mb-3">
                    <label class="form-label text-muted">كلمة المرور الحالية</label>
                    <input type="password" name="current_password" class="form-control form-control-glass" required>
                    @error('current_password', 'updatePassword')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">كلمة المرور الجديدة</label>
                    <input type="password" name="password" class="form-control form-control-glass" required>
                    @error('password', 'updatePassword')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" class="form-control form-control-glass" required>
                </div>
                <button type="submit" class="btn-glass"><i class="bi bi-key me-1"></i> تحديث كلمة المرور</button>
            </form>
        </div>
    </div>
</div>
@endsection

