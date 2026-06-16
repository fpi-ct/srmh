<?php

namespace App\Models;

use App\Enums\CareStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'student_code',
        'full_name',
        'care_status',
    ];

    protected function casts(): array
    {
        return [
            'care_status' => CareStatus::class,
            'updated_at' => 'datetime',
        ];
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function studentClasses(): HasMany
    {
        return $this->hasMany(StudentClass::class);
    }

    public function hasInstructor(string $accessCode): bool
    {
        return $this->studentClasses()
            ->whereHas('lecturer', fn ($q) => $q->where('access_code', $accessCode))
            ->exists();
    }

    public function hasLecturer(int $lecturerId): bool
    {
        return $this->studentClasses()
            ->where('lecturer_id', $lecturerId)
            ->exists();
    }

    public function belongsToFaculty(string $faculties): bool
    {
        return $this->belongsToFaculties(array_map('trim', explode(',', $faculties)));
    }

    public function belongsToFaculties(array $faculties): bool
    {
        return $this->studentClasses()
            ->whereIn('faculty', $faculties)
            ->exists();
    }
}
