<?php

namespace Atrium\Core\Support\Features;

/**
 * A flag decided per portal — "is the new picker on in the app portal?".
 * Check it with Flags::active(MyFlag::class) or @flag(MyFlag::class), which
 * pass the current request's portal.
 */
abstract class PortalFlag extends FeatureFlag
{
    /**
     * Initial value for a portal nothing is stored for yet. Return a Lottery
     * to roll out to a share of portals.
     */
    abstract protected function initial(Portal $portal): mixed;

    /**
     * Portals the flag applies to, listed on the Feature Flags page. Defaults
     * to every portal in config/multidomain.php.
     *
     * @return array<int, string>
     */
    public function portals(): array
    {
        return Portal::names();
    }

    final public function scope(): string
    {
        return 'portal';
    }

    final public function resolve(mixed $scope): mixed
    {
        return $scope instanceof Portal && in_array($scope->name, $this->portals(), true)
            ? $this->initial($scope)
            : false;
    }
}
