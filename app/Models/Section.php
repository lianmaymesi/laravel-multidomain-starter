<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    /**
     * Get all of the verses for the Section
     *
     * @return HasMany<Verse, $this>
     */
    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class);
    }
}
