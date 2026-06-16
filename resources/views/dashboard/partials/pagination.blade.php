@if(($lastPage ?? 1) > 1)
    <div class="flex items-center justify-center gap-2 mb-8">
        @if($page > 1)
            <a href="{{ route('dashboard', array_merge(request()->query(), ['page' => $page - 1])) }}"
               class="px-4 py-2 text-sm font-semibold bg-white border border-slate-200 rounded-xl hover:bg-slate-50">← Trước</a>
        @endif
        <span class="text-sm text-slate-500">Trang {{ $page }} / {{ $lastPage }} ({{ $totalStudents }} SV)</span>
        @if($page < $lastPage)
            <a href="{{ route('dashboard', array_merge(request()->query(), ['page' => $page + 1])) }}"
               class="px-4 py-2 text-sm font-semibold bg-white border border-slate-200 rounded-xl hover:bg-slate-50">Sau →</a>
        @endif
    </div>
@endif
