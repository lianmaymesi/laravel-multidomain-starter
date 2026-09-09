{{--
    Theme switcher — Light / Dark / System.

    Uses Flux's appearance system: setting `$flux.appearance` (an Alpine magic
    provided by @fluxScripts) persists the choice to localStorage and toggles the
    `.dark` class on <html>. `@fluxAppearance` in each layout <head> applies the
    stored value before first paint, so there is no flash.
--}}
<flux:dropdown position="bottom" align="end" x-data>
    <flux:button
        variant="ghost"
        size="sm"
        square
        aria-label="{{ __('Switch theme') }}"
        title="{{ __('Switch theme') }}"
    >
        <flux:icon.sun variant="mini" class="size-4 hidden dark:block" />
        <flux:icon.moon variant="mini" class="size-4 block dark:hidden" />
    </flux:button>

    <flux:menu>
        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">
            <span class="flex flex-1 items-center justify-between gap-6">
                {{ __('Light') }}
                <flux:icon.check class="size-3.5" x-cloak x-show="$flux.appearance === 'light'" />
            </span>
        </flux:menu.item>

        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">
            <span class="flex flex-1 items-center justify-between gap-6">
                {{ __('Dark') }}
                <flux:icon.check class="size-3.5" x-cloak x-show="$flux.appearance === 'dark'" />
            </span>
        </flux:menu.item>

        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">
            <span class="flex flex-1 items-center justify-between gap-6">
                {{ __('System') }}
                <flux:icon.check class="size-3.5" x-cloak x-show="!$flux.appearance || $flux.appearance === 'system'" />
            </span>
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
