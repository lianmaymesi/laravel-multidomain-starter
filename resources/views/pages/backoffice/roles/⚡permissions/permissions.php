<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    public Role $role;

    public ?int $justSavedId = null;

    public function mount(Role $role): void
    {
        abort_unless(Gate::allows('roles.assign-permissions'), 403);
        abort_if($role->locked, 403, "\"{$role->name}\" is a system role and can't be modified.");
        abort_if($role->isPortalRole() && ! $this->viewerIsSuperAdmin(), 403, "\"{$role->name}\" is a portal role and can't be modified.");

        $this->role = $role;
    }

    public function viewerIsSuperAdmin(): bool
    {
        return auth()->user()->hasRoleSlug(Role::SUPER_ADMIN);
    }

    /**
     * An Admin can only grant permissions it itself holds — this is what
     * stops an Admin-created role from being escalated with a permission
     * (e.g. permissions.create) Admin doesn't have, without a separate
     * explicit check.
     */
    private function assignablePermissions(): Collection
    {
        return $this->viewerIsSuperAdmin()
            ? Permission::orderBy('name')->get()
            : auth()->user()->getAllPermissions()->sortBy('name')->values();
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public function groupedPermissions(): Collection
    {
        return $this->assignablePermissions()->groupBy(
            fn (Permission $permission) => str($permission->name)->before('.')->headline()->toString(),
        );
    }

    /** @return array<int, int> */
    public function assignedPermissionIds(): array
    {
        return $this->role->permissions->pluck('id')->all();
    }

    public function togglePermission(int $permissionId): void
    {
        $permission = $this->assignablePermissions()->firstWhere('id', $permissionId);

        abort_if(! $permission, 403);

        $wasGranted = $this->role->hasPermissionTo($permission);

        if ($wasGranted) {
            $this->role->revokePermissionTo($permission);
        } else {
            $this->role->givePermissionTo($permission);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->withProperties(['permission' => $permission->name])
            ->log($wasGranted ? 'permission revoked' : 'permission granted');

        $this->role->load('permissions');
        $this->justSavedId = $permissionId;
    }
};
