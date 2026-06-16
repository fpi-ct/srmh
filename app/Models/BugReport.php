<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReport extends Model
{
    protected $fillable = [
        'author_access_code',
        'author_name',
        'content',
        'status',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_access_code', 'access_code');
    }
}
