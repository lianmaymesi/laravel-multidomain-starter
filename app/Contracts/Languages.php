<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * What core code (layouts, portal resolution, route registration, the account
 * settings page) needs from the multi-language feature. Bound to
 * NullLanguages by default — one language, left-to-right, no locale URL
 * prefix — and rebound to the database-backed service by the Language module
 * while it is enabled.
 */
interface Languages
{
    /**
     * Active languages, each with `code`, `name`, `native_name`, `direction`.
     *
     * @return Collection<int, object>
     */
    public function active(): Collection;

    /** @return array<int, string> */
    public function activeCodes(): array;

    /** Whether there is more than one language to choose between. */
    public function isMultiLanguageEnabled(): bool;

    public function primaryCode(): string;

    public function isValidCode(string $code): bool;

    /** "ltr" or "rtl" for the current locale. */
    public function currentDirection(): string;
}
