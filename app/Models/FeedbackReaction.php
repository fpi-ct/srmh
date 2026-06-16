<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackReaction extends Model
{
    protected $table = 'feedback_reactions';

    public $timestamps = false;

    protected $fillable = [
        'feedback_id',
        'user_access_code',
        'user_name',
        'user_role',
    ];

    protected function casts(): array
    {
        return [
            'user_role' => UserRole::class,
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}
