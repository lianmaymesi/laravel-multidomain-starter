<?php

use App\Modules\Language\Models\Language;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('api');
    $this->seed(RolePermissionSeeder::class);

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_primary' => true, 'is_active' => true, 'order' => 1]);
    Language::create(['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_primary' => false, 'is_active' => false, 'order' => 2]);
});

function apiAs($user, array $abilities = ['languages:read', 'languages:write']): void
{
    Sanctum::actingAs($user, $abilities);
}

// ── Read ─────────────────────────────────────────────────────────────

it('lists languages in order, paginated, as resources', function () {
    apiAs(superAdminActor(), ['languages:read']);

    $this->getJson(route('api.v1.languages.index', ['per_page' => 1]))
        ->assertOk()
        ->assertJsonPath('data.0.code', 'en')
        ->assertJsonPath('data.0.is_primary', true)
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'native_name', 'direction', 'is_primary', 'is_active', 'order', 'created_at', 'updated_at']], 'links', 'meta'])
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('filters by active and caps the page size', function () {
    apiAs(superAdminActor(), ['languages:read']);

    $this->getJson(route('api.v1.languages.index', ['active' => 0]))->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'ar');
    $this->getJson(route('api.v1.languages.index', ['per_page' => 5000]))->assertJsonPath('meta.per_page', 100);
});

it('shows one language by code, and 404s an unknown one in JSON', function () {
    apiAs(superAdminActor(), ['languages:read']);

    $this->getJson(route('api.v1.languages.show', 'ar'))->assertOk()->assertJsonPath('data.direction', 'rtl');
    $this->getJson(route('api.v1.languages.show', 'xx'))->assertNotFound()->assertJsonStructure(['message']);
});

// ── Write ────────────────────────────────────────────────────────────

it('creates a language', function () {
    apiAs(superAdminActor());

    $this->postJson(route('api.v1.languages.store'), ['code' => 'FR', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr'])
        ->assertCreated()
        ->assertJsonPath('data.code', 'fr')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.order', 3);
});

it('validates new languages', function () {
    apiAs(superAdminActor());

    $this->postJson(route('api.v1.languages.store'), ['code' => 'en', 'name' => '', 'direction' => 'up'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'name', 'native_name', 'direction']);
});

it('updates a language partially, but never its code', function () {
    apiAs(superAdminActor());

    $this->patchJson(route('api.v1.languages.update', 'ar'), ['name' => 'Arabic (MSA)', 'is_active' => true])
        ->assertOk()
        ->assertJsonPath('data.name', 'Arabic (MSA)')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.native_name', 'العربية');

    $this->patchJson(route('api.v1.languages.update', 'ar'), ['code' => 'xx'])->assertJsonValidationErrors('code');
});

it('makes a language primary, activating it and demoting the old one', function () {
    apiAs(superAdminActor());

    $this->patchJson(route('api.v1.languages.update', 'ar'), ['is_primary' => true])
        ->assertOk()
        ->assertJsonPath('data.is_primary', true)
        ->assertJsonPath('data.is_active', true);

    expect(Language::where('code', 'en')->value('is_primary'))->toBeFalse();
});

it('keeps the primary language active and undeletable', function () {
    apiAs(superAdminActor());

    $this->patchJson(route('api.v1.languages.update', 'en'), ['is_active' => false])->assertJsonValidationErrors('is_active');
    $this->deleteJson(route('api.v1.languages.destroy', 'en'))->assertJsonValidationErrors('language');

    expect(Language::where('code', 'en')->exists())->toBeTrue();
});

it('deletes a language', function () {
    apiAs(superAdminActor());

    $this->deleteJson(route('api.v1.languages.destroy', 'ar'))->assertNoContent();

    expect(Language::where('code', 'ar')->exists())->toBeFalse();
});

// ── Authorization ────────────────────────────────────────────────────

it('needs languages:write to change anything', function () {
    apiAs(superAdminActor(), ['languages:read']);

    $this->postJson(route('api.v1.languages.store'), ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr'])->assertForbidden();
    $this->patchJson(route('api.v1.languages.update', 'ar'), ['name' => 'x'])->assertForbidden();
    $this->deleteJson(route('api.v1.languages.destroy', 'ar'))->assertForbidden();
});

it('never lets a token do more than its user may', function () {
    // Full abilities, but users without languages.* (Admin: those are
    // Super-Admin-only; plain staff: none at all).
    foreach ([adminActor(), staffUser()] as $user) {
        apiAs($user);
        $this->getJson(route('api.v1.languages.index'))->assertForbidden();
    }

    apiAs(staffUser());

    $this->getJson(route('api.v1.languages.index'))->assertForbidden();
    $this->postJson(route('api.v1.languages.store'), ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr'])->assertForbidden();
    $this->deleteJson(route('api.v1.languages.destroy', 'ar'))->assertForbidden();

    expect(Language::count())->toBe(2);
});

it('404s every language route while the module is off', function () {
    $this->disableModules('language')->enableModules('api');
    $this->seed(RolePermissionSeeder::class);
    apiAs(superAdminActor());

    $host = config('multidomain.sub_domains.api');

    $this->getJson("http://{$host}/v1/languages")->assertNotFound();
    $this->postJson("http://{$host}/v1/languages", [])->assertNotFound();

    // The rest of the API keeps working.
    apiAs(superAdminActor(), ['profile:read']);
    $this->getJson(route('api.v1.me'))->assertOk();
});
