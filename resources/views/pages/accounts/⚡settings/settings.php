<?php

use Atrium\Core\Contracts\Languages;
use Atrium\Core\Services\TimezoneService;
use Atrium\Core\Support\Modules\Module;
use Atrium\Core\Support\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    public string $locale = '';

    public string $timezone = '';

    public function mount(Languages $languages, TimezoneService $timezones): void
    {
        $this->locale = auth()->user()->locale ?? $languages->primaryCode();
        $this->timezone = $timezones->current();
    }

    /**
     * Cards contributed by feature modules (Module::contribute('account.settings.cards')),
     * each a Livewire component that saves itself.
     *
     * @return array<int, array{component: string, order?: int}>
     */
    public function cards(): array
    {
        return collect(Module::contributions('account.settings.cards'))
            ->sortBy(fn (array $card) => $card['order'] ?? 100)
            ->values()
            ->all();
    }

    public function languages()
    {
        return app(Languages::class)->active();
    }

    public function isMultiLanguageEnabled(): bool
    {
        return app(Languages::class)->isMultiLanguageEnabled();
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
    public function save(Languages $languages, TimezoneService $timezones): void
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
            Toast::success(__('Saved.'), afterRedirect: true);
            $this->redirect(route('account.settings'));

            return;
        }

        Toast::success(__('Saved.'));
    }
};
