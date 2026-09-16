@php $title = "Permissions — {$role->name}"; @endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Permissions for "{{ $role->name }}"</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Toggle a permission to grant or revoke it — changes save immediately.</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('backoffice.roles.index')" wire:navigate>
            Back to Roles
        </flux:button>
    </div>

    @php $assignedIds = $this->assignedPermissionIds(); @endphp

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->groupedPermissions() as $group => $permissions)
        <flux:card class="space-y-3" wire:key="group-{{ $group }}">
            <flux:heading size="lg">{{ $group }}</flux:heading>

            <div class="divide-y divide-zinc-100 dark:divide-white/6">
                @foreach ($permissions as $permission)
                <div class="flex items-center justify-between gap-4 py-2.5" wire:key="permission-{{ $permission->id }}">
                    <div class="flex items-center gap-2">
                        <flux:text class="text-sm text-zinc-700 dark:text-white/75">{{ $permission->name }}</flux:text>
                        @if ($justSavedId === $permission->id)
                        <flux:badge size="sm" color="emerald">Saved</flux:badge>
                        @endif
                    </div>
                    <flux:switch :checked="in_array($permission->id, $assignedIds)" wire:click="togglePermission({{ $permission->id }})" />
                </div>
                @endforeach
            </div>
        </flux:card>
        @empty
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-white/40">No permissions you can grant yet.</flux:text>
        </flux:card>
        @endforelse
    </div>

</div>
