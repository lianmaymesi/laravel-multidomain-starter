<?php

namespace Database\Seeders;

use App\Support\Modules\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,

            // Each module lists its own seeders (ModuleProvider::seeders()).
            ...Module::contributions('database.seeders'),
        ]);
    }
}
