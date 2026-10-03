@php
    $title = __('Media Library');
    $items = $this->libraryItems();
    $hasMore = $items->count() > $limit;
    $items = $items->take($limit);
    $active = $this->active();
@endphp

<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Media Library') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Every uploaded file in one place. Files can be reused anywhere a media picker appears.') }}</flux:text>
        </div>
        <flux:button type="button" variant="primary" icon="arrow-up-tray" wire:click="$toggle('showUploader')">{{ __('Add new') }}</flux:button>
    </div>

    @if ($status)
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-500" data-status>
        {{ $status }}
    </div>
    @endif

    @if ($showUploader)
    <x-media::dropzone :hint="$this->uploadHint()" :failures="$uploadErrors" />
    @endif

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:select wire:model.live="type" class="max-w-44" aria-label="{{ __('File type') }}">
            <flux:select.option value="">{{ __('All media') }}</flux:select.option>
            <flux:select.option value="image">{{ __('Images') }}</flux:select.option>
            <flux:select.option value="video">{{ __('Video') }}</flux:select.option>
            <flux:select.option value="audio">{{ __('Audio') }}</flux:select.option>
            <flux:select.option value="document">{{ __('Documents') }}</flux:select.option>
        </flux:select>

        <flux:button type="button" size="sm" :variant="$bulk ? 'primary' : 'filled'" wire:click="toggleBulk">
            {{ $bulk ? __('Cancel') : __('Bulk select') }}
        </flux:button>

        @if ($bulk)
        <flux:button type="button" size="sm" variant="danger" icon="trash"
            :disabled="$checked === []"
            wire:click="deleteChecked"
            wire:confirm="{{ __('Delete the selected files permanently? They will also disappear from everywhere they are used.') }}">
            {{ __('Delete selected') }} ({{ count($checked) }})
        </flux:button>
        @endif

        <div class="ms-auto w-full sm:w-64">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search media…') }}" aria-label="{{ __('Search media') }}" />
        </div>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Grid --}}
        <div class="min-w-0 flex-1 space-y-4">
            @if ($items->isEmpty())
            <flux:card class="py-12 text-center">
                <flux:icon.photo class="mx-auto size-10 text-zinc-300 dark:text-white/20" />
                <flux:text class="mt-3 text-zinc-500 dark:text-white/50">
                    {{ $search !== '' || $type !== '' ? __('No media matches your filters.') : __('No media yet — drop some files in to get started.') }}
                </flux:text>
            </flux:card>
            @else
            <x-media::grid :items="$items"
                :selected="$bulk ? $checked : array_filter([$activeId])"
                :action="$bulk ? 'toggleCheck' : 'select'" />

            @if ($hasMore)
            <div class="text-center">
                <flux:button type="button" wire:click="loadMore">{{ __('Load more') }}</flux:button>
            </div>
            @endif
            @endif
        </div>

        {{-- Details --}}
        @if ($active && ! $bulk)
        <flux:card class="w-full shrink-0 space-y-5 lg:sticky lg:top-6 lg:w-80" wire:key="details-{{ $active->id }}" data-details>
            <div class="flex items-start justify-between gap-2">
                <flux:heading size="lg">{{ __('Details') }}</flux:heading>
                <flux:button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="closeDetails" aria-label="{{ __('Close') }}" />
            </div>

            <div class="overflow-hidden border border-zinc-200 bg-zinc-100 dark:border-white/10 dark:bg-white/5">
                @switch($active->type())
                @case('image')
                <img src="{{ $active->conversion('medium')->url() }}" alt="{{ $active->alt ?? '' }}" class="max-h-64 w-full object-contain" />
                @break
                @case('video')
                <video src="{{ $active->url() }}" controls class="max-h-64 w-full"></video>
                @break
                @case('audio')
                <div class="p-4"><audio src="{{ $active->url() }}" controls class="w-full"></audio></div>
                @break
                @default
                <div class="aspect-video"><x-media::preview :media="$active" /></div>
                @endswitch
            </div>

            <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs">
                <dt class="text-zinc-500 dark:text-white/40">{{ __('File') }}</dt>
                <dd class="truncate" title="{{ $active->original_name }}">{{ $active->original_name }}</dd>
                <dt class="text-zinc-500 dark:text-white/40">{{ __('Type') }}</dt>
                <dd>{{ $active->mime_type }}</dd>
                <dt class="text-zinc-500 dark:text-white/40">{{ __('Size') }}</dt>
                <dd>{{ $active->humanSize() }}</dd>
                @if ($active->width())
                <dt class="text-zinc-500 dark:text-white/40">{{ __('Dimensions') }}</dt>
                <dd>{{ $active->width() }} × {{ $active->height() }}</dd>
                @endif
                <dt class="text-zinc-500 dark:text-white/40">{{ __('Uploaded') }}</dt>
                <dd>{{ $active->created_at->forUser()->format('M j, Y') }}@if ($active->uploader) · {{ $active->uploader->name }}@endif</dd>
            </dl>

            @if ($this->canEdit($active))
            <form wire:submit="save" class="space-y-3">
                <flux:input wire:model="name" :label="__('Title')" />
                @if ($active->isImage())
                <flux:input wire:model="alt" :label="__('Alt text')" :description="__('Describes the image for screen readers.')" />
                @endif
                <flux:button type="submit" size="sm" variant="primary">{{ __('Save') }}</flux:button>
            </form>
            @endif

            <div x-data="{ copied: false }">
                <flux:label>{{ __('File URL') }}</flux:label>
                <div class="mt-2 flex gap-2">
                    <input type="text" readonly x-ref="url" value="{{ $active->url() }}" x-on:focus="$el.select()"
                        class="min-w-0 flex-1 border border-zinc-200 bg-zinc-50 px-2 py-1.5 text-xs text-zinc-600 dark:border-white/10 dark:bg-white/5 dark:text-white/60" />
                    <flux:button type="button" size="sm" icon="clipboard"
                        x-on:click="navigator.clipboard.writeText($refs.url.value); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-show="! copied">{{ __('Copy') }}</span>
                        <span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
                    </flux:button>
                </div>
                @if ($active->disk !== 'public')
                <flux:text class="mt-1 text-xs text-zinc-500 dark:text-white/40">{{ __('Private file — this link expires after :minutes minutes.', ['minutes' => config('media.temporary_url_minutes')]) }}</flux:text>
                @endif
            </div>

            <div>
                <flux:heading size="sm">{{ __('Used in') }}</flux:heading>
                @forelse ($active->attachments as $attachment)
                <flux:text class="text-xs">
                    {{ str(class_basename($attachment->mediable_type))->headline() }} #{{ $attachment->mediable_id }}
                    · {{ $attachment->collection }}
                    @if ($attachment->mediable === null) <span class="text-zinc-400">({{ __('deleted') }})</span> @endif
                </flux:text>
                @empty
                <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Not used anywhere yet.') }}</flux:text>
                @endforelse
            </div>

            @if ($this->canEdit($active))
            <flux:button type="button" size="sm" variant="danger" icon="trash" class="w-full"
                wire:click="delete({{ $active->id }})"
                wire:confirm="{{ $active->attachments->isEmpty()
                    ? __('Delete this file permanently?')
                    : trans_choice('Delete this file permanently? It is used in :count place and will disappear from there.|Delete this file permanently? It is used in :count places and will disappear from all of them.', $active->attachments->count()) }}">
                {{ __('Delete permanently') }}
            </flux:button>
            @endif
        </flux:card>
        @endif
    </div>

</div>
