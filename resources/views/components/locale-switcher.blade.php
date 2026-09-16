{{--
    Locale switcher — optional, standalone. Not wired into every layout on
    purpose: a signed-in user's saved preference (account portal's
    Preferred Language setting) outranks everything else in SetLocale, so
    on app/account/backoffice a switcher here would look clickable but do
    nothing — those three deliberately go without it. Only landing/auth
    include it, since a visitor without an account has no such preference
    to defer to.

    Self-hides whenever fewer than two languages are active, so a portal
    that includes it unconditionally never has to guard for that itself.

    Clicking a link here builds a URL under the target locale — either
    path-prefixed or `?lang=`, whichever URL mode is active (see backoffice
    Languages page) — and updates the current session.
--}}
@php $languages = app(App\Services\LanguageService::class); @endphp

@if ($languages->isMultiLanguageEnabled())
<flux:dropdown position="bottom" align="end">
    <flux:button variant="ghost" size="sm" aria-label="{{ __('Switch language') }}" title="{{ __('Switch language') }}">
        {{ $languages->current()?->native_name ?? strtoupper(app()->getLocale()) }}
    </flux:button>

    <flux:menu>
        @foreach ($languages->active() as $language)
        <flux:menu.item :href="$languages->switchUrl(request(), $language->code)">
            <span class="flex flex-1 items-center justify-between gap-6">
                {{ $language->native_name }}
                @if ($language->code === app()->getLocale())
                <flux:icon.check class="size-3.5" />
                @endif
            </span>
        </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>
@endif
