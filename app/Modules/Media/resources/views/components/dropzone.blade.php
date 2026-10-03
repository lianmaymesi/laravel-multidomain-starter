{{--
    Drag-and-drop / click-to-pick upload area. Lives inside a Livewire component
    that uses BrowsesLibrary: files land in its $uploads and are stored by
    updatedUploads(). Pass the upload hint and the per-file failures to show.
--}}
@props(['hint' => '', 'failures' => []])

<div class="space-y-3">
    <div
        x-data="{ dragging: false, progress: null }"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="
            dragging = false;
            if (! $event.dataTransfer.files.length) return;
            progress = 0;
            $wire.uploadMultiple('uploads', $event.dataTransfer.files,
                () => progress = null,
                () => progress = null,
                (e) => progress = e.detail.progress)
        "
        x-on:livewire-upload-start="progress = 0"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:livewire-upload-finish="progress = null"
        x-on:livewire-upload-error="progress = null"
        x-bind:class="dragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/10' : 'border-zinc-300 dark:border-white/15'"
        class="flex flex-col items-center justify-center gap-3 border-2 border-dashed px-6 py-12 text-center transition-colors"
        data-dropzone
    >
        <flux:icon.cloud-arrow-up class="size-10 text-zinc-400 dark:text-white/30" />

        <div>
            <flux:heading size="lg">{{ __('Drop files to upload') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-white/50">{{ __('or') }}</flux:text>
        </div>

        <flux:button type="button" size="sm" x-on:click="$refs.input.click()">{{ __('Select files') }}</flux:button>
        <input type="file" multiple x-ref="input" wire:model="uploads" class="sr-only" aria-label="{{ __('Select files') }}" />

        @if ($hint !== '')
        <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ $hint }}</flux:text>
        @endif

        <div x-show="progress !== null" x-cloak class="w-full max-w-xs">
            <div class="h-1.5 w-full overflow-hidden bg-zinc-200 dark:bg-white/10">
                <div class="h-full bg-blue-500 transition-all" x-bind:style="`width: ${progress}%`"></div>
            </div>
            <flux:text class="mt-1 text-xs text-zinc-500 dark:text-white/50">{{ __('Uploading…') }} <span x-text="progress + '%'"></span></flux:text>
        </div>
    </div>

    @error('uploads.*')
    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
    @enderror

    @if ($failures !== [])
    <ul class="space-y-1 border border-red-500/20 bg-red-500/6 px-4 py-3 text-sm text-red-500" data-upload-errors>
        @foreach ($failures as $failure)
        <li>{{ $failure }}</li>
        @endforeach
    </ul>
    @endif
</div>
