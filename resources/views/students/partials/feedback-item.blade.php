@php
    use App\Support\FeedbackRoleUi;
    $ui = FeedbackRoleUi::cardClasses($feedback->author_role);
    $likes = $feedback->reactions;
    $agrees = $feedback->agrees;
    $userReacted = $likes->contains('user_access_code', auth()->user()->access_code);
    $userAgreed = $agrees->contains('author_access_code', auth()->user()->access_code);
    $isOpenEscalation = $feedback->requires_escalation && $feedback->escalation_resolved_at === null;
    $isResolvedEscalation = $feedback->requires_escalation && $feedback->escalation_resolved_at !== null;
@endphp

<div class="tl-item" data-feedback-id="{{ $feedback->id }}">
    <div class="fb-bubble border {{ $ui['bg'] }} rounded-xl {{ $isReply ? 'text-sm' : '' }}">
        <div class="fb-bubble-head">
            <div class="min-w-0">
                @if($isOpenEscalation)
                    <span class="bg-rose-100 text-rose-700 text-[10px] px-2 py-0.5 rounded uppercase font-bold mr-1 inline-flex items-center gap-1">📢 Cần hỗ trợ</span>
                @elseif($isResolvedEscalation)
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded uppercase font-bold mr-1 inline-flex items-center gap-1">✅ Đã xử lý</span>
                @endif
                <span class="font-bold text-sm {{ $ui['text'] }}">
                    {{ FeedbackRoleUi::icon($feedback->author_role) }} {{ $feedback->author_name }}
                    <span class="js-agree-names text-xs font-normal opacity-80 {{ $agrees->isNotEmpty() ? '' : 'hidden' }}">@if($agrees->isNotEmpty())(+ {{ $agrees->pluck('author_name')->implode(', ') }})@endif</span>
                </span>
            </div>
            <span class="text-xs text-slate-400 shrink-0">{{ $feedback->created_at->format('d/m H:i') }}</span>
        </div>
        <p class="fb-bubble-body text-slate-700">{{ trim($feedback->content) }}</p>
        <div class="fb-bubble-actions">
            @if(!$isReply && $isOpenEscalation)
                @can('resolve', $feedback)
                    <form method="POST" action="{{ route('feedbacks.resolve', [$student, $feedback]) }}" class="js-resolve-form">
                        @csrf
                        <input type="hidden" name="note" value="">
                        <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-700 font-bold px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 transition">✅ Đã giải quyết</button>
                    </form>
                @endcan
            @endif
            @if(!$isReply)
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('reply-to', { detail: { id: {{ $feedback->id }}, name: @js($feedback->author_name) } }))"
                        class="text-xs text-slate-400 hover:text-indigo-600 transition">↪️ Trả lời</button>
            @endif
            @if(!$isReply)
                @can('agree', $feedback)
                    @if(!$userAgreed)
                        <form method="POST" action="{{ route('feedbacks.agree', [$student, $feedback]) }}" class="js-modal-form">
                            @csrf
                            <button type="submit" class="text-xs text-slate-400 hover:text-emerald-600 transition">🤝 Cùng comment</button>
                        </form>
                    @endif
                @endcan
            @endif
            @if(!$isReply)
                @can('escalate', $feedback)
                    <form method="POST" action="{{ route('feedbacks.escalate', [$student, $feedback]) }}" class="js-modal-form"
                          data-confirm="Chuyển bình luận này thành yêu cầu CTSV hỗ trợ?">
                        @csrf
                        <button type="submit" class="text-xs text-rose-500 hover:text-rose-700 transition">📢 Nhờ CTSV hỗ trợ</button>
                    </form>
                @endcan
            @endif
            @can('react', $feedback)
                <form method="POST" action="{{ route('feedbacks.react', [$student, $feedback]) }}" class="js-react-form">
                    @csrf
                    <button type="button" class="reaction-btn {{ $userReacted ? 'reacted' : '' }} inline-flex items-center gap-1 text-xs">
                        <span class="js-react-icon">{{ $userReacted ? '❤️' : '🤍' }}</span>
                        <span class="js-react-count font-semibold text-slate-500 {{ $likes->count() > 0 ? '' : 'hidden' }}">{{ $likes->count() ?: '' }}</span>
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>
