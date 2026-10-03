<?php

use App\Models\User;
use App\Modules\Api\Services\TokenIssuer;
use App\Modules\Api\Support\Api;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('api');
});

it('answers on the api subdomain under the version prefix', function () {
    expect(parse_url(route('api.v1.index'), PHP_URL_HOST))->toBe(config('multidomain.sub_domains.api'))
        ->and(parse_url(route('api.v1.index'), PHP_URL_PATH))->toBe('/v1');

    $this->getJson(route('api.v1.index'))
        ->assertOk()
        ->assertExactJson(['name' => config('app.name'), 'version' => 'v1']);
});

it('rejects requests without a token, in JSON', function () {
    $this->get(route('api.v1.me'))
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('answers unknown API routes with JSON, not the HTML error page', function () {
    $this->get('http://'.config('multidomain.sub_domains.api').'/v1/nope')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns the token\'s user and abilities from /me', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    // Admin-issued, so the default policy (nobody self-serves) doesn't block it.
    $token = app(TokenIssuer::class)->issueFor($user, User::factory()->create(), 'script', ['profile:read'], 30);

    $this->withToken($token->plainTextToken)
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertJsonPath('data.name', 'Ada')
        ->assertJsonMissingPath('data.password')
        ->assertJsonPath('token.name', 'script')
        ->assertJsonPath('token.abilities', ['profile:read']);
});

it('enforces token abilities, with * granting everything', function () {
    Sanctum::actingAs(User::factory()->create(), ['languages:read']);
    $this->getJson(route('api.v1.me'))->assertForbidden();

    Sanctum::actingAs(User::factory()->create(), ['*']);
    $this->getJson(route('api.v1.me'))->assertOk();
});

it('never authenticates the API with a portal session', function () {
    $this->actingAs(User::factory()->create(), 'web');

    $this->getJson(route('api.v1.me'))->assertUnauthorized();
});

it('rejects expired and revoked tokens', function () {
    $user = User::factory()->create();

    $expired = $user->createToken('old', ['profile:read'], now()->subDay());
    $this->withToken($expired->plainTextToken)->getJson(route('api.v1.me'))->assertUnauthorized();

    $revoked = $user->createToken('gone', ['profile:read']);
    $revoked->accessToken->delete();
    $this->app['auth']->forgetGuards();
    $this->withToken($revoked->plainTextToken)->getJson(route('api.v1.me'))->assertUnauthorized();
});

it('rate-limits per user', function () {
    config(['api.rate_limit' => 2]);
    Sanctum::actingAs(User::factory()->create(), ['profile:read']);

    $this->getJson(route('api.v1.me'))->assertOk();
    $this->getJson(route('api.v1.me'))->assertOk();
    $this->getJson(route('api.v1.me'))->assertTooManyRequests()->assertHeader('Retry-After');
});

it('detects API requests by host, or by /api in single-domain mode', function () {
    $host = config('multidomain.sub_domains.api');

    expect(Api::is(Request::create("https://{$host}/v1/anything")))->toBeTrue()
        ->and(Api::is(Request::create('https://'.config('multidomain.sub_domains.app').'/v1')))->toBeFalse()
        ->and(Api::prefix())->toBe('v1');

    config(['multidomain.single_domain' => true]);

    expect(Api::prefix())->toBe('api/v1')
        ->and(Api::is(Request::create('https://example.test/api/v1/me')))->toBeTrue()
        ->and(Api::is(Request::create('https://example.test/app')))->toBeFalse();
});
