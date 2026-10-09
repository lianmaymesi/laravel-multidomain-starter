<?php

use App\Modules\Maintenance\Models\PortalSetting;
use Atrium\Core\Support\Health\Checks\CacheCheck;
use Atrium\Core\Support\Health\Checks\DatabaseCheck;
use Atrium\Core\Support\Health\Checks\DiskSpaceCheck;
use Atrium\Core\Support\Health\Checks\EnvironmentCheck;
use Atrium\Core\Support\Health\Checks\PortalsCheck;
use Atrium\Core\Support\Health\Checks\QueueCheck;
use Atrium\Core\Support\Health\Checks\SchedulerCheck;
use Atrium\Core\Support\Health\Checks\StorageCheck;
use Atrium\Core\Support\Health\HealthChecker;
use Atrium\Core\Support\Health\Result;
use Atrium\Core\Support\Health\Status;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function runCheck(string $class): Result
{
    return app($class)->run();
}

// ── Database ─────────────────────────────────────────────────────────

it('passes the database check when connected and migrated', function () {
    expect(runCheck(DatabaseCheck::class))->status->toBe(Status::Ok);
});

it('warns about migrations a deploy forgot to run', function () {
    $dir = storage_path('framework/testing/pending-migrations');
    File::ensureDirectoryExists($dir);
    File::put("{$dir}/2099_01_01_000000_pending_example.php", "<?php\n\nreturn new class extends Illuminate\\Database\\Migrations\\Migration { public function up(): void {} };\n");
    app('migrator')->path($dir);

    try {
        $result = runCheck(DatabaseCheck::class);

        expect($result->status)->toBe(Status::Warning)
            ->and($result->meta['pending'])->toBe(1);
    } finally {
        File::deleteDirectory($dir);
    }
});

// ── Cache / storage ──────────────────────────────────────────────────

it('round-trips the cache', function () {
    expect(runCheck(CacheCheck::class))->status->toBe(Status::Ok);
});

it('writes, reads and deletes a probe file on each disk', function () {
    Storage::fake('local');
    Storage::fake('public');

    expect(runCheck(StorageCheck::class))->status->toBe(Status::Ok)
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('fails for a disk that can\'t be written', function () {
    config(['health.disks' => ['missing-disk']]);

    expect(runCheck(StorageCheck::class))->status->toBe(Status::Failed)
        ->and(runCheck(StorageCheck::class)->meta['broken'])->toBe(['missing-disk']);
});

// ── Queue ────────────────────────────────────────────────────────────

it('warns about the sync queue only in production', function () {
    config(['queue.default' => 'sync']);
    expect(runCheck(QueueCheck::class))->status->toBe(Status::Ok);

    app()->detectEnvironment(fn () => 'production');
    expect(runCheck(QueueCheck::class))->status->toBe(Status::Warning);
});

it('warns about a stale backlog and failed jobs on the database queue', function () {
    config(['queue.default' => 'database', 'queue.connections.database.table' => 'jobs']);
    expect(runCheck(QueueCheck::class))->status->toBe(Status::Ok);

    DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => now()->subHour()->timestamp, 'created_at' => now()->subHour()->timestamp]);
    DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);

    $result = runCheck(QueueCheck::class);

    expect($result->status)->toBe(Status::Warning)
        ->and($result->meta)->toMatchArray(['pending' => 1, 'failed' => 1])
        ->and($result->message)->toContain('is a worker running');
});

// ── Scheduler ────────────────────────────────────────────────────────

it('tracks the scheduler heartbeat', function () {
    expect(runCheck(SchedulerCheck::class))->status->toBe(Status::Warning);

    Cache::forever(SchedulerCheck::HEARTBEAT_KEY, time());
    expect(runCheck(SchedulerCheck::class))->status->toBe(Status::Ok);

    Cache::forever(SchedulerCheck::HEARTBEAT_KEY, time() - 600);
    expect(runCheck(SchedulerCheck::class))->status->toBe(Status::Failed);
});

it('records the heartbeat from the scheduler every minute', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === 'health:heartbeat');

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');

    $event->run(app());

    expect(Cache::get(SchedulerCheck::HEARTBEAT_KEY))->toBeGreaterThan(time() - 5);
});

// ── Environment / disk / portals ─────────────────────────────────────

it('fails on debug mode or a missing key in production', function () {
    app()->detectEnvironment(fn () => 'production');

    config(['app.debug' => true]);
    expect(runCheck(EnvironmentCheck::class))->status->toBe(Status::Failed);

    config(['app.debug' => false]);
    expect(runCheck(EnvironmentCheck::class))->status->toBe(Status::Warning); // caches, https

    config(['app.key' => '']);
    expect(runCheck(EnvironmentCheck::class))->status->toBe(Status::Failed);
});

it('applies the disk space thresholds', function () {
    expect(runCheck(DiskSpaceCheck::class))->status->toBe(Status::Ok);

    config(['health.thresholds.disk_free_warning_percent' => 101]);
    expect(runCheck(DiskSpaceCheck::class))->status->toBe(Status::Warning);

    config(['health.thresholds.disk_free_failed_percent' => 101]);
    expect(runCheck(DiskSpaceCheck::class))->status->toBe(Status::Failed);
});

it('checks every portal has a host and its entry route', function () {
    expect(runCheck(PortalsCheck::class))->status->toBe(Status::Ok);

    config(['multidomain.sub_domains.blog' => 'blog.example.test']);
    expect(runCheck(PortalsCheck::class)->message)->toContain('"blog" has no blog.dashboard route');

    config(['multidomain.sub_domains.blog' => config('multidomain.sub_domains.app')]);
    expect(runCheck(PortalsCheck::class)->message)->toContain('several portals share');
});

// ── Module checks ────────────────────────────────────────────────────

it('includes the Maintenance module\'s check while it is on', function () {
    PortalSetting::create(['portal' => 'app', 'maintenance_mode' => true]);

    $report = app(HealthChecker::class)->run();

    expect($report->checks['maintenance']['result']->status)->toBe(Status::Warning)
        ->and($report->checks['maintenance']['result']->message)->toContain('app');
});

it('drops a module\'s check when the module is off', function () {
    $this->disableModules('maintenance');

    expect(app(HealthChecker::class)->run()->checks)->not->toHaveKey('maintenance');
});
