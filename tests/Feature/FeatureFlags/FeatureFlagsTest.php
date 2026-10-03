<?php

use App\Features\WhatsNewCard;
use App\Models\User;
use App\Support\Features\Flags;
use App\Support\Features\Portal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Lottery;
use Laravel\Pennant\Feature;
use Tests\Fixtures\Features\BetaDashboard;

uses(RefreshDatabase::class);

/** Make the current request look like it came in on the given portal's host. */
function onPortal(string $portal): void
{
    app()->instance('request', Request::create('http://'.config("multidomain.sub_domains.{$portal}").'/'));
}

function appUser(): User
{
    return User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
}

it('discovers flag classes in app/Features', function () {
    expect(Flags::all())->toHaveKey('whats-new-card')
        ->and(Flags::find(WhatsNewCard::class))->toBeInstanceOf(WhatsNewCard::class)
        ->and(Flags::find('whats-new-card'))->toBeInstanceOf(WhatsNewCard::class);
});

it('stores a portal scope as "portal:<name>"', function () {
    expect(Feature::serializeScope(Portal::named('app')))->toBe('portal:app');

    Feature::for(Portal::named('app'))->activate('whats-new-card');

    $this->assertDatabaseHas('features', ['name' => 'whats-new-card', 'scope' => 'portal:app']);
});

it('resolves the current portal from the request host', function () {
    onPortal('backoffice');

    expect(Portal::current()->name)->toBe('backoffice');
});

it('ships the example flag dark and turns it on per portal', function () {
    onPortal('app');
    expect(Flags::active(WhatsNewCard::class))->toBeFalse();

    Feature::for(Portal::named('app'))->activate(WhatsNewCard::class);

    expect(Flags::active(WhatsNewCard::class))->toBeTrue();

    onPortal('backoffice');
    expect(Flags::active(WhatsNewCard::class))->toBeFalse();
});

it('answers false for a portal the flag does not apply to, or for the wrong kind of scope', function () {
    expect(Feature::for(Portal::named('backoffice'))->active(WhatsNewCard::class))->toBeFalse()
        // A bare Feature::active() / @feature scopes to the signed-in user — no TypeError.
        ->and(Feature::for(appUser())->active(WhatsNewCard::class))->toBeFalse();
});

it('treats unknown flags as off', function () {
    expect(Flags::active('no-such-flag'))->toBeFalse()
        ->and(Flags::active(stdClass::class))->toBeFalse();
});

it('decides a user flag per user and keeps the lottery result', function () {
    Feature::define(BetaDashboard::class);

    $user = appUser();

    Lottery::alwaysWin(function () use ($user) {
        expect(Flags::active(BetaDashboard::class, $user))->toBeTrue();
    });

    // Stored after the first check, so losing the lottery later changes nothing.
    Lottery::alwaysLose(function () use ($user) {
        Feature::flushCache();

        expect(Flags::active(BetaDashboard::class, $user))->toBeTrue();
    });

    expect(Flags::active(BetaDashboard::class, null))->toBeFalse();
});

it('uses the signed-in user for a user flag', function () {
    Feature::define(BetaDashboard::class);

    $user = appUser();
    Feature::for($user)->activate(BetaDashboard::class);

    $this->actingAs($user);

    expect(Flags::active(BetaDashboard::class))->toBeTrue();
});

it('shows the example card on the app dashboard only while the flag is on for the app portal', function () {
    $user = appUser();

    $this->actingAs($user)->get(route('app.dashboard'))
        ->assertOk()
        ->assertDontSee('data-flag="whats-new-card"', false);

    Feature::for(Portal::named('app'))->activate(WhatsNewCard::class);
    Feature::flushCache();

    $this->actingAs($user)->get(route('app.dashboard'))
        ->assertOk()
        ->assertSee('data-flag="whats-new-card"', false);
});
