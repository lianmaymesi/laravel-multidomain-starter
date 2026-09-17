<?php

use App\Services\LanguageService;
use App\Services\TimezoneService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    public string $locale = '';

    public string $timezone = '';

    public function mount(LanguageService $languages, TimezoneService $timezones): void
    {
        $this->locale = auth()->user()->locale ?? $languages->primaryCode();
        $this->timezone = $timezones->current();
    }

    public function languages()
    {
        return app(LanguageService::class)->active();
    }

    public function isMultiLanguageEnabled(): bool
    {
        return app(LanguageService::class)->isMultiLanguageEnabled();
    }

    public function timezones(): array
    {
        return app(TimezoneService::class)->identifiers();
    }

    /**
     * One button saves everything on the page. Preferred language is a
     * durable, account-level choice — once set it bypasses session/cookie/
     * browser negotiation on every portal (see SetLocale middleware) — so
     * saving it needs a full reload (not a wire:navigate SPA swap) for
     * <html dir> and everything else baked into the initial render to pick
     * up the change. Timezone doesn't affect the initial render, but riding
     * along on the same reload is harmless.
     */
    public function save(LanguageService $languages, TimezoneService $timezones): void
    {
        if ($this->isMultiLanguageEnabled() && ! $languages->isValidCode($this->locale)) {
            $this->addError('locale', __('That language is not available.'));

            return;
        }

        if (! $timezones->isValid($this->timezone)) {
            $this->addError('timezone', __('That timezone is not valid.'));

            return;
        }

        $updates = ['timezone' => $this->timezone];

        if ($this->isMultiLanguageEnabled()) {
            $updates['locale'] = $this->locale;
        }

        auth()->user()->update($updates);

        if ($this->isMultiLanguageEnabled()) {
            $this->redirect(route('account.settings'));

            return;
        }

        session()->flash('status', __('Saved.'));
    }
};
