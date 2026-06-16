@php
    $accentMap = [
        'rose' => 'border-rose-100',
        'amber' => 'border-amber-100',
        'orange' => 'border-orange-100',
    ];
    $border = $accentMap[$accent] ?? 'border-slate-100';
@endphp

<div class="bg-white rounded-xl border {{ $border }} shadow-sm overflow-hidden flex flex-col">
    <div class="px-4 py-3 border-b border-slate-100">
        <div class="flex items-center justify-between gap-2">
            <h3 class="font-bold text-slate-700 text-sm">{{ $title }}</h3>
            <span class="text-xs font-black text-slate-400">{{ count($items) }}</span>
        </div>
        @isset($subtitle)
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $subtitle }}</p>
        @endisset
    </div>
    <div class="divide-y divide-slate-50 max-h-80 overflow-y-auto">
        @forelse($items as $item)
            <a href="{{ route('dashboard', ['status' => 'all', 'student' => $item['student_id']]) }}"
               class="block px-4 py-2.5 hover:bg-slate-50 transition">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['student_name'] }}</p>
                        <p class="text-[11px] text-slate-400 font-mono">{{ $item['student_code'] }}</p>
                    </div>
                    @if($renderItem === 'escalation')
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 shrink-0">
                            chờ {{ $item['waiting_label'] }}
                        </span>
                    @elseif($renderItem === 'critical')
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 shrink-0">
                            {{ $item['stale_days'] === null ? 'chưa có FB' : $item['stale_days'].'d trước' }}
                        </span>
                    @elseif($renderItem === 'absence')
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 shrink-0">
                            vắng {{ number_format($item['absence_rate'], 1) }}%
                        </span>
                    @endif
                </div>
                @if($renderItem === 'escalation')
                    <p class="text-xs text-slate-500 mt-1 truncate">💬 {{ $item['content'] }}</p>
                @elseif($renderItem === 'critical')
                    <p class="text-xs text-slate-500 mt-1 truncate">📝 {{ $item['note'] }}</p>
                @endif
            </a>
        @empty
            <div class="px-4 py-8 text-center text-slate-400 text-xs">
                <p class="text-2xl mb-2">✓</p>
                {{ $empty }}
            </div>
        @endforelse
    </div>
</div>
