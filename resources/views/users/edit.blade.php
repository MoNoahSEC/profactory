@extends('layouts.app')

@section('title', 'تعديل المستخدم | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'تعديل بيانات المستخدم')

@section('content')
<div class="row mb-4 fade-in-up">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>تعديل المستخدم: {{ $user->name }}</h4>
        <a href="{{ route('users.index') }}" class="btn-glass"><i class="bi bi-arrow-right me-1"></i> رجوع للمستخدمين</a>
    </div>
</div>

<div class="glass-card fade-in-up">
    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">الاسم الرباعي <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name) }}">
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-bold">البريد الإلكتروني (اسم المستخدم) <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email) }}" dir="ltr">
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-bold">كلمة المرور الجديدة (اتركه فارغاً إذا لم ترد تغييره)</label>
                <input type="password" name="password" class="form-control" dir="ltr">
                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-bold">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="password_confirmation" class="form-control" dir="ltr">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold mb-3">تحديد الصلاحيات (الدور الوظيفي) <span class="text-danger">*</span></label>
                <div class="row g-3">
                    @foreach($roles as $role)
                    <div class="col-md-3 col-sm-6">
                        <div class="form-check form-switch glass-panel p-3 rounded-3 h-100 d-flex align-items-center">
                            <input class="form-check-input ms-0 me-3 fs-4 mt-0" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}" {{ in_array($role->name, old('roles', $userRoles)) ? 'checked' : '' }}>
                            <label class="form-check-label mb-0 fw-bold" for="role_{{ $role->id }}">
                                {{ $role->name == 'Admin' ? 'مدير عام (كافة الصلاحيات)' : ($role->name == 'Cashier' ? 'كاشير (مبيعات وخزينة)' : ($role->name == 'Storekeeper' ? 'أمين مخزن (خامات ومنتجات)' : ($role->name == 'HR' ? 'شؤون موظفين (موظفين ورواتب)' : ($role->name == 'Loader' ? 'موظف تحميل (تأكيد تحميل الطلبيات)' : $role->name)))) }}
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
                @error('roles')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mt-4 pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold"><i class="bi bi-save me-1"></i> حفظ التعديلات</button>
        </div>
    </form>
</div>
@endsection
