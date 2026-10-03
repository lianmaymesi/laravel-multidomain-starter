<?php

use App\Support\Health\HealthChecker;
use App\Support\Health\Report;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Backoffice → System health (health.view): the same report /health and
 * `php artisan health:check` produce, with every check's detail. "Run
 * checks now" skips the short report cache.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    public bool $fresh = false;

    public function mount(): void
    {
        abort_unless(Gate::allows('health.view'), 403);
    }

    public function report(): Report
    {
        return app(HealthChecker::class)->report(fresh: $this->fresh);
    }

    public function runNow(): void
    {
        abort_unless(Gate::allows('health.view'), 403);

        $this->fresh = true;
    }

    public function tokenConfigured(): bool
    {
        return filled(config('health.token'));
    }
};
