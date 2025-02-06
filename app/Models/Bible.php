<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Bible extends Model
{
    /** @use HasFactory<\Database\Factories\BibleFactory> */
    use HasFactory;

    protected $fillable = [
        'language_id',
        'title',
        'version',
        'version_code',
        'status',
    ];

    /**
     * Get the language that owns the Bible
     *
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get all of the verses for the Bible
     *
     * @return HasMany<Verse, $this>
     */
    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }
}
