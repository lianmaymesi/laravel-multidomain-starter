@php $attached = $this->items(); @endphp

<div class="space-y-3" data-media-field="{{ $collection }}">
    @if ($label !== '')
    <flux:label>{{ $label }}</flux:label>
    @endif

    @if ($attached->isNotEmpty())
    <div class="flex flex-wrap gap-3">
        @foreach ($attached as $media)
        <div class="group relative size-24 overflow-hidden border border-zinc-200 bg-zinc-100 dark:border-white/10 dark:bg-white/5" wire:key="attached-{{ $media->id }}" data-attached="{{ $media->id }}">
            <a href="{{ $media->url() }}" target="_blank" rel="noopener" title="{{ $media->name }}">
                <x-media::preview :media="$media" />
            </a>
            <button type="button" wire:click="remove({{ $media->id }})"
                class="absolute end-1 top-1 flex size-6 items-center justify-center bg-zinc-900/70 text-white opacity-80 hover:opacity-100"
                aria-label="{{ __('Remove :name', ['name' => $media->name]) }}">
                <flux:icon.x-mark class="size-4" />
            </button>
        </div>
        @endforeach
    </div>
    @endif

    <flux:button type="button" size="sm" icon="photo" wire:click="openPicker">
        @if ($attached->isEmpty()) {{ __('Add media') }} @elseif ($this->single()) {{ __('Replace') }} @else {{ __('Add more') }} @endif
    </flux:button>

    {{-- Picker --}}
    <flux:modal :name="$this->modalName()" class="w-full md:max-w-5xl">
        @php
            $items = $tab === 'library' ? $this->libraryItems() : collect();
            $hasMore = $items->count() > $limit;
            $items = $items->take($limit);
        @endphp

        <div class="space-y-5">
            <flux:heading size="lg">{{ $this->single() ? __('Choose a file') : __('Add media') }}</flux:heading>

            {{-- Tabs --}}
            <div class="flex gap-1 border-b border-zinc-200 dark:border-white/10" role="tablist">
                @foreach (['upload' => __('Upload files'), 'library' => __('Media library')] as $key => $text)
                <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    wire:click="$set('tab', '{{ $key }}')"
                    class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors
                        {{ $tab === $key ? 'border-blue-500 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-white/50 dark:hover:text-white/80' }}">
                    {{ $text }}
                </button>
                @endforeach
            </div>

            @if ($tab === 'upload')
            <x-media::dropzone :hint="$this->uploadHint()" :failures="$uploadErrors" />
            @else
            <div class="flex flex-wrap items-center gap-3">
                <flux:select wire:model.live="type" class="max-w-40" aria-label="{{ __('File type') }}">
                    <flux:select.option value="">{{ __('All media') }}</flux:select.option>
                    <flux:select.option value="image">{{ __('Images') }}</flux:select.option>
                    <flux:select.option value="video">{{ __('Video') }}</flux:select.option>
                    <flux:select.option value="audio">{{ __('Audio') }}</flux:select.option>
                    <flux:select.option value="document">{{ __('Documents') }}</flux:select.option>
                </flux:select>
                <div class="ms-auto w-full sm:w-64">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search media…') }}" aria-label="{{ __('Search media') }}" />
                </div>
            </div>

            @if ($uploadErrors !== [])
            <ul class="space-y-1 border border-red-500/20 bg-red-500/6 px-4 py-3 text-sm text-red-500">
                @foreach ($uploadErrors as $failure)
                <li>{{ $failure }}</li>
                @endforeach
            </ul>
            @endif

            <div class="max-h-[55vh] overflow-y-auto pe-1">
                @if ($items->isEmpty())
                <div class="py-12 text-center">
                    <flux:text class="text-zinc-500 dark:text-white/50">
                        {{ $search !== '' || $type !== '' ? __('No media matches your filters.') : __('Nothing here yet.') }}
                    </flux:text>
                    <flux:button type="button" size="sm" class="mt-3" wire:click="$set('tab', 'upload')">{{ __('Upload files') }}</flux:button>
                </div>
                @else
                <x-media::grid :items="$items" :selected="$selected" action="toggle" />

                @if ($hasMore)
                <div class="mt-4 text-center">
                    <flux:button type="button" size="sm" wire:click="loadMore">{{ __('Load more') }}</flux:button>
                </div>
                @endif
                @endif
            </div>
            @endif

            <flux:error name="selected" />

            <div class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-white/10">
                <flux:text class="text-sm text-zinc-500 dark:text-white/50">
                    {{ trans_choice('{0} Nothing selected|{1} :count file selected|[2,*] :count files selected', count($selected)) }}
                </flux:text>
                <div class="flex gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="button" variant="primary" wire:click="insert" :disabled="$selected === []">
                        {{ $this->single() ? __('Use this file') : __('Insert') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
