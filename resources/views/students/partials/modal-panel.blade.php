@php
    $formId = 'careStatusForm-' . $student->id;
@endphp

<div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-auto flex flex-col overflow-hidden border border-slate-100"
     style="max-height:88vh"
     x-data="{
        classesOpen: false,
        replyParentId: null,
        replyName: '',
        setReply(id, name) {
            this.replyParentId = id;
            this.replyName = name;
            this.$nextTick(() => this.$refs.feedbackInput?.focus());
        },
        cancelReply() {
            this.replyParentId = null;
            this.replyName = '';
        }
     }"
     @reply-to.window="setReply($event.detail.id, $event.detail.name)">
    <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-4 text-white shrink-0">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-black">{{ $student->full_name }}</h2>
                <p class="text-indigo-200 text-sm mt-0.5">
                    {{ $student->student_code }} · {{ $classNames ?: 'N/A' }}
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span id="student-modal-status" class="text-xs font-semibold px-2.5 py-1 rounded-full {{ \App\Support\CareStatusUi::badge($student->care_status) }}">
                        {{ \App\Support\CareStatusUi::emoji($student->care_status) }}
                        {{ \App\Support\CareStatusUi::label($student->care_status) }}
                    </span>
                    @if($faculties)
                        <span class="text-xs text-indigo-200/90">{{ $faculties }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($canChangeStatus)
                    <form id="{{ $formId }}" method="POST" action="{{ route('students.care-status', $student) }}" class="flex gap-1.5 js-care-status-form" data-method="PATCH">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="care_status" value="">
                        <input type="hidden" name="reason" value="">
                        <button type="button" data-care-status="stable"
                                class="px-2.5 py-1.5 bg-white/20 hover:bg-emerald-500 text-white rounded-lg text-sm transition font-bold" title="Ổn định">🟢</button>
                        <button type="button" data-care-status="monitoring"
                                class="px-2.5 py-1.5 bg-white/20 hover:bg-amber-500 text-white rounded-lg text-sm transition font-bold" title="Theo dõi">🟡</button>
                        <button type="button" data-care-status="critical"
                                class="px-2.5 py-1.5 bg-white/20 hover:bg-rose-500 text-white rounded-lg text-sm transition font-bold" title="Cảnh báo">🔴</button>
                    </form>
                @endif
                <button type="button" onclick="SrmhModal.close()"
                        class="text-white/70 hover:text-white text-2xl leading-none">×</button>
            </div>
        </div>
    </div>

    @if($student->studentClasses->isNotEmpty())
        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 shrink-0">
            <button type="button" @click="classesOpen = !classesOpen"
                    class="w-full flex items-center justify-between gap-2 text-left py-1 group">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide group-hover:text-slate-500">Lớp/học phần</span>
                <span class="text-slate-400 text-xs shrink-0" x-text="classesOpen ? '▾' : '▸'">▸</span>
            </button>
            <div x-show="classesOpen" x-cloak class="space-y-2 pt-2 pb-1">
                @foreach($student->studentClasses as $class)
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        <span class="font-semibold text-slate-700">{{ $class->subject_code }}</span>
                        <span class="text-slate-500">{{ $class->class_name }}</span>
                        @if($class->lecturer)
                            <span class="text-slate-400 font-mono">GV: {{ $class->lecturer->access_code }}</span>
                        @endif
                        @if($class->absence_rate !== null && $class->absence_rate > 0)
                            <span class="font-semibold px-1.5 py-0.5 rounded {{ \App\Support\AbsenceRateUi::badgeClass($class->absence_rate) }}">
                                Vắng {{ number_format($class->absence_rate, 1) }}%
                            </span>
                        @endif
                        @if($class->note)
                            <span class="font-semibold px-1.5 py-0.5 rounded bg-rose-50 text-rose-600">{{ $class->note }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div id="student-modal-chat" class="flex-1 overflow-y-auto p-4 bg-slate-50 min-h-[200px]">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-3">Lịch sử chăm sóc</p>
        @include('students.partials.timeline', ['student' => $student])
    </div>

    <div class="p-4 bg-white border-t border-slate-100 shrink-0">
        <div x-show="replyParentId" x-cloak class="mb-2 flex items-center justify-between text-xs bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg">
            <span>↪️ Trả lời <strong x-text="replyName"></strong></span>
            <button type="button" @click="cancelReply()" class="hover:text-indigo-900 font-bold">✕</button>
        </div>
        <form method="POST" action="{{ route('students.feedbacks.store', $student) }}" class="space-y-2 js-modal-form">
            @csrf
            <input type="hidden" name="parent_id" :value="replyParentId">
            <div class="flex gap-2">
                <input type="text" name="content" x-ref="feedbackInput" required maxlength="5000"
                       :placeholder="replyParentId ? 'Trả lời ' + replyName + '...' : 'Nhập phản hồi...'"
                       class="flex-1 px-3 py-2.5 text-sm border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Gửi</button>
            </div>
            @if($canEscalate)
                <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer w-fit">
                    <input type="checkbox" name="requires_escalation" value="1" class="w-4 h-4 rounded border-slate-300 text-rose-500 focus:ring-rose-500">
                    <span class="font-medium text-rose-600">📢 Cần CTSV hỗ trợ / can thiệp</span>
                </label>
            @endif
        </form>
        <p id="student-modal-form-error" class="text-xs text-rose-600 mt-1 hidden"></p>
    </div>
</div>
