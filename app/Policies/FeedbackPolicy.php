<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\User;

class FeedbackPolicy
{
    public function create(User $user, Student $student): bool
    {
        return app(StudentPolicy::class)->view($user, $student);
    }

    public function reply(User $user, Feedback $feedback): bool
    {
        return $this->create($user, $feedback->student);
    }

    public function react(User $user, Feedback $feedback): bool
    {
        return $this->create($user, $feedback->student);
    }

    public function agree(User $user, Feedback $feedback): bool
    {
        if ($feedback->parent_id !== null || $feedback->is_agreed_duplicate) {
            return false;
        }

        if (! in_array($user->role, [UserRole::Lecturer, UserRole::DepartmentHead], true)) {
            return false;
        }

        if ($feedback->author_access_code === $user->access_code) {
            return false;
        }

        if (in_array($feedback->author_role, [UserRole::StudentAffairs, UserRole::Admin], true)) {
            return false;
        }

        return $this->create($user, $feedback->student);
    }

    public function escalate(User $user, Feedback $feedback): bool
    {
        if ($feedback->parent_id !== null || $feedback->requires_escalation) {
            return false;
        }

        if ($feedback->author_access_code === $user->access_code) {
            return in_array($user->role, [UserRole::Lecturer, UserRole::DepartmentHead], true);
        }

        if ($user->role === UserRole::DepartmentHead) {
            return $feedback->student->belongsToFaculties($user->facultyList());
        }

        return false;
    }

    public function resolve(User $user, Feedback $feedback): bool
    {
        if (! in_array($user->role, [UserRole::Admin, UserRole::StudentAffairs], true)) {
            return false;
        }

        return $feedback->requires_escalation && $feedback->escalation_resolved_at === null;
    }
}
