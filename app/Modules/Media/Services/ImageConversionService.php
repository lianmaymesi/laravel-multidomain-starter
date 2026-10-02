<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\Media;
use App\Support\Modules\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Generates the variants configured under media.collections.{collection}
 * .conversions with Intervention Image. Each variant is stored next to the
 * original (".../conversions/{uuid}-{name}.{ext}", same disk) as its own
 * media row pointing back via conversion_of_id.
 *
 * Runs synchronously on upload. Move it into a queued job once upload
 * volume makes that worth it.
 */
class ImageConversionService
{
    /** @return Collection<int, Media> the generated conversions (empty if none apply) */
    public function generate(Media $media): Collection
    {
        $definitions = config("media.collections.{$media->collection}.conversions", []);

        if (! Module::enabled('media')
            || $definitions === []
            || ! in_array($media->mime_type, config('media.convertible_mimes', []), true)) {
            return new Collection;
        }

        $disk = Storage::disk($media->disk);
        $source = $disk->get($media->path);
        $directory = dirname($media->path).'/conversions';
        $created = new Collection;

        foreach ($definitions as $name => $definition) {
            $image = $this->manager()->decodeBinary($source);

            $width = $definition['width'] ?? null;
            $height = $definition['height'] ?? null;

            ($definition['fit'] ?? 'contain') === 'crop' && $width && $height
                ? $image->cover($width, $height)
                : $image->scaleDown($width, $height);

            // Same format as the original (AutoEncoder).
            $encoded = $image->encode();
            $extension = pathinfo($media->path, PATHINFO_EXTENSION);
            $path = "{$directory}/{$media->uuid}-{$name}".($extension ? ".{$extension}" : '');

            $disk->put($path, $encoded->toString());

            $created->push(Media::create([
                'uuid' => (string) str()->uuid(),
                'mediable_type' => $media->mediable_type,
                'mediable_id' => $media->mediable_id,
                'collection' => "{$media->collection}:{$name}",
                'conversion_of_id' => $media->getKey(),
                'disk' => $media->disk,
                'path' => $path,
                'original_name' => $media->original_name,
                'mime_type' => $encoded->mediaType(),
                'size' => $encoded->size(),
                'metadata' => ['width' => $image->width(), 'height' => $image->height()],
            ]));
        }

        return $created;
    }

    private function manager(): ImageManager
    {
        return new ImageManager(config('media.image_driver') === 'imagick' ? ImagickDriver::class : GdDriver::class);
    }
}
