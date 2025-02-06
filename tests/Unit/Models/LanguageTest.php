<?php

declare(strict_types=1);

use App\Models\Bible;
use App\Models\Language;
use Cviebrock\EloquentSluggable\Sluggable;

test('to array', function () {
    $language = Language::factory()->create()->refresh();

    expect(array_keys($language->toArray()))
        ->toBe([
            'id',
            'title',
            'slug',
            'iso_code',
            'original_text',
            'created_at',
            'updated_at',
        ]);
});

test('the model uses the Sluggable trait', function () {
    $usesTrait = in_array(Sluggable::class, class_uses(Language::class), true);

    expect($usesTrait)->toBeTrue();
});

test('the sluggable method exists and returns an array', function () {
    $language = new Language();

    $methodExists = method_exists($language, 'sluggable');

    $sluggableOutput = $language->sluggable();

    expect($methodExists)->toBeTrue()
        ->and(is_array($sluggableOutput))->toBeTrue();
});

it('has many bibles', function () {
    $language = Language::factory()->hasBibles(3)->create();

    expect($language->bibles)->toHaveCount(3)
        ->each->toBeInstanceOf(Bible::class);
});
