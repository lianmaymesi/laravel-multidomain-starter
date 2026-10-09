<?php

/*
|--------------------------------------------------------------------------
| Module boundaries
|--------------------------------------------------------------------------
|
| The rules in app/Modules/README.md, enforced: core never reaches into a
| module, and modules never reach into each other. Talk across those lines
| through contracts (Atrium\Core\Contracts), events, or Module::contribute()
| extension points — that's what keeps every module removable.
|
*/

use Atrium\Core\Support\Modules\ModuleProvider;

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

// atrium-php/core is a package: it can't know the project it's installed in,
// so it never reaches into App\ (models included — the user model comes from
// config('auth.providers.users.model')).
arch('atrium core never imports project code')
    ->expect('Atrium\Core')
    ->not->toUse('App');

// No vendor is special-cased: first-party modules are found the same way as
// anyone's, through the "atrium-module" package type.
arch('atrium core never names a first-party module package')
    ->expect('Atrium\Core')
    ->not->toUse(['Atrium\Activity', 'Atrium\Api', 'Atrium\Billing', 'Atrium\Currency', 'Atrium\Language', 'Atrium\Maintenance', 'Atrium\Media']);
