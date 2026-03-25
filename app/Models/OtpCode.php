<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'code', 'expires_at', 'used_at', 'ip_address'])]
class OtpCode extends Model
{
    /** @use HasFactory<OtpCodeFactory> */
    use HasFactory, MassPrunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'user_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the OtpCode
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function isValid(): bool
    {
        return !$this->used_at && !$this->isExpired();
    }

    /**
     * Prunable: remove used codes or codes expired for > 24 hours.
     * Runs via: php artisan model:prune --model=App\\Models\\OtpCode
     */
    public function prunable(): Builder
    {
        return static::query()->where(function ($q) {
            $q->where('used', true)
                ->orWhere('expires_at', '<', now()->subDay());
        });
    }
}
