<?php

namespace App\Support\Features;

use App\Models\User;

/**
 * A flag decided per user — "is this user in the beta?". Check it with
 * Flags::active(MyFlag::class) or @flag(MyFlag::class), which pass the
 * signed-in user (null for a guest).
 */
abstract class UserFlag extends FeatureFlag
{
    /**
     * Initial value for a user nothing is stored for yet. Return a Lottery
     * for a percentage rollout — each user's result is stored, so it sticks.
     */
    abstract protected function initial(?User $user): mixed;

    final public function scope(): string
    {
        return 'user';
    }

    final public function resolve(mixed $scope): mixed
    {
        return $scope === null || $scope instanceof User
            ? $this->initial($scope)
            : false;
    }
}
