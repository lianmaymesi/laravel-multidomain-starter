<?php

use App\Models\User;
use Atrium\Core\Models\Permission;
use Atrium\Core\Models\Role;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    public int $totalUsers = 0;

    public int $staffUsers = 0;

    public int $totalRoles = 0;

    public int $totalPermissions = 0;

    /** @var Collection<int, User> */
    public $recentUsers;

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->staffUsers = User::where('privilege', 'staff')->count();
        $this->totalRoles = Role::count();
        $this->totalPermissions = Permission::count();
        $this->recentUsers = User::latest()->limit(5)->get();
    }
};
