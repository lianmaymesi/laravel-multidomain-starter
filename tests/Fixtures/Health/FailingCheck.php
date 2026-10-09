<?php

namespace Tests\Fixtures\Health;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;

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
