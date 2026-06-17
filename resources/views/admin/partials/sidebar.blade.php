@php
    $items = [
        ['key' => 'users', 'route' => 'admin.users', 'icon' => '👥', 'label' => 'Tài khoản'],
        ['key' => 'students', 'route' => 'admin.students', 'icon' => '🎓', 'label' => 'Sinh viên'],
        ['key' => 'bug-reports', 'route' => 'admin.bug-reports', 'icon' => '🐛', 'label' => 'Báo lỗi'],
    ];
    $section = $adminSection ?? 'users';
@endphp

<aside class="md:w-56 shrink-0">
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2 md:sticky md:top-20">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide px-3 py-2">Quản trị</p>
        <nav class="flex md:flex-col gap-1">
            @foreach($items as $item)
                @php $active = $section === $item['key']; @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-semibold transition flex-1 md:flex-none
                          {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    <span class="text-base">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="mt-3 pt-3 border-t border-slate-100">
            <form method="POST" action="{{ route('admin.bug-reports.student-data.destroy') }}" onsubmit="return confirm('Xác nhận xoá toàn bộ dữ liệu sinh viên?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-3 py-2 text-sm font-semibold bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">Xoá dữ liệu</button>
            </form>
        </div>
    </div>
</aside>
