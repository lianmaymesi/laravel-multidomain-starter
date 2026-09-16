<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.backoffice')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $editingName = '';

    /** @var array<int, int> */
    public array $selectedRoles = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('users.view'), 403);
    }

    public function viewerIsSuperAdmin(): bool
    {
        return auth()->user()->hasRoleSlug(Role::SUPER_ADMIN);
    }

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

    /**
     * Super Admin is invisible to everyone except Super Admin users, so a
     * non-Super-Admin can never assign it to (or see it on) another user.
     */
    public function roles(): Collection
    {
        return Role::orderBy('name')
            ->when(! $this->viewerIsSuperAdmin(), fn ($query) => $query->where('slug', '!=', Role::SUPER_ADMIN))
            ->get();
    }

    public function editRoles(int $userId): void
    {
        abort_unless(Gate::allows('users.assign-roles'), 403);

        $user = User::with('roles')->findOrFail($userId);

        // A non-Super-Admin can't even see the Super Admin role in the
        // checkbox list (see roles() above) — editing a Super Admin user's
        // roles here would otherwise silently sync it away on save().
        abort_if($user->hasRoleSlug(Role::SUPER_ADMIN) && ! $this->viewerIsSuperAdmin(), 403);

        $this->editingId = $user->id;
        $this->editingName = $user->name;
        $this->selectedRoles = $user->roles->pluck('id')->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(Gate::allows('users.assign-roles'), 403);

        $user = User::findOrFail($this->editingId);

        // editingId is a public property Livewire hydrates straight from
        // the request payload, so re-check here too rather than trusting
        // the editRoles() guard alone.
        abort_if($user->hasRoleSlug(Role::SUPER_ADMIN) && ! $this->viewerIsSuperAdmin(), 403);

        // Re-filter server-side against the same visible-roles allow-list,
        // in case a tampered payload tried to smuggle in a role id (e.g.
        // Super Admin) the acting user was never shown.
        $allowedIds = $this->roles()->pluck('id')->all();
        $roleIds = array_intersect($this->selectedRoles, $allowedIds);

        $user->syncRoles(Role::whereIn('id', $roleIds)->get());

        $this->showModal = false;

        session()->flash('status', 'Roles updated.');
    }
};
