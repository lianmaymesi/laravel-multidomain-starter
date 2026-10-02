<?php

namespace App\Concerns;

use Illuminate\Contracts\Database\Query\Builder;
use Livewire\Attributes\Url;

/**
 * Sortable columns for a Livewire page with a Flux table:
 *
 *   <flux:table.column sortable :sorted="$this->isSortedBy('name')" :direction="$sortDirection"
 *       wire:click="sort('name')">Name</flux:table.column>
 *
 *   $query->tap(fn ($q) => $this->applySort($q));
 *
 * Only columns listed in sortableColumns() are accepted, so the browser can
 * never sort by (or probe) an arbitrary column. The sort is kept in the URL.
 */
trait SortsTable
{
    #[Url(as: 'sort')]
    public string $sortBy = '';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /**
     * Column key => SQL column (or aggregate alias, e.g. "users_count").
     *
     * @return array<string, string>
     */
    abstract protected function sortableColumns(): array;

    /** The column used until the user picks one. */
    abstract protected function defaultSort(): string;

    public function sort(string $column): void
    {
        if (! array_key_exists($column, $this->sortableColumns())) {
            return;
        }

        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** Whether the table is currently sorted by this column (the default counts). */
    public function isSortedBy(string $column): bool
    {
        return ($this->sortBy !== '' ? $this->sortBy : $this->defaultSort()) === $column;
    }

    protected function applySort(Builder $query): Builder
    {
        $columns = $this->sortableColumns();
        $column = $columns[$this->sortBy] ?? $columns[$this->defaultSort()];
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction);
    }
}
