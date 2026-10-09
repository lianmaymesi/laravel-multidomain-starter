<?php

namespace Tests\Fixtures\Health;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;

class PassingCheck implements Check
{
    public function name(): string
    {
        return 'passing';
    }

    public function label(): string
    {
        return 'Passing';
    }

    public function run(): Result
    {
        return Result::ok('All good.', ['answer' => 42]);
    }
}
