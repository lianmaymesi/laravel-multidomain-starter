<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Verse extends Model
{
    /** @use HasFactory<\Database\Factories\VerseFactory> */
    use HasFactory;

    protected $fillable = [
        'bible_id',
        'section_id',
        'chapter',
        'verse_no',
        'have_note',
        'verse',
        'order',
    ];

    /**
     * Get the bible that owns the Verse
     *
     * @return BelongsTo<Bible, $this>
     */
    public function bible(): BelongsTo
    {
        return $this->belongsTo(Bible::class);
    }

    /**
     * Get the section that owns the Verse
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
