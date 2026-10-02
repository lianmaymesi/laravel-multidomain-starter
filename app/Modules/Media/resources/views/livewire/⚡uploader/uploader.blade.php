<div class="space-y-4">
    @php $items = $this->items(); @endphp

    @if ($items->isNotEmpty())
    <ul class="space-y-2">
        @foreach ($items as $media)
        <li class="flex items-center gap-3 border border-zinc-200 px-3 py-2 dark:border-white/10" wire:key="media-{{ $media->id }}" data-media="{{ $media->uuid }}">
            @if ($media->isImage())
            <img src="{{ $media->conversion('thumb')->url() }}" alt="" class="size-12 shrink-0 object-cover" />
            @else
            <div class="flex size-12 shrink-0 items-center justify-center bg-zinc-100 dark:bg-white/5">
                <flux:icon.document class="size-5 text-zinc-400 dark:text-white/30" />
            </div>
            @endif

            <div class="min-w-0 flex-1">
                <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="block truncate text-sm text-zinc-800 hover:underline dark:text-white/80">{{ $media->original_name }}</a>
                <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ $media->humanSize() }}</flux:text>
            </div>

            <flux:button type="button" variant="ghost" size="sm" icon="trash"
                wire:click="remove({{ $media->id }})"
                wire:confirm="{{ __('Remove :name?', ['name' => $media->original_name]) }}"
                aria-label="{{ __('Remove :name', ['name' => $media->original_name]) }}" />
        </li>
        @endforeach
    </ul>
    @endif

    <flux:field>
        <flux:label>{{ $this->single() && $items->isNotEmpty() ? __('Replace file') : __('Upload a file') }}</flux:label>
        <input type="file" wire:model="file"
            class="block w-full text-sm text-zinc-600 file:me-3 file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:text-zinc-700 hover:file:bg-zinc-200 dark:text-white/60 dark:file:bg-white/10 dark:file:text-white/80" />
        @if ($this->hint() !== '')
        <flux:description>{{ $this->hint() }}</flux:description>
        @endif
        <div wire:loading wire:target="file" class="text-sm text-zinc-500 dark:text-white/50">{{ __('Uploading…') }}</div>
        <flux:error name="file" />
    </flux:field>
</div>
