<?php

use App\Modules\Currency\Models\Currency;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Read-only reference — which currencies exist, their formatting rules,
 * and their current rate against the primary currency. Managing which
 * currencies are active and which one is primary happens on the Settings
 * page instead (see resources/views/pages/backoffice/⚡settings), so this
 * page has no mutating actions at all.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    public function mount(): void
    {
        abort_unless(Gate::allows('currencies.view'), 403);
    }

    public function currencies()
    {
        return Currency::orderBy('order')->get();
    }

    public function primary(): ?Currency
    {
        return Currency::primary();
    }
};
