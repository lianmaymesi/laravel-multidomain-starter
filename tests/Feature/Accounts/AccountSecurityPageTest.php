<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

it('lists the sessions belonging to the authenticated user and flags the current one', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $currentSessionId = session()->getId();

    DB::table('sessions')->insert([
        ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'other-session', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0 (iPhone) Safari/604.1', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'not-mine', 'user_id' => $otherUser->id, 'ip_address' => '10.0.0.2', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    $sessions = Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->instance()
        ->sessions();

    expect($sessions)->toHaveCount(2);
    expect($sessions->firstWhere('id', $currentSessionId)->is_current)->toBeTrue();
    expect($sessions->firstWhere('id', 'other-session')->is_current)->toBeFalse();
});

it('logs out another session but not the current one', function () {
    $user = User::factory()->create();
    $currentSessionId = session()->getId();

    DB::table('sessions')->insert([
        ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'other-session', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->call('logoutSession', 'other-session');

    expect(DB::table('sessions')->where('id', 'other-session')->exists())->toBeFalse();
    expect(DB::table('sessions')->where('id', $currentSessionId)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->call('logoutSession', $currentSessionId);

    expect(DB::table('sessions')->where('id', $currentSessionId)->exists())->toBeTrue();
});

it('logs out all other sessions but keeps the current one', function () {
    $user = User::factory()->create();
    $currentSessionId = session()->getId();

    DB::table('sessions')->insert([
        ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'other-session-1', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'other-session-2', 'user_id' => $user->id, 'ip_address' => '10.0.0.2', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->call('logoutOtherSessions');

    expect(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe([$currentSessionId]);
});

it('rotates the remember token when logging out other sessions so remember-me cookies elsewhere stop working', function () {
    $user = User::factory()->create(['remember_token' => 'original-token']);
    $currentSessionId = session()->getId();

    DB::table('sessions')->insert([
        ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'other-session', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->call('logoutOtherSessions');

    expect($user->fresh()->remember_token)->not->toBe('original-token');
});

it('rotates the remember token when logging out a single other session', function () {
    $user = User::factory()->create(['remember_token' => 'original-token']);
    $currentSessionId = session()->getId();

    DB::table('sessions')->insert([
        ['id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'other-session', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.security')
        ->call('logoutSession', 'other-session');

    expect($user->fresh()->remember_token)->not->toBe('original-token');
});
