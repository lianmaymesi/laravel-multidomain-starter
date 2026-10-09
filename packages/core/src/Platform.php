<?php

namespace Atrium\Core;

use Atrium\Core\Models\User;
use InvalidArgumentException;

/**
 * Project-level extension points of atrium-php/core. Call these from a
 * service provider's register() in the project.
 */
final class Platform
{
    /** @var class-string<User>|null */
    private static ?string $userModel = null;

    /**
     * Use the project's own user model (extra columns, relations, module
     * traits). It must extend Atrium\Core\Models\User. Also points the
     * default auth provider at it.
     *
     * @param  class-string  $class
     */
    public static function useUserModel(string $class): void
    {
        if (! is_a($class, User::class, true)) {
            throw new InvalidArgumentException("[{$class}] must extend ".User::class.'.');
        }

        self::$userModel = $class;

        config(['auth.providers.users.model' => $class]);
    }

    /**
     * The user model core code creates, queries and relates to. Defaults to
     * the auth provider's model (config/auth.php), then the core base model.
     *
     * @return class-string<User>
     */
    public static function userModel(): string
    {
        return self::$userModel ?? config('auth.providers.users.model') ?? User::class;
    }
}
