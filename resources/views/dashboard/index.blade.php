@extends('layouts.app')

@section('title', 'Dashboard — SRMH')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5">
    <div id="dashboard-stats">
        @include('dashboard.partials.stats')
    </div>

    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-3 mb-4">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row gap-2">
            <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm MSSV hoặc Họ tên..."
                   class="flex-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none">
            @if($showFacultyFilter)
                <select name="faculty" class="px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none bg-white">
                    <option value="">📚 Tất cả bộ môn</option>
                    @foreach($faculties as $faculty)
                        <option value="{{ $faculty }}" @selected(request('faculty') === $faculty)>{{ $faculty }}</option>
                    @endforeach
                </select>
            @endif
            <select name="class_section" class="px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none bg-white">
                <option value="">🏫 Tất cả lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class }}" @selected(request('class_section') === $class)>{{ $class }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-xl hover:shadow-md transition">
                Lọc
            </button>
            <a href="{{ route('reports') }}"
               class="px-3 py-2 text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl hover:shadow-md transition text-center">
                📄 Báo cáo
            </a>
        </form>
    </div>

    <div id="dashboard-students" class="flex flex-col gap-2 mb-8">
        @include('dashboard.partials.student-list')
    </div>

    <div id="dashboard-pagination">
        @include('dashboard.partials.pagination')
    </div>
</div>

@include('students.partials.modal-shell')
<div id="srmh-toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-800 text-white text-sm px-4 py-2.5 rounded-xl shadow-lg"></div>
@endsection

@push('scripts')
<script src="{{ asset('js/srmh-dashboard.js') }}?v=2"></script>
<script src="{{ asset('js/srmh-modal.js') }}?v=2"></script>
@endpush
