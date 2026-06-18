@php
    $items = [
        ['key' => 'users', 'route' => 'admin.users', 'icon' => '👥', 'label' => 'Tài khoản', 'short' => 'Tài khoản'],
        ['key' => 'students', 'route' => 'admin.students', 'icon' => '🎓', 'label' => 'Sinh viên', 'short' => 'Sinh viên'],
        ['key' => 'fap-sync-guide', 'route' => 'admin.fap-sync-guide', 'icon' => '📘', 'label' => 'Hướng dẫn đồng bộ data FAP', 'short' => 'Đồng bộ FAP'],
        [
            'key' => 'bug-reports',
            'route' => 'admin.bug-reports',
            'icon' => '🐛',
            'label' => 'Báo lỗi',
            'short' => 'Báo lỗi',
            'submenu' => [
                [
                    'label' => 'Xoá dữ liệu',
                    'action' => 'admin.bug-reports.student-data.destroy',
                    'confirm' => 'Xác nhận xoá toàn bộ dữ liệu sinh viên?',
                ],
            ],
        ],
    ];
    $section = $adminSection ?? 'users';
@endphp

<aside class="w-full md:w-56 shrink-0 sticky top-14 md:top-20 z-30">
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2 md:sticky md:top-20">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide px-3 py-2 hidden md:block">Quản trị</p>
        <nav class="flex md:flex-col gap-1.5 md:gap-1 overflow-x-auto md:overflow-visible flex-nowrap srmh-scroll-x pb-1 md:pb-0">
            @foreach($items as $item)
                @php $active = $section === $item['key']; @endphp
                @if(! empty($item['submenu']))
                    <div x-data="{ open: false }" class="relative shrink-0 md:w-full" @click.outside="open = false">
                        <div class="flex items-center rounded-lg transition overflow-hidden
                                    {{ $active ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-600 bg-slate-50/70 md:bg-transparent' }}">
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center gap-2 px-3 py-2 text-sm font-semibold transition shrink-0 whitespace-nowrap hover:bg-slate-50/80 flex-1 min-w-0">
                                <span class="text-base">{{ $item['icon'] }}</span>
                                <span class="md:hidden">{{ $item['short'] }}</span>
                                <span class="hidden md:inline">{{ $item['label'] }}</span>
                            </a>
                            <button type="button"
                                    @click="open = !open"
                                    class="px-2.5 py-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100/80 transition shrink-0 md:border-l {{ $active ? 'border-indigo-100' : 'border-slate-200' }}"
                                    :aria-expanded="open">
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        <div x-show="open"
                             x-cloak
                             x-transition.opacity
                             class="absolute left-0 top-full mt-1 z-50 min-w-[11rem] rounded-lg border border-slate-100 bg-white shadow-lg p-1
                                    md:static md:mt-0 md:min-w-0 md:rounded-none md:border-0 md:bg-transparent md:shadow-none md:p-0 md:pl-3 md:pt-1">
                            @foreach($item['submenu'] as $sub)
                                <form method="POST"
                                      action="{{ route($sub['action']) }}"
                                      onsubmit="return confirm(@json($sub['confirm']));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="w-full text-left px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition whitespace-nowrap">
                                        {{ $sub['label'] }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold transition shrink-0 whitespace-nowrap
                              {{ $active ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 bg-slate-50/70 md:bg-transparent' }}">
                        <span class="text-base">{{ $item['icon'] }}</span>
                        <span class="md:hidden">{{ $item['short'] }}</span>
                        <span class="hidden md:inline">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
</aside>
