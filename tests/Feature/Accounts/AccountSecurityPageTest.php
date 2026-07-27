<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('updates the password with the correct current password', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->set('current_password', 'current-password')
        ->set('password', 'Jrb!2026-New-Pass-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-New-Pass-Q9v#72')
        ->call('updatePassword')
        ->assertSet('passwordSuccess', true);

    expect(Hash::check('Jrb!2026-New-Pass-Q9v#72', $user->fresh()->password))->toBeTrue();
});

it('rejects an incorrect current password', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->set('current_password', 'wrong-password')
        ->set('password', 'Jrb!2026-New-Pass-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-New-Pass-Q9v#72')
        ->call('updatePassword')
        ->assertHasErrors('current_password');
});

it('clears the success flag once the current password field is changed again', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->set('passwordSuccess', true)
        ->set('current_password', 'anything')
        ->assertSet('passwordSuccess', false);
});
