<?php

declare(strict_types=1);

use App\Enums\Status;

test('enum cases have correct values', function () {
    expect(Status::Active->value)->toBe('active')
        ->and(Status::InActive->value)->toBe('in_active');
});

test('toArray returns correct structure', function () {
    expect(Status::toArray())->toEqual([
        'active' => 'Active',
        'in_active' => 'In Active',
    ]);
});
