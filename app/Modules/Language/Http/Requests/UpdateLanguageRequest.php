<?php

namespace App\Modules\Language\Http\Requests;

use App\Modules\Language\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Partial update. The code is the language's identity (URLs, user
 * preferences, translations), so it can't be changed — add a new language
 * instead. Making a language primary also activates it.
 */
class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('languages.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'native_name' => ['sometimes', 'required', 'string', 'max:255'],
            'direction' => ['sometimes', Rule::in(['ltr', 'rtl'])],
            'is_active' => ['sometimes', 'boolean'],
            'is_primary' => ['sometimes', 'accepted'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'code' => ['prohibited'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Language $language */
                $language = $this->route('language');

                if ($language->is_primary && $this->has('is_active') && ! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('The primary language must stay active — set another language as primary first.'));
                }
            },
        ];
    }
}
