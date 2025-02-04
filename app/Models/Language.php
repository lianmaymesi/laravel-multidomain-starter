<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Language extends Model
{
    /** @use HasFactory<\Database\Factories\LanguageFactory> */
    use HasFactory, Sluggable;

    protected $fillable = [
        'title',
        'slug',
        'iso_code',
        'original_text',
    ];

    /**
     * Define the sluggable configuration for the model.
     *
     * @return array{slug: array{source: string}}
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }
}
