<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\RespondsForStudentModal;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveEscalationRequest;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Models\Student;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    use RespondsForStudentModal;

    public function __construct(
        private FeedbackService $feedbackService
    ) {}

    public function store(StoreFeedbackRequest $request, Student $student): JsonResponse|RedirectResponse
    {
        $this->authorize('create', [Feedback::class, $student]);

        $parentId = $request->integer('parent_id') ?: null;
        if ($parentId) {
            $parent = Feedback::query()->where('student_id', $student->id)->findOrFail($parentId);
            $this->authorize('reply', $parent);
        }

        $this->feedbackService->create(
            $request->user(),
            $student,
            $request->string('content')->toString(),
            $parentId,
            $request->boolean('requires_escalation')
        );

        return $this->modalOrBack($request, 'Đã gửi phản hồi.');
    }

    public function react(Request $request, Student $student, Feedback $feedback): JsonResponse|RedirectResponse
    {
        abort_unless($feedback->student_id === $student->id, 404);
        $this->authorize('react', $feedback);

        $reacted = $this->feedbackService->toggleReaction($request->user(), $feedback);

        if ($request->header('X-SRMH-Modal')) {
            return response()->json([
                'ok' => true,
                'reacted' => $reacted,
                'count' => $feedback->fresh()->reactions()->count(),
            ]);
        }

        return back();
    }

    public function agree(Request $request, Student $student, Feedback $feedback): JsonResponse|RedirectResponse
    {
        abort_unless($feedback->student_id === $student->id, 404);
        $this->authorize('agree', $feedback);

        $this->feedbackService->agree($request->user(), $feedback);

        return $this->modalOrBack($request, 'Đã ghi nhận cùng ý kiến.');
    }

    public function escalate(Request $request, Student $student, Feedback $feedback): JsonResponse|RedirectResponse
    {
        abort_unless($feedback->student_id === $student->id, 404);
        $this->authorize('escalate', $feedback);

        $this->feedbackService->escalate($request->user(), $feedback);

        return $this->modalOrBack($request, 'Đã chuyển yêu cầu CTSV hỗ trợ.');
    }

    public function resolve(ResolveEscalationRequest $request, Student $student, Feedback $feedback): JsonResponse|RedirectResponse
    {
        abort_unless($feedback->student_id === $student->id, 404);
        $this->authorize('resolve', $feedback);

        $this->feedbackService->resolve(
            $request->user(),
            $feedback,
            $request->string('note')->toString()
        );

        return $this->modalOrBack($request, 'Đã đánh dấu đã xử lý.');
    }

    public function item(Request $request, Student $student, Feedback $feedback): View
    {
        abort_unless($feedback->student_id === $student->id, 404);
        $this->authorize('view', $student);

        $feedback->load(['replies', 'reactions', 'agrees']);

        return view('students.partials.feedback-item', [
            'student' => $student,
            'feedback' => $feedback,
            'isReply' => $feedback->parent_id !== null,
        ]);
    }
}
