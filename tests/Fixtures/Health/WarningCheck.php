<?php

namespace Tests\Fixtures\Health;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;

class WarningCheck implements Check
{
    public function name(): string
    {
        return 'warning';
    }

    public function label(): string
    {
        return 'Warning';
    }

    public function run(): Result
    {
        return Result::warning('Hmm.');
    }
}
