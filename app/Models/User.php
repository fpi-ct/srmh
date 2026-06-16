<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'access_code',
        'full_name',
        'role_label',
        'role',
        'faculties',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function studentClasses(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'lecturer_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'author_access_code', 'access_code');
    }

    public function facultyList(): array
    {
        if (blank($this->faculties)) {
            return [];
        }

        return array_map('trim', explode(',', $this->faculties));
    }
}
