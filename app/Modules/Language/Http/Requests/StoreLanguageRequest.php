<?php

namespace App\Modules\Language\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('languages.create');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->code)) {
            $this->merge(['code' => strtolower($this->code)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Same rules as the backoffice Languages page.
        return [
            'code' => ['required', 'string', 'size:2', 'alpha', Rule::unique('languages', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'native_name' => ['required', 'string', 'max:255'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
