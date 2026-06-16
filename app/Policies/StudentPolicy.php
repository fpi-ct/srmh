<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Student $student): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::Admin, UserRole::StudentAffairs => true,
            UserRole::DepartmentHead => $student->belongsToFaculties($user->facultyList()),
            UserRole::Lecturer => $student->hasLecturer($user->id),
        };
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Student $student): bool
    {
        return $this->view($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->is_active && $user->role === UserRole::Admin;
    }
}
