<flux:card class="space-y-4">
    <div>
        <flux:heading size="lg">{{ __('Profile photo') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Shown next to your name. A new photo replaces the old one.') }}</flux:text>
    </div>

    <livewire:media::uploader :model="auth()->user()" collection="avatar" />
</flux:card>
