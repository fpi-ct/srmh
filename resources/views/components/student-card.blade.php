@php
    use App\Support\AbsenceRateUi;
    use App\Support\CareStatusUi;
    $latestFb = $student->feedbacks->first();
    $preview = $latestFb?->content
        ? \Illuminate\Support\Str::limit($latestFb->content, 70)
        : 'Chưa có phản hồi';
    $classes = $student->studentClasses;
    $classNames = $classes->pluck('class_name')->unique()->implode(', ');
    $facultyNames = $classes->pluck('faculty')->unique()->filter()->implode(', ');
    $lecturerCodes = $classes->map(fn ($c) => $c->lecturer?->access_code)->filter()->unique()->implode(', ');
    $maxAbsence = $classes->max('absence_rate');
    $warnings = $classes->pluck('note')->filter()->unique();
    $hasHl = $warnings->contains(fn ($n) => str_contains(strtoupper($n), 'HL'));
    $hasGh = $warnings->contains(fn ($n) => str_contains(strtolower($n), 'gh'));
@endphp

<a href="{{ route('dashboard', ['status' => $filters['status'] ?? 'yellow', 'student' => $student->id]) }}"
   class="bg-white rounded-xl border border-slate-100 shadow-sm flex overflow-hidden hover:shadow-md transition-all cursor-pointer group block w-full text-left"
   onclick="event.preventDefault(); SrmhModal.open({{ $student->id }});">
    <div class="{{ CareStatusUi::strip($student->care_status) }} w-1.5 shrink-0"></div>
    <div class="flex-1 p-3.5 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 min-w-0">
        <div class="flex-1 min-w-0 sm:max-w-sm">
            <p class="font-bold text-slate-800 group-hover:text-indigo-600 transition text-sm truncate">
                {{ $student->full_name }}
                <span class="font-mono font-semibold text-slate-400">· {{ $student->student_code }}</span>
            </p>
            <p class="text-xs text-slate-400 mt-0.5 truncate">🏫 {{ $classNames ?: 'N/A' }}</p>
            @if($facultyNames || $lecturerCodes)
                <p class="text-xs text-slate-400 mt-0.5 truncate">
                    @if($facultyNames)📚 {{ $facultyNames }}@endif
                    @if($facultyNames && $lecturerCodes) · @endif
                    @if($lecturerCodes)<span class="font-mono">👨‍🏫 {{ $lecturerCodes }}</span>@endif
                </p>
            @endif
            <div class="flex flex-wrap gap-1 mt-1">
                @if($maxAbsence !== null && $maxAbsence > 0)
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ AbsenceRateUi::badgeClass($maxAbsence) }}">
                        Vắng {{ number_format($maxAbsence, 1) }}%
                    </span>
                @endif
                @if($hasHl)
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-rose-100 text-rose-700">HL</span>
                @endif
                @if($hasGh)
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">GH</span>
                @endif
            </div>
        </div>
        <div class="flex-1 min-w-0 hidden md:block">
            <p class="text-xs {{ $latestFb ? 'text-slate-500' : 'text-slate-400 italic' }} truncate">💬 {{ $preview }}</p>
        </div>
        <div class="shrink-0">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ CareStatusUi::badge($student->care_status) }}">
                {{ CareStatusUi::emoji($student->care_status) }} {{ CareStatusUi::label($student->care_status) }}
            </span>
        </div>
    </div>
</a>
