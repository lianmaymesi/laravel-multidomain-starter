{{-- Shows a toast flashed by App\Support\Toast before a redirect. Must come after the Flux toast element. --}}
@if (is_array($toast = session('toast')))
<div x-data x-init="$nextTick(() => $flux.toast(@js($toast['text']), { variant: @js($toast['variant'] ?? 'success') }))" data-flash-toast="{{ $toast['text'] }}"></div>
@endif
