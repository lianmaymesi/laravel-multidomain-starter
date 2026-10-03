<?php

/*
|--------------------------------------------------------------------------
| Code hygiene
|--------------------------------------------------------------------------
|
| Cheap rules that catch leftovers and keep folders meaning what they say.
|
*/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\ServiceProvider;

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

// env() returns null once config is cached — read config() instead.
arch('env() is only read in config files')
    ->expect('env')
    ->not->toBeUsedIn(['App', 'Database\Seeders'])
    // Moved to config('multidomain.setup.*') on feat/tenant-seeding — drop
    // this exception once that branch is merged.
    ->ignoring('Database\Seeders\AdminUserSeeder');

arch('contracts are interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();

arch('concerns are traits')
    ->expect('App\Concerns')
    ->toBeTraits();

arch('core models are Eloquent models')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class);

arch('providers are service providers')
    ->expect('App\Providers')
    ->toExtend(ServiceProvider::class);

arch('seeders are seeders')
    ->expect('Database\Seeders')
    ->toExtend(Seeder::class);

arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();
