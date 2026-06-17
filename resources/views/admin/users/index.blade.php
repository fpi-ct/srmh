@extends('admin.layout')

@section('title', 'Tài khoản — Admin')

@section('admin')
<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-700">👥 Quản lý tài khoản</h3>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="document.getElementById('createUserModal').classList.add('active')" class="text-xs px-3 py-2 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition shadow-sm">
                + Tạo tài khoản
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap gap-2 px-5 py-3 border-b border-slate-50 bg-slate-50/40">
        <input type="text" name="search" value="{{ $search }}" placeholder="🔍 Mã hoặc tên..."
               class="flex-1 min-w-[160px] px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:border-indigo-400 focus:outline-none">
        <select name="role" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả vai trò</option>
            @foreach(\App\Enums\UserRole::cases() as $role)
                <option value="{{ $role->value }}" @selected($roleFilter === $role->value)>{{ $role->value }}</option>
            @endforeach
        </select>
        <select name="faculty" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả ngành</option>
            @foreach($faculties as $faculty)
                <option value="{{ $faculty }}" @selected($facultyFilter === $faculty)>{{ $faculty }}</option>
            @endforeach
        </select>
        <select name="active" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Mọi trạng thái</option>
            <option value="1" @selected($statusFilter === '1')>Hoạt động</option>
            <option value="0" @selected($statusFilter === '0')>Vô hiệu</option>
        </select>
        <button type="submit" class="px-3 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Lọc</button>
    </form>

    <div class="divide-y divide-slate-50">
        @forelse($users as $user)
            @php
                $icon = match($user->role) {
                    \App\Enums\UserRole::Admin => '🔧',
                    \App\Enums\UserRole::Lecturer => '👨‍🏫',
                    \App\Enums\UserRole::StudentAffairs => '👤',
                    \App\Enums\UserRole::DepartmentHead => '🎓',
                };
                $bgIcon = match($user->role) {
                    \App\Enums\UserRole::Admin => 'bg-rose-100',
                    \App\Enums\UserRole::Lecturer => 'bg-blue-100',
                    \App\Enums\UserRole::StudentAffairs => 'bg-emerald-100',
                    \App\Enums\UserRole::DepartmentHead => 'bg-purple-100',
                };
            @endphp
            <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/50">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 {{ $bgIcon }} rounded-xl flex items-center justify-center text-lg shrink-0">{{ $icon }}</div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 text-sm truncate">{{ $user->full_name }}</p>
                        <p class="text-xs text-slate-400">{{ $user->access_code }} · {{ $user->role_label }}@if($user->faculties) · {{ $user->faculties }}@endif</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $user->is_active ? 'Hoạt động' : 'Vô hiệu' }}
                    </span>
                    @if($user->role !== \App\Enums\UserRole::Admin)
                        <form method="POST" action="{{ route('admin.users.toggle', $user->access_code) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg font-semibold {{ $user->is_active ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }} transition">
                                {{ $user->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 text-sm">Không có tài khoản phù hợp bộ lọc</div>
        @endforelse
    </div>

    @if($users->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">{{ $users->links() }}</div>
    @endif
</div>

<div id="createUserModal" class="modal-overlay" onclick="if(event.target===this) this.classList.remove('active')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-700">Tạo tài khoản mới</h3>
            <button type="button" onclick="document.getElementById('createUserModal').classList.remove('active')" class="text-slate-400 hover:text-slate-600 text-xl">×</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 space-y-3">
            @csrf
            <div>
                <label class="text-xs text-slate-500 font-medium">Mã truy cập</label>
                <input type="text" name="access_code" required class="w-full mt-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none" value="{{ old('access_code') }}">
            </div>
            <div>
                <label class="text-xs text-slate-500 font-medium">Họ tên</label>
                <input type="text" name="full_name" required class="w-full mt-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none" value="{{ old('full_name') }}">
            </div>
            <div>
                <label class="text-xs text-slate-500 font-medium">Vai trò</label>
                <select name="role" required class="w-full mt-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none bg-white">
                    @foreach(\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500 font-medium">Nhãn vai trò</label>
                <input type="text" name="role_label" required class="w-full mt-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none" value="{{ old('role_label', 'Giảng viên') }}">
            </div>
            <div>
                <label class="text-xs text-slate-500 font-medium">Bộ môn (phân cách bằng dấu phẩy)</label>
                <input type="text" name="faculties" class="w-full mt-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none" value="{{ old('faculties') }}">
            </div>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition">Tạo tài khoản</button>
        </form>
    </div>
</div>
@endsection
