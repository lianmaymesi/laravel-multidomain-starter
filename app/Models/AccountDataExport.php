<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDataExport extends Model
{
    const EXPORT_TTL_DAYS = 7;

    protected $fillable = [
        'user_id',
        'token',
        'path',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function downloadUrl(): string
    {
        return route('account.export.download', $this->token);
    }
}
