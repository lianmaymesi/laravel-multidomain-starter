<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.backoffice')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $editingName = '';

    /** @var array<int, int> */
    public array $selectedRoles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function users(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::query()
            ->when($this->search, fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->with('roles')
            ->orderBy('name')
            ->paginate(10);
    }

    public function roles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    public function editRoles(int $userId): void
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->editingId = $user->id;
        $this->editingName = $user->name;
        $this->selectedRoles = $user->roles->pluck('id')->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $user = User::findOrFail($this->editingId);
        $user->syncRoles(Role::whereIn('id', $this->selectedRoles)->get());

        $this->showModal = false;

        session()->flash('status', 'Roles updated.');
    }
};
