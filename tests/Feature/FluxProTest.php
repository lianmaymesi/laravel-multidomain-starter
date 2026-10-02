<?php

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Flux\Flux;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('runs on Flux Pro', function () {
    expect(Flux::pro())->toBeTrue();
});

it('renders the weekly activity as a Flux chart', function () {
    $user = User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);

    $this->actingAs($user)->get(route('app.dashboard'))
        ->assertOk()
        ->assertSee('data-chart="weekly-activity"', false)
        ->assertSee('<ui-chart', false);
});

it('confirms Livewire actions with a toast', function () {
    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.permissions')
        ->call('create')
        ->set('name', 'reports.view')
        ->call('save')
        ->assertDispatched('toast-show', fn (string $event, array $params) => $params['slots']['text'] === 'Permission saved.'
            && $params['dataset']['variant'] === 'success');
});

it('carries a toast across a redirect and shows it on the next page', function () {
    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.modules')
        ->call('toggle', 'currency')
        ->assertRedirect(route('backoffice.modules.index'))
        ->assertNotDispatched('toast-show');

    expect(session('toast'))->toBe(['text' => '"Currencies" is now disabled.', 'variant' => 'success']);

    $this->actingAs(superAdminActor())
        ->withSession(['toast' => ['text' => 'Hello there', 'variant' => 'success']])
        ->get(route('backoffice.dashboard'))
        ->assertSee('data-flash-toast="Hello there"', false)
        ->assertSee('<ui-toast', false);
});

it('sorts the users table by an allowed column, in both directions', function () {
    User::factory()->create(['name' => 'Zed', 'email' => 'a@sorting.test']);
    User::factory()->create(['name' => 'Amy', 'email' => 'z@sorting.test']);

    $page = Livewire::actingAs(superAdminActor())->test('pages::backoffice.users')->set('search', '@sorting.test');

    expect($page->instance()->users()->pluck('name')->all())->toBe(['Amy', 'Zed']);

    $page->call('sort', 'email');
    expect($page->instance()->users()->pluck('name')->all())->toBe(['Zed', 'Amy'])
        ->and($page->instance()->isSortedBy('email'))->toBeTrue();

    $page->call('sort', 'email');
    expect($page->get('sortDirection'))->toBe('desc')
        ->and($page->instance()->users()->pluck('name')->all())->toBe(['Amy', 'Zed']);
});

it('ignores sort columns that are not allowed', function () {
    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.users')
        ->call('sort', 'password')
        ->assertSet('sortBy', '')
        ->set('sortBy', 'password')
        ->assertOk();
});

it('sorts roles by how many users they have', function () {
    $page = Livewire::actingAs(superAdminActor())->test('pages::backoffice.roles');

    $page->call('sort', 'users')->call('sort', 'users');

    $counts = $page->instance()->roles()->pluck('users_count')->all();

    expect($counts)->toBe(collect($counts)->sortDesc()->values()->all());
});

it('paginates the permissions table', function () {
    collect(range(1, 30))->each(fn ($i) => Permission::create(['name' => "extra.permission-{$i}", 'guard_name' => 'web']));

    $permissions = Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.permissions')
        ->instance()
        ->permissions();

    expect($permissions->perPage())->toBe(25)
        ->and($permissions->total())->toBe(Permission::count());
});
