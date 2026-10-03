<?php

use App\Models\Role;
use App\Models\User;
use App\Modules\Api\Services\ApiAccess;
use App\Modules\Api\Services\TokenIssuer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('api');
    $this->seed(RolePermissionSeeder::class);
});

function setPolicy(array $overrides): void
{
    app(ApiAccess::class)->savePolicy([...config('api.policy'), ...$overrides]);
}

function plainUser(): User
{
    return User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
}

// ── Who may self-serve ───────────────────────────────────────────────

it('lets nobody self-serve by default', function () {
    expect(app(ApiAccess::class)->policy()['self_service'])->toBe('none')
        ->and(app(ApiAccess::class)->canSelfServe(superAdminActor()))->toBeFalse()
        ->and(app(ApiAccess::class)->canSelfServe(plainUser()))->toBeFalse();
});

it('decides self-service by mode', function (string $mode, bool $staff, bool $user) {
    setPolicy(['self_service' => $mode]);
    $access = app(ApiAccess::class);

    expect($access->canSelfServe(staffUser()))->toBe($staff)
        ->and($access->canSelfServe(plainUser()))->toBe($user);
})->with([
    'staff' => ['staff', true, false],
    'everyone' => ['everyone', true, true],
]);

it('limits self-service to chosen roles', function () {
    $partner = Role::create(['name' => 'Partner', 'slug' => 'partner', 'guard_name' => 'web']);
    setPolicy(['self_service' => 'roles', 'roles' => ['partner']]);

    $withRole = plainUser();
    $withRole->assignRole($partner);

    expect(app(ApiAccess::class)->canSelfServe($withRole))->toBeTrue()
        ->and(app(ApiAccess::class)->canSelfServe(plainUser()))->toBeFalse()
        ->and(app(ApiAccess::class)->canSelfServe(staffUser()))->toBeFalse();
});

// ── Self-service limits ──────────────────────────────────────────────

it('refuses self-service tokens to users outside the policy', function () {
    app(TokenIssuer::class)->issueForSelf(plainUser(), 'x', ['profile:read'], 30);
})->throws(ValidationException::class, 'not enabled for your account');

it('limits self-service abilities, lifetime and count', function () {
    setPolicy(['self_service' => 'everyone', 'abilities' => ['profile:read'], 'max_days' => 30, 'max_tokens' => 1]);
    $issuer = app(TokenIssuer::class);
    $user = plainUser();

    expect(fn () => $issuer->issueForSelf($user, 'a', ['languages:write'], 30))->toThrow(ValidationException::class)
        ->and(fn () => $issuer->issueForSelf($user, 'b', ['profile:read'], 90))->toThrow(ValidationException::class)
        ->and(fn () => $issuer->issueForSelf($user, 'c', ['profile:read'], null))->toThrow(ValidationException::class);

    $issuer->issueForSelf($user, 'ok', ['profile:read'], 30);

    expect(fn () => $issuer->issueForSelf($user, 'one-too-many', ['profile:read'], 30))->toThrow(ValidationException::class)
        ->and($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->sole()->issued_by)->toBe($user->id);
});

it('offers the abilities enabled modules contribute', function () {
    expect(app(ApiAccess::class)->abilities())->toHaveKeys(['profile:read', 'languages:read', 'languages:write']);
});

// ── Enforcement on every request ─────────────────────────────────────

it('stops self-issued tokens the moment the policy no longer allows their owner', function () {
    setPolicy(['self_service' => 'everyone']);
    $token = app(TokenIssuer::class)->issueForSelf(plainUser(), 'script', ['profile:read'], 30);

    $this->withToken($token->plainTextToken)->getJson(route('api.v1.me'))->assertOk();

    setPolicy(['self_service' => 'none']);
    $this->app['auth']->forgetGuards();

    $this->withToken($token->plainTextToken)->getJson(route('api.v1.me'))
        ->assertForbidden()
        ->assertJsonPath('message', 'API access is not enabled for your account.');
});

it('keeps admin-issued tokens working whatever the policy says', function () {
    $owner = plainUser();
    $token = app(TokenIssuer::class)->issueFor($owner, superAdminActor(), 'ERP sync', ['profile:read'], null);

    expect($token->accessToken->fresh()->issued_by)->not->toBe($owner->id);

    $this->withToken($token->plainTextToken)->getJson(route('api.v1.me'))->assertOk();
});

it('audits token creation and revocation in the activity log', function () {
    $admin = superAdminActor();
    $owner = plainUser();
    $token = app(TokenIssuer::class)->issueFor($owner, $admin, 'ERP sync', ['*'], 30);
    app(TokenIssuer::class)->revoke($token->accessToken, $admin);

    $log = Activity::where('log_name', 'api')->orderBy('id')->get();

    expect($log->pluck('description')->all())->toBe(['api token created', 'api token revoked'])
        ->and($log[0]->causer_id)->toBe($admin->id)
        ->and($log[0]->subject_id)->toBe($owner->id)
        ->and($log[0]->properties['abilities'])->toBe(['*']);
});
