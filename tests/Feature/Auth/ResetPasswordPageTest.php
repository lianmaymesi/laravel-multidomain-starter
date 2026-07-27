<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('bounces to forgot-password when the token is missing', function () {
    Livewire::test('pages::auth.reset-password', ['token' => ''])
        ->assertRedirect(route('auth.forgot-password'));
});

it('bounces to forgot-password when the token is invalid', function () {
    $user = User::factory()->create();

    Livewire::test('pages::auth.reset-password', ['token' => 'not-a-real-token', 'email' => $user->email])
        ->assertRedirect(route('auth.forgot-password'));
});

it('resets the password with a valid token and redirects to login', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::test('pages::auth.reset-password', ['token' => $token, 'email' => $user->email])
        ->set('password', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->call('resetPassword')
        ->assertRedirect(route('auth.login'));

    expect(Hash::check('Jrb!2026-Reset-Flow-Q9v#72', $user->fresh()->password))->toBeTrue();
    expect(Password::tokenExists($user->fresh(), $token))->toBeFalse();
});

it('requires the password confirmation to match', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::test('pages::auth.reset-password', ['token' => $token, 'email' => $user->email])
        ->set('password', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->set('password_confirmation', 'does-not-match')
        ->call('resetPassword')
        ->assertHasErrors('password');
});
