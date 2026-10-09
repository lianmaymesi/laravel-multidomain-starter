<?php

namespace Tests\Fixtures\Health;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;
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
