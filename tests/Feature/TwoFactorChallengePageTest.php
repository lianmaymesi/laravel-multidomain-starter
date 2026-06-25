<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the two factor challenge in authenticator and recovery modes', function () {
    $user = User::factory()->create([
        'country_code' => '+91',
        'phone' => '9876543210',
    ]);

    session(['2fa_user_id' => $user->id]);

    Livewire::test('pages::auth.two-factor-challenge')
        ->assertSee('Two-factor check')
        ->assertSee('Authenticator code required')
        ->assertSee('Use a recovery code instead')
        ->call('toggleRecovery')
        ->assertSee('Use a recovery code')
        ->assertSee('Recovery Code')
        ->assertSee('Use authenticator app instead');
});
