<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function staffUser(): User
{
    return User::factory()->create([
        'privilege' => 'staff',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);
}

it('creates a role with permissions', function () {
    $permission = Permission::create(['name' => 'edit-articles', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.roles')
        ->call('create')
        ->set('name', 'editor')
        ->set('selectedPermissions', [$permission->id])
        ->call('save');

    $role = Role::where('name', 'editor')->firstOrFail();

    expect($role->hasPermissionTo('edit-articles'))->toBeTrue();
});

it('updates a role and its permissions', function () {
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $permission = Permission::create(['name' => 'edit-articles', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.roles')
        ->call('edit', $role->id)
        ->set('name', 'senior-editor')
        ->set('selectedPermissions', [$permission->id])
        ->call('save');

    $role->refresh();

    expect($role->name)->toBe('senior-editor')
        ->and($role->hasPermissionTo('edit-articles'))->toBeTrue();
});

it('deletes a role', function () {
    $role = Role::create(['name' => 'temp-role', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.roles')
        ->call('confirmDelete', $role->id)
        ->call('delete');

    expect(Role::find($role->id))->toBeNull();
});

it('refuses to delete a role tied to a portal subdomain', function () {
    $role = Role::create(['name' => 'app', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.roles')
        ->call('confirmDelete', $role->id)
        ->assertSet('confirmingDelete', false);

    expect(Role::find($role->id))->not->toBeNull();
});

it('creates a permission', function () {
    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.permissions')
        ->call('create')
        ->set('name', 'view-reports')
        ->call('save');

    expect(Permission::where('name', 'view-reports')->exists())->toBeTrue();
});

it('deletes a permission', function () {
    $permission = Permission::create(['name' => 'temp-permission', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.permissions')
        ->call('confirmDelete', $permission->id)
        ->call('delete');

    expect(Permission::find($permission->id))->toBeNull();
});

it('assigns a role to a user', function () {
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $targetUser = User::factory()->create();

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.users')
        ->call('editRoles', $targetUser->id)
        ->set('selectedRoles', [$role->id])
        ->call('save');

    expect($targetUser->fresh()->hasRole('editor'))->toBeTrue();
});

it('renames a permission', function () {
    $permission = Permission::create(['name' => 'view-reports', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.permissions')
        ->call('edit', $permission->id)
        ->set('name', 'view-financial-reports')
        ->call('save');

    expect($permission->fresh()->name)->toBe('view-financial-reports');
});

it('rejects a permission name that already exists', function () {
    Permission::create(['name' => 'view-reports', 'guard_name' => 'web']);

    Livewire::actingAs(staffUser())
        ->test('pages::backoffice.permissions')
        ->call('create')
        ->set('name', 'view-reports')
        ->call('save')
        ->assertHasErrors('name');
});

it('filters the users list by name or email', function () {
    User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    $results = Livewire::actingAs(staffUser())
        ->test('pages::backoffice.users')
        ->set('search', 'Ada')
        ->instance()
        ->users();

    expect($results->pluck('name')->all())->toBe(['Ada Lovelace']);
});
