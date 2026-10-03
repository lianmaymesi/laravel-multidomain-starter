<?php

namespace App\Support\Modules;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled(string $module)
 * @method static array<int, string> names()
 * @method static array<int, string> enabledNames()
 * @method static string path(string $module, string $path = '')
 * @method static void routes(string $portal)
 * @method static void applyOverrides(array $overrides)
 * @method static bool defaultEnabled(string $module)
 * @method static bool isOverridden(string $module)
 * @method static void describe(string $module, array $meta)
 * @method static array info(string $module)
 * @method static void contribute(string $point, array $items)
 * @method static array<int, mixed> contributions(string $point)
 *
 * @see ModuleManager
 */
class Module extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleManager::class;
    }
}
