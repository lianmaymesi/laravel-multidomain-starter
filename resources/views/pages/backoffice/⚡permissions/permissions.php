<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

new #[Layout('layouts.backoffice')] class extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('permissions.view'), 403);
    }

    public function permissions(): Collection
    {
        return Permission::withCount('roles')->orderBy('name')->get();
    }

    public function create(): void
    {
        abort_unless(Gate::allows('permissions.create'), 403);

        $this->reset(['editingId', 'name']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $permissionId): void
    {
        abort_unless(Gate::allows('permissions.edit'), 403);

        $permission = Permission::findOrFail($permissionId);

        $this->editingId = $permission->id;
        $this->name = $permission->name;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(Gate::allows($this->editingId ? 'permissions.edit' : 'permissions.create'), 403);

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId) {
            Permission::findOrFail($this->editingId)->update(['name' => $this->name]);
        } else {
            Permission::create(['name' => $this->name, 'guard_name' => 'web']);
        }

        $this->showModal = false;

        session()->flash('status', 'Permission saved.');
    }

    public function confirmDelete(int $permissionId): void
    {
        abort_unless(Gate::allows('permissions.delete'), 403);

        $this->deletingId = $permissionId;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        abort_unless(Gate::allows('permissions.delete'), 403);

        Permission::findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->confirmingDelete = false;

        session()->flash('status', 'Permission deleted.');
    }
};
