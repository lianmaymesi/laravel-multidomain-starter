<?php

use Atrium\Core\Support\Features\Portal;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Tests\Fixtures\Features\BetaDashboard;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('is open to Super Admin and Admin, not other staff', function () {
    Livewire::actingAs(superAdminActor())->test('pages::backoffice.feature-flags')->assertOk();
    Livewire::actingAs(adminActor())->test('pages::backoffice.feature-flags')->assertOk();
    Livewire::actingAs(staffUser())->test('pages::backoffice.feature-flags')->assertStatus(403);
});

it('links to the page from the backoffice sidebar', function () {
    $this->actingAs(adminActor())
        ->get(route('backoffice.dashboard'))
        ->assertSee(route('backoffice.feature-flags.index'), false);

    $this->actingAs(staffUser())
        ->get(route('backoffice.dashboard'))
        ->assertDontSee(route('backoffice.feature-flags.index'), false);
});

it('lists portal flags with a value per portal they apply to', function () {
    $flags = collect(Livewire::actingAs(adminActor())
        ->test('pages::backoffice.feature-flags')
        ->instance()
        ->flags())->keyBy('name');

    expect($flags['whats-new-card']['scope'])->toBe('portal')
        ->and($flags['whats-new-card']['portals'])->toBe(['app' => false]);
});

it('renders a switch per portal', function () {
    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.feature-flags')
        ->assertSeeHtml("setPortal('whats-new-card', 'app', true)")
        ->assertSee("What's new card");
});

it('switches a portal flag on and off for one portal', function () {
    $page = Livewire::actingAs(adminActor())->test('pages::backoffice.feature-flags');

    $page->call('setPortal', 'whats-new-card', 'app', true)
        ->assertSet('status', '"What\'s new card" is now on in the app portal.');

    expect(Feature::for(Portal::named('app'))->active('whats-new-card'))->toBeTrue();

    $page->call('setPortal', 'whats-new-card', 'app', false);

    Feature::flushCache();
    expect(Feature::for(Portal::named('app'))->active('whats-new-card'))->toBeFalse();
});

it('switches a portal flag on everywhere and resets it to the default rule', function () {
    $page = Livewire::actingAs(adminActor())->test('pages::backoffice.feature-flags');

    $page->call('setEverywhere', 'whats-new-card', true);
    expect(Feature::for(Portal::named('app'))->active('whats-new-card'))->toBeTrue();

    $page->call('resetFlag', 'whats-new-card');

    // The page re-reads after the reset, so the default (off) is stored again.
    $this->assertDatabaseHas('features', ['name' => 'whats-new-card', 'scope' => 'portal:app', 'value' => 'false']);
    Feature::flushCache();
    expect(Feature::for(Portal::named('app'))->active('whats-new-card'))->toBeFalse();
});

it('switches a user flag for every user already decided and counts them', function () {
    Feature::define(BetaDashboard::class);

    [$a, $b] = [staffUser(), staffUser()];
    Feature::for($a)->activate('beta-dashboard');
    Feature::for($b)->deactivate('beta-dashboard');

    $page = Livewire::actingAs(adminActor())->test('pages::backoffice.feature-flags');

    expect(collect($page->instance()->flags())->firstWhere('name', 'beta-dashboard')['users'])
        ->toBe(['active' => 1, 'total' => 2]);

    $page->call('setEverywhere', 'beta-dashboard', true);

    Feature::flushCache();
    expect(Feature::for($b)->active('beta-dashboard'))->toBeTrue();
});

it('rejects unknown flags and portals the flag does not apply to', function () {
    $admin = adminActor();

    Livewire::actingAs($admin)->test('pages::backoffice.feature-flags')
        ->call('setPortal', 'no-such-flag', 'app', true)->assertStatus(404);
    Livewire::actingAs($admin)->test('pages::backoffice.feature-flags')
        ->call('setPortal', 'whats-new-card', 'backoffice', true)->assertStatus(404);

    $this->assertDatabaseMissing('features', ['scope' => 'portal:backoffice']);
});
