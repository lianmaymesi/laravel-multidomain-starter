<?php

use App\Services\LanguageService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    public string $locale = '';

    public function mount(LanguageService $languages): void
    {
        $this->locale = auth()->user()->locale ?? $languages->primaryCode();
    }

    public function languages()
    {
        return app(LanguageService::class)->active();
    }

    public function isMultiLanguageEnabled(): bool
    {
        return app(LanguageService::class)->isMultiLanguageEnabled();
    }

    /**
     * Preferred language is a durable, account-level choice — once set it
     * bypasses session/cookie/browser negotiation on every portal (see
     * SetLocale middleware), unlike the plain `?lang=` switcher which only
     * affects the current session.
     */
    public function save(LanguageService $languages): void
    {
        if (! $languages->isValidCode($this->locale)) {
            $this->addError('locale', 'That language is not available.');

            return;
        }

        auth()->user()->update(['locale' => $this->locale]);

        // Full reload, not a wire:navigate SPA swap — <html dir>, nav
        // labels, and everything else baked into the initial page render
        // needs to re-render under the new locale.
        $this->redirect(route('account.settings'));
    }
};
