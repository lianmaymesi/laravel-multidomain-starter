@php $title = 'Maintenance'; @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">Maintenance</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Take a portal down for visitors with a branded maintenance page. Backoffice can't be put into maintenance from here.</flux:text>
    </div>

    @if (config('maintenance.global'))
    <div class="border border-amber-500/20 bg-amber-500/6 px-4 py-3 text-sm text-amber-400">
        <strong>APP_MAINTENANCE is on.</strong> Every portal, including backoffice, is currently showing the maintenance page — this overrides the toggles below. Set it back to <code>false</code> in your environment to restore normal access.
    </div>
    @endif

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <div class="space-y-4">
        @foreach ($this->portals() as $portal)
        @php $setting = $this->settings()->get($portal); @endphp
        <flux:card class="space-y-4" wire:key="portal-{{ $portal }}">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg" class="capitalize">{{ $portal }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-white/50">
                        {{ $setting?->maintenance_mode ? 'Currently under maintenance.' : 'Currently online.' }}
                    </flux:text>
                </div>
                @can('maintenance.update')
                <flux:switch :checked="(bool) $setting?->maintenance_mode" wire:click="toggle('{{ $portal }}')" />
                @else
                <flux:badge size="sm" :color="$setting?->maintenance_mode ? 'amber' : 'zinc'">
                    {{ $setting?->maintenance_mode ? 'Under maintenance' : 'Online' }}
                </flux:badge>
                @endcan
            </div>

            @can('maintenance.update')
            <form wire:submit="saveMessage('{{ $portal }}')" class="flex items-end gap-3">
                <flux:field class="flex-1">
                    <flux:label>Maintenance message (optional)</flux:label>
                    <flux:input wire:model="messages.{{ $portal }}" placeholder="We'll be back shortly." />
                </flux:field>
                <flux:button type="submit" variant="ghost">Save message</flux:button>
            </form>
            @endcan
        </flux:card>
        @endforeach
    </div>

</div>
