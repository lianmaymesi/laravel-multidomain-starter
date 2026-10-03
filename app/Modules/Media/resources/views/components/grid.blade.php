{{--
    Grid of library tiles. Clicking a tile calls $action($id) on the
    surrounding Livewire component; tiles whose id is in $selected are
    highlighted with a check.
--}}
@props(['items', 'selected' => [], 'action' => 'select'])

<div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6">
    @foreach ($items as $media)
    @php $isSelected = in_array($media->id, $selected, true); @endphp
    <button type="button"
        wire:key="tile-{{ $media->id }}"
        wire:click="{{ $action }}({{ $media->id }})"
        title="{{ $media->name }}"
        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
        data-media-tile="{{ $media->id }}"
        class="group relative aspect-square overflow-hidden border bg-zinc-100 transition dark:bg-white/5
            {{ $isSelected ? 'border-blue-500 ring-2 ring-blue-500' : 'border-zinc-200 hover:border-zinc-400 dark:border-white/10 dark:hover:border-white/30' }}">
        <x-media::preview :media="$media" />

        @if ($isSelected)
        <span class="absolute end-1.5 top-1.5 flex size-5 items-center justify-center bg-blue-500 text-white">
            <flux:icon.check class="size-3.5" />
        </span>
        @endif
    </button>
    @endforeach
</div>
