<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDataExport extends Model
{
    const EXPORT_TTL_DAYS = 7;
    const MAX_DOWNLOADS_PER_DAY = 3;
    const DOWNLOAD_LIMITER_KEY = 'export-download:';

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'path',
        'expires_at',
        'downloaded_at',
        'download_count',
        'download_token',
        'download_token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'                => 'datetime',
            'downloaded_at'             => 'datetime',
            'download_token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->expires_at?->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'ready' && $this->expires_at?->isPast();
    }

    public function hasValidDownloadToken(string $token): bool
    {
        return $this->download_token === $token
            && $this->download_token_expires_at?->isFuture();
    }

    /**
     * Update stats — called from Livewire before re-render so the table reflects immediately.
     */
    public function recordDownload(): void
    {
        $this->update([
            'downloaded_at'  => now(),
            'download_count' => $this->download_count + 1,
        ]);
    }

    /**
     * Invalidate the one-time token — called from the controller after serving the file.
     */
    public function consumeDownloadToken(): void
    {
        $this->update([
            'download_token'            => null,
            'download_token_expires_at' => null,
        ]);
    }

    public function downloadUrl(): string
    {
        return route('account.export.download', $this->token);
    }
}
