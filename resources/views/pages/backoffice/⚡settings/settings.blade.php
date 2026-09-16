@php $title = 'Settings'; @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">Settings</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">System-wide behavior.</flux:text>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">Language URL mode</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Only one of these is ever active — switching here changes how every portal's URLs are generated.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:radio.group wire:model="urlMode" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <flux:radio value="path" label="Path prefix" description="example.com/ · example.com/ar · example.com/ta" />
                <flux:radio value="query" label="Query string" description="example.com/?lang=ar" />
            </flux:radio.group>

            <flux:button type="submit" variant="primary">Save</flux:button>
        </form>
    </flux:card>

</div>
