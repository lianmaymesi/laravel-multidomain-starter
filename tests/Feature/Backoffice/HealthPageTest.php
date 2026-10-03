<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fixtures\Health\FailingCheck;
use Tests\Fixtures\Health\NestedMetaCheck;
use Tests\Fixtures\Health\PassingCheck;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    config(['health.checks' => [PassingCheck::class, FailingCheck::class]]);
});

it('is open to Admin and Super Admin, not other staff, and linked in the sidebar', function () {
    Livewire::actingAs(adminActor())->test('pages::backoffice.health')->assertOk();
    Livewire::actingAs(superAdminActor())->test('pages::backoffice.health')->assertOk();
    Livewire::actingAs(staffUser())->test('pages::backoffice.health')->assertForbidden();

    $this->actingAs(adminActor())->get(route('backoffice.dashboard'))
        ->assertSee(route('backoffice.health.index'), false);
});

it('shows every check with its status and the overall result', function () {
    Livewire::actingAs(adminActor())->test('pages::backoffice.health')
        ->assertSeeHtml('data-overall="failed"')
        ->assertSeeHtml('data-check="passing" data-status="ok"')
        ->assertSeeHtml('data-check="failing" data-status="failed"')
        ->assertSee('Down.')
        ->assertSee(route('health'));
});

it('renders nested check meta', function () {
    config(['health.checks' => [NestedMetaCheck::class]]);

    Livewire::actingAs(adminActor())->test('pages::backoffice.health')
        ->assertOk()
        ->assertSee('backups: (reachable: yes, count: 1, newest: —)')
        ->assertSee('local, public');
});

it('runs the checks again on demand', function () {
    $page = Livewire::actingAs(adminActor())->test('pages::backoffice.health');

    config(['health.checks' => [PassingCheck::class]]);

    $page->call('runNow')->assertSeeHtml('data-overall="ok"');
});

it('nudges to set HEALTH_TOKEN when it is missing', function () {
    config(['health.token' => null]);
    Livewire::actingAs(adminActor())->test('pages::backoffice.health')->assertSeeHtml('data-no-token');

    config(['health.token' => 'x']);
    Livewire::actingAs(adminActor())->test('pages::backoffice.health')->assertDontSeeHtml('data-no-token');
});
