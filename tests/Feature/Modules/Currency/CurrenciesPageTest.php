<?php

use App\Models\User;
use App\Modules\Currency\Seeders\CurrencySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the read-only currencies reference page with no management actions', function () {
    test()->seed(RolePermissionSeeder::class);
    test()->seed(CurrencySeeder::class);

    $staff = User::factory()->create(['privilege' => 'staff', 'phone_verified_at' => now(), 'email_verified_at' => now()]);
    $staff->assignRole('Super Admin');

    $response = test()->actingAs($staff)->get(route('backoffice.currencies.index'));

    $response->assertSuccessful()
        ->assertSee('US Dollar')
        ->assertSee('Primary')
        ->assertDontSee('Add currency')
        ->assertDontSee('Make primary');
});
