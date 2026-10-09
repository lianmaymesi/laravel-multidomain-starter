<?php

namespace Atrium\Core\Support\Health;

/**
 * One health check. Core's are listed in config/health.php; a module adds its
 * own with Module::contribute('health.checks', [MyCheck::class]).
 */
interface Check
{
    /** Stable key used in the JSON report, e.g. "database". */
    public function name(): string;

    /** Human label for the backoffice page and the CLI. */
    public function label(): string;

    /** Must not throw — HealthChecker turns an exception into a failure anyway. */
    public function run(): Result;
}
