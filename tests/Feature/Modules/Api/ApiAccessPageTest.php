<?php

use App\Models\Role;
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

it('is reserved for Super Admin and linked from the sidebar', function () {
    Livewire::actingAs(superAdminActor())->test('api::access')->assertOk();
    Livewire::actingAs(adminActor())->test('api::access')->assertForbidden();
    Livewire::actingAs(staffUser())->test('api::access')->assertForbidden();

    $this->actingAs(superAdminActor())->get(route('backoffice.dashboard'))
        ->assertSee(route('backoffice.api.index'), false);
});

it('saves the access policy and reports who it affects', function () {
    $partner = Role::create(['name' => 'Partner', 'slug' => 'partner', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($partner);

    Livewire::actingAs(superAdminActor())->test('api::access')
        ->set('mode', 'roles')
        ->set('roles', ['partner'])
        ->assertSeeText('1 user can create tokens.')
        ->set('policyAbilities', ['profile:read', 'languages:read'])
        ->set('maxDays', '90')
        ->set('maxTokens', 3)
        ->call('savePolicy')
        ->assertHasNoErrors();

    expect(app(ApiAccess::class)->policy())->toMatchArray([
        'self_service' => 'roles',
        'roles' => ['partner'],
        'abilities' => ['profile:read', 'languages:read'],
        'max_days' => 90,
        'max_tokens' => 3,
    ]);
});

it('validates the policy', function () {
    Livewire::actingAs(superAdminActor())->test('api::access')
        ->set('mode', 'roles')->set('roles', [])
        ->set('policyAbilities', ['nuke:all'])
        ->set('maxDays', '12')
        ->set('maxTokens', 0)
        ->call('savePolicy')
        ->assertHasErrors(['roles', 'policyAbilities.0', 'maxDays', 'maxTokens']);
});

it('issues a token to any user, shown once and marked as admin-issued', function () {
    $admin = superAdminActor();
    $owner = User::factory()->create(['email' => 'erp@example.com']);

    $page = Livewire::actingAs($admin)->test('api::access')
        ->set('issueEmail', 'erp@example.com')
        ->set('issueName', 'ERP sync')
        ->set('issueAbilities', ['*'])
        ->set('issueExpiresIn', '')
        ->call('issue')
        ->assertHasNoErrors()
        ->assertSeeHtml('data-issued-token');

    $token = $owner->tokens()->sole();

    expect($token->abilities)->toBe(['*'])
        ->and($token->issued_by)->toBe($admin->id)
        ->and($token->expires_at)->toBeNull()
        ->and(PersonalAccessToken::findToken($page->get('issuedToken'))?->is($token))->toBeTrue();
});

it('validates the issue form', function () {
    Livewire::actingAs(superAdminActor())->test('api::access')
        ->set('issueEmail', 'nobody@example.com')
        ->set('issueName', '')
        ->set('issueAbilities', ['nuke:all'])
        ->call('issue')
        ->assertHasErrors(['issueEmail', 'issueName', 'issueAbilities.0']);
});

it('lists, searches and revokes every user\'s tokens', function () {
    $admin = superAdminActor();
    $ada = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $bob = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
    $adaToken = app(TokenIssuer::class)->issueFor($ada, $admin, 'Ada script', ['profile:read'], 30)->accessToken;
    app(TokenIssuer::class)->issueFor($bob, $admin, 'Bob script', ['profile:read'], 30);

    $page = Livewire::actingAs($admin)->test('api::access');
    expect($page->instance()->tokens()->total())->toBe(2);

    $page->set('search', 'ada@');
    expect($page->instance()->tokens()->pluck('name')->all())->toBe(['Ada script']);

    $page->call('revoke', $adaToken->id);
    expect(PersonalAccessToken::count())->toBe(1);
});

it('revokes all tokens at once', function () {
    $admin = superAdminActor();
    foreach (range(1, 3) as $i) {
        app(TokenIssuer::class)->issueFor(User::factory()->create(), $admin, "t{$i}", ['profile:read'], 30);
    }

    Livewire::actingAs($admin)->test('api::access')
        ->call('revokeAll')
        ->assertSet('status', '3 tokens revoked.');

    expect(PersonalAccessToken::count())->toBe(0);
});
