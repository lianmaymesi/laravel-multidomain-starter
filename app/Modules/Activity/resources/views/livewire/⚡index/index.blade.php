@php $title = __('Activity Log'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Activity Log') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Click the comment icon on any change to ask why, or to leave context for the next person who finds it.') }}</flux:text>
    </div>

    <livewire:activity-timeline />

</div>
