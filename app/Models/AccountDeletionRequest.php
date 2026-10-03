<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property Carbon $requested_at
 * @property Carbon $scheduled_at
 * @property-read User $user
 */
class AccountDeletionRequest extends Model
{
    const GRACE_PERIOD_DAYS = 30;

    protected $fillable = [
        'user_id',
        'requested_at',
        'scheduled_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCancellable(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isFuture();
    }

    public function daysRemaining(): int
    {
        return max(0, (int) now()->diffInDays($this->scheduled_at, false));
    }
}
