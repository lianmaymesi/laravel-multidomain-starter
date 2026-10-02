<?php

namespace App\Support\Features;

/**
 * Base for every flag class in app/Features. Extend PortalFlag or UserFlag,
 * not this. Pennant stores the flag under $name; the label/description show
 * on the backoffice Feature Flags page.
 *
 * resolve() is final and checks the scope type first, so asking a portal
 * flag about a user (e.g. a bare @feature, whose default scope is the
 * signed-in user) just answers false instead of throwing.
 */
abstract class FeatureFlag
{
    /** Stored flag name, kebab-case, e.g. "whats-new-card". */
    public string $name;

    abstract public function label(): string;

    public function description(): string
    {
        return '';
    }

    /** "portal" or "user" — which scope the flag is decided per. */
    abstract public function scope(): string;

    /** Value for a scope that has nothing stored yet. */
    abstract public function resolve(mixed $scope): mixed;
}
