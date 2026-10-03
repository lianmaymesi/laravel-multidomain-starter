<?php

namespace App\Features;

use App\Models\User;
use App\Support\Features\UserFlag;
use Illuminate\Support\Lottery;

/**
 * Demo flag: a "Hello, World" card on the app dashboard that shows most of
 * Pennant in one place. Every user goes through these rules, top to bottom:
 *
 *   1. Kill switch        PENNANT_KILLED=hello-world-advanced  → off for everyone
 *   2. New users          signed up in the last 7 days        → always "wave"
 *   3. Stored value       decided earlier (or set by an admin) → that value
 *   4. Gradual rollout    50% of users are let in, once        → stored, sticks
 *   5. A/B variant        those let in get classic/wave/rocket, picked from
 *                         their id, so the same user always sees the same one
 *
 * 1–2 live in before(): Pennant runs it ahead of the stored value and never
 * stores its answer, so they apply instantly and undo themselves. 4–5 live
 * in initial(), which only runs the first time a user is checked.
 *
 * The value is a variant name, not just true/false — read it with
 * Flags::value(). "All on" on the Feature Flags page stores plain true,
 * which the dashboard shows as "classic".
 *
 * Delete it (and its @flag block in the app dashboard) once you have your own.
 */
class HelloWorldAdvanced extends UserFlag
{
    public const VARIANTS = ['classic', 'wave', 'rocket'];

    public string $name = 'hello-world-advanced';

    public function label(): string
    {
        return 'Hello World (advanced demo)';
    }

    public function description(): string
    {
        return 'A/B-tested greeting on the app dashboard: 50% rollout, three variants, always on for new users, kill switch via PENNANT_KILLED.';
    }

    public function before(mixed $scope): mixed
    {
        if (($value = parent::before($scope)) !== null) {
            return $value;
        }

        if ($scope instanceof User && $scope->created_at?->gt(now()->subDays(7))) {
            return 'wave';
        }

        return null;
    }

    protected function initial(?User $user): mixed
    {
        if ($user === null) {
            return false;
        }

        return Lottery::odds(1, 2)
            ->winner(fn () => self::VARIANTS[$user->getKey() % count(self::VARIANTS)])
            ->loser(fn () => false);
    }
}
