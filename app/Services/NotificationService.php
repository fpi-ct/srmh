<?php

namespace App\Services;

use App\Enums\CareStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Events\NotificationSent;
use App\Models\AppNotification;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\User;
use App\Notifications\WebPushAlert;
use App\Support\CareStatusUi;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyForFeedback(Feedback $feedback): void
    {
        $feedback->loadMissing(['student.studentClasses.lecturer', 'parent']);

        if ($feedback->requires_escalation && $feedback->parent_id === null) {
            $this->notifyRole(
                UserRole::StudentAffairs,
                NotificationType::Escalation,
                'Yêu cầu CTSV hỗ trợ',
                $feedback->student->full_name.' cần hỗ trợ',
                $feedback
            );
        }

        if ($feedback->parent_id && $feedback->parent) {
            $parentAuthor = $feedback->parent->author_access_code;
            if ($parentAuthor !== $feedback->author_access_code) {
                $this->notifyUser(
                    $parentAuthor,
                    NotificationType::Reply,
                    'Phản hồi mới',
                    $feedback->author_name.' đã trả lời bình luận của bạn',
                    $feedback
                );
            }
        }

        if ($this->isCareStatusFeedback($feedback)) {
            $this->notifyCareStatusChange($feedback);
        }

        if (in_array($feedback->student->care_status, [CareStatus::Monitoring, CareStatus::Critical], true)
            && $feedback->parent_id === null
            && ! $feedback->requires_escalation
            && ! $this->isCareStatusFeedback($feedback)
            && ! $feedback->is_agreed_duplicate) {
            $this->notifyStudentStakeholders(
                $feedback->student,
                NotificationType::NewFeedback,
                'Phản hồi mới',
                $feedback->author_name.': '.$this->preview($feedback->content),
                $feedback,
                $feedback->author_access_code
            );
        }
    }

    public function notifyForEscalation(Feedback $feedback): void
    {
        $feedback->loadMissing('student');

        $this->notifyRole(
            UserRole::StudentAffairs,
            NotificationType::Escalation,
            'Yêu cầu CTSV hỗ trợ',
            $feedback->student->full_name.' cần hỗ trợ',
            $feedback
        );
    }

    public function listForUser(User $user): Collection
    {
        return AppNotification::query()
            ->where('user_access_code', $user->access_code)
            ->where('created_at', '>=', now()->subDay())
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (AppNotification $n) => $this->isVisible($user, $n))
            ->values();
    }

    public function unreadCount(User $user): int
    {
        return $this->listForUser($user)->count();
    }

    public function markRead(User $user, AppNotification $notification): void
    {
        abort_unless($notification->user_access_code === $user->access_code, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllRead(User $user): void
    {
        AppNotification::query()
            ->where('user_access_code', $user->access_code)
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subDay())
            ->update(['read_at' => now()]);
    }

    private function notifyCareStatusChange(Feedback $feedback): void
    {
        $status = $feedback->student->care_status;

        if (! in_array($status, [CareStatus::Monitoring, CareStatus::Critical], true)) {
            return;
        }

        [$type, $title] = match ($status) {
            CareStatus::Critical => [NotificationType::StatusCritical, 'Cảnh báo đỏ'],
            CareStatus::Monitoring => [NotificationType::StatusMonitoring, 'Theo dõi'],
        };

        $body = $feedback->student->full_name.' — '.CareStatusUi::label($status);

        $this->notifyRole(UserRole::StudentAffairs, $type, $title, $body, $feedback);
        $this->notifyRole(UserRole::Admin, $type, $title, $body, $feedback);

        $faculties = $feedback->student->studentClasses->pluck('faculty')->unique()->filter();
        User::query()
            ->where('role', UserRole::DepartmentHead)
            ->where('is_active', true)
            ->get()
            ->filter(function (User $head) use ($faculties) {
                return $faculties->intersect($head->facultyList())->isNotEmpty();
            })
            ->each(fn (User $head) => $this->notifyUser(
                $head->access_code,
                $type,
                $title,
                $body,
                $feedback
            ));
    }

    private function notifyStudentStakeholders(
        Student $student,
        NotificationType $type,
        string $title,
        string $body,
        Feedback $feedback,
        ?string $excludeAccessCode = null
    ): void {
        $codes = $student->studentClasses
            ->pluck('lecturer.access_code')
            ->filter()
            ->unique();

        $faculties = $student->studentClasses->pluck('faculty')->unique()->filter();
        $heads = User::query()
            ->where('role', UserRole::DepartmentHead)
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $head) => $faculties->intersect($head->facultyList())->isNotEmpty())
            ->pluck('access_code');

        $codes->merge($heads)
            ->unique()
            ->reject(fn ($code) => $code === $excludeAccessCode)
            ->each(fn (string $code) => $this->notifyUser($code, $type, $title, $body, $feedback));
    }

    private function notifyRole(UserRole $role, NotificationType $type, string $title, string $body, Feedback $feedback): void
    {
        User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->pluck('access_code')
            ->each(fn (string $code) => $this->notifyUser($code, $type, $title, $body, $feedback));
    }

    private function notifyUser(string $accessCode, NotificationType $type, string $title, string $body, Feedback $feedback): void
    {
        $notification = AppNotification::create([
            'user_access_code' => $accessCode,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => [
                'student_id' => $feedback->student_id,
                'feedback_id' => $feedback->id,
                'care_status' => $feedback->student->care_status->value,
                'student_code' => $feedback->student->student_code,
                'student_name' => $feedback->student->full_name,
            ],
        ]);

        event(new NotificationSent($notification));

        $user = User::where('access_code', $accessCode)->first();
        if ($user && in_array($type, [NotificationType::Escalation, NotificationType::StatusCritical, NotificationType::StatusMonitoring, NotificationType::Reply], true)) {
            $user->notify(new WebPushAlert($notification));
        }
    }

    private function isCareStatusFeedback(Feedback $feedback): bool
    {
        $content = trim($feedback->content);
        if (! str_starts_with($content, '[')) {
            return false;
        }

        return str_contains($content, CareStatusUi::emoji(CareStatus::Stable))
            || str_contains($content, CareStatusUi::emoji(CareStatus::Monitoring))
            || str_contains($content, CareStatusUi::emoji(CareStatus::Critical));
    }

    private function preview(string $content): string
    {
        $text = trim($content);

        return mb_strlen($text) > 55 ? mb_substr($text, 0, 55).'…' : $text;
    }

    private function isVisible(User $user, AppNotification $notification): bool
    {
        $studentId = $notification->data['student_id'] ?? null;
        if (! $studentId) {
            return true;
        }

        $student = Student::with([
            'feedbacks' => fn ($q) => $q->orderByDesc('created_at')->limit(5),
            'feedbacks.reactions',
            'studentClasses.lecturer',
        ])->find($studentId);

        if (! $student) {
            return true;
        }

        $latest = $student->feedbacks->first();
        if (! $latest) {
            return true;
        }

        $withinDay = $latest->created_at && $latest->created_at->gte(now()->subDay());

        if ($user->role === UserRole::Lecturer && $withinDay) {
            $reacted = $latest->reactions->contains('user_access_code', $user->access_code);
            $isAuthor = $latest->author_access_code === $user->access_code;
            if ($reacted || $isAuthor) {
                return false;
            }
        }

        if ($user->role === UserRole::StudentAffairs && $withinDay) {
            if ($latest->author_role === UserRole::StudentAffairs) {
                return false;
            }
        }

        return true;
    }
}
