@extends('layouts.guest')

@section('title', 'Đăng nhập — SRMH')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-xl shadow-indigo-200 mb-4">
                <span class="text-3xl">🎓</span>
            </div>
            <h1 class="text-3xl font-black text-slate-800">SRMH</h1>
            <p class="text-slate-500 text-sm mt-1">Student Real-time Monitoring Hub</p>
            <span class="inline-block mt-2 text-xs bg-violet-100 text-violet-700 px-3 py-1 rounded-full font-bold">v2.3</span>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <label class="block text-sm font-semibold text-slate-700 mb-2">Mã truy cập</label>
                <div class="relative mb-3">
                    <input type="password" name="access_code" id="accessCode" value="{{ old('access_code') }}"
                           placeholder="Nhập mã..." required autocomplete="username"
                           class="w-full px-4 py-3 pr-12 text-sm border-2 {{ $errors->has('access_code') ? 'border-rose-400' : 'border-slate-200' }} rounded-xl focus:border-indigo-500 focus:outline-none font-mono transition tracking-widest"
                           autofocus>
                    <button type="button" onclick="togglePassword()" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-indigo-600 focus:outline-none transition">
                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                @error('access_code')
                    <p class="text-sm text-rose-600 mb-3">{{ $message }}</p>
                @enderror
                <button type="submit" id="loginBtn"
                        class="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-xl font-bold text-sm hover:shadow-lg hover:shadow-indigo-200 transition hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-wait disabled:hover:translate-y-0">
                    <span id="loginBtnText">Đăng nhập</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword() {
    const input = document.getElementById('accessCode');
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('loginBtn');
    const text = document.getElementById('loginBtnText');
    btn.disabled = true;
    text.textContent = 'Đang đăng nhập...';
});
</script>
@endpush
