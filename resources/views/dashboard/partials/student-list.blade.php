@forelse($students as $student)
    @include('components.student-card', ['student' => $student])
@empty
    <div class="text-center text-slate-400 py-16 text-sm">Không có sinh viên phù hợp bộ lọc.</div>
@endforelse
