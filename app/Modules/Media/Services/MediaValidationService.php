<?php

namespace App\Modules\Media\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Upload rules per collection, from config('media.collections'). Used both
 * by MediaService (always, server-side) and by forms that want the same
 * rules for their own field-level error messages.
 */
class MediaValidationService
{
    /**
     * A collection's settings; collections not configured use 'default'.
     *
     * @return array{disk?: ?string, mimes?: array<int, string>, max_size?: int, dimensions?: array<string, int>, single?: bool, conversions?: array<string, array<string, mixed>>}
     */
    public function collection(string $collection): array
    {
        return config("media.collections.{$collection}")
            ?? config('media.collections.default', []);
    }

    /** @return array<int, mixed> */
    public function rules(string $collection): array
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
    public function validate(File $file, string $collection, string $attribute = 'file'): void
    {
        Validator::make([$attribute => $file], [$attribute => $this->rules($collection)])->validate();
    }
}
