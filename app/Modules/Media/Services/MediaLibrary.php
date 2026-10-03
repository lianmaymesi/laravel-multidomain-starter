<?php

namespace App\Modules\Media\Services;

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Models\MediaAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;

/**
 * The library itself: upload files into it, and attach/detach library items
 * to models. Model code goes through HasMedia, which adds the module guard.
 */
class MediaLibrary
{
    public function __construct(
        private MediaValidationService $validation,
        private ImageConversionService $conversions,
    ) {}

    /**
     * Validate (against the collection's rules if one is given, else the
     * library's), store as "{Y}/{m}/{uuid}.{ext}" and generate image sizes.
     *
     * @throws ValidationException
     */
    public function upload(File $file, ?User $uploader = null, ?string $collection = null, ?string $disk = null): Media
    {
        $this->validation->validate($file, $collection);

        // Explicit argument → the collection's disk → the default disk.
        $disk ??= ($this->validation->collection($collection)['disk'] ?? null) ?: config('media.default_disk', 'local');

        $uuid = (string) Str::uuid();
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $extension = $file->guessExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);
        $path = Storage::disk($disk)->putFileAs(now()->format('Y/m'), $file, $uuid.($extension ? ".{$extension}" : ''));

        // Disks are configured with throw => false, so a failed write is just false.
        if ($path === false) {
            throw new RuntimeException("Could not store the file on the [{$disk}] disk.");
        }

        try {
            $size = ($real = $file->getRealPath()) ? @getimagesize($real) : false;

            $media = Media::create([
                'uuid' => $uuid,
                'disk' => $disk,
                'path' => $path,
                'name' => Str::limit(pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName, 250, ''),
                'original_name' => $originalName,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'metadata' => $size ? ['width' => $size[0], 'height' => $size[1]] : null,
                'uploaded_by' => $uploader?->getKey(),
            ]);
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);

            throw $e;
        }

        $this->conversions->generate($media);

        return $media->load('conversions');
    }

    /**
     * Use a library item in a model's collection (validated against the
     * collection's rules). Single-item collections drop what was there.
     *
     * @throws ValidationException
     */
    public function attach(Model $model, Media $media, string $collection = 'default'): void
    {
        $this->validation->validateExisting($media, $collection);

        DB::transaction(function () use ($model, $media, $collection) {
            if ($this->validation->collection($collection)['single'] ?? false) {
                $this->attachments($model, $collection)->where('media_id', '!=', $media->getKey())->delete();
            }

            MediaAttachment::firstOrCreate(
                [...$this->key($model, $collection), 'media_id' => $media->getKey()],
                ['order' => (int) $this->attachments($model, $collection)->max('order') + 1],
            );
        });
    }

    /**
     * Make a collection hold exactly these items, in this order. Pass the
     * acting user when the ids came from a form: ids that user may not see
     * (Media::visibleTo) are rejected.
     *
     * @param  array<int, int>  $ids
     *
     * @throws ValidationException
     */
    public function sync(Model $model, array $ids, string $collection = 'default', ?User $actor = null): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $media = Media::query()
            ->originals()
            ->when($actor !== null, fn ($query) => $query->visibleTo($actor))
            ->whereKey($ids)
            ->get()
            ->keyBy('id');

        if ($media->count() !== count($ids)) {
            throw ValidationException::withMessages(['media' => __('One or more selected files are not available.')]);
        }

        foreach ($media as $item) {
            $this->validation->validateExisting($item, $collection);
        }

        if (($this->validation->collection($collection)['single'] ?? false) && count($ids) > 1) {
            throw ValidationException::withMessages(['media' => __('Only one file can be used here.')]);
        }

        DB::transaction(function () use ($model, $ids, $media, $collection) {
            $this->attachments($model, $collection)->whereNotIn('media_id', $ids)->delete();

            foreach (array_values(array_filter($ids, fn (int $id) => $media->has($id))) as $position => $id) {
                MediaAttachment::updateOrCreate([...$this->key($model, $collection), 'media_id' => $id], ['order' => $position + 1]);
            }
        });
    }

    /** Stop using an item (in one collection, or everywhere on this model). The file stays in the library. */
    public function detach(Model $model, Media|int $media, ?string $collection = null): void
    {
        $this->attachments($model, $collection)
            ->where('media_id', $media instanceof Media ? $media->getKey() : $media)
            ->delete();
    }

    /** @return array{mediable_type: string, mediable_id: mixed, collection: string} */
    private function key(Model $model, string $collection): array
    {
        return ['mediable_type' => $model->getMorphClass(), 'mediable_id' => $model->getKey(), 'collection' => $collection];
    }

    /** @return Builder<MediaAttachment> */
    public function attachments(Model $model, ?string $collection = null)
    {
        return MediaAttachment::query()
            ->where('mediable_type', $model->getMorphClass())
            ->where('mediable_id', $model->getKey())
            ->when($collection !== null, fn ($query) => $query->where('collection', $collection));
    }
}
