<?php

namespace Atrium\Core\Support\Health;

use Illuminate\Support\Carbon;

/** The outcome of one run of every check. */
final class Report
{
    /**
     * @param  array<string, array{label: string, result: Result}>  $checks  keyed by check name
     */
    public function __construct(
        public readonly array $checks,
        public readonly Carbon $checkedAt,
    ) {}

    public function status(): Status
    {
        return Status::worst(array_map(fn (array $check) => $check['result']->status, $this->checks));
    }

    public function failed(): bool
    {
        return $this->status() === Status::Failed;
    }

    /** Overall status only — safe to show anyone. */
    public function summary(): array
    {
        return [
            'status' => $this->status()->value,
            'checked_at' => $this->checkedAt->toIso8601String(),
        ];
    }

    /**
     * Rebuild from toArray() — the cache stores plain arrays (object
     * unserialization is disabled in config/cache.php).
     */
    public static function fromArray(array $data): self
    {
        $checks = [];

        foreach ($data['checks'] ?? [] as $name => $check) {
            $checks[$name] = [
                'label' => $check['label'],
                'result' => new Result(Status::from($check['status']), $check['message'], $check['meta'] ?? [], $check['duration_ms'] ?? null),
            ];
        }

        return new self($checks, Carbon::parse($data['checked_at']));
    }

    /** Everything, for monitors holding the health token and for admins. */
    public function toArray(): array
    {
        return [
            ...$this->summary(),
            'checks' => array_map(
                fn (array $check) => ['label' => $check['label'], ...$check['result']->toArray()],
                $this->checks,
            ),
        ];
    }
}
