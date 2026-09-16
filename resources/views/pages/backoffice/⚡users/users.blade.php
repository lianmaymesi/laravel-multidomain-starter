@php $title = 'Users'; @endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Users</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Manage user accounts and assign roles.</flux:text>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search users…" icon="magnifying-glass" class="max-w-xs" />
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <flux:card>
        <flux:table :paginate="$this->users()">
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Portal</flux:table.column>
                <flux:table.column>Roles</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users() as $user)
                <flux:table.row wire:key="user-{{ $user->id }}">
                    <flux:table.cell class="flex items-center gap-3 font-medium text-zinc-900 dark:text-white">
                        <flux:avatar size="xs" name="{{ $user->name }}" />
                        {{ $user->name }}
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$user->privilege === 'staff' ? 'blue' : 'zinc'">
                            {{ ucfirst($user->privilege) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($user->roles as $role)
                            <flux:badge size="sm" color="zinc">{{ $role->name }}</flux:badge>
                            @empty
                            <span class="text-zinc-400 dark:text-white/30">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            @can('activity.view')
                            <x-activity-log-button :model="$user" />
                            @endcan
                            @can('users.assign-roles')
                            <flux:button size="xs" variant="ghost" icon="shield-check" wire:click="editRoles({{ $user->id }})">
                                Roles
                            </flux:button>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 dark:text-white/40">No users found.</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Assign roles modal --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Roles for {{ $editingName }}</flux:heading>
            </div>

            <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 dark:border-white/10 p-3">
                @forelse ($this->roles() as $role)
                <flux:checkbox wire:model="selectedRoles" value="{{ $role->id }}" label="{{ $role->name }}" />
                @empty
                <flux:text class="text-sm text-zinc-500 dark:text-white/40">No roles yet — create one first.</flux:text>
                @endforelse
            </div>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

</div>
