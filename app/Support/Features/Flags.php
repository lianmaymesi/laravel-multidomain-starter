<?php

namespace App\Support\Features;

use App\Models\User;
use Laravel\Pennant\Feature;

/**
 * Checks a flag against the right scope for it — the current portal for a
 * PortalFlag, the signed-in user for a UserFlag — so call sites never pick
 * a scope by hand:
 *
 *   Flags::active(WhatsNewCard::class)
 *
 * In Blade, the flag directive (registered in FeatureFlagsServiceProvider)
 * does the same.
 *
 * For anything else (a specific portal or user, setting values) use
 * Pennant directly: Feature::for(Portal::named('app'))->activate(...).
 */
class Flags
{
    /**
     * Unknown flags (or ones that aren't FeatureFlag classes) count as off,
     * like an unknown module.
     */
    public static function active(string $flag, ?User $user = null): bool
    {
        $instance = static::find($flag);

        if ($instance === null) {
            return false;
        }

        return Feature::for(static::scopeFor($instance, $user))->active($instance->name);
    }

    public static function scopeFor(FeatureFlag $flag, ?User $user = null): Portal|User|null
    {
        return $flag instanceof PortalFlag
            ? Portal::current()
            : $user ?? auth()->user();
    }

    /**
     * Every defined flag class (app/Features, discovered at boot), keyed by name.
     *
     * @return array<string, FeatureFlag>
     */
    public static function all(): array
    {
        return collect(Feature::nameMap())
            ->filter(fn ($implementation) => is_string($implementation) && is_subclass_of($implementation, FeatureFlag::class))
            ->map(fn (string $class) => app($class))
            ->all();
    }

    /** Look a flag up by its stored name or its class name. */
    public static function find(string $flag): ?FeatureFlag
    {
        $flags = static::all();

        return $flags[$flag] ?? collect($flags)->first(fn (FeatureFlag $instance) => $instance::class === $flag);
    }
}
