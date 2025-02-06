<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bible;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Verse>
 */
final class VerseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_id' => Bible::factory(),
            'section_id' => Section::factory(),
            'chapter' => fake()->randomNumber(3, false),
            'verse_no' => $order = fake()->randomNumber(3, false),
            'have_note' => fake()->boolean(0.1),
            'verse' => fake()->sentence(16),
            'order' => $order,
        ];
    }
}
