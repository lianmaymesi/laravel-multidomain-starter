{{-- Square preview of a library item: image thumbnail, or a type icon + extension. --}}
@props(['media', 'size' => 'thumb'])

@if ($media->isImage())
<img src="{{ $media->conversion($size)->url() }}" alt="{{ $media->alt ?? '' }}" loading="lazy" class="size-full object-cover" />
@else
<div class="flex size-full flex-col items-center justify-center gap-1.5 p-2 text-zinc-500 dark:text-white/50">
    @switch($media->type())
    @case('video') <flux:icon.film class="size-7" /> @break
    @case('audio') <flux:icon.musical-note class="size-7" /> @break
    @default <flux:icon.document-text class="size-7" />
    @endswitch
    <span class="text-[10px] font-semibold tracking-wider">{{ $media->extension() }}</span>
    <span class="w-full truncate text-center text-[11px]">{{ $media->name }}</span>
</div>
@endif
