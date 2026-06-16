<?php

namespace App\Services;

use App\Enums\CareStatus;
use App\Enums\UserRole;
use App\Models\Feedback;
use App\Models\StudentClass;
use App\Models\User;
use App\Support\AbsenceRateUi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AnalyticsService
{
    private const STALE_DAYS = 7;

    private const TREND_WEEKS = 8;

    private const HIGH_ABSENCE_MIN = 16;

    public function __construct(
        private StudentService $studentService
    ) {}

    public function dashboard(User $user): array
    {
        $students = $this->studentService->scopedQuery($user)
            ->with('studentClasses')
            ->withCount('feedbacks')
            ->withMax('feedbacks', 'created_at')
            ->get();

        $studentIds = $students->pluck('id');

        return [
            'kpis' => $this->kpis($user, $students, $studentIds),
            'actions' => $this->actionItems($students, $studentIds),
            'coverage' => $this->coverage($user, $students),
            'status' => $this->statusDistribution($students),
            'by_faculty' => $this->facultyStats($user),
            'absence_histogram' => $this->absenceHistogram($students),
            'absence_by_faculty' => $this->absenceByFaculty($user),
            'trend' => $this->trend($studentIds),
        ];
    }

    private function absenceBucket(float $rate): string
    {
        if ($rate >= AbsenceRateUi::DANGER_MIN) {
            return '≥20%';
        }
        if ($rate >= AbsenceRateUi::WARNING_MAX) {
            return '17-20%';
        }
        if ($rate >= AbsenceRateUi::WARNING_MIN) {
            return '10-17%';
        }

        return '0-10%';
    }

    private function absenceByFaculty(User $user): array
    {
        $query = StudentClass::query();

        if ($user->role === UserRole::Lecturer) {
            $query->where('lecturer_id', $user->id);
        } elseif ($user->role === UserRole::DepartmentHead) {
            $query->whereIn('faculty', $user->facultyList());
        }

        $maxByFacultyStudent = [];

        foreach ($query->get(['faculty', 'student_id', 'absence_rate']) as $class) {
            $faculty = $class->faculty ?: 'Khác';
            $rate = (float) ($class->absence_rate ?? 0);
            $current = $maxByFacultyStudent[$faculty][$class->student_id] ?? 0;
            $maxByFacultyStudent[$faculty][$class->student_id] = max($current, $rate);
        }

        $labels = array_keys($maxByFacultyStudent);
        sort($labels);

        $buckets = ['0-10%', '10-17%', '17-20%', '≥20%'];
        $series = array_fill_keys($buckets, []);

        foreach ($labels as $faculty) {
            $counts = array_fill_keys($buckets, 0);
            foreach ($maxByFacultyStudent[$faculty] as $rate) {
                $counts[$this->absenceBucket($rate)]++;
            }
            foreach ($buckets as $bucket) {
                $series[$bucket][] = $counts[$bucket];
            }
        }

        return [
            'labels' => $labels,
            'buckets' => $buckets,
            'series' => $series,
        ];
    }

    private function kpis(User $user, Collection $students, Collection $studentIds): array
    {
        $withFeedback = $students->where('feedbacks_count', '>', 0)->count();
        $totalRoster = $this->rosterCount($user);
        $coveragePct = $totalRoster > 0 ? round(($withFeedback / $totalRoster) * 100, 1) : 0.0;

        $now = CarbonImmutable::now();
        $last7 = Feedback::query()
            ->whereIn('student_id', $studentIds)
            ->where('created_at', '>=', $now->subDays(7))
            ->count();
        $prev7 = Feedback::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('created_at', [$now->subDays(14), $now->subDays(7)])
            ->count();

        $pendingEscalations = Feedback::query()
            ->whereIn('student_id', $studentIds)
            ->where('requires_escalation', true)
            ->whereNull('escalation_resolved_at')
            ->count();

        return [
            'coverage_pct' => $coveragePct,
            'coverage_label' => "{$withFeedback}/{$totalRoster} SV",
            'feedback_7d' => $last7,
            'feedback_delta' => $last7 - $prev7,
            'pending_escalations' => $pendingEscalations,
            'avg_resolution_hours' => $this->avgResolutionHours($studentIds),
        ];
    }

    private function avgResolutionHours(Collection $studentIds): ?float
    {
        $resolved = Feedback::query()
            ->whereIn('student_id', $studentIds)
            ->where('requires_escalation', true)
            ->whereNotNull('escalation_resolved_at')
            ->get(['created_at', 'escalation_resolved_at']);

        if ($resolved->isEmpty()) {
            return null;
        }

        $hours = $resolved->map(
            fn ($f) => $f->created_at->diffInMinutes($f->escalation_resolved_at) / 60
        );

        return round($hours->avg(), 1);
    }

    private function actionItems(Collection $students, Collection $studentIds): array
    {
        $pendingEscalations = Feedback::query()
            ->whereIn('student_id', $studentIds)
            ->where('requires_escalation', true)
            ->whereNull('escalation_resolved_at')
            ->with('student')
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($f) => [
                'student_id' => $f->student_id,
                'student_name' => $f->student?->full_name,
                'student_code' => $f->student?->student_code,
                'content' => \Illuminate\Support\Str::limit($f->content, 80),
                'waiting_label' => $this->waitingLabel($f->created_at),
            ])
            ->values()
            ->all();

        $now = CarbonImmutable::now();

        $criticalStudents = $students->where('care_status', CareStatus::Critical);

        $latestContent = Feedback::query()
            ->whereIn('student_id', $criticalStudents->pluck('id'))
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get(['student_id', 'content'])
            ->groupBy('student_id')
            ->map(fn ($group) => $group->first()->content);

        $criticalAttention = $criticalStudents
            ->map(function ($student) use ($now, $latestContent) {
                $last = $student->feedbacks_max_created_at
                    ? CarbonImmutable::parse($student->feedbacks_max_created_at)
                    : null;

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'student_code' => $student->student_code,
                    'stale_days' => $last ? (int) $last->diffInDays($now) : null,
                    'note' => $this->criticalNote($student, $latestContent->get($student->id)),
                ];
            })
            ->filter(fn ($row) => $row['stale_days'] === null || $row['stale_days'] >= self::STALE_DAYS)
            ->sortByDesc(fn ($row) => $row['stale_days'] ?? PHP_INT_MAX)
            ->take(20)
            ->values()
            ->all();

        $highAbsenceStable = $students
            ->where('care_status', CareStatus::Stable)
            ->map(function ($student) {
                $maxAbsence = (float) ($student->studentClasses->max('absence_rate') ?? 0);

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'student_code' => $student->student_code,
                    'absence_rate' => $maxAbsence,
                ];
            })
            ->filter(fn ($row) => $row['absence_rate'] >= self::HIGH_ABSENCE_MIN)
            ->sortByDesc('absence_rate')
            ->take(20)
            ->values()
            ->all();

        return [
            'pending_escalations' => $pendingEscalations,
            'critical_attention' => $criticalAttention,
            'high_absence_stable' => $highAbsenceStable,
        ];
    }

    private function waitingLabel($createdAt): string
    {
        $minutes = (int) $createdAt->diffInMinutes(CarbonImmutable::now());

        if ($minutes >= 1440) {
            return (int) floor($minutes / 1440).'d';
        }

        if ($minutes >= 60) {
            return (int) floor($minutes / 60).'h';
        }

        return max(1, $minutes).'p';
    }

    private function criticalNote($student, ?string $latestContent): string
    {
        if (filled($latestContent)) {
            return \Illuminate\Support\Str::limit($latestContent, 80);
        }

        $warnings = $student->studentClasses
            ->pluck('note')
            ->filter()
            ->unique()
            ->implode(' · ');

        if (filled($warnings)) {
            return 'Học vụ: '.\Illuminate\Support\Str::limit($warnings, 70);
        }

        $maxAbsence = (float) ($student->studentClasses->max('absence_rate') ?? 0);
        if ($maxAbsence > 0) {
            return 'Vắng '.number_format($maxAbsence, 1).'% · chưa có phản hồi';
        }

        return 'Chưa có ghi nhận nào';
    }

    private function coverage(User $user, Collection $students): array
    {
        $withFeedback = $students->where('feedbacks_count', '>', 0)->count();
        $totalRoster = $this->rosterCount($user);
        $without = max(0, $totalRoster - $withFeedback);
        $pct = $totalRoster > 0 ? round(($withFeedback / $totalRoster) * 100, 1) : 0.0;

        return [
            'with' => $withFeedback,
            'without' => $without,
            'pct' => $pct,
            'label' => "{$withFeedback} / {$totalRoster} sinh viên",
        ];
    }

    private function statusDistribution(Collection $students): array
    {
        $stable = $students->where('care_status', CareStatus::Stable)->count();
        $monitoring = $students->where('care_status', CareStatus::Monitoring)->count();
        $critical = $students->where('care_status', CareStatus::Critical)->count();
        $withFeedback = $students->where('feedbacks_count', '>', 0)->count();
        $pct = $withFeedback > 0 ? round((($monitoring + $critical) / $withFeedback) * 100, 1) : 0.0;

        return [
            'stable' => $stable,
            'monitoring' => $monitoring,
            'critical' => $critical,
            'pct' => $pct,
            'label' => "{$stable} Ổn định · {$monitoring} Theo dõi · {$critical} Cảnh báo",
        ];
    }

    private function absenceHistogram(Collection $students): array
    {
        $buckets = [
            '0-10%' => 0,
            '10-17%' => 0,
            '17-20%' => 0,
            '≥20%' => 0,
        ];

        foreach ($students as $student) {
            $rate = (float) ($student->studentClasses->max('absence_rate') ?? 0);
            $buckets[$this->absenceBucket($rate)]++;
        }

        return [
            'labels' => array_keys($buckets),
            'data' => array_values($buckets),
        ];
    }

    private function trend(Collection $studentIds): array
    {
        $now = CarbonImmutable::now()->startOfWeek();
        $start = $now->subWeeks(self::TREND_WEEKS - 1);

        $feedbacks = Feedback::query()
            ->whereIn('student_id', $studentIds)
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'requires_escalation']);

        $labels = [];
        $feedbackSeries = [];
        $escalationSeries = [];

        for ($i = 0; $i < self::TREND_WEEKS; $i++) {
            $weekStart = $start->addWeeks($i);
            $weekEnd = $weekStart->addWeek();

            $inWeek = $feedbacks->filter(
                fn ($f) => $f->created_at >= $weekStart && $f->created_at < $weekEnd
            );

            $labels[] = $weekStart->format('d/m');
            $feedbackSeries[] = $inWeek->count();
            $escalationSeries[] = $inWeek->where('requires_escalation', true)->count();
        }

        return [
            'labels' => $labels,
            'feedbacks' => $feedbackSeries,
            'escalations' => $escalationSeries,
        ];
    }

    private function rosterCount(User $user): int
    {
        $query = StudentClass::query();

        if ($user->role === UserRole::Lecturer) {
            $query->where('lecturer_id', $user->id);
        } elseif ($user->role === UserRole::DepartmentHead) {
            $query->whereIn('faculty', $user->facultyList());
        }

        return (int) $query->distinct()->count('student_id');
    }

    private function facultyStats(User $user): array
    {
        $query = StudentClass::query()->with('student');

        if ($user->role === UserRole::Lecturer) {
            $query->where('lecturer_id', $user->id);
        } elseif ($user->role === UserRole::DepartmentHead) {
            $query->whereIn('faculty', $user->facultyList());
        }

        $buckets = [];

        foreach ($query->get() as $class) {
            $faculty = $class->faculty ?: 'Khác';
            $status = $class->student->care_status->value;
            $buckets[$faculty][$status][$class->student_id] = true;
        }

        ksort($buckets);

        $labels = array_keys($buckets);

        return [
            'labels' => $labels,
            'stable' => array_map(fn ($f) => count($buckets[$f]['stable'] ?? []), $labels),
            'monitoring' => array_map(fn ($f) => count($buckets[$f]['monitoring'] ?? []), $labels),
            'critical' => array_map(fn ($f) => count($buckets[$f]['critical'] ?? []), $labels),
        ];
    }
}
