@php $title = __('Permissions'); @endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Permissions') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Manage the individual permissions roles can be granted.') }}</flux:text>
        </div>
        @can('permissions.create')
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New Permission') }}</flux:button>
        @endcan
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->permissions() as $permission)
                <flux:table.row wire:key="permission-{{ $permission->id }}">
                    <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $permission->name }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $permission->roles_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            @can('permissions.edit')
                            <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="edit({{ $permission->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @can('permissions.delete')
                            <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $permission->id }})"
                                class="text-red-400! hover:text-red-300!">
                                {{ __('Delete') }}
                            </flux:button>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="text-center text-zinc-500 dark:text-white/40">{{ __('No permissions yet.') }}</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create / Edit modal --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Permission') : __('New Permission') }}</flux:heading>
            </div>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('e.g. edit-articles') }}" />
                <flux:error name="name" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete confirmation modal --}}
    <flux:modal wire:model="confirmingDelete" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete permission?') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-white/50">{{ __('This cannot be undone. Roles granting this permission will lose it.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
