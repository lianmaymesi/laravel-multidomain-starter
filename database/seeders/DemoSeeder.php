<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Modules\Currency\Models\Currency;
use App\Modules\Language\Models\Language;
use Atrium\Core\Support\Modules\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo data for trying Atrium — never for production (app:setup
 * refuses it there). One account per kind of user, so every portal has
 * someone to sign in as, plus a little data for enabled modules.
 *
 * All demo accounts share one password: DEMO_PASSWORD, or a generated one
 * printed by app:setup. Safe to re-run: existing accounts keep their password
 * unless DEMO_PASSWORD is set.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** email => [name, privilege, role slug or null, portal it signs into] */
    public const ACCOUNTS = [
        'demo-admin@example.com' => ['Demo Admin', 'staff', Role::ADMIN, 'backoffice'],
        'demo-staff@example.com' => ['Demo Staff', 'staff', null, 'backoffice'],
        'demo-user@example.com' => ['Demo User', 'user', null, 'app'],
    ];

    /** Set when this run generated the shared password, so app:setup can show it. */
    public static ?string $generatedPassword = null;

    public function run(): void
    {
        static::$generatedPassword = null;
        $password = config('multidomain.setup.demo_password');

        foreach (self::ACCOUNTS as $email => [$name, $privilege, $role]) {
            $user = User::where('email', $email)->first();

            if ($user === null) {
                $password ??= static::$generatedPassword = Str::password(16, symbols: false);
                $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
            } elseif (config('multidomain.setup.demo_password')) {
                $user->forceFill(['password' => Hash::make($password)]);
            }

            $user->forceFill([
                'privilege' => $privilege,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ])->save();

            $user->syncRoles($role ? [Role::where('slug', $role)->firstOrFail()] : []);
        }

        $this->seedModules();

        $this->command?->info('Demo accounts ready: '.implode(', ', array_keys(self::ACCOUNTS)));
    }

    /** A little data per enabled module; a disabled module is skipped. */
    private function seedModules(): void
    {
        if (Module::enabled('currency')) {
            // Something to switch between besides the primary currency.
            Currency::whereIn('code', ['EUR', 'GBP'])->update(['is_active' => true]);
        }

        if (Module::enabled('language')) {
            // Inactive until switched on from Backoffice → Languages — shows RTL.
            Language::firstOrCreate(['code' => 'ar'], [
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_primary' => false,
                'is_active' => false,
                'order' => (int) Language::max('order') + 1,
            ]);
        }
    }
}
