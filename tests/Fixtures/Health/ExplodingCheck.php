<?php

namespace Tests\Fixtures\Health;

use App\Support\Health\Check;
use App\Support\Health\Result;
use RuntimeException;

class ExplodingCheck implements Check
{
    public function name(): string
    {
        return 'exploding';
    }

    public function label(): string
    {
        return 'Exploding';
    }

    public function run(): Result
    {
        throw new RuntimeException('boom');
    }
}
