<?php

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Models\MediaAttachment;
use App\Modules\Media\Services\MediaLibrary;
use Atrium\Core\Events\UserAnonymized;
use Atrium\Core\Services\AccountDeletionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

function uploadToLibrary(UploadedFile $file, ?User $uploader = null, ?string $collection = null): Media
{
    return app(MediaLibrary::class)->upload($file, $uploader, $collection);
}

it('uploads into the library under year/month with title, uploader and image metadata', function () {
    $user = User::factory()->create();

    $media = uploadToLibrary(UploadedFile::fake()->image('Summer Trip.jpg', 1200, 900), $user);

    expect($media->path)->toStartWith(now()->format('Y/m')."/{$media->uuid}.")
        ->and($media->name)->toBe('Summer Trip')
        ->and($media->original_name)->toBe('Summer Trip.jpg')
        ->and($media->uploaded_by)->toBe($user->id)
        ->and($media->metadata)->toBe(['width' => 1200, 'height' => 900]);

    Storage::disk('local')->assertExists($media->path);
});

it('generates image sizes once per library item, skipping sizes the original already fits', function () {
    $media = uploadToLibrary(UploadedFile::fake()->image('photo.png', 1200, 900));

    expect($media->conversions->pluck('conversion')->sort()->values()->all())->toBe(['medium', 'thumb'])
        ->and($media->conversion('thumb')->metadata)->toBe(['width' => 300, 'height' => 300])
        ->and($media->conversion('medium')->metadata)->toBe(['width' => 800, 'height' => 600])
        // 1200×900 already fits "large" (1600) — falls back to the original.
        ->and($media->conversion('large')->is($media))->toBeTrue();

    Storage::disk('local')->assertExists($media->conversion('thumb')->path);
});

it('stores non-images without sizes', function () {
    $media = uploadToLibrary(UploadedFile::fake()->create('report.pdf', 50, 'application/pdf'));

    expect($media->conversions)->toBeEmpty()
        ->and($media->type())->toBe('document')
        ->and($media->conversion('thumb')->is($media))->toBeTrue();
});

it('rejects files the library or collection does not accept, storing nothing', function (UploadedFile $file, ?string $collection) {
    expect(fn () => uploadToLibrary($file, null, $collection))->toThrow(ValidationException::class);

    expect(Media::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'not allowed in the library' => [fn () => UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload'), null],
    'wrong type for avatar' => [fn () => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'), 'avatar'],
    'too large for avatar' => [fn () => UploadedFile::fake()->image('big.jpg', 200, 200)->size(3000), 'avatar'],
    'too small for avatar' => [fn () => UploadedFile::fake()->image('tiny.jpg', 50, 50), 'avatar'],
]);

it('adds a new upload to a model collection', function () {
    $user = User::factory()->create();

    $media = $user->addMedia(UploadedFile::fake()->image('me.jpg', 300, 300), 'avatar', $user);

    expect($user->getFirstMedia('avatar')->is($media))->toBeTrue()
        ->and($user->getFirstMediaUrl('avatar', 'thumb'))->toContain('expiration=');
});

it('reuses one library item in several places', function () {
    [$a, $b] = [User::factory()->create(), User::factory()->create()];
    $media = uploadToLibrary(UploadedFile::fake()->image('shared.jpg', 300, 300));

    $a->attachMedia($media, 'avatar');
    $b->attachMedia($media->id, 'avatar');

    expect($a->getFirstMedia('avatar')->is($media))->toBeTrue()
        ->and($b->getFirstMedia('avatar')->is($media))->toBeTrue()
        ->and($media->attachments()->count())->toBe(2);
});

it('checks a picked library item against the collection rules', function () {
    $user = User::factory()->create();
    $pdf = uploadToLibrary(UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'));
    $tiny = uploadToLibrary(UploadedFile::fake()->image('tiny.jpg', 50, 50));

    expect(fn () => $user->attachMedia($pdf, 'avatar'))->toThrow(ValidationException::class)
        ->and(fn () => $user->attachMedia($tiny, 'avatar'))->toThrow(ValidationException::class);

    expect(MediaAttachment::count())->toBe(0);
});

it('replaces the item in a single collection without deleting the old file', function () {
    $user = User::factory()->create();
    $old = $user->addMedia(UploadedFile::fake()->image('old.jpg', 300, 300), 'avatar');
    $new = $user->addMedia(UploadedFile::fake()->image('new.jpg', 300, 300), 'avatar');

    expect($user->getMedia('avatar')->pluck('id')->all())->toBe([$new->id])
        ->and(Media::whereKey($old->id)->exists())->toBeTrue();

    Storage::disk('local')->assertExists($old->path);
});

it('keeps order in a multi-item collection and syncs to an exact list', function () {
    $user = User::factory()->create();
    $files = collect(['a', 'b', 'c'])->map(fn ($n) => uploadToLibrary(UploadedFile::fake()->create("{$n}.pdf", 10, 'application/pdf')));

    $user->attachMedia($files[0], 'files');
    $user->attachMedia($files[1], 'files');
    expect($user->getMedia('files')->pluck('id')->all())->toBe([$files[0]->id, $files[1]->id]);

    $user->syncMedia([$files[2]->id, $files[0]->id], 'files');
    expect($user->getMedia('files')->pluck('id')->all())->toBe([$files[2]->id, $files[0]->id]);
});

it('rejects syncing ids the acting user may not see', function () {
    $user = User::factory()->create();
    $someoneElses = uploadToLibrary(UploadedFile::fake()->create('secret.pdf', 10, 'application/pdf'), User::factory()->create());

    expect(fn () => $user->syncMedia([$someoneElses->id], 'files', $user))->toThrow(ValidationException::class);
    expect(MediaAttachment::count())->toBe(0);
});

it('only detaches when a model is deleted or media is cleared — the library keeps the file', function () {
    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('me.jpg', 300, 300), 'avatar');

    $user->delete();

    expect(MediaAttachment::count())->toBe(0)
        ->and(Media::whereKey($media->id)->exists())->toBeTrue();
});

it('deletes the file, its sizes and every use of it when a library item is deleted', function () {
    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('me.jpg', 1200, 900), 'avatar');
    $paths = [$media->path, ...$media->conversions->pluck('path')];

    $media->delete();

    expect(Media::count())->toBe(0)
        ->and(MediaAttachment::count())->toBe(0)
        ->and($user->getMedia('avatar'))->toBeEmpty();
    Storage::disk('local')->assertMissing($paths);
});

it('lets staff with media.view see the whole library, everyone else only their own uploads', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $mine = uploadToLibrary(UploadedFile::fake()->create('mine.pdf', 10, 'application/pdf'), $user);
    $theirs = uploadToLibrary(UploadedFile::fake()->create('theirs.pdf', 10, 'application/pdf'), User::factory()->create());

    expect(Media::originals()->visibleTo($user)->pluck('id')->all())->toBe([$mine->id])
        ->and(Media::originals()->visibleTo(adminActor())->pluck('id')->sort()->values()->all())->toBe([$mine->id, $theirs->id])
        ->and(Media::originals()->visibleTo(null)->count())->toBe(0);
});

it('removes an anonymized user\'s photo and their unused uploads, but not files used elsewhere', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $avatar = $user->addMedia(UploadedFile::fake()->image('me.jpg', 300, 300), 'avatar', $user);
    $unused = uploadToLibrary(UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), $user);
    $shared = uploadToLibrary(UploadedFile::fake()->image('shared.jpg', 300, 300), $user);
    $other->attachMedia($shared, 'avatar');

    UserAnonymized::dispatch($user);

    expect(Media::whereKey([$avatar->id, $unused->id])->exists())->toBeFalse()
        ->and(Media::whereKey($shared->id)->exists())->toBeTrue()
        ->and($other->getFirstMedia('avatar')->is($shared))->toBeTrue();
});

it('runs that cleanup when an account deletion is processed', function () {
    Notification::fake();

    $user = User::factory()->create();
    $avatar = $user->addMedia(UploadedFile::fake()->image('me.jpg', 300, 300), 'avatar', $user);

    $service = app(AccountDeletionService::class);
    $service->process($service->request($user));

    expect(Media::whereKey($avatar->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($avatar->path);
});
