<?php

declare(strict_types=1);

use App\Enums\Status;
use App\Models\Bible;
use App\Models\Language;
use App\Models\Verse;

test('to array', function () {
    $bible = Bible::factory()->create()->refresh();

    expect(array_keys($bible->toArray()))
        ->toBe([
            'id',
            'language_id',
            'title',
            'version',
            'version_code',
            'status',
            'created_at',
            'updated_at',
        ]);
});

test('belongs to a language', function () {
    $bible = Bible::factory()->create();

    expect($bible->language)->toBeInstanceOf(Language::class);
});

test('status attribute is cast to Status class', function () {
    $model = new Bible();
    $model->status = 'active';
    expect($model->status)->toBeInstanceOf(Status::class);

    expect($model->status->value)->toBe('active');
});

it('has many verses', function () {
    $bible = Bible::factory()->hasVerses(500)->create();

    expect($bible->verses)->toHaveCount(500)
        ->each->toBeInstanceOf(Verse::class);
});
