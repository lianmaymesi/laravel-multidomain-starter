<?php

namespace Tests\Fixtures\Health;

use App\Support\Health\Check;
use App\Support\Health\Result;

class FailingCheck implements Check
{
    public function name(): string
    {
        return 'failing';
    }

    public function label(): string
    {
        return 'Failing';
    }

    public function run(): Result
    {
        return Result::failed('Down.');
    }
}
