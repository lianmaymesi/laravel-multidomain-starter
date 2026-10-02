<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Rules from config('media'): the library's own limits, narrowed per
 * collection. Checked for new uploads (rules()/validate()) and when an
 * existing library item is picked for a collection (validateExisting()).
 */
class MediaValidationService
{
    /**
     * Library rules merged with a collection's (unknown collections use
     * 'default'; null = library rules only).
     *
     * @return array{mimes: array<int, string>, max_size: int, dimensions?: array<string, int>, single?: bool, disk?: ?string}
     */
    public function collection(?string $collection): array
    {
        $overrides = $collection === null
            ? []
            : config("media.collections.{$collection}") ?? config('media.collections.default', []);

        return array_merge(config('media.library', ['mimes' => [], 'max_size' => 10240]), array_filter($overrides, fn ($value) => $value !== null));
    }

    /** @return array<int, mixed> */
    public function rules(?string $collection = null): array
    {
        $config = $this->collection($collection);

        $rules = ['required', 'file'];

        if (! empty($config['mimes'])) {
            $rules[] = 'mimetypes:'.implode(',', $config['mimes']);
        }

        if (! empty($config['max_size'])) {
            $rules[] = 'max:'.(int) $config['max_size'];
        }

        if (! empty($config['dimensions'])) {
            $rules[] = Rule::dimensions($config['dimensions']);
        }

        return $rules;
    }

    /** @throws ValidationException */
    public function validate(File $file, ?string $collection = null, string $attribute = 'file'): void
    {
        Validator::make([$attribute => $file], [$attribute => $this->rules($collection)])->validate();
    }

    /** Why a library item can't go into a collection, or null if it can. */
    public function rejectionFor(Media $media, string $collection): ?string
    {
        $config = $this->collection($collection);

        if (! empty($config['mimes']) && ! in_array($media->mime_type, $config['mimes'], true)) {
            return __(':name is not an accepted file type here.', ['name' => $media->name]);
        }

        if (! empty($config['max_size']) && $media->size > $config['max_size'] * 1024) {
            return __(':name is larger than :size.', ['name' => $media->name, 'size' => Number::fileSize($config['max_size'] * 1024)]);
        }

        $dimensions = $config['dimensions'] ?? [];
        $width = $media->width();
        $height = $media->height();

        if ($dimensions !== [] && ($width === null
            || $width < ($dimensions['min_width'] ?? 0) || $height < ($dimensions['min_height'] ?? 0)
            || $width > ($dimensions['max_width'] ?? PHP_INT_MAX) || $height > ($dimensions['max_height'] ?? PHP_INT_MAX))) {
            return __(':name does not have the required image dimensions.', ['name' => $media->name]);
        }

        return null;
    }

    /** @throws ValidationException */
    public function validateExisting(Media $media, string $collection, string $attribute = 'media'): void
    {
        if (($message = $this->rejectionFor($media, $collection)) !== null) {
            throw ValidationException::withMessages([$attribute => $message]);
        }
    }
}
