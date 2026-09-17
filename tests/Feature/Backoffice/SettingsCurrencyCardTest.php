<?php

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function settingsCurrencyStaff(): User
{
    test()->seed(RolePermissionSeeder::class);
    test()->seed(CurrencySeeder::class);

    $staff = User::factory()->create(['privilege' => 'staff', 'phone_verified_at' => now(), 'email_verified_at' => now()]);
    $staff->assignRole('Super Admin');

    return $staff;
}

it('renders the generic setting fields with the right widget per type', function () {
    $staff = settingsCurrencyStaff();

    $response = test()->actingAs($staff)->get(route('backoffice.settings.index'));

    $response->assertSuccessful()
        ->assertSee('Language URL mode')
        ->assertSee('Default timezone')
        ->assertSee('Google Translate API key')
        ->assertSee('Currencies');
});

it('adding a currency from the picker stages it as a tag without touching the database yet', function () {
    $staff = settingsCurrencyStaff();

    Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->set('addCurrencyCode', 'EUR')
        ->call('addCurrency')
        ->assertSet('activeCurrencyCodes', ['USD', 'EUR']);

    expect(Currency::where('code', 'EUR')->first()->is_active)->toBeFalse();
});

it('removing a tag does not allow removing the primary currency', function () {
    $staff = settingsCurrencyStaff();

    Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->call('removeCurrency', 'USD')
        ->assertSet('activeCurrencyCodes', ['USD']);
});

it('saving persists added currencies and the chosen primary in one global save', function () {
    $staff = settingsCurrencyStaff();

    Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->set('values.default_timezone', 'UTC')
        ->set('addCurrencyCode', 'EUR')
        ->call('addCurrency')
        ->set('primaryCurrency', 'EUR')
        ->call('save');

    $eur = Currency::where('code', 'EUR')->first();
    $usd = Currency::where('code', 'USD')->first();

    expect($eur->is_primary)->toBeTrue()
        ->and($eur->is_active)->toBeTrue()
        ->and((float) $eur->exchange_rate)->toBe(1.0)
        ->and($usd->is_primary)->toBeFalse()
        ->and($usd->is_active)->toBeTrue();
});
