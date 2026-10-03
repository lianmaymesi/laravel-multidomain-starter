<?php

namespace App\Support;

use App\Contracts\Languages;
use Illuminate\Support\Collection;

/**
 * Fallback while the Language module is off: the app runs on
 * config('app.locale') alone — no switcher, no locale URL prefix, no
 * per-user language choice, left-to-right.
 */
class NullLanguages implements Languages
{
    public function active(): Collection
    {
        return collect();
    }

    public function activeCodes(): array
    {
        return [];
    }

    public function isMultiLanguageEnabled(): bool
    {
        return false;
    }

    public function primaryCode(): string
    {
        return config('app.locale');
    }

    public function isValidCode(string $code): bool
    {
        return $code === $this->primaryCode();
    }

    public function currentDirection(): string
    {
        return 'ltr';
    }
}
