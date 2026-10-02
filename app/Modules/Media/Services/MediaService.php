<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;

/**
 * Stores a file for a model: validate → put on disk → media row →
 * conversions. Call it through HasMedia::addMedia(), which adds the
 * module-enabled guard.
 */
class MediaService
{
    public function __construct(
        private MediaValidationService $validation,
        private ImageConversionService $conversions,
    ) {}

    /** @throws ValidationException */
    public function attach(Model $model, File $file, string $collection = 'default', ?string $disk = null): Media
    {
        $this->validation->validate($file, $collection);

        $config = $this->validation->collection($collection);
        // Explicit argument → the collection's disk → the default disk.
        $disk ??= ($config['disk'] ?? null) ?: config('media.default_disk', 'local');

        $uuid = (string) Str::uuid();
        $extension = $file->guessExtension() ?: ($file instanceof UploadedFile ? $file->getClientOriginalExtension() : $file->getExtension());
        $path = Storage::disk($disk)->putFileAs(self::directory($model, $collection), $file, $uuid.($extension ? ".{$extension}" : ''));

        // Disks are configured with throw => false, so a failed write is just false.
        if ($path === false) {
            throw new RuntimeException("Could not store the file on the [{$disk}] disk.");
        }

        try {
            $media = $model->morphMany(Media::class, 'mediable')->create([
                'uuid' => $uuid,
                'collection' => $collection,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'order' => (int) Media::query()
                    ->where('mediable_type', $model->getMorphClass())
                    ->where('mediable_id', $model->getKey())
                    ->where('collection', $collection)
                    ->max('order') + 1,
                'metadata' => self::imageMetadata($file->getRealPath()),
            ]);
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);

            throw $e;
        }

        $this->conversions->generate($media);

        // Single-file collections (avatar): the new file replaces the old ones,
        // removed only once the new one is safely stored.
        if (! empty($config['single'])) {
            Media::query()
                ->originals()
                ->where('mediable_type', $model->getMorphClass())
                ->where('mediable_id', $model->getKey())
                ->where('collection', $collection)
                ->whereKeyNot($media->getKey())
                ->get()
                ->each->delete();
        }

        return $media->load('conversions');
    }

    /** "{collection}/{model}/{id}", e.g. "avatar/user/42" — predictable, collision-free, easy to clean up. */
    public static function directory(Model $model, string $collection): string
    {
        return implode('/', [
            Str::slug($collection),
            Str::kebab(class_basename($model->getMorphClass())),
            $model->getKey(),
        ]);
    }

    /** @return array{width: int, height: int}|null */
    public static function imageMetadata(string|false $path): ?array
    {
        $size = $path ? @getimagesize($path) : false;

        return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
    }
}
