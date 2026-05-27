<?php

use App\Enums\OtpType;
use App\Models\User;
use App\Services\Auth\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registers a user and redirects them to phone verification', function () {
    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')
        ->once()
        ->with('+919876543210', Mockery::pattern('/^\d{6}$/'))
        ->andReturnTrue();

    $this->app->instance(SmsService::class, $smsService);

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('country_code', '+91')
        ->set('phone', '9876543210')
        ->call('register')
        ->assertRedirect(route('auth.verify-phone'));

    $user = User::where('email', 'taylor@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedPhone())->toBeFalse();

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $user->id,
        'type' => OtpType::PHONE_VERIFICATION->value,
        'used_at' => null,
    ]);
});

it('normalizes wildcard session domains into browser-valid parent domains', function () {
    expect(config('session.domain'))->toBe('.justreadbible.test');
});
