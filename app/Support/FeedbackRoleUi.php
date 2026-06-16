<?php

namespace App\Support;

use App\Enums\UserRole;

class FeedbackRoleUi
{
    public static function cardClasses(UserRole $role): array
    {
        return match ($role) {
            UserRole::Lecturer => ['bg' => 'bg-blue-50 border-blue-100', 'text' => 'text-blue-700'],
            UserRole::DepartmentHead => ['bg' => 'bg-purple-50 border-purple-100', 'text' => 'text-purple-700'],
            UserRole::StudentAffairs => ['bg' => 'bg-emerald-50 border-emerald-100', 'text' => 'text-emerald-700'],
            UserRole::Admin => ['bg' => 'bg-slate-50 border-slate-200', 'text' => 'text-slate-700'],
        };
    }

    public static function icon(UserRole $role): string
    {
        return match ($role) {
            UserRole::Lecturer => '👨‍🏫',
            UserRole::DepartmentHead => '🎓',
            UserRole::StudentAffairs, UserRole::Admin => '👤',
        };
    }
}
