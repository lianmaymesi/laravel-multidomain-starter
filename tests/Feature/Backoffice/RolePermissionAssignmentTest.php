<?php

use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('toggles a permission on and off immediately, with no save button', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $permission = Permission::where('name', 'roles.view')->firstOrFail();

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $role])
        ->call('togglePermission', $permission->id);

    expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $role])
        ->call('togglePermission', $permission->id);

    expect($role->fresh()->hasPermissionTo($permission))->toBeFalse();
});

it('lets Admin toggle a permission it holds but not one it lacks', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $held = Permission::where('name', 'users.view')->firstOrFail();
    $notHeld = Permission::where('name', 'permissions.create')->firstOrFail();

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $role])
        ->call('togglePermission', $held->id);

    expect($role->fresh()->hasPermissionTo($held))->toBeTrue();

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $role])
        ->call('togglePermission', $notHeld->id)
        ->assertStatus(403);

    expect($role->fresh()->hasPermissionTo($notHeld))->toBeFalse();
});

it('blocks access to the permissions page for locked system roles, even for Super Admin', function () {
    $superAdminRole = Role::where('slug', Role::SUPER_ADMIN)->firstOrFail();
    $adminRole = Role::where('slug', Role::ADMIN)->firstOrFail();

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $superAdminRole])
        ->assertStatus(403);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $adminRole])
        ->assertStatus(403);
});

it('blocks Admin from a portal role\'s permissions page but allows Super Admin', function () {
    config(['multidomain.sub_domains.blog' => 'blog.test']);
    $portalRole = Role::create(['name' => 'Blog', 'slug' => 'blog', 'guard_name' => 'web']);
    $permission = Permission::where('name', 'roles.view')->firstOrFail();

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $portalRole])
        ->assertStatus(403);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $portalRole])
        ->call('togglePermission', $permission->id);

    expect($portalRole->fresh()->hasPermissionTo($permission))->toBeTrue();
});

it('groups permissions by their dot-case module prefix', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    $groups = Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $role])
        ->instance()
        ->groupedPermissions()
        ->keys();

    expect($groups)->toContain('Roles', 'Permissions', 'Users', 'Maintenance');
});
