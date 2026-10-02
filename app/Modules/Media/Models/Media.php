<?php

namespace App\Modules\Media\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * One stored file. Conversions (thumbnails, ...) are rows of their own that
 * point back with conversion_of_id and use collection "<collection>:<name>".
 * Deleting a row deletes its file and its conversions.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'mediable_type',
        'mediable_id',
        'collection',
        'conversion_of_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'order' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Through Eloquent (not the FK cascade) so each conversion's file goes too.
        static::deleting(fn (Media $media) => $media->conversions()->get()->each->delete());

        // After the row is gone, so a failed delete never leaves a row without a file.
        static::deleted(fn (Media $media) => Storage::disk($media->disk)->delete($media->path));
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(self::class, 'conversion_of_id');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'conversion_of_id');
    }

    /** @param  Builder<self>  $query */
    public function scopeOriginals(Builder $query): void
    {
        $query->whereNull('conversion_of_id');
    }

    /**
     * A generated variant, e.g. ->conversion('thumb'). Falls back to this
     * file when the variant doesn't exist (not an image, or uploaded while
     * conversions were not configured).
     */
    public function conversion(string $name): self
    {
        return $this->conversions->firstWhere('collection', "{$this->collection}:{$name}") ?? $this;
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

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
