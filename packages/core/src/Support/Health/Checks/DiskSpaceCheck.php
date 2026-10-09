<?php

namespace Atrium\Core\Support\Health\Checks;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;
use Illuminate\Support\Number;

/** Free space on the volume holding storage/ (logs, uploads, cache files). */
class DiskSpaceCheck implements Check
{
    public function name(): string
    {
        return 'disk_space';
    }

    public function label(): string
    {
        return 'Disk space';
    }

    public function run(): Result
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if (! $free || ! $total) {
            return Result::warning('Free space could not be read on this platform.');
        }

        $percent = round($free / $total * 100, 1);
        $meta = ['free_percent' => $percent, 'free' => Number::fileSize($free, 1), 'total' => Number::fileSize($total, 1)];
        $message = Number::fileSize($free, 1)." free ({$percent}%).";

        return match (true) {
            $percent < (float) config('health.thresholds.disk_free_failed_percent', 3) => Result::failed('Almost full: '.$message, $meta),
            $percent < (float) config('health.thresholds.disk_free_warning_percent', 10) => Result::warning('Running low: '.$message, $meta),
            default => Result::ok($message, $meta),
        };
    }
}
