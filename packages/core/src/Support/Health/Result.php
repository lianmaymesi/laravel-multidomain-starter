<?php

namespace Atrium\Core\Support\Health;

final class Result
{
    /** @param  array<string, mixed>  $meta  small, non-secret facts (counts, timings) */
    public function __construct(
        public readonly Status $status,
        public readonly string $message,
        public readonly array $meta = [],
        public ?float $durationMs = null,
    ) {}

    /** @param  array<string, mixed>  $meta */
    public static function ok(string $message, array $meta = []): self
    {
        return new self(Status::Ok, $message, $meta);
    }

    /** @param  array<string, mixed>  $meta */
    public static function warning(string $message, array $meta = []): self
    {
        return new self(Status::Warning, $message, $meta);
    }

    /** @param  array<string, mixed>  $meta */
    public static function failed(string $message, array $meta = []): self
    {
        return new self(Status::Failed, $message, $meta);
    }

    /** @return array{status: string, message: string, meta: array<string, mixed>, duration_ms: float|null} */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->message,
            'meta' => $this->meta,
            'duration_ms' => $this->durationMs,
        ];
    }
}
