<?php

use App\Models\User;
use Atrium\Core\Models\AppSetting;
use Atrium\Core\Services\TimezoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('uses the user\'s own timezone first', function () {
    $user = User::factory()->create(['timezone' => 'Asia/Kolkata']);

    expect(app(TimezoneService::class)->current($user))->toBe('Asia/Kolkata');
});

it('falls back to the signed-in user, then the app default', function () {
    AppSetting::set(AppSetting::DEFAULT_TIMEZONE, 'Europe/Berlin');
    $service = app(TimezoneService::class);

    expect($service->current())->toBe('Europe/Berlin');

    $this->actingAs(User::factory()->create(['timezone' => 'America/New_York']));
    expect($service->current())->toBe('America/New_York');

    $this->actingAs(User::factory()->create(['timezone' => null]));
    expect($service->current())->toBe('Europe/Berlin');
});

it('uses config app.timezone when no default is set', function () {
    config(['app.timezone' => 'UTC']);

    expect(app(TimezoneService::class)->default())->toBe('UTC');
});

it('validates identifiers', function () {
    $service = app(TimezoneService::class);

    expect($service->isValid('Asia/Dubai'))->toBeTrue()
        ->and($service->isValid('Mars/Olympus_Mons'))->toBeFalse()
        ->and($service->identifiers())->toContain('UTC');
});

it('shows dates in the user\'s timezone without changing storage', function () {
    $user = User::factory()->create(['timezone' => 'Asia/Kolkata']);
    $utc = Carbon::parse('2026-01-01 00:00:00', 'UTC');

    expect($utc->forUser($user)->format('Y-m-d H:i'))->toBe('2026-01-01 05:30')
        ->and($utc->getTimezone()->getName())->toBe('UTC');
});
