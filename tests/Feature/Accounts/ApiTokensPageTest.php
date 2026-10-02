<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function tokenOwner(): User
{
    return User::factory()->create(['email_verified_at' => now(), 'phone_verified_at' => now()]);
}

it('is linked from the account sidebar and renders', function () {
    $this->actingAs(tokenOwner())->get(route('account.api-tokens'))
        ->assertOk()
        ->assertSee('API tokens')
        ->assertSee(route('api.v1.index'));
});

it('creates a token with the chosen abilities and expiry, showing it once', function () {
    $user = tokenOwner();

    $page = Livewire::actingAs($user)
        ->test('pages::accounts.api-tokens')
        ->set('name', 'Mobile app')
        ->set('abilities', ['read', 'write'])
        ->set('expiresIn', '30')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSeeHtml('data-new-token');

    $token = $user->tokens()->sole();

    expect($token->name)->toBe('Mobile app')
        ->and($token->abilities)->toBe(['read', 'write'])
        ->and($token->expires_at->isSameDay(now()->addDays(30)))->toBeTrue();

    // The plain token works against the API…
    $plain = $page->get('plainToken');
    $this->withToken($plain)->getJson(route('api.v1.me'))->assertOk();

    // …and is gone once dismissed.
    $page->call('dismissToken')->assertSet('plainToken', null)->assertDontSeeHtml('data-new-token');
});

it('creates never-expiring tokens', function () {
    $user = tokenOwner();

    Livewire::actingAs($user)->test('pages::accounts.api-tokens')
        ->set('name', 'CI')->set('expiresIn', '')->call('create')->assertHasNoErrors();

    expect($user->tokens()->sole()->expires_at)->toBeNull();
});

it('validates name, abilities and expiry', function () {
    $user = tokenOwner();
    $user->createToken('Taken', ['read']);

    Livewire::actingAs($user)->test('pages::accounts.api-tokens')
        ->set('name', 'Taken')->set('abilities', ['admin'])->set('expiresIn', '7')
        ->call('create')
        ->assertHasErrors(['name', 'abilities.0', 'expiresIn']);

    Livewire::actingAs($user)->test('pages::accounts.api-tokens')
        ->set('name', 'x')->set('abilities', [])
        ->call('create')
        ->assertHasErrors(['abilities']);
});

it('revokes only the signed-in user\'s own tokens', function () {
    $user = tokenOwner();
    $other = tokenOwner();
    $mine = $user->createToken('mine', ['read'])->accessToken;
    $theirs = $other->createToken('theirs', ['read'])->accessToken;

    Livewire::actingAs($user)->test('pages::accounts.api-tokens')
        ->call('revoke', $theirs->id)
        ->call('revoke', $mine->id);

    expect(PersonalAccessToken::whereKey($mine->id)->exists())->toBeFalse()
        ->and(PersonalAccessToken::whereKey($theirs->id)->exists())->toBeTrue();
});
