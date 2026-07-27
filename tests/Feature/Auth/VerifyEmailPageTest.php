<?php

use App\Enums\OtpType;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('skips the page and redirects when the email is already verified', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test('pages::auth.verify-email')
        ->assertRedirect($user->redirect());
});

it('verifies the email with a correct code and redirects to the portal', function () {
    $user = User::factory()->unverified()->create();

    $otp = OtpCode::create([
        'user_id' => $user->id,
        'type' => OtpType::EMAIL_VERIFICATION->value,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'used_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test('pages::auth.verify-email')
        ->set('code', $otp->code)
        ->call('verify')
        ->assertRedirect($user->redirect());

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid verification code', function () {
    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test('pages::auth.verify-email')
        ->set('code', '000000')
        ->call('verify')
        ->assertHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends a fresh verification code by email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test('pages::auth.verify-email')
        ->call('resend');

    Notification::assertSentTo($user, VerifyEmail::class);
});
