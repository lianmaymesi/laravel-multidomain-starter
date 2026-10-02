<?php

namespace App\Modules\Media\Concerns;

use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use App\Support\Modules\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Opt-in media for any model:
 *
 *   $user->addMedia($request->file('photo'), 'avatar');
 *   $user->getFirstMediaUrl('avatar', 'thumb');
 *
 * Safe on a core model: while the Media module is off every method is a
 * no-op that behaves as if the model has no media — nothing throws, and
 * nothing is deleted.
 */
trait HasMedia
{
    public static function bootHasMedia(): void
    {
        static::deleting(function ($model) {
            if (! Module::enabled('media') || ! config('media.delete_with_model', true)) {
                return;
            }

            // A soft delete can be undone — keep the files until it's final.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->clearMedia();
        });
    }

    /** Original files (not conversions), in upload order. Empty while the module is off. */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->whereNull('conversion_of_id')
            ->when(! Module::enabled('media'), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('order')
            ->orderBy('id');
    }

    /**
     * Validate and store a file. Returns null while the module is off.
     *
     * @throws ValidationException
     */
    public function addMedia(File $file, string $collection = 'default', ?string $disk = null): ?Media
    {
        if (! Module::enabled('media')) {
            return null;
        }

        return app(MediaService::class)->attach($this, $file, $collection, $disk);
    }

    /** @return Collection<int, Media> */
    public function getMedia(string $collection = 'default'): Collection
    {
        if (! Module::enabled('media')) {
            return new Collection;
        }

        return $this->media()->where('collection', $collection)->with('conversions')->get();
    }

    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->getMedia($collection)->first();
    }

    /** URL of the first file in a collection (or of one of its conversions), null if none. */
    public function getFirstMediaUrl(string $collection = 'default', ?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia($collection);

        if ($media === null) {
            return null;
        }

        return ($conversion ? $media->conversion($conversion) : $media)->url();
    }

    /** Delete the files in one collection, or all of them. */
    public function clearMedia(?string $collection = null): void
    {
        if (! Module::enabled('media')) {
            return;
        }

        $this->media()
            ->when($collection !== null, fn ($query) => $query->where('collection', $collection))
            ->get()
            ->each->delete();
    }
}
