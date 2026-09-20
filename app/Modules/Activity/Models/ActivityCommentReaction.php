<?php

namespace App\Modules\Activity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityCommentReaction extends Model
{
    protected $fillable = [
        'activity_comment_id',
        'user_id',
        'is_like',
    ];

    protected function casts(): array
    {
        return [
            'is_like' => 'boolean',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ActivityComment::class, 'activity_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
