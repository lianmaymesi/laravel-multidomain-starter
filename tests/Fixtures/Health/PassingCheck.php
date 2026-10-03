<?php

namespace Tests\Fixtures\Health;

use App\Support\Health\Check;
use App\Support\Health\Result;

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
