@extends('admin.layout')

@section('title', 'Sinh viên — Admin')

@section('admin')
<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div>
            <h3 class="font-bold text-slate-700">🎓 Danh sách sinh viên</h3>
            <p class="text-xs text-slate-400">
                Dữ liệu cập nhật lần cuối:
                {{ $lastUpdatedAt ? $lastUpdatedAt->format('d/m/Y H:i') : '--' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-semibold">{{ $students->total() }} SV</span>
            <form method="POST" action="{{ route('admin.roster.import') }}" enctype="multipart/form-data">
                @csrf
                <label class="text-xs px-3 py-2 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition shadow-sm cursor-pointer">
                    📄 Import FAP CSV
                    <input type="file" name="file" accept=".csv,.txt" class="hidden" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.students') }}" class="flex flex-wrap gap-2 px-5 py-3 border-b border-slate-50 bg-slate-50/40">
        <input type="text" name="search" value="{{ $search }}" placeholder="🔍 MSSV hoặc tên..."
               class="flex-1 min-w-[160px] px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:border-indigo-400 focus:outline-none">
        <select name="care_status" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Mọi trạng thái</option>
            @foreach(\App\Enums\CareStatus::cases() as $cs)
                <option value="{{ $cs->value }}" @selected($status === $cs->value)>{{ \App\Support\CareStatusUi::emoji($cs) }} {{ \App\Support\CareStatusUi::label($cs) }}</option>
            @endforeach
        </select>
        <select name="faculty" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả bộ môn</option>
            @foreach($faculties as $f)
                <option value="{{ $f }}" @selected($faculty === $f)>{{ $f }}</option>
            @endforeach
        </select>
        <select name="class_section" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả lớp</option>
            @foreach($classes as $c)
                <option value="{{ $c }}" @selected($classSection === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-3 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Lọc</button>
    </form>

    <div class="divide-y divide-slate-50">
        @forelse($students as $student)
            @php
                $classNames = $student->studentClasses->pluck('class_name')->unique()->implode(', ');
                $facultyNames = $student->studentClasses->pluck('faculty')->unique()->filter()->implode(', ');
                $maxAbsence = $student->studentClasses->max('absence_rate');
            @endphp
            <a href="{{ route('dashboard', ['status' => 'all', 'student' => $student->id]) }}"
               class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/50 transition">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-800 text-sm truncate">
                        {{ $student->full_name }}
                        <span class="font-mono font-medium text-slate-400">· {{ $student->student_code }}</span>
                    </p>
                    <p class="text-xs text-slate-400 truncate">
                        🏫 {{ $classNames ?: 'N/A' }}@if($facultyNames) · 📚 {{ $facultyNames }}@endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($maxAbsence !== null && $maxAbsence > 0)
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ \App\Support\AbsenceRateUi::badgeClass($maxAbsence) }}">
                            Vắng {{ number_format($maxAbsence, 1) }}%
                        </span>
                    @endif
                    <span class="text-[11px] text-slate-400">💬 {{ $student->feedbacks_count }}</span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ \App\Support\CareStatusUi::badge($student->care_status) }}">
                        {{ \App\Support\CareStatusUi::emoji($student->care_status) }} {{ \App\Support\CareStatusUi::label($student->care_status) }}
                    </span>
                </div>
            </a>
        @empty
            <div class="p-8 text-center text-slate-400 text-sm">Không có sinh viên phù hợp bộ lọc</div>
        @endforelse
    </div>

    @if($students->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">{{ $students->links() }}</div>
    @endif
</div>
@endsection
