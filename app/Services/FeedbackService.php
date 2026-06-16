<?php

namespace App\Services;

use App\Enums\CareStatus;
use App\Enums\UserRole;
use App\Events\FeedbackAgreed;
use App\Events\FeedbackCreated;
use App\Events\FeedbackItemRefreshed;
use App\Events\FeedbackReacted;
use App\Models\Feedback;
use App\Models\FeedbackReaction;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\CareStatusUi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function create(User $user, Student $student, string $content, ?int $parentId = null, bool $requiresEscalation = false): Feedback
    {
        if ($parentId) {
            $parent = Feedback::query()->where('student_id', $student->id)->findOrFail($parentId);
            abort_unless(app(\App\Policies\FeedbackPolicy::class)->reply($user, $parent), 403);
        }

        if (in_array($user->role, [UserRole::Admin, UserRole::StudentAffairs], true) && $parentId === null) {
            $this->resolveOpenEscalations($student);
        }

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $user->access_code,
            'author_name' => $user->full_name,
            'author_role' => $user->role,
            'content' => $content,
            'parent_id' => $parentId,
            'requires_escalation' => $requiresEscalation && $parentId === null,
        ]);

        $this->touchStudent($student);
        event(new FeedbackCreated($feedback));

        return $feedback;
    }

    public function toggleReaction(User $user, Feedback $feedback): bool
    {
        $existing = FeedbackReaction::query()
            ->where('feedback_id', $feedback->id)
            ->where('user_access_code', $user->access_code)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->broadcastReaction($feedback, $user->access_code);

            return false;
        }

        FeedbackReaction::create([
            'feedback_id' => $feedback->id,
            'user_access_code' => $user->access_code,
            'user_name' => $user->full_name,
            'user_role' => $user->role,
        ]);

        $this->touchStudent($feedback->student);
        $this->broadcastReaction($feedback, $user->access_code);

        return true;
    }

    private function broadcastReaction(Feedback $feedback, string $actorAccessCode): void
    {
        event(new FeedbackReacted($feedback, $feedback->reactions()->count(), $actorAccessCode));
    }

    public function agree(User $user, Feedback $feedback): void
    {
        if (Feedback::query()
            ->where('agreed_feedback_id', $feedback->id)
            ->where('author_access_code', $user->access_code)
            ->exists()) {
            throw ValidationException::withMessages(['agree' => 'Bạn đã đồng ý với phản hồi này.']);
        }

        DB::transaction(function () use ($user, $feedback) {
            Feedback::create([
                'student_id' => $feedback->student_id,
                'author_access_code' => $user->access_code,
                'author_name' => $user->full_name,
                'author_role' => $user->role,
                'content' => $feedback->content,
                'agreed_feedback_id' => $feedback->id,
                'is_agreed_duplicate' => true,
            ]);

            $this->touchStudent($feedback->student);
        });

        $names = $feedback->agrees()->orderBy('created_at')->pluck('author_name')->all();
        event(new FeedbackAgreed($feedback, $names, $user->access_code));
    }

    public function escalate(User $user, Feedback $feedback): void
    {
        $feedback->update(['requires_escalation' => true]);
        $this->touchStudent($feedback->student);
        $this->notificationService->notifyForEscalation($feedback->fresh());
        event(new FeedbackItemRefreshed($feedback, $user->access_code));
    }

    public function resolve(User $user, Feedback $escalation, string $note): Feedback
    {
        $escalation->update(['escalation_resolved_at' => now()]);

        $reply = Feedback::create([
            'student_id' => $escalation->student_id,
            'author_access_code' => $user->access_code,
            'author_name' => $user->full_name,
            'author_role' => $user->role,
            'content' => $note,
            'parent_id' => $escalation->id,
        ]);

        $this->touchStudent($escalation->student);
        event(new FeedbackCreated($reply));
        event(new FeedbackItemRefreshed($escalation, $user->access_code));

        return $reply;
    }

    public function updateCareStatus(User $user, Student $student, CareStatus $careStatus, string $reason): void
    {
        $student->update(['care_status' => $careStatus]);

        $label = CareStatusUi::label($careStatus);
        $emoji = CareStatusUi::emoji($careStatus);

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $user->access_code,
            'author_name' => $user->full_name,
            'author_role' => $user->role,
            'content' => "[{$emoji} {$label}] {$reason}",
        ]);

        $this->touchStudent($student);
        event(new FeedbackCreated($feedback));
    }

    private function resolveOpenEscalations(Student $student): void
    {
        Feedback::query()
            ->where('student_id', $student->id)
            ->where('requires_escalation', true)
            ->whereNull('escalation_resolved_at')
            ->update(['escalation_resolved_at' => now()]);
    }

    private function touchStudent(Student $student): void
    {
        $student->update(['updated_at' => now()]);
    }
}
