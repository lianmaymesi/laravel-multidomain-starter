<?php

namespace App\Concerns;

trait Anonymizable
{
    /**
     * Replace sensitive columns with anonymized values.
     * Each model defines its own anonymizeMap() returning [column => value|callable].
     */
    public function anonymize(): void
    {
        $data = [];

        foreach ($this->anonymizeMap() as $column => $value) {
            $data[$column] = is_callable($value) ? $value($this) : $value;
        }

        $this->forceFill($data)->saveQuietly();
    }

    /**
     * Return a map of [column => replacement].
     * Value can be a scalar or a callable(model): mixed.
     * Keep sales/invoices/financial columns OUT of this map.
     */
    protected function anonymizeMap(): array
    {
        return [];
    }
}
