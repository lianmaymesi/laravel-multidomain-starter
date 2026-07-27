<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('marks the status invalid when the token does not match any user', function () {
    Livewire::test('pages::auth.verify-email-change', ['token' => 'not-a-real-token'])
        ->assertSet('status', 'invalid');
});

it('marks the status expired and swaps in the new email once the window has passed', function () {
    $user = User::factory()->create([
        'pending_email' => 'new@example.com',
        'pending_email_token' => 'a-token',
        'pending_email_requested_at' => now()->subHours(49),
    ]);

    Livewire::test('pages::auth.verify-email-change', ['token' => 'a-token'])
        ->assertSet('status', 'expired')
        ->assertSet('pendingEmail', 'new@example.com');

    expect($user->fresh()->email)->not->toBe('new@example.com');
});

it('confirms the new email and clears the pending fields', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'pending_email' => 'new@example.com',
        'pending_email_token' => 'a-token',
        'pending_email_requested_at' => now(),
    ]);

    Livewire::test('pages::auth.verify-email-change', ['token' => 'a-token'])
        ->assertSet('status', 'success');

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->pending_email)->toBeNull()
        ->and($user->pending_email_token)->toBeNull();
});
