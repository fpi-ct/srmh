@extends('layouts.app')

@section('title', 'Analytics — SRMH')

@php
    use Illuminate\Support\Js;
    $k = $data['kpis'];
    $act = $data['actions'];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5 space-y-5">

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400 font-medium mb-1">Độ bao phủ Feedback</p>
            <p class="text-2xl font-black text-indigo-600">{{ $k['coverage_pct'] }}%</p>
            <p class="text-[11px] text-slate-400 mt-1">{{ $k['coverage_label'] }}</p>
        </div>
        <a href="{{ route('dashboard', ['status' => 'all']) }}"
           class="card-hover bg-white rounded-xl border border-slate-100 shadow-sm p-4 block">
            <p class="text-xs text-slate-400 font-medium mb-1">Feedback 7 ngày</p>
            <p class="text-2xl font-black text-slate-800">{{ $k['feedback_7d'] }}</p>
            <p class="text-[11px] mt-1 {{ $k['feedback_delta'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $k['feedback_delta'] >= 0 ? '▲' : '▼' }} {{ abs($k['feedback_delta']) }} so với tuần trước
            </p>
        </a>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 {{ $k['pending_escalations'] > 0 ? 'ring-2 ring-rose-200' : '' }}">
            <p class="text-xs text-slate-400 font-medium mb-1">Yêu cầu hỗ trợ chờ xử lý</p>
            <p class="text-2xl font-black {{ $k['pending_escalations'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $k['pending_escalations'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">cần CTSV phản hồi</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400 font-medium mb-1">Thời gian xử lý TB</p>
            <p class="text-2xl font-black text-slate-800">
                {{ $k['avg_resolution_hours'] !== null ? $k['avg_resolution_hours'].'h' : '—' }}
            </p>
            <p class="text-[11px] text-slate-400 mt-1">mỗi yêu cầu</p>
        </div>
    </div>

    <div>
        <h2 class="text-sm font-black text-slate-700 mb-3 flex items-center gap-2">
            <span class="text-base">⚡</span> Cần xử lý ngay
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            @include('analytics.partials.action-list', [
                'title' => '🚨 Yêu cầu hỗ trợ chờ xử lý',
                'subtitle' => 'Yêu cầu cần CTSV hỗ trợ nhưng chưa được giải quyết',
                'accent' => 'rose',
                'items' => $act['pending_escalations'],
                'empty' => 'Không có yêu cầu nào đang chờ',
                'renderItem' => 'escalation',
            ])

            @include('analytics.partials.action-list', [
                'title' => '🔴 Cảnh báo thiếu theo dõi',
                'subtitle' => 'SV mức cảnh báo nhưng ≥7 ngày không có cập nhật mới',
                'accent' => 'amber',
                'items' => $act['critical_attention'],
                'empty' => 'SV cảnh báo đều được theo dõi gần đây',
                'renderItem' => 'critical',
            ])

            @include('analytics.partials.action-list', [
                'title' => '📉 Vắng cao nhưng chưa chăm sóc',
                'subtitle' => 'SV vắng ≥16% nhưng vẫn ở trạng thái ổn định',
                'accent' => 'orange',
                'items' => $act['high_absence_stable'],
                'empty' => 'Không có SV vắng cao bị bỏ sót',
                'renderItem' => 'absence',
            ])

        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center text-sm">📈</span>
                <h3 class="font-bold text-slate-700 text-sm">Độ bao phủ Feedback</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4 ml-9">Tỉ lệ SV đã được ghi nhận trên tổng số</p>
            <div style="height:240px;" class="flex items-center justify-center">
                <canvas id="coverageDonutChart"></canvas>
            </div>
            <p class="text-center text-xs text-slate-400 mt-2 font-semibold">{{ $data['coverage']['label'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-7 h-7 bg-emerald-50 rounded-lg flex items-center justify-center text-sm">📊</span>
                <h3 class="font-bold text-slate-700 text-sm">Trạng thái SV đã có Feedback</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4 ml-9">Click vào vòng để lọc Dashboard theo trạng thái</p>
            <div style="height:240px;" class="flex items-center justify-center">
                <canvas id="donutChart"></canvas>
            </div>
            <p class="text-center text-xs text-slate-400 mt-2 font-semibold">{{ $data['status']['label'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between gap-2 mb-1">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 bg-rose-50 rounded-lg flex items-center justify-center text-sm">📉</span>
                    <h3 class="font-bold text-slate-700 text-sm">Phân bố tỉ lệ vắng</h3>
                </div>
                <div class="flex bg-slate-100 rounded-lg p-0.5 text-xs font-semibold">
                    <button type="button" data-absence-tab="all" class="px-3 py-1 rounded-md bg-white text-indigo-600 shadow-sm">Toàn bộ</button>
                    <button type="button" data-absence-tab="faculty" class="px-3 py-1 rounded-md text-slate-500">Theo bộ môn</button>
                </div>
            </div>
            <p class="text-xs text-slate-400 mb-4 ml-9">Theo tỉ lệ vắng cao nhất của mỗi SV</p>
            <div data-absence-panel="all" style="height:260px;"><canvas id="absenceHistogramChart"></canvas></div>
            <div data-absence-panel="faculty" class="hidden" style="height:260px;"><canvas id="absenceFacultyChart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-7 h-7 bg-violet-50 rounded-lg flex items-center justify-center text-sm">📅</span>
                <h3 class="font-bold text-slate-700 text-sm">Xu hướng {{ count($data['trend']['labels']) }} tuần</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4 ml-9">Số feedback & yêu cầu hỗ trợ mỗi tuần</p>
            <div style="height:260px;"><canvas id="trendChart"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <div class="flex items-center gap-2 mb-1">
            <span class="w-7 h-7 bg-violet-50 rounded-lg flex items-center justify-center text-sm">🏢</span>
            <h3 class="font-bold text-slate-700 text-sm">Phân bổ trạng thái theo Bộ môn</h3>
        </div>
        <p class="text-xs text-slate-400 mb-4 ml-9">Click vào cột để lọc Dashboard theo bộ môn</p>
        <div style="height:320px;"><canvas id="facultyStackedBarChart"></canvas></div>
    </div>
</div>
@endsection

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
    window.__SRMH_ANALYTICS__ = {!! Js::from($data) !!};
</script>
<script src="{{ asset('js/srmh-analytics.js') }}?v=6"></script>
@endpush
