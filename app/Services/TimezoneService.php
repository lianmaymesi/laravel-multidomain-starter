<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use DateTimeZone;

class TimezoneService
{
    /**
     * @return array<int, string>
     */
    public function identifiers(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    public function isValid(string $timezone): bool
    {
        return in_array($timezone, $this->identifiers(), true);
    }

    public function default(): string
    {
        return AppSetting::defaultTimezone();
    }

    public function current(?User $user = null): string
    {
        $user ??= auth()->user();

        return $user?->timezone ?? $this->default();
    }
}
