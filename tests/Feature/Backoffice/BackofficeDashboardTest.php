<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('loads dashboard stats on mount', function () {
    User::factory()->count(2)->create();
    User::factory()->create(['privilege' => 'staff', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $staffUser = User::where('privilege', 'staff')->first();

    Livewire::actingAs($staffUser)
        ->test('pages::backoffice.dashboard')
        ->assertSet('totalUsers', 3)
        ->assertSet('staffUsers', 1)
        ->assertSet('totalRoles', 1);
});
