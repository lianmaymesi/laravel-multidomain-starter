<?php

namespace App\Modules\Activity\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function comments(): HasMany
    {
        return $this->hasMany(ActivityComment::class);
    }
}
