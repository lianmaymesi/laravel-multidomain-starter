<?php

namespace Tests\Fixtures\Health;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;

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
