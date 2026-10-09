<?php

use Atrium\Core\Support\Health\HealthChecker;
use Atrium\Core\Support\Health\Report;
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

    /**
     * One meta value as text. Checks may nest (e.g. backups → per-disk
     * facts), so lists and maps are formatted recursively:
     * "backups: (reachable: yes, count: 1)".
     */
    public function formatMeta(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'yes' : 'no',
            $value === null => '—',
            is_array($value) => collect($value)
                ->map(function ($item, $key) {
                    $text = is_array($item) ? '('.$this->formatMeta($item).')' : $this->formatMeta($item);

                    return is_string($key) ? "{$key}: {$text}" : $text;
                })
                ->implode(', '),
            default => (string) $value,
        };
    }

    public function tokenConfigured(): bool
    {
        return filled(config('health.token'));
    }
};
