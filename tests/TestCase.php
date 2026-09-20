<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var array<int, string> */
    private array $moduleEnvKeys = [];

    /**
     * Boot a fresh application with the given feature modules switched off.
     *
     * Modules register routes/middleware/providers at boot, so flipping
     * config('modules.x') mid-test would be too late — the app has to be
     * rebuilt with the toggle already in the environment.
     */
    protected function disableModules(string ...$modules): static
    {
        foreach ($modules as $module) {
            $key = 'MODULE_'.strtoupper($module);

            $_ENV[$key] = $_SERVER[$key] = 'false';
            $this->moduleEnvKeys[] = $key;
        }

        // RefreshDatabase wraps the test in a transaction opened during
        // setUp on the old app's connection. Close it, rebuild, then reopen
        // on the new app — restoring the shared in-memory PDO so the schema
        // migrated for this run survives the rebuild.
        $refreshesDatabase = method_exists($this, 'beginDatabaseTransaction');

        if ($refreshesDatabase) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $this->app->make('db')->connection($name);

                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            }
        }

        $this->refreshApplication();

        if ($refreshesDatabase) {
            if ($this->usingInMemoryDatabases()) {
                $this->restoreInMemoryDatabase();
            }

            $this->beginDatabaseTransaction();
        }

        return $this;
    }

    protected function tearDown(): void
    {
        foreach ($this->moduleEnvKeys as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }

        $this->moduleEnvKeys = [];

        parent::tearDown();
    }
}
