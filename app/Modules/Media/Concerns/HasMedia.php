<?php

namespace App\Modules\Media\Concerns;

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaLibrary;
use Atrium\Core\Support\Modules\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Lets a model use media-library items, per named collection:
 *
 *   $post->addMedia($request->file('cover'), 'cover');    // upload into the library + use it
 *   $post->attachMedia($media, 'gallery');                // use an existing library item
 *   $post->syncMedia([4, 9, 2], 'gallery');               // exactly these, in this order
 *   $post->getFirstMediaUrl('cover', 'medium');
 *
 * Detaching (or deleting the model) only removes the reference — the file
 * stays in the library, like WordPress. Safe on a core model: while the
 * Media module is off every method is a no-op that behaves as if the model
 * has no media.
 */
trait HasMedia
{
    public static function bootHasMedia(): void
    {
        static::deleting(function ($model) {
            if (! Module::enabled('media')) {
                return;
            }

            // A soft delete can be undone — keep the references until it's final.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            app(MediaLibrary::class)->attachments($model)->delete();
        });
    }

    /** Library items this model uses (all collections), in order. Empty while the module is off. */
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'order'])
            ->withTimestamps()
            ->when(! Module::enabled('media'), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByPivot('order')
            ->orderBy('media.id');
    }

    /** @return Collection<int, Media> */
    public function getMedia(string $collection = 'default'): Collection
    {
        if (! Module::enabled('media')) {
            return new Collection;
        }

        return $this->media()->wherePivot('collection', $collection)->with('conversions')->get();
    }

    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->getMedia($collection)->first();
    }

    /** URL of the first item in a collection (or of one of its sizes), null if none. */
    public function getFirstMediaUrl(string $collection = 'default', ?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia($collection);

        return $media === null ? null : ($conversion ? $media->conversion($conversion) : $media)->url();
    }

    /**
     * Upload a file into the library (validated with the collection's rules)
     * and use it here. Returns null while the module is off.
     *
     * @throws ValidationException
     */
    public function addMedia(File $file, string $collection = 'default', ?User $uploader = null, ?string $disk = null): ?Media
    {
        if (! Module::enabled('media')) {
            return null;
        }

        $library = app(MediaLibrary::class);
        $media = $library->upload($file, $uploader ?? auth()->user(), $collection, $disk);
        $library->attach($this, $media, $collection);

        return $media;
    }

    /** @throws ValidationException */
    public function attachMedia(Media|int $media, string $collection = 'default'): void
    {
        if (Module::enabled('media')) {
            app(MediaLibrary::class)->attach($this, $media instanceof Media ? $media : Media::findOrFail($media), $collection);
        }
    }

    /**
     * Exactly these library items, in this order. Pass the acting user when
     * the ids came from a form, so files they may not see are rejected.
     *
     * @param  array<int, int>  $ids
     *
     * @throws ValidationException
     */
    public function syncMedia(array $ids, string $collection = 'default', ?User $actor = null): void
    {
        if (Module::enabled('media')) {
            app(MediaLibrary::class)->sync($this, $ids, $collection, $actor);
        }
    }

    public function detachMedia(Media|int $media, ?string $collection = null): void
    {
        if (Module::enabled('media')) {
            app(MediaLibrary::class)->detach($this, $media, $collection);
        }
    }

    /** Stop using everything in one collection, or in all of them. Files stay in the library. */
    public function clearMedia(?string $collection = null): void
    {
        if (Module::enabled('media')) {
            app(MediaLibrary::class)->attachments($this, $collection)->delete();
        }
    }
}
