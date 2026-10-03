<?php

namespace Tests\Fixtures\Health;

use App\Support\Health\Check;
use App\Support\Health\Result;

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
