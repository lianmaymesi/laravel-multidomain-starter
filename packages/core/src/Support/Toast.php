<?php

namespace Atrium\Core\Support;

use Flux\Flux;

/**
 * Flux toasts from PHP, whether or not a page load sits in between:
 *
 *   Toast::success(__('Saved.'));                 // inside a Livewire action
 *   Toast::success(__('Saved.'), afterRedirect: true);  // before $this->redirect(...)
 *
 * In a Livewire action the toast is dispatched to the browser right away.
 * Otherwise — or when the action redirects, which would drop that dispatch —
 * it's flashed to the session and the layout's <x-flash-toast /> shows it on
 * the next page load.
 */
class Toast
{
    public static function success(string $text, bool $afterRedirect = false): void
    {
        static::show($text, 'success', $afterRedirect);
    }

    public static function warning(string $text, bool $afterRedirect = false): void
    {
        static::show($text, 'warning', $afterRedirect);
    }

    public static function danger(string $text, bool $afterRedirect = false): void
    {
        static::show($text, 'danger', $afterRedirect);
    }

    public static function show(string $text, ?string $variant = null, bool $afterRedirect = false): void
    {
        if (! $afterRedirect && app('livewire')->current() !== null) {
            Flux::toast($text, variant: $variant);

            return;
        }

        session()->flash('toast', ['text' => $text, 'variant' => $variant]);
    }
}
