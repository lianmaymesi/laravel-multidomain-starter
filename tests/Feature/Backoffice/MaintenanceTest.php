<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('excludes backoffice, account, auth, and api from the per-portal toggle list', function () {
    $portals = Livewire::actingAs(superAdminActor())
        ->test('maintenance::index')
        ->instance()
        ->portals();

    expect($portals)->toContain('app', 'landing')
        ->and($portals)->not->toContain('backoffice', 'account', 'auth', 'api');
});

it('refuses to toggle a portal outside the manageable list', function () {
    Livewire::actingAs(superAdminActor())
        ->test('maintenance::index')
        ->call('toggle', 'account')
        ->assertStatus(403);
});
