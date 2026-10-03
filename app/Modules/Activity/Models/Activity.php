<?php

namespace App\Modules\Activity\Models;

use App\Support\Modules\Module;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use Searchable;

    public function comments(): HasMany
    {
        return $this->hasMany(ActivityComment::class);
    }

    /**
     * The database driver LIKE-searches every key returned here, so the
     * filter/sort fields (subject_*, created_at) are only sent to a real
     * index like Meilisearch — on Postgres a LIKE against a timestamp
     * column would error. `properties` is JSON and stays out entirely.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $document = [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
        ];

        if (config('scout.driver') === 'database') {
            return $document;
        }

        return $document + [
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'created_at' => $this->created_at?->getTimestamp(),
        ];
    }

    /**
     * A switched-off module never pushes rows into an external index — the
     * table and its rows stay, and a re-enable plus `scout:import` resyncs.
     */
    public function shouldBeSearchable(): bool
    {
        return Module::enabled('activity');
    }
}
