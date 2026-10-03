<?php

use App\Models\User;
use App\Modules\Api\Services\ApiAccess;
use App\Modules\Api\Services\TokenIssuer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('api');
    $this->seed(RolePermissionSeeder::class);
});

function accountUser(): User
{
    return User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
}

function allowEveryone(array $overrides = []): void
{
    app(ApiAccess::class)->savePolicy([...config('api.policy'), 'self_service' => 'everyone', ...$overrides]);
}

it('is hidden and forbidden while the policy does not include the user', function () {
    $user = accountUser();

    $this->actingAs($user)->get(route('account.index'))
        ->assertOk()
        ->assertDontSee(route('account.api-tokens'), false);

    $this->actingAs($user)->get(route('account.api-tokens'))->assertForbidden();
});

it('appears in the account nav once the policy includes the user', function () {
    allowEveryone();

    $this->actingAs(accountUser())->get(route('account.index'))
        ->assertSee(route('account.api-tokens'), false);
});

it('creates a token within the policy, showing it once', function () {
    allowEveryone(['abilities' => ['profile:read'], 'max_days' => 30]);
    $user = accountUser();

    $page = Livewire::actingAs($user)->test('api::tokens')
        ->assertSet('abilities', ['profile:read'])
        ->assertSet('expiresIn', '30')
        ->set('name', 'Mobile app')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSeeHtml('data-new-token');

    $token = $user->tokens()->sole();

    expect($token->abilities)->toBe(['profile:read'])
        ->and($token->issued_by)->toBe($user->id)
        ->and($token->expires_at->isSameDay(now()->addDays(30)))->toBeTrue();

    $this->withToken($page->get('plainToken'))->getJson(route('api.v1.me'))->assertOk();

    $page->call('dismissToken')->assertDontSeeHtml('data-new-token');
});

it('only offers abilities and lifetimes the policy allows', function () {
    allowEveryone(['abilities' => ['profile:read'], 'max_days' => 30]);

    $page = Livewire::actingAs(accountUser())->test('api::tokens');

    expect(array_keys($page->instance()->abilityOptions()))->toBe(['profile:read'])
        ->and(array_keys($page->instance()->expiryOptions()))->toBe([7, 30]);

    // Whatever the browser sends, the issuer enforces the policy.
    $page->set('name', 'x')->set('abilities', ['languages:write'])->call('create')->assertHasErrors('abilities');
});

it('rejects a duplicate token name', function () {
    allowEveryone();
    $user = accountUser();
    app(TokenIssuer::class)->issueForSelf($user, 'Taken', ['profile:read'], 30);

    Livewire::actingAs($user)->test('api::tokens')->set('name', 'Taken')->call('create')->assertHasErrors('name');
});

it('shows admin-issued tokens read-only when the user may not create their own', function () {
    $user = accountUser();
    app(TokenIssuer::class)->issueFor($user, superAdminActor(), 'ERP sync', ['profile:read'], null);

    Livewire::actingAs($user)->test('api::tokens')
        ->assertOk()
        ->assertSeeHtml('data-self-service-off')
        ->assertDontSeeHtml('data-create-form')
        ->assertSee('ERP sync');
});

it('revokes only the signed-in user\'s own tokens', function () {
    allowEveryone();
    [$user, $other] = [accountUser(), accountUser()];
    $mine = app(TokenIssuer::class)->issueForSelf($user, 'mine', ['profile:read'], 30)->accessToken;
    $theirs = app(TokenIssuer::class)->issueForSelf($other, 'theirs', ['profile:read'], 30)->accessToken;

    Livewire::actingAs($user)->test('api::tokens')->call('revoke', $theirs->id)->call('revoke', $mine->id);

    expect(PersonalAccessToken::whereKey($mine->id)->exists())->toBeFalse()
        ->and(PersonalAccessToken::whereKey($theirs->id)->exists())->toBeTrue();
});
