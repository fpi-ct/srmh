<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClass extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'lecturer_id',
        'class_name',
        'subject_code',
        'subject_name',
        'group_id',
        'faculty',
        'semester',
        'absence_rate',
        'note',
        'member_code',
    ];

    protected function casts(): array
    {
        return [
            'absence_rate' => 'decimal:2',
            'group_id' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
