<?php

use App\Contracts\SmsService;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Notifications\PendingEmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('saves the profile name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('editName')
        ->set('name', 'New Name')
        ->call('saveName')
        ->assertSet('editingName', false);

    expect($user->fresh()->name)->toBe('New Name');
});

it('requests an email change and notifies the pending address', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'old@example.com']);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('editEmail')
        ->set('newEmail', 'new@example.com')
        ->call('requestEmailChange')
        ->assertSet('editingEmail', false);

    $user->refresh();

    expect($user->pending_email)->toBe('new@example.com')
        ->and($user->email)->toBe('old@example.com');

    Notification::assertSentTo($user, PendingEmailVerification::class);
});

it('rejects an email change to an address already in use', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('editEmail')
        ->set('newEmail', 'taken@example.com')
        ->call('requestEmailChange')
        ->assertHasErrors('newEmail');
});

it('cancels a pending email change', function () {
    $user = User::factory()->create([
        'pending_email' => 'new@example.com',
        'pending_email_token' => 'a-token',
        'pending_email_requested_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('cancelEmailChange');

    $user->refresh();

    expect($user->pending_email)->toBeNull()
        ->and($user->pending_email_token)->toBeNull();
});

it('updates the phone number when phone verification is enabled', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = User::factory()->create([
        'country_code' => '+91',
        'phone' => '9876543210',
        'phone_verified_at' => now(),
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')->once()->andReturnTrue();
    $this->app->instance(SmsService::class, $smsService);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('editPhone')
        ->set('newPhone', '9999999999')
        ->call('savePhone')
        ->assertRedirect(route('auth.verify-phone'));

    $user->refresh();

    expect($user->phone)->toBe('9999999999')
        ->and($user->phone_verified_at)->toBeNull();
});

it('requests account deletion with the correct password and logs out', function () {
    $user = User::factory()->create();
    $user->password = bcrypt('correct-password');
    $user->save();

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->set('deletePassword', 'correct-password')
        ->call('requestDeletion')
        ->assertRedirect(route('auth.login'));

    expect(AccountDeletionRequest::where('user_id', $user->id)->where('status', 'pending')->exists())->toBeTrue();
});

it('rejects account deletion with an incorrect password', function () {
    $user = User::factory()->create();
    $user->password = bcrypt('correct-password');
    $user->save();

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->set('deletePassword', 'wrong-password')
        ->call('requestDeletion')
        ->assertHasErrors('deletePassword');

    expect(AccountDeletionRequest::where('user_id', $user->id)->exists())->toBeFalse();
});

it('cancels an active deletion request', function () {
    $user = User::factory()->create();

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS),
        'status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('cancelDeletion')
        ->assertSet('deletionRequest', null);

    expect($request->fresh()->status)->toBe('cancelled');
});
