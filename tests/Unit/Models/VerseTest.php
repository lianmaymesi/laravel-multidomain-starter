<?php

declare(strict_types=1);

use App\Models\Bible;
use App\Models\Section;
use App\Models\Verse;

test('to array', function () {
    $verse = Verse::factory()->create()->refresh();

    expect(array_keys($verse->toArray()))
        ->toBe([
            'id',
            'bible_id',
            'section_id',
            'chapter',
            'verse_no',
            'have_note',
            'verse',
            'order',
            'created_at',
            'updated_at',
        ]);
});

test('belongs to a section', function () {
    $verse = Verse::factory()->create();

    expect($verse->section)->toBeInstanceOf(Section::class);
});

test('belongs to a bible', function () {
    $verse = Verse::factory()->create();

    expect($verse->bible)->toBeInstanceOf(Bible::class);
});
