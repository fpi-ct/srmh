<?php

namespace App\Services;

use App\Enums\CareStatus;
use App\Enums\UserRole;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private StudentService $studentService
    ) {}

    public function subtitle(User $user): string
    {
        return match ($user->role) {
            UserRole::Admin => 'Quyền xem: Tất cả feedback · Lọc theo lớp tuỳ chọn',
            UserRole::DepartmentHead => 'Quyền xem: Tất cả feedback — Bộ môn '.($user->faculties ?: 'N/A').' · Lọc theo lớp',
            UserRole::StudentAffairs => 'Quyền xem: Feedback do bạn tạo · Lọc theo lớp',
            UserRole::Lecturer => 'Quyền xem: Feedback do bạn tạo · Chỉ lớp được phân công',
        };
    }

    public function rows(User $user, ?string $classSection = null): Collection
    {
        $students = $this->studentService->scopedQuery($user)
            ->with(['studentClasses.lecturer'])
            ->get();

        $feedbackQuery = Feedback::query()
            ->whereNull('parent_id')
            ->whereNull('agreed_feedback_id');

        if (in_array($user->role, [UserRole::Lecturer, UserRole::StudentAffairs], true)) {
            $feedbackQuery->where('author_access_code', $user->access_code);
        }

        $feedbacksByStudent = $feedbackQuery
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('student_id');

        $rows = collect();

        foreach ($students as $student) {
            $feedbacks = $feedbacksByStudent->get($student->id);
            if (! $feedbacks || $feedbacks->isEmpty()) {
                continue;
            }

            if ($classSection && $classSection !== 'all') {
                $classes = $student->studentClasses->pluck('class_name')->unique();
                if (! $classes->contains($classSection)) {
                    continue;
                }
            }

            $rows->push([
                'student' => $student,
                'feedback' => $feedbacks->first(),
            ]);
        }

        return $rows->sort(function ($a, $b) {
            $priority = fn (CareStatus $status) => match ($status) {
                CareStatus::Critical => 0,
                CareStatus::Monitoring => 1,
                default => 2,
            };

            $statusDiff = $priority($a['student']->care_status) <=> $priority($b['student']->care_status);
            if ($statusDiff !== 0) {
                return $statusDiff;
            }

            return $b['feedback']->created_at <=> $a['feedback']->created_at;
        })->values();
    }

    public function instructorNames($student): string
    {
        return $student->studentClasses
            ->map(fn ($c) => $c->lecturer?->full_name)
            ->filter()
            ->unique()
            ->implode(', ') ?: 'N/A';
    }

    public function classDisplay($student): string
    {
        $classes = $student->studentClasses->pluck('class_name')->unique()->filter();
        $subjects = $student->studentClasses->pluck('subject_code')->unique()->filter();
        $classPart = $classes->implode(', ') ?: 'N/A';
        $subjectPart = $subjects->isNotEmpty() ? ' (📚 '.$subjects->implode(', ').')' : '';

        return $classPart.$subjectPart;
    }
}
