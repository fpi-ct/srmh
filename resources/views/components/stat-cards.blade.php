@php
    $activeStatus = $filters['status'] ?? 'all';
    $ring = fn ($key) => $activeStatus === $key ? 'ring-2 ring-indigo-400' : 'ring-0';
@endphp

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <a href="{{ route('dashboard', ['status' => 'all'] + request()->except('status', 'page')) }}"
       class="card-hover bg-white rounded-xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all {{ $ring('all') }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1">Tổng SV</p>
                <p class="text-2xl font-black text-slate-800">{{ $stats['total'] }}</p>
            </div>
            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-xl">👥</div>
        </div>
    </a>
    <a href="{{ route('dashboard', ['status' => 'green'] + request()->except('status', 'page')) }}"
       class="card-hover bg-white rounded-xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all {{ $ring('green') }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1">Ổn định</p>
                <p class="text-2xl font-black text-emerald-600">{{ $stats['stable'] }}</p>
            </div>
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-xl">🟢</div>
        </div>
    </a>
    <a href="{{ route('dashboard', ['status' => 'yellow'] + request()->except('status', 'page')) }}"
       class="card-hover bg-white rounded-xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all {{ $ring('yellow') }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1">Theo dõi</p>
                <p class="text-2xl font-black text-amber-500">{{ $stats['monitoring'] }}</p>
            </div>
            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-xl">🟡</div>
        </div>
    </a>
    <a href="{{ route('dashboard', ['status' => 'red'] + request()->except('status', 'page')) }}"
       class="card-hover bg-white rounded-xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all {{ $ring('red') }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1">Cảnh báo</p>
                <p class="text-2xl font-black text-rose-600">{{ $stats['critical'] }}</p>
            </div>
            <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center pulse text-xl">🔴</div>
        </div>
    </a>
</div>
