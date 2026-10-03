<?php

use App\Features\HelloWorldAdvanced;
use App\Models\User;
use App\Support\Features\Flags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Lottery;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/** An app-portal user who signed up long enough ago to not count as "new". */
function establishedUser(): User
{
    return User::factory()->create([
        'privilege' => 'user',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'created_at' => now()->subMonth(),
    ]);
}

it('lets half the users in, each with a stable variant, and remembers who lost', function () {
    $winner = establishedUser();
    $loser = establishedUser();

    Lottery::alwaysWin(fn () => expect(Flags::value(HelloWorldAdvanced::class, $winner))
        ->toBe(HelloWorldAdvanced::VARIANTS[$winner->id % 3]));

    Lottery::alwaysLose(fn () => expect(Flags::active(HelloWorldAdvanced::class, $loser))->toBeFalse());

    // Both results are stored, so flipping the odds changes nothing for them.
    Feature::flushCache();
    Lottery::alwaysWin(fn () => expect(Flags::active(HelloWorldAdvanced::class, $loser))->toBeFalse());
    Lottery::alwaysLose(fn () => expect(Flags::value(HelloWorldAdvanced::class, $winner))
        ->toBe(HelloWorldAdvanced::VARIANTS[$winner->id % 3]));
});

it('always shows new users the "wave" variant, without storing it', function () {
    $newcomer = establishedUser();
    $newcomer->forceFill(['created_at' => now()->subDay()])->save();

    Lottery::alwaysLose(fn () => expect(Flags::value(HelloWorldAdvanced::class, $newcomer))->toBe('wave'));

    $this->assertDatabaseMissing('features', ['name' => 'hello-world-advanced']);
});

it('is off for guests', function () {
    expect(Flags::active(HelloWorldAdvanced::class, null))->toBeFalse();
});

it('is forced off by the PENNANT_KILLED kill switch, whatever is stored', function () {
    $user = establishedUser();
    Feature::for($user)->activate(HelloWorldAdvanced::class, 'rocket');

    config(['pennant.killed' => ['hello-world-advanced']]);
    Feature::flushCache();

    expect(Flags::active(HelloWorldAdvanced::class, $user))->toBeFalse();

    config(['pennant.killed' => []]);
    Feature::flushCache();

    expect(Flags::value(HelloWorldAdvanced::class, $user))->toBe('rocket');
});

it('renders the stored variant on the app dashboard, and "classic" for a plain true', function () {
    $user = establishedUser();

    Feature::for($user)->activate(HelloWorldAdvanced::class, 'rocket');

    $this->actingAs($user)->get(route('app.dashboard'))
        ->assertOk()
        ->assertSee('data-variant="rocket"', false)
        ->assertSee('Hello, World — now 10x faster!');

    Feature::for($user)->activate(HelloWorldAdvanced::class);
    Feature::flushCache();

    $this->actingAs($user)->get(route('app.dashboard'))
        ->assertSee('data-variant="classic"', false);

    Feature::for($user)->deactivate(HelloWorldAdvanced::class);
    Feature::flushCache();

    $this->actingAs($user)->get(route('app.dashboard'))
        ->assertDontSee('data-flag="hello-world-advanced"', false);
});
