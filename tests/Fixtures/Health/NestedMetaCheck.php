<?php

namespace Tests\Fixtures\Health;

use App\Support\Health\Check;
use App\Support\Health\Result;

/** Meta nested like BackupCheck's (destinations → disk → facts). */
class NestedMetaCheck implements Check
{
    public function name(): string
    {
        return 'nested';
    }

    public function label(): string
    {
        return 'Nested';
    }

    public function run(): Result
    {
        return Result::ok('Nested meta.', [
            'destinations' => ['backups' => ['reachable' => true, 'count' => 1, 'newest' => null]],
            'disks' => ['local', 'public'],
        ]);
    }
}
