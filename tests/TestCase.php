<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var array<int, string> */
    private array $moduleEnvKeys = [];

    private ?string $sqliteFile = null;

    private mixed $originalDatabase = null;

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

    /**
     * Point the next application boots at a throwaway SQLite file instead of
     * the shared in-memory database, so a test can write to the database and
     * then reboot the app to see what reading it *during boot* does — the
     * in-memory connection is only restored after the app has booted.
     * Call refreshApplication() afterwards; use it in tests that do not use
     * RefreshDatabase.
     */
    protected function useSqliteFile(): void
    {
        $this->originalDatabase = $_SERVER['DB_DATABASE'] ?? ':memory:';
        $this->sqliteFile = tempnam(sys_get_temp_dir(), 'modtest');

        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $this->sqliteFile;
    }

    protected function tearDown(): void
    {
        foreach ($this->moduleEnvKeys as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }

        $this->moduleEnvKeys = [];

        if ($this->sqliteFile !== null) {
            $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $this->originalDatabase;
        }

        parent::tearDown();

        if ($this->sqliteFile !== null) {
            @unlink($this->sqliteFile);
            $this->sqliteFile = null;
        }
    }
}
