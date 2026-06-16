<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'student_id',
        'author_access_code',
        'author_name',
        'author_role',
        'content',
        'parent_id',
        'agreed_feedback_id',
        'requires_escalation',
        'escalation_resolved_at',
        'is_agreed_duplicate',
    ];

    protected function casts(): array
    {
        return [
            'author_role' => UserRole::class,
            'requires_escalation' => 'boolean',
            'is_agreed_duplicate' => 'boolean',
            'escalation_resolved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_access_code', 'access_code');
    }

    public function agreedWith(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'agreed_feedback_id');
    }

    public function agrees(): HasMany
    {
        return $this->hasMany(Feedback::class, 'agreed_feedback_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Feedback::class, 'parent_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(FeedbackReaction::class);
    }
}
