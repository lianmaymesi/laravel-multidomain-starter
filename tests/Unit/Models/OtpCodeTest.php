<?php

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOtp(array $overrides = []): OtpCode
{
    $user = User::factory()->create();

    return OtpCode::create(array_merge([
        'user_id' => $user->id,
        'type' => 'email_verification',
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'used_at' => null,
    ], $overrides));
}

it('is expired once past the expiry timestamp', function () {
    expect(makeOtp(['expires_at' => now()->addMinute()])->isExpired())->toBeFalse();
    expect(makeOtp(['expires_at' => now()->subMinute()])->isExpired())->toBeTrue();
});

it('is valid only when unused and not expired', function () {
    expect(makeOtp(['expires_at' => now()->addMinute(), 'used_at' => null])->isValid())->toBeTrue();
    expect(makeOtp(['expires_at' => now()->addMinute(), 'used_at' => now()])->isValid())->toBeFalse();
    expect(makeOtp(['expires_at' => now()->subMinute(), 'used_at' => null])->isValid())->toBeFalse();
});

it('is prunable once used or expired for more than a day', function () {
    $used = makeOtp(['used_at' => now()]);
    $expired = makeOtp(['expires_at' => now()->subDays(2)]);
    $fresh = makeOtp(['expires_at' => now()->addMinutes(10), 'used_at' => null]);

    $prunable = $fresh->prunable()->pluck('id');

    expect($prunable)->toContain($used->id)
        ->and($prunable)->toContain($expired->id)
        ->and($prunable)->not->toContain($fresh->id);
});
