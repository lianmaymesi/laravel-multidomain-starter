<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Generates the image sizes in config('media.conversions') with Intervention
 * Image — once per library item, shared by every place it's used. Each size
 * is stored next to the original (".../conversions/{uuid}-{size}.{ext}",
 * same disk) as its own media row pointing back via conversion_of_id.
 *
 * Runs synchronously on upload. Move it into a queued job once upload
 * volume makes that worth it.
 */
class ImageConversionService
{
    /** @return Collection<int, Media> the generated sizes (empty if none apply) */
    public function generate(Media $media): Collection
    {
        $definitions = config('media.conversions', []);

        if ($definitions === [] || ! in_array($media->mime_type, config('media.convertible_mimes', []), true)) {
            return new Collection;
        }

        $disk = Storage::disk($media->disk);
        $source = $disk->get($media->path);
        $directory = dirname($media->path).'/conversions';
        $extension = pathinfo($media->path, PATHINFO_EXTENSION);
        $created = new Collection;

        foreach ($definitions as $name => $definition) {
            $width = $definition['width'] ?? null;
            $height = $definition['height'] ?? null;
            $crop = ($definition['fit'] ?? 'contain') === 'crop' && $width && $height;

            // Already fits: a copy would be the same image. conversion() falls back to the original.
            if (! $crop && $media->width() !== null
                && $media->width() <= ($width ?? PHP_INT_MAX) && $media->height() <= ($height ?? PHP_INT_MAX)) {
                continue;
            }

            $image = $this->manager()->decodeBinary($source);

            $crop
                ? $image->cover($width, $height)
                : $image->scaleDown($width, $height);

            // Same format as the original (AutoEncoder).
            $encoded = $image->encode();
            $path = "{$directory}/{$media->uuid}-{$name}".($extension ? ".{$extension}" : '');

            $disk->put($path, $encoded->toString());

            $created->push(Media::create([
                'uuid' => (string) Str::uuid(),
                'conversion_of_id' => $media->getKey(),
                'conversion' => $name,
                'disk' => $media->disk,
                'path' => $path,
                'name' => $media->name,
                'original_name' => $media->original_name,
                'mime_type' => $encoded->mediaType(),
                'size' => $encoded->size(),
                'metadata' => ['width' => $image->width(), 'height' => $image->height()],
                'uploaded_by' => $media->uploaded_by,
            ]));
        }

        return $created;
    }

    private function manager(): ImageManager
    {
        return new ImageManager(config('media.image_driver') === 'imagick' ? ImagickDriver::class : GdDriver::class);
    }
}
