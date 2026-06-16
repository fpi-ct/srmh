<div id="feedback-timeline">
    @forelse($student->feedbacks as $feedback)
        @include('students.partials.feedback-item', ['feedback' => $feedback, 'student' => $student, 'isReply' => false])
        @if($feedback->replies->isNotEmpty())
            <div class="ml-7 pl-3 border-l-2 border-slate-200 space-y-2">
                @foreach($feedback->replies as $reply)
                    @include('students.partials.feedback-item', ['feedback' => $reply, 'student' => $student, 'isReply' => true])
                @endforeach
            </div>
        @endif
    @empty
        <p class="text-center text-slate-400 text-sm py-6">Chưa có phản hồi chăm sóc.</p>
    @endforelse
</div>
