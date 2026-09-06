@extends('layouts.app')

@section('title', 'إدارة المستخدمين والصلاحيات | ' . ($globalSettings['company_name'] ?? config('app.name')))
@section('page_title', 'المستخدمين والصلاحيات')

@section('content')
<div class="row mb-4 fade-in-up">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill text-primary me-2"></i>المستخدمين</h4>
        <a href="{{ route('users.create') }}" class="btn-glass"><i class="bi bi-person-plus-fill me-1"></i> إضافة مستخدم جديد</a>
    </div>
</div>

<div class="glass-card fade-in-up">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الصلاحية (الدور)</th>
                    <th>تاريخ الإضافة</th>
                    <th class="text-center">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="fw-bold">
                        <i class="bi bi-person-circle text-muted fs-4 me-2 align-middle"></i>
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                            <span class="badge bg-primary ms-1">أنت</span>
                        @endif
                    </td>
                    <td dir="ltr" class="text-end">{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge bg-info text-dark">{{ $role->name == 'Admin' ? 'مدير عام' : ($role->name == 'Cashier' ? 'كاشير' : ($role->name == 'Storekeeper' ? 'أمين مخزن' : ($role->name == 'HR' ? 'شؤون موظفين' : $role->name))) }}</span>
                        @endforeach
                        @if($user->roles->isEmpty())
                            <span class="badge bg-secondary">بدون صلاحيات</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary rounded-circle border-0" title="تعديل الصلاحيات">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم نهائياً؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle border-0" title="حذف">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-1 d-block mb-3"></i>
                        لا يوجد مستخدمين آخرين
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
    <div class="mt-4">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
