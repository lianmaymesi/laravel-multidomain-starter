<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'privilege' => 'staff',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $admin->syncRoles([Role::where('slug', Role::SUPER_ADMIN)->firstOrFail()]);

        $this->command?->info("Admin user ready: {$admin->email}");
    }
}
