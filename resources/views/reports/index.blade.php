@extends('layouts.app')

@section('title', 'Báo cáo — SRMH')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5">
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-5 mb-5 text-white shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-black">📄 Xuất báo cáo phản hồi</h1>
                <p class="text-emerald-100 text-xs mt-0.5">{{ $subtitle }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-white/70 hover:text-white text-2xl leading-none">×</a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('reports') }}" class="flex flex-col sm:flex-row flex-wrap gap-2 items-end">
            <input type="hidden" name="generate" value="1">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-slate-500 font-medium block mb-1">Lọc theo lớp</label>
                <select name="class_section" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:border-indigo-400 focus:outline-none bg-white">
                    <option value="all" @selected($classSection === 'all')>Tất cả lớp</option>
                    @foreach($classes as $class)
                        <option value="{{ $class }}" @selected($classSection === $class)>{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                Tạo báo cáo
            </button>
            @if($generated && $rows->isNotEmpty())
                <button type="button" id="reportCopyBtn" class="hidden px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                    Copy cột đã chọn
                </button>
                <button type="button" id="reportCopyAllBtn" class="px-4 py-2 text-sm font-semibold bg-slate-700 text-white rounded-xl hover:bg-slate-800 transition">
                    Copy toàn bộ
                </button>
            @endif
            <p id="reportCopyHint" class="text-xs text-slate-400 sm:ml-auto">Click tiêu đề cột để chọn → copy</p>
        </form>
    </div>

    @if(!$generated)
        <div class="text-center text-slate-400 py-16 bg-white rounded-xl border border-slate-100">
            <p class="text-4xl mb-3">📋</p>
            <p class="text-sm">Chọn lớp và nhấn <strong>Tạo báo cáo</strong> để xem dữ liệu</p>
        </div>
    @elseif($rows->isEmpty())
        <div class="text-center text-slate-400 py-16 bg-white rounded-xl border border-slate-100">
            <p class="text-sm">Không có dữ liệu phù hợp bộ lọc</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <table class="report-table" id="reportTable">
                <thead>
                    <tr>
                        @foreach(['MSSV', 'Họ và tên sinh viên', 'Lớp / Mã môn', 'Giảng viên phụ trách', 'Người ghi nhận', 'Phản hồi gần nhất', 'Thời gian'] as $i => $col)
                            <th data-col="{{ $i }}" class="report-col-hd cursor-pointer" title="Click để bôi xanh cột">
                                {{ $col }} <span style="font-size:9px;opacity:.7">▼</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $student = $row['student'];
                            $feedback = $row['feedback'];
                        @endphp
                        <tr>
                            <td class="font-mono text-xs text-slate-500 whitespace-nowrap">{{ $student->student_code }}</td>
                            <td class="font-semibold text-slate-800 whitespace-nowrap">{{ $student->full_name }}</td>
                            <td class="text-xs text-slate-600 whitespace-nowrap">{{ $reportService->classDisplay($student) }}</td>
                            <td class="text-xs text-slate-600 whitespace-nowrap">{{ $reportService->instructorNames($student) }}</td>
                            <td class="text-xs text-slate-600 whitespace-nowrap">{{ $feedback->author_name }} ({{ $feedback->author_role->value }})</td>
                            <td class="text-sm text-slate-700" style="min-width:260px;max-width:400px">{{ $feedback->content }}</td>
                            <td class="text-xs text-slate-400 whitespace-nowrap">{{ $feedback->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-500 mt-3">
            📋 {{ $rows->count() }} sinh viên · Bộ lọc: {{ $classSection === 'all' ? 'Tất cả lớp' : $classSection }} · Hiển thị feedback gần nhất
        </p>
    @endif
</div>
@endsection

@push('scripts')
@if($generated && $rows->isNotEmpty())
<script src="{{ asset('js/srmh-report.js') }}?v=1"></script>
@endif
@endpush
