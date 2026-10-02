<flux:card class="space-y-4">
    <div>
        <flux:heading size="lg">{{ __('Profile photo') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Shown next to your name. Upload a new photo or pick one you uploaded before.') }}</flux:text>
    </div>

    <livewire:media::field :model="auth()->user()" collection="avatar" />
</flux:card>
