<?php

namespace App\Support\Modules;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled(string $module)
 * @method static array<int, string> names()
 * @method static array<int, string> enabledNames()
 * @method static string path(string $module, string $path = '')
 * @method static void routes(string $portal)
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
