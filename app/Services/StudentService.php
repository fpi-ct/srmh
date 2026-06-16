<?php

namespace App\Services;

use App\Enums\CareStatus;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use App\Enums\UserRole;
use App\Support\AbsenceRateUi;
use Illuminate\Support\Collection;

class StudentService
{
    public function scopedQuery(User $user)
    {
        $query = Student::query()->with(['studentClasses.lecturer']);

        return match ($user->role) {
            UserRole::Admin, UserRole::StudentAffairs => $query,
            UserRole::Lecturer => $query->whereHas('studentClasses', function ($q) use ($user) {
                $q->where('lecturer_id', $user->id);
            }),
            UserRole::DepartmentHead => $query->whereHas('studentClasses', function ($q) use ($user) {
                $q->whereIn('faculty', $user->facultyList());
            }),
        };
    }

    public function list(User $user, array $filters = [])
    {
        $query = $this->scopedQuery($user);

        if (! empty($filters['care_status'])) {
            $query->where('care_status', $filters['care_status']);
        }

        if (! empty($filters['class_section'])) {
            $query->whereHas('studentClasses', function ($q) use ($filters) {
                $q->where('class_name', $filters['class_section']);
            });
        }

        if (! empty($filters['faculty'])) {
            $query->whereHas('studentClasses', function ($q) use ($filters) {
                $q->where('faculty', $filters['faculty']);
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function sortForDashboard(Collection $students): Collection
    {
        return $students->sort(fn ($a, $b) => $this->dashboardPriority($b) <=> $this->dashboardPriority($a))->values();
    }

    public function stats(User $user): array
    {
        $query = $this->scopedQuery($user);

        return [
            'total' => (clone $query)->count(),
            'stable' => (clone $query)->where('care_status', 'stable')->count(),
            'monitoring' => (clone $query)->where('care_status', 'monitoring')->count(),
            'critical' => (clone $query)->where('care_status', 'critical')->count(),
        ];
    }

    public function availableClasses(User $user): array
    {
        $query = StudentClass::query()->select('class_name')->distinct();

        $this->scopeStudentClasses($query, $user);

        return $query->orderBy('class_name')->pluck('class_name')->all();
    }

    public function availableFaculties(User $user): array
    {
        $query = StudentClass::query()->select('faculty')->distinct();

        $this->scopeStudentClasses($query, $user);

        return $query->orderBy('faculty')->pluck('faculty')->all();
    }

    private function dashboardPriority(Student $student): int
    {
        $score = match ($student->care_status) {
            CareStatus::Critical => 3000,
            CareStatus::Monitoring => 2000,
            default => 1000,
        };

        $maxAbsence = (float) ($student->studentClasses->max('absence_rate') ?? 0);

        if ($maxAbsence >= AbsenceRateUi::DANGER_MIN) {
            $score += 500;
        } elseif ($maxAbsence >= AbsenceRateUi::WARNING_MIN) {
            $score += 200;
        }

        if ($this->hasRiskNote($student)) {
            $score += 100;
        }

        return $score + (int) round($maxAbsence * 10);
    }

    private function hasRiskNote(Student $student): bool
    {
        return $student->studentClasses->contains(function ($class) {
            $note = strtoupper((string) $class->note);

            return $note !== '' && (str_contains($note, 'HL') || str_contains(strtolower($note), 'gh'));
        });
    }

    private function scopeStudentClasses($query, User $user): void
    {
        if ($user->role === UserRole::Lecturer) {
            $query->where('lecturer_id', $user->id);
        } elseif ($user->role === UserRole::DepartmentHead) {
            $query->whereIn('faculty', $user->facultyList());
        }
    }
}
