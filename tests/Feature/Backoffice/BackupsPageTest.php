<?php

use Atrium\Core\Jobs\RunBackup;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Backup\Config\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    Storage::fake('backups');
    config(['backup.backup.tries' => 1]);
    app()->forgetInstance(Config::class);

    $this->name = config('backup.backup.name');
    $this->path = $this->name.'/'.now()->subHour()->format('Y-m-d-H-i-s').'.zip';
    Storage::disk('backups')->put($this->path, 'zip-bytes');
});

it('is Super Admin only, and linked in the sidebar only for them', function () {
    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')->assertOk();
    Livewire::actingAs(adminActor())->test('pages::backoffice.backups')->assertForbidden();
    Livewire::actingAs(staffUser())->test('pages::backoffice.backups')->assertForbidden();

    $this->actingAs(superAdminActor())->get(route('backoffice.dashboard'))
        ->assertSee(route('backoffice.backups.index'), false);

    $this->actingAs(adminActor())->get(route('backoffice.dashboard'))
        ->assertDontSee(route('backoffice.backups.index'), false);
});

it('lists the backups on each destination and says when scheduling is off', function () {
    config(['backup.enabled' => false]);

    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')
        ->assertSeeHtml('data-disk="backups"')
        ->assertSeeHtml('data-backup="'.$this->path.'"')
        ->assertSeeHtml('data-disabled')
        ->assertSeeHtml('data-single-disk');

    config(['backup.enabled' => true]);

    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')
        ->assertDontSeeHtml('data-disabled');
});

it('queues a backup on demand and logs who asked', function () {
    Queue::fake();
    $admin = superAdminActor();

    Livewire::actingAs($admin)->test('pages::backoffice.backups')
        ->set('scope', 'db')
        ->call('backUpNow')
        ->assertHasNoErrors();

    Queue::assertPushed(RunBackup::class, fn (RunBackup $job) => $job->scope === 'db' && $job->requestedBy === $admin->id);

    $this->assertDatabaseHas('activity_log', ['log_name' => 'backups', 'description' => 'backup requested', 'causer_id' => $admin->id]);
});

it('falls back to a full backup for an unknown scope', function () {
    Queue::fake();

    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')
        ->set('scope', 'everything')
        ->call('backUpNow');

    Queue::assertPushed(RunBackup::class, fn (RunBackup $job) => $job->scope === 'full');
});

it('deletes a backup and logs it', function () {
    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')
        ->call('delete', 'backups', $this->path)
        ->assertHasNoErrors();

    Storage::disk('backups')->assertMissing($this->path);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'backups', 'description' => 'backup deleted']);
});

it('only deletes files that are listed backups', function () {
    Storage::disk('backups')->put('other/secret.txt', 'x');

    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')
        ->call('delete', 'backups', 'other/secret.txt')
        ->assertNotFound();

    Livewire::actingAs(superAdminActor())->test('pages::backoffice.backups')
        ->call('delete', 'local', $this->path)
        ->assertNotFound();

    Storage::disk('backups')->assertExists('other/secret.txt');
});

it('streams a download to Super Admin and logs it', function () {
    $response = $this->actingAs(superAdminActor())
        ->get(route('backoffice.backups.download', ['disk' => 'backups', 'path' => $this->path]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/zip')
        ->assertDownload(basename($this->path));

    expect($response->streamedContent())->toBe('zip-bytes');
    $this->assertDatabaseHas('activity_log', ['log_name' => 'backups', 'description' => 'backup downloaded']);
});

it('refuses downloads to anyone else, and of anything that is not a backup', function () {
    $this->actingAs(adminActor())
        ->get(route('backoffice.backups.download', ['disk' => 'backups', 'path' => $this->path]))
        ->assertForbidden();

    Storage::disk('backups')->put('other/secret.txt', 'x');

    foreach (['other/secret.txt', '../../.env', $this->name.'/missing.zip'] as $path) {
        $this->actingAs(superAdminActor())
            ->get(route('backoffice.backups.download', ['disk' => 'backups', 'path' => $path]))
            ->assertNotFound();
    }

    $this->assertDatabaseMissing('activity_log', ['description' => 'backup downloaded']);
});

it('runs the backup in the job and logs the outcome', function () {
    $source = storage_path('framework/testing/backup-job-source');
    File::ensureDirectoryExists($source);
    File::put("{$source}/file.txt", 'data');

    config([
        'backup.backup.source.files.include' => [$source],
        'backup.backup.source.files.relative_path' => $source,
        'backup.backup.temporary_directory' => storage_path('framework/testing/backup-job-temp'),
    ]);
    app()->forgetInstance(Config::class);
    // spatie's commands capture Config when Artisan builds them (db:seed did).
    app(Kernel::class)->setArtisan(null);

    try {
        (new RunBackup('files', superAdminActor()->id))->handle();
    } finally {
        File::deleteDirectory($source);
        File::deleteDirectory(storage_path('framework/testing/backup-job-temp'));
    }

    $zips = Storage::disk('backups')->files($this->name);
    expect($zips)->toHaveCount(2);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path(collect($zips)->sort()->last()));
    expect($zip->numFiles)->toBe(1);
    $zip->close();

    $this->assertDatabaseHas('activity_log', ['log_name' => 'backups', 'description' => 'backup completed']);
});
