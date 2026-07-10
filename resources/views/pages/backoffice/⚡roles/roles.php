<?php

use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.backoffice')] class extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int, int> */
    public array $selectedPermissions = [];

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function roles(): Collection
    {
        return Role::withCount(['users', 'permissions'])->orderBy('name')->get();
    }

    public function permissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->all();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->editingId),
            ],
        ]);

        $role = $this->editingId
            ? tap(Role::findOrFail($this->editingId))->update(['name' => $this->name])
            : Role::create(['name' => $this->name, 'guard_name' => 'web']);

        $role->syncPermissions(Permission::whereIn('id', $this->selectedPermissions)->get());

        $this->showModal = false;

        session()->flash('status', 'Role saved.');
    }

    public function confirmDelete(int $roleId): void
    {
        $this->deletingId = $roleId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        Role::findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->confirmingDelete = false;

        session()->flash('status', 'Role deleted.');
    }
};
