<?php

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('roles.view'), 403);
    }

    public function viewerIsSuperAdmin(): bool
    {
        return auth()->user()->hasRoleSlug(Role::SUPER_ADMIN);
    }

    /**
     * Super Admin is invisible to everyone except Super Admin users.
     */
    public function roles(): Collection
    {
        return Role::withCount(['users', 'permissions'])
            ->when(! $this->viewerIsSuperAdmin(), fn ($query) => $query->where('slug', '!=', Role::SUPER_ADMIN))
            ->orderBy('name')
            ->get();
    }

    /**
     * System roles (Super Admin, Admin) can never be edited or deleted by
     * anyone, Super Admin included. Portal roles (slug matches a configured
     * subdomain — see Role::isPortalRole()) drive post-login redirects and
     * the `portal:{slug}` middleware, so only Super Admin may touch them;
     * Admin gets read + assign-to-user only.
     */
    private function guardMutable(Role $role): void
    {
        abort_if($role->locked, 403, "\"{$role->name}\" is a system role and can't be modified.");
        abort_if($role->isPortalRole() && ! $this->viewerIsSuperAdmin(), 403, "\"{$role->name}\" is a portal role and can't be modified.");
    }

    public function create(): void
    {
        abort_unless(Gate::allows('roles.create'), 403);

        $this->reset(['editingId', 'name']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $roleId): void
    {
        abort_unless(Gate::allows('roles.edit'), 403);

        $role = Role::findOrFail($roleId);
        $this->guardMutable($role);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(Gate::allows($this->editingId ? 'roles.edit' : 'roles.create'), 403);

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);
            $this->guardMutable($role);
            $role->update(['name' => $this->name]);
        } else {
            Role::create(['name' => $this->name, 'guard_name' => 'web']);
        }

        $this->showModal = false;

        session()->flash('status', 'Role saved.');
    }

    public function confirmDelete(int $roleId): void
    {
        abort_unless(Gate::allows('roles.delete'), 403);

        $role = Role::findOrFail($roleId);

        if ($role->locked || ($role->isPortalRole() && ! $this->viewerIsSuperAdmin())) {
            session()->flash('error', "\"{$role->name}\" can't be deleted.");

            return;
        }

        $this->deletingId = $roleId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        abort_unless(Gate::allows('roles.delete'), 403);

        $role = Role::findOrFail($this->deletingId);
        $this->guardMutable($role);

        $role->delete();
        $this->deletingId = null;
        $this->confirmingDelete = false;

        session()->flash('status', 'Role deleted.');
    }
};
