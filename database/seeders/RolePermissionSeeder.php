<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Modules\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Permissions Admin is never granted — managing the permission catalog
     * itself is reserved for Super Admin, so Admin can't mint a permission
     * and then grant it to a role it creates.
     *
     * @var array<int, string>
     */
    private const ADMIN_EXCLUDED_PERMISSIONS = [
        'permissions.create',
        'permissions.edit',
        'permissions.delete',

        // Switching features on and off changes the whole app — Super Admin only.
        'modules.manage',
    ];

    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'settings.edit',
        'modules.manage',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'roles.assign-permissions',
        'permissions.view',
        'permissions.create',
        'permissions.edit',
        'permissions.delete',
        'users.view',
        'users.assign-roles',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Feature modules declare their own permissions (ModuleProvider::permissions()),
        // seeded even while a module is off so roles keep them across a toggle.
        foreach ([...self::PERMISSIONS, ...Module::contributions('permissions')] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(
            ['slug' => Role::SUPER_ADMIN, 'guard_name' => 'web'],
            ['name' => 'Super Admin', 'locked' => true],
        );
        $superAdmin->forceFill(['name' => 'Super Admin', 'locked' => true])->save();
        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());

        $admin = Role::firstOrCreate(
            ['slug' => Role::ADMIN, 'guard_name' => 'web'],
            ['name' => 'Admin', 'locked' => true],
        );
        $admin->forceFill(['name' => 'Admin', 'locked' => true])->save();
        $admin->syncPermissions(
            Permission::where('guard_name', 'web')
                ->whereNotIn('name', [...self::ADMIN_EXCLUDED_PERMISSIONS, ...Module::contributions('permissions.super-admin-only')])
                ->get(),
        );
    }
}
