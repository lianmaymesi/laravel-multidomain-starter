<?php

namespace App\Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A file in the media library. It belongs to no model: models reference it
 * through MediaAttachment rows (table "mediables"), so one image can be used
 * in many places. Generated image sizes are rows of their own pointing back
 * with conversion_of_id. Deleting an item deletes its file, its sizes and
 * every attachment to it.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'conversion_of_id',
        'conversion',
        'disk',
        'path',
        'name',
        'original_name',
        'alt',
        'mime_type',
        'size',
        'metadata',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Through Eloquent (not the FK cascade) so each size's file goes too.
        static::deleting(fn (Media $media) => $media->conversions()->get()->each->delete());

        // After the row is gone, so a failed delete never leaves a row without a file.
        static::deleted(fn (Media $media) => Storage::disk($media->disk)->delete($media->path));
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(self::class, 'conversion_of_id');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'conversion_of_id');
    }

    /** Where this item is used. */
    public function attachments(): HasMany
    {
        return $this->hasMany(MediaAttachment::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeOriginals(Builder $query): void
    {
        $query->whereNull('conversion_of_id');
    }

    /**
     * Library items a user may browse and pick: everything for staff with
     * media.view, otherwise only their own uploads — a regular user must
     * never see (or attach) someone else's files.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        if ($user?->can('media.view')) {
            return;
        }

        $query->where('uploaded_by', $user?->getKey() ?? 0);
    }

    /** @param  Builder<self>  $query */
    public function scopeOfType(Builder $query, string $type): void
    {
        match ($type) {
            'image', 'video', 'audio' => $query->where('mime_type', 'like', "{$type}/%"),
            'document' => $query->where('mime_type', 'not like', 'image/%')
                ->where('mime_type', 'not like', 'video/%')
                ->where('mime_type', 'not like', 'audio/%'),
            default => null,
        };
    }

    /** @param  Builder<self>  $query */
    public function scopeSearch(Builder $query, string $term): void
    {
        // Plain substring match: LIKE wildcards typed by the user are dropped.
        $term = '%'.str_replace(['%', '_'], '', $term).'%';

        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', $term)
            ->orWhere('original_name', 'like', $term)
            ->orWhere('alt', 'like', $term));
    }

    /**
     * A generated size, e.g. ->conversion('thumb'). Falls back to this file
     * when the size doesn't exist (not an image, or too small to need it).
     */
    public function conversion(string $name): self
    {
        return $this->conversions->firstWhere('conversion', $name) ?? $this;
    }

    /**
     * A signed, expiring URL where the disk supports one (private local disk,
     * S3/R2/Spaces), otherwise the disk's public URL.
     */
    public function url(): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->providesTemporaryUrls()
            ? $disk->temporaryUrl($this->path, now()->addMinutes((int) config('media.temporary_url_minutes', 30)))
            : $disk->url($this->path);
    }

    /** "image", "video", "audio" or "document". */
    public function type(): string
    {
        $family = str($this->mime_type)->before('/')->toString();

        return in_array($family, ['image', 'video', 'audio'], true) ? $family : 'document';
    }

    public function isImage(): bool
    {
        return $this->type() === 'image';
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: str($this->mime_type)->afterLast('/')->toString());
    }

    public function width(): ?int
    {
        return $this->metadata['width'] ?? null;
    }

    public function height(): ?int
    {
        return $this->metadata['height'] ?? null;
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
