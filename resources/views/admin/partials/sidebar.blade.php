@php
    $items = [
        ['key' => 'users', 'route' => 'admin.users', 'icon' => '👥', 'label' => 'Tài khoản', 'short' => 'Tài khoản'],
        ['key' => 'students', 'route' => 'admin.students', 'icon' => '🎓', 'label' => 'Sinh viên', 'short' => 'Sinh viên'],
        ['key' => 'fap-sync-guide', 'route' => 'admin.fap-sync-guide', 'icon' => '📘', 'label' => 'Hướng dẫn đồng bộ data FAP', 'short' => 'Đồng bộ FAP'],
        ['key' => 'bug-reports', 'route' => 'admin.bug-reports', 'icon' => '🐛', 'label' => 'Báo lỗi', 'short' => 'Báo lỗi'],
    ];
    $section = $adminSection ?? 'users';
@endphp

<aside class="w-full md:w-56 shrink-0 sticky top-14 md:top-20 z-30">
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2 md:sticky md:top-20">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide px-3 py-2 hidden md:block">Quản trị</p>
        <nav class="flex md:flex-col gap-1.5 md:gap-1 overflow-x-auto md:overflow-visible flex-nowrap md:flex-wrap srmh-scroll-x pb-1 md:pb-0">
            @foreach($items as $item)
                @php $active = $section === $item['key']; @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center md:items-start gap-2 px-3 py-2 rounded-lg text-sm font-semibold transition shrink-0 md:shrink md:w-full whitespace-nowrap md:whitespace-normal
                          {{ $active ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 bg-slate-50/70 md:bg-transparent' }}">
                    <span class="text-base shrink-0">{{ $item['icon'] }}</span>
                    <span class="md:hidden">{{ $item['short'] }}</span>
                    <span class="hidden md:inline min-w-0 leading-snug">{{ $item['label'] }}</span>
                </a>
            @endforeach
            <form method="POST"
                  action="{{ route('admin.bug-reports.student-data.destroy') }}"
                  onsubmit="return confirm('Xác nhận xoá toàn bộ dữ liệu sinh viên?');"
                  class="shrink-0 md:shrink md:w-full">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="flex items-center md:items-start gap-2 px-3 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap md:whitespace-normal text-rose-600 hover:bg-rose-50 bg-rose-50/70 md:bg-transparent w-full">
                    <span class="text-base shrink-0">🗑️</span>
                    <span class="min-w-0 leading-snug">Xoá dữ liệu</span>
                </button>
            </form>
        </nav>
    </div>
</aside>
