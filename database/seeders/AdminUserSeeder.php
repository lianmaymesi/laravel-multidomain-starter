<?php

namespace Database\Seeders;

use App\Models\User;
use Atrium\Core\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The Super Admin account, from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD.
 *
 * - No ADMIN_PASSWORD: a strong random one is generated and printed once —
 *   there is no shared default password to forget about.
 * - Safe to re-run: an existing admin keeps their password unless
 *   ADMIN_PASSWORD is set, so a re-seed never undoes a password change.
 */
class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Set when this run generated a password, so app:setup can show it. */
    public static ?string $generatedPassword = null;

    public function run(): void
    {
        static::$generatedPassword = null;

        $email = config('multidomain.setup.admin_email');
        $password = config('multidomain.setup.admin_password');

        $admin = User::where('email', $email)->first();

        if ($admin === null) {
            $password ??= static::$generatedPassword = Str::password(20, symbols: false);

            $admin = User::create([
                'name' => config('multidomain.setup.admin_name'),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        } elseif ($password !== null) {
            $admin->forceFill(['password' => Hash::make($password)]);
        }

        $admin->forceFill([
            'privilege' => 'staff',
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'phone_verified_at' => $admin->phone_verified_at ?? now(),
        ])->save();

        $admin->syncRoles([Role::where('slug', Role::SUPER_ADMIN)->firstOrFail()]);

        $this->command?->info("Super Admin ready: {$admin->email}");

        if (static::$generatedPassword !== null) {
            $this->command?->warn('Generated password (shown once — change it after signing in): '.static::$generatedPassword);
        }
    }
}
