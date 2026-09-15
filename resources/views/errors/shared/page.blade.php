@extends('layouts.errors', ['title' => $meta['title']])

@section('content')
<div class="mt-8">
    @unless ($code === 'maintenance')
    <div class="text-sm font-medium tracking-[0.12em] uppercase text-zinc-400 dark:text-white/30">Error {{ $code }}</div>
    @endunless

    <flux:heading size="xl" class="mt-3">{{ $meta['title'] }}</flux:heading>
    <flux:text class="mt-2 text-zinc-500 dark:text-white/50">{{ $message ?? $meta['description'] }}</flux:text>

    @unless ($code === 'maintenance')
    <div class="mt-8">
        <flux:button href="/" variant="primary">Go back home</flux:button>
    </div>
    @endunless
</div>
@endsection
