<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\Verse;

test('to array', function () {
    $section = Section::factory()->create()->refresh();

    expect(array_keys($section->toArray()))
        ->toBe([
            'id',
            'title',
            'created_at',
            'updated_at',
        ]);
});

it('has many verses', function () {
    $section = Section::factory()->hasVerses(500)->create();

    expect($section->verses)->toHaveCount(500)
        ->each->toBeInstanceOf(Verse::class);
});
