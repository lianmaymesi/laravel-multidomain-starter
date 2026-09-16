@php $title = 'Roles'; @endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Roles</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Manage roles and the permissions assigned to them.</flux:text>
        </div>
        @can('roles.create')
        <flux:button variant="primary" icon="plus" wire:click="create">New Role</flux:button>
        @endcan
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    @if (session('error'))
    <div class="border border-red-500/20 bg-red-500/6 px-4 py-3 text-sm text-red-400">
        {{ session('error') }}
    </div>
    @endif

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Permissions</flux:table.column>
                <flux:table.column>Users</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->roles() as $role)
                <flux:table.row wire:key="role-{{ $role->id }}">
                    <flux:table.cell class="font-medium text-zinc-900 dark:text-white">
                        {{ $role->name }}
                        @if ($role->locked)
                        <flux:badge size="sm" color="amber" class="ml-2">System</flux:badge>
                        @elseif ($role->isPortalRole())
                        <flux:badge size="sm" color="zinc" class="ml-2">Portal</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $role->permissions_count }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $role->users_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            @can('roles.assign-permissions')
                            @unless ($role->locked || ($role->isPortalRole() && ! $this->viewerIsSuperAdmin()))
                            <flux:button size="xs" variant="ghost" icon="key" :href="route('backoffice.roles.permissions', $role)" wire:navigate>
                                Permissions
                            </flux:button>
                            @endunless
                            @endcan
                            @can('roles.edit')
                            @unless ($role->locked || ($role->isPortalRole() && ! $this->viewerIsSuperAdmin()))
                            <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="edit({{ $role->id }})">
                                Edit
                            </flux:button>
                            @endunless
                            @endcan
                            @can('roles.delete')
                            @unless ($role->locked || ($role->isPortalRole() && ! $this->viewerIsSuperAdmin()))
                            <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $role->id }})"
                                class="text-red-400! hover:text-red-300!">
                                Delete
                            </flux:button>
                            @endunless
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500 dark:text-white/40">No roles yet.</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create / Edit modal --}}
    <flux:modal wire:model="showModal" class="md:w-[28rem]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Role' : 'New Role' }}</flux:heading>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="e.g. editor" />
                <flux:error name="name" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete confirmation modal --}}
    <flux:modal wire:model="confirmingDelete" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete role?</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-white/50">This cannot be undone. Users with this role will lose the permissions it grants.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
