<?php

use App\Models\User;
use App\Modules\Currency\Models\Currency;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Seeders\LanguageSeeder;
use Atrium\Core\Models\Role;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Independent of whatever the local .env says.
beforeEach(function () {
    config(['multidomain.setup' => [
        'admin_name' => 'Admin',
        'admin_email' => 'admin@example.com',
        'admin_password' => null,
        'demo_password' => null,
    ]]);
});

// ── Super Admin ──────────────────────────────────────────────────────

it('creates the Super Admin with a generated password when ADMIN_PASSWORD is empty', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->sole();

    expect(AdminUserSeeder::$generatedPassword)->toHaveLength(20)->toMatch('/^[A-Za-z0-9]+$/')
        ->and(Hash::check(AdminUserSeeder::$generatedPassword, $admin->password))->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeFalse()
        ->and($admin->hasRoleSlug(Role::SUPER_ADMIN))->toBeTrue()
        ->and($admin->privilege)->toBe('staff');
});

it('never resets an existing admin password on re-seed unless ADMIN_PASSWORD is set', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@example.com')->sole();
    $admin->forceFill(['password' => Hash::make('Changed-By-Me-2026')])->save();

    $this->seed(AdminUserSeeder::class);
    expect(Hash::check('Changed-By-Me-2026', $admin->fresh()->password))->toBeTrue()
        ->and(AdminUserSeeder::$generatedPassword)->toBeNull();

    config(['multidomain.setup.admin_password' => 'From-Env-2026']);
    $this->seed(AdminUserSeeder::class);
    expect(Hash::check('From-Env-2026', $admin->fresh()->password))->toBeTrue();
});

// ── Module defaults ──────────────────────────────────────────────────

it('creates the primary language from APP_LOCALE once', function () {
    config(['app.locale' => 'en']);

    $this->artisan('db:seed', ['--class' => LanguageSeeder::class])->assertSuccessful();

    $language = Language::sole();
    expect($language->code)->toBe('en')
        ->and($language->name)->toBe('English')
        ->and($language->is_primary)->toBeTrue();

    // An admin's later setup is never touched.
    $language->update(['name' => 'British English']);
    $this->artisan('db:seed', ['--class' => LanguageSeeder::class])->assertSuccessful();
    expect(Language::sole()->name)->toBe('British English');
});

it('marks right-to-left primary languages as rtl', function () {
    config(['app.locale' => 'ar_SA']);

    $this->artisan('db:seed', ['--class' => LanguageSeeder::class])->assertSuccessful();

    expect(Language::sole())->code->toBe('ar')->direction->toBe('rtl');
});

// ── app:setup ────────────────────────────────────────────────────────

it('sets everything up in one command and prints where to sign in', function () {
    $this->artisan('app:setup')
        ->expectsOutputToContain('Ready.')
        ->expectsOutputToContain(route('auth.login'))
        ->expectsOutputToContain(route('backoffice.dashboard'))
        ->expectsOutputToContain('admin@example.com')
        ->expectsOutputToContain('generated — shown once')
        ->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->exists())->toBeTrue()
        ->and(Role::where('slug', Role::SUPER_ADMIN)->exists())->toBeTrue()
        ->and(Currency::where('is_primary', true)->value('code'))->toBe('USD')
        ->and(Language::where('is_primary', true)->exists())->toBeTrue();
});

it('is safe to run twice', function () {
    $this->artisan('app:setup')->assertSuccessful();
    $this->artisan('app:setup')->expectsOutputToContain('unchanged')->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->count())->toBe(1)
        ->and(Language::count())->toBe(1);
});

it('creates demo accounts for every portal with --demo', function () {
    $this->artisan('app:setup', ['--demo' => true])
        ->expectsOutputToContain('demo-user@example.com')
        ->assertSuccessful();

    $password = DemoSeeder::$generatedPassword;

    expect(User::where('email', 'demo-admin@example.com')->sole()->hasRoleSlug(Role::ADMIN))->toBeTrue()
        ->and(User::where('email', 'demo-staff@example.com')->sole()->privilege)->toBe('staff')
        ->and(User::where('email', 'demo-user@example.com')->sole()->privilege)->toBe('user')
        ->and(Hash::check($password, User::where('email', 'demo-user@example.com')->sole()->password))->toBeTrue()
        ->and(Currency::where('is_active', true)->pluck('code')->sort()->values()->all())->toBe(['EUR', 'GBP', 'USD'])
        ->and(Language::where('code', 'ar')->sole()->is_active)->toBeFalse();
});

it('lets demo accounts sign in to their portal', function () {
    config(['multidomain.setup.demo_password' => 'Demo-Pass-2026']);
    $this->artisan('app:setup', ['--demo' => true])->assertSuccessful();

    $this->actingAs(User::where('email', 'demo-user@example.com')->sole())
        ->get(route('app.dashboard'))->assertOk();

    $this->actingAs(User::where('email', 'demo-admin@example.com')->sole())
        ->get(route('backoffice.dashboard'))->assertOk();
});

it('skips demo data for disabled modules', function () {
    $this->disableModules('currency', 'language');

    $this->artisan('app:setup', ['--demo' => true])->assertSuccessful();

    expect(User::where('email', 'demo-user@example.com')->exists())->toBeTrue()
        ->and(Currency::where('is_active', true)->pluck('code')->all())->toBe(['USD'])
        ->and(Language::where('code', 'ar')->exists())->toBeFalse();
});

it('refuses demo accounts in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('app:setup', ['--demo' => true, '--force' => true])
        ->expectsOutputToContain('never created in production')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('asks before wiping the database with --fresh', function () {
    $this->artisan('app:setup')->assertSuccessful();
    User::factory()->create(['email' => 'keep-me@example.com']);

    $this->artisan('app:setup', ['--fresh' => true])
        ->expectsConfirmation('Drop ALL tables and data and start over?', 'no')
        ->assertFailed();

    expect(User::where('email', 'keep-me@example.com')->exists())->toBeTrue();
});
