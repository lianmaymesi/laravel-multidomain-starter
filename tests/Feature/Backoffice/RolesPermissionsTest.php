<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('grants Super Admin every ability via Gate::before, even undefined ones', function () {
    expect(Gate::forUser(superAdminActor())->allows('some-made-up-ability'))->toBeTrue()
        ->and(Gate::forUser(adminActor())->allows('some-made-up-ability'))->toBeFalse()
        ->and(Gate::forUser(staffUser())->allows('roles.view'))->toBeFalse();
});

it('blocks a staff user with no permissions from every access control page', function () {
    $user = staffUser();

    Livewire::actingAs($user)->test('pages::backoffice.roles')->assertStatus(403);
    Livewire::actingAs($user)->test('pages::backoffice.permissions')->assertStatus(403);
    Livewire::actingAs($user)->test('pages::backoffice.users')->assertStatus(403);
    Livewire::actingAs($user)->test('pages::backoffice.maintenance')->assertStatus(403);
});

it('hides Super Admin from the roles list for non-Super-Admin viewers', function () {
    $names = Livewire::actingAs(adminActor())
        ->test('pages::backoffice.roles')
        ->instance()
        ->roles()
        ->pluck('slug');

    expect($names)->not->toContain(Role::SUPER_ADMIN)
        ->and($names)->toContain(Role::ADMIN);
});

it('shows Super Admin to a Super Admin viewer', function () {
    $names = Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles')
        ->instance()
        ->roles()
        ->pluck('slug');

    expect($names)->toContain(Role::SUPER_ADMIN);
});

it('lets Admin create a role and gives it full CRUD over roles it creates', function () {
    $admin = adminActor();

    Livewire::actingAs($admin)
        ->test('pages::backoffice.roles')
        ->call('create')
        ->set('name', 'Editor')
        ->call('save');

    $role = Role::where('name', 'Editor')->firstOrFail();

    Livewire::actingAs($admin)
        ->test('pages::backoffice.roles')
        ->call('edit', $role->id)
        ->set('name', 'Senior Editor')
        ->call('save');

    expect($role->fresh()->name)->toBe('Senior Editor');

    Livewire::actingAs($admin)
        ->test('pages::backoffice.roles')
        ->call('confirmDelete', $role->id)
        ->call('delete');

    expect(Role::find($role->id))->toBeNull();
});

it('never lets anyone, including Super Admin, edit or delete the two system roles', function () {
    $superAdminRole = Role::where('slug', Role::SUPER_ADMIN)->firstOrFail();
    $adminRole = Role::where('slug', Role::ADMIN)->firstOrFail();

    foreach ([superAdminActor(), adminActor()] as $actor) {
        foreach ([$superAdminRole, $adminRole] as $role) {
            Livewire::actingAs($actor)
                ->test('pages::backoffice.roles')
                ->call('edit', $role->id)
                ->assertStatus(403);

            Livewire::actingAs($actor)
                ->test('pages::backoffice.roles')
                ->set('deletingId', $role->id)
                ->call('delete')
                ->assertStatus(403);
        }
    }
});

it('blocks Admin from editing or deleting a portal role, but allows Super Admin', function () {
    config(['multidomain.sub_domains.blog' => 'blog.test']);
    $portalRole = Role::create(['name' => 'Blog', 'slug' => 'blog', 'guard_name' => 'web']);

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.roles')
        ->call('edit', $portalRole->id)
        ->assertStatus(403);

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.roles')
        ->set('deletingId', $portalRole->id)
        ->call('delete')
        ->assertStatus(403);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles')
        ->call('edit', $portalRole->id)
        ->set('name', 'Company Blog')
        ->call('save');

    expect($portalRole->fresh()->name)->toBe('Company Blog');
});

it('blocks Admin from creating, editing, or deleting permissions', function () {
    $admin = adminActor();
    $permission = Permission::where('name', 'roles.view')->firstOrFail();

    Livewire::actingAs($admin)->test('pages::backoffice.permissions')->call('create')->assertStatus(403);
    Livewire::actingAs($admin)->test('pages::backoffice.permissions')->call('edit', $permission->id)->assertStatus(403);
    Livewire::actingAs($admin)->test('pages::backoffice.permissions')->call('confirmDelete', $permission->id)->assertStatus(403);
});

it('lets Super Admin manage permissions freely', function () {
    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.permissions')
        ->call('create')
        ->set('name', 'view-reports')
        ->call('save');

    expect(Permission::where('name', 'view-reports')->exists())->toBeTrue();
});

it('lets Super Admin rename and delete a permission', function () {
    $permission = Permission::create(['name' => 'view-reports', 'guard_name' => 'web']);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.permissions')
        ->call('edit', $permission->id)
        ->set('name', 'view-financial-reports')
        ->call('save');

    expect($permission->fresh()->name)->toBe('view-financial-reports');

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.permissions')
        ->call('confirmDelete', $permission->id)
        ->call('delete');

    expect(Permission::find($permission->id))->toBeNull();
});

it('rejects a permission name that already exists', function () {
    Permission::create(['name' => 'view-reports', 'guard_name' => 'web']);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.permissions')
        ->call('create')
        ->set('name', 'view-reports')
        ->call('save')
        ->assertHasErrors('name');
});

it('assigns a non-system role to a user', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $targetUser = User::factory()->create();

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->call('editRoles', $targetUser->id)
        ->set('selectedRoles', [$role->id])
        ->call('save');

    expect($targetUser->fresh()->hasRole('Editor'))->toBeTrue();
});

it('hides Super Admin from the assignable roles list and strips a smuggled Super Admin id on save', function () {
    $superAdminRole = Role::where('slug', Role::SUPER_ADMIN)->firstOrFail();
    $targetUser = User::factory()->create();

    $roleIds = Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->instance()
        ->roles()
        ->pluck('id');

    expect($roleIds)->not->toContain($superAdminRole->id);

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->call('editRoles', $targetUser->id)
        ->set('selectedRoles', [$superAdminRole->id])
        ->call('save');

    expect($targetUser->fresh()->hasRole('Super Admin'))->toBeFalse();
});

it('blocks Admin from opening the roles editor for a user who already is Super Admin', function () {
    $targetUser = User::factory()->create();
    $targetUser->assignRole(Role::where('slug', Role::SUPER_ADMIN)->firstOrFail());

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->call('editRoles', $targetUser->id)
        ->assertStatus(403);
});

it('filters the users list by name or email', function () {
    User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    $results = Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->set('search', 'Ada')
        ->instance()
        ->users();

    expect($results->pluck('name')->all())->toBe(['Ada Lovelace']);
});
