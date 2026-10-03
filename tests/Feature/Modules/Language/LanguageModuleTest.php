<?php

use App\Contracts\Languages;
use App\Models\User;
use App\Modules\Language\Http\Middleware\SetLocale;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Services\LanguageService;
use App\Support\Modules\Module;
use App\Support\NullLanguages;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\TranslationLoader\TranslationLoaders\Db;

uses(RefreshDatabase::class);

function seedTwoLanguages(): void
{
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_primary' => true, 'is_active' => true, 'order' => 1]);
    Language::create(['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => true, 'order' => 2]);
}

it('wires up everything while the language module is enabled', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
    seedTwoLanguages();

    expect(Module::enabled('language'))->toBeTrue()
        ->and(Route::has('backoffice.languages.index'))->toBeTrue()
        ->and(Route::has('backoffice.translations.index'))->toBeTrue()
        ->and(Route::getMiddlewareGroups()['web'])->toContain(SetLocale::class)
        ->and(app(Languages::class))->toBeInstanceOf(LanguageService::class)
        ->and(config('translation-loader.translation_loaders'))->toBe([Db::class])
        ->and(collect(Module::contributions('backoffice.nav'))->pluck('route'))->toContain('backoffice.languages.index');

    // The locale switcher shows on landing once a second language is active.
    $this->get(route('index'))->assertOk()->assertSee('Switch language');

    $this->actingAs(superAdminActor())
        ->get(route('backoffice.settings.index'))
        ->assertOk()
        ->assertSee('Language URL mode');

    // ...and users can pick a preferred language on their account settings.
    $user = User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('account.settings'))
        ->assertOk()
        ->assertSee('Preferred language');
});

it('breaks nothing when the language module is disabled', function () {
    $this->disableModules('language');
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();
    seedTwoLanguages();

    expect(Module::enabled('language'))->toBeFalse()
        ->and(Route::has('backoffice.languages.index'))->toBeFalse()
        ->and(Route::has('backoffice.translations.index'))->toBeFalse()
        ->and(Route::getMiddlewareGroups()['web'])->not->toContain(SetLocale::class)
        // Core falls back to a single LTR locale, and DB translation overrides are off.
        ->and(app(Languages::class))->toBeInstanceOf(NullLanguages::class)
        ->and(app(Languages::class)->isMultiLanguageEnabled())->toBeFalse()
        ->and(app(Languages::class)->activeCodes())->toBe([])
        ->and(config('translation-loader.translation_loaders'))->toBe([])
        // Schema and permissions survive so re-enabling needs no reseed.
        ->and(Schema::hasTable('languages'))->toBeTrue()
        ->and(Schema::hasTable('language_lines'))->toBeTrue()
        ->and(Permission::where('name', 'languages.view')->exists())->toBeTrue();

    // Public pages render with no switcher and no locale prefix, even with
    // two languages still sitting in the table.
    $this->get(route('index'))
        ->assertOk()
        ->assertDontSee('Switch language')
        ->assertSee('dir="ltr"', false);

    $this->get(route('auth.login'))->assertOk()->assertDontSee('Switch language');

    $actor = superAdminActor();

    $this->actingAs($actor)->get(backofficeUrl('languages'))->assertNotFound();
    $this->actingAs($actor)->get(backofficeUrl('translations'))->assertNotFound();

    // Backoffice renders without the nav links; Settings drops the language fields
    // but keeps the core ones.
    $this->actingAs($actor)->get(route('backoffice.dashboard'))
        ->assertOk()
        ->assertDontSee('/languages')
        ->assertDontSee('/translations');

    $this->actingAs($actor)->get(route('backoffice.settings.index'))
        ->assertOk()
        ->assertSee('Default timezone')
        ->assertDontSee('Language URL mode')
        ->assertDontSee('Google Translate API key');
});

it('hides the language choice on the account settings page while disabled', function () {
    $this->disableModules('language');
    $this->withoutVite();
    seedTwoLanguages();

    $user = User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('account.settings'))
        ->assertOk()
        ->assertDontSee('Preferred language');
});
