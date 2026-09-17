@php $title = __('Dashboard'); @endphp

<div class="space-y-8">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Overview of your application.') }}</flux:text>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <flux:card as="a" href="{{ route('backoffice.users.index') }}" class="space-y-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
                <flux:icon.users class="size-4.5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="lg">{{ $totalUsers }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-white/50">{{ __('Total Users') }}</flux:text>
            </div>
        </flux:card>

        <flux:card as="a" href="{{ route('backoffice.users.index') }}" class="space-y-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
                <flux:icon.user-group class="size-4.5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="lg">{{ $staffUsers }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-white/50">{{ __('Staff Members') }}</flux:text>
            </div>
        </flux:card>

        <flux:card as="a" href="{{ route('backoffice.roles.index') }}" class="space-y-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
                <flux:icon.shield-check class="size-4.5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="lg">{{ $totalRoles }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-white/50">{{ __('Roles') }}</flux:text>
            </div>
        </flux:card>

        <flux:card as="a" href="{{ route('backoffice.permissions.index') }}" class="space-y-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
                <flux:icon.key class="size-4.5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="lg">{{ $totalPermissions }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-white/50">{{ __('Permissions') }}</flux:text>
            </div>
        </flux:card>

    </div>

    {{-- Recent users --}}
    <flux:card class="space-y-5">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Recent Users') }}</flux:heading>
            <flux:button :href="route('backoffice.users.index')" variant="ghost" size="sm">{{ __('View all') }}</flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Joined') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($recentUsers as $user)
                <flux:table.row>
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar size="xs" name="{{ $user->name }}" />
                        {{ $user->name }}
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$user->privilege === 'staff' ? 'blue' : 'zinc'">
                            {{ ucfirst($user->privilege) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $user->created_at?->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500 dark:text-white/40">{{ __('No users yet.') }}</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

</div>
