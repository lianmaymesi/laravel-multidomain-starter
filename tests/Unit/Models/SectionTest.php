<?php

declare(strict_types=1);

use App\Models\Section;

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
