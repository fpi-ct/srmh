<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>@yield('title', 'SRMH')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/srmh.css') }}">
    @stack('head')
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-indigo-50 min-h-screen">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-14">
            <div class="flex items-center gap-5">
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xl">🎓</span>
                    <span class="font-black text-slate-800 hidden sm:block">SRMH</span>
                    <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full font-bold">v2.3</span>
                </div>
                <nav class="flex items-center">
                    <a href="{{ route('dashboard') }}"
                       class="tab-btn text-sm font-semibold px-3 py-4 {{ ($activeTab ?? '') === 'dashboard' ? 'active text-slate-600' : 'text-slate-400' }} hover:text-indigo-600">Dashboard</a>
                    <a href="{{ route('analytics') }}"
                       class="tab-btn text-sm font-semibold px-3 py-4 {{ ($activeTab ?? '') === 'analytics' ? 'active text-slate-600' : 'text-slate-400' }} hover:text-indigo-600">Analytics</a>
                    @if(auth()->user()->role === \App\Enums\UserRole::Admin)
                        <a href="{{ route('admin.users') }}"
                           class="tab-btn text-sm font-semibold px-3 py-4 {{ ($activeTab ?? '') === 'admin' ? 'active text-slate-600' : 'text-slate-400' }} hover:text-indigo-600">Admin</a>
                    @endif
                </nav>
            </div>
            <div class="flex items-center gap-2">
                @include('layouts.partials.notifications')
                <button type="button" onclick="document.getElementById('bugReportModal').classList.add('active')"
                        class="text-xs px-2 py-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition font-semibold" title="Báo lỗi / Góp ý">🐛</button>
                <div class="pl-2 border-l border-slate-200 flex items-center gap-2">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-700">{{ auth()->user()->full_name }}</p>
                        <p class="text-xs text-slate-400">{{ auth()->user()->role_label }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs px-3 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 rounded-lg transition font-semibold">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    @yield('content')

    <div id="bugReportModal" class="modal-overlay" onclick="if(event.target===this) this.classList.remove('active')">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-700">🐛 Báo lỗi / Góp ý</h3>
                <button type="button" onclick="document.getElementById('bugReportModal').classList.remove('active')" class="text-slate-400 hover:text-slate-600 text-xl">×</button>
            </div>
            <form method="POST" action="{{ route('bug-reports.store') }}" class="p-5 space-y-3">
                @csrf
                <textarea name="content" required rows="5" placeholder="Mô tả lỗi hoặc góp ý cải thiện hệ thống..."
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none resize-none">{{ old('content') }}</textarea>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition">Gửi báo cáo</button>
            </form>
        </div>
    </div>

    @auth
        <script>
            (function () {
                const successMessage = @json(session('success'));
                const errorMessage = @json(session('error'));

                if (successMessage) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: successMessage,
                        confirmButtonText: 'Đóng'
                    });
                }

                if (errorMessage) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Có lỗi',
                        text: errorMessage,
                        confirmButtonText: 'Đóng'
                    });
                }
            })();
        </script>
        <script>
            window.__SRMH_REVERB__ = {!! json_encode([
                'key' => config('broadcasting.connections.reverb.key'),
                'wsHost' => config('broadcasting.connections.reverb.options.host'),
                'wsPort' => (int) config('broadcasting.connections.reverb.options.port'),
                'wssPort' => (int) config('broadcasting.connections.reverb.options.port'),
                'forceTLS' => config('broadcasting.connections.reverb.options.scheme') === 'https',
            ]) !!};
            window.__SRMH_USER__ = @json(auth()->user()->access_code);
            window.__SRMH_VAPID__ = @json(config('webpush.vapid.public_key'));
        </script>
        <script src="{{ asset('js/vendor/pusher.min.js') }}"></script>
        <script src="{{ asset('js/vendor/echo.iife.js') }}"></script>
        <script src="{{ asset('js/srmh-notifications.js') }}?v=9"></script>
        <script src="{{ asset('js/srmh-echo.js') }}?v=7"></script>
        <script src="{{ asset('js/srmh-push.js') }}?v=5"></script>
        <script>
            window.SrmhEcho?.init();
            window.SrmhPush?.init();
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>
    @endauth
    @stack('scripts')
</body>
</html>
