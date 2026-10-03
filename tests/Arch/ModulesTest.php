<?php

/*
|--------------------------------------------------------------------------
| Module boundaries
|--------------------------------------------------------------------------
|
| The rules in app/Modules/README.md, enforced: core never reaches into a
| module, and modules never reach into each other. Talk across those lines
| through contracts (App\Contracts), events, or Module::contribute()
| extension points — that's what keeps every module removable.
|
*/

use App\Support\Modules\ModuleProvider;

/** @return array<int, string> e.g. ['Activity', 'Currency', ...] */
function moduleNames(): array
{
    return array_map('basename', glob(dirname(__DIR__, 2).'/app/Modules/*', GLOB_ONLYDIR));
}

// One test per core namespace — every top-level folder in app/ except Modules.
// (Deliberately not expect([...]): an array target silently passed when one
// namespace in it broke the rule.)
foreach (glob(dirname(__DIR__, 2).'/app/*', GLOB_ONLYDIR) as $dir) {
    $namespace = 'App\\'.basename($dir);

    if ($namespace === 'App\\Modules') {
        continue;
    }

    arch("core {$namespace} never imports a module")
        ->expect($namespace)
        ->not->toUse('App\Modules')
        // The one sanctioned exception: Media's HasMedia trait is built to sit
        // on core models (User) — every method no-ops while the module is off.
        ->ignoring('App\Modules\Media\Concerns\HasMedia');
}

foreach (moduleNames() as $module) {
    $others = array_map(
        fn (string $name) => "App\\Modules\\{$name}",
        array_values(array_diff(moduleNames(), [$module])),
    );

    if ($others === []) {
        continue;
    }

    arch("the {$module} module does not import other modules")
        ->expect("App\\Modules\\{$module}")
        ->not->toUse($others);

    arch("the {$module} module has a provider extending ModuleProvider")
        ->expect("App\\Modules\\{$module}\\{$module}ServiceProvider")
        ->toExtend(ModuleProvider::class);
}
