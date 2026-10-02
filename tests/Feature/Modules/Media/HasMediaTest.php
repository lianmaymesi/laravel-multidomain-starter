<?php

use App\Events\UserAnonymized;
use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Services\AccountDeletionService;
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

it('stores an upload on the default disk under collection/model/id with image metadata', function () {
    $user = User::factory()->create();

    $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 800, 600), 'avatar');

    expect($media->disk)->toBe('local')
        ->and($media->path)->toStartWith("avatar/user/{$user->id}/{$media->uuid}.")
        ->and($media->original_name)->toBe('photo.jpg')
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->metadata)->toBe(['width' => 800, 'height' => 600])
        ->and($user->getMedia('avatar')->pluck('id')->all())->toBe([$media->id]);

    Storage::disk('local')->assertExists($media->path);
});

it('generates the configured conversions, each its own row on the same disk', function () {
    $user = User::factory()->create();

    $media = $user->addMedia(UploadedFile::fake()->image('photo.png', 800, 600), 'avatar');

    $thumb = $media->conversion('thumb');
    $medium = $media->conversion('medium');

    expect($media->conversions)->toHaveCount(2)
        ->and($thumb->collection)->toBe('avatar:thumb')
        ->and($thumb->conversion_of_id)->toBe($media->id)
        ->and($thumb->metadata)->toBe(['width' => 150, 'height' => 150])
        ->and($medium->metadata)->toBe(['width' => 600, 'height' => 450])
        ->and($thumb->mime_type)->toBe('image/png')
        ->and($thumb->path)->toContain("/conversions/{$media->uuid}-thumb.");

    Storage::disk('local')->assertExists([$thumb->path, $medium->path]);

    // Conversions are not listed as media of their own.
    expect($user->getMedia('avatar'))->toHaveCount(1);
});

it('skips conversions for files that are not convertible images, and falls back to the original', function () {
    config(['media.collections.default.conversions' => ['thumb' => ['width' => 100, 'height' => 100, 'fit' => 'crop']]]);

    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->create('report.pdf', 50, 'application/pdf'));

    expect($media->conversions)->toBeEmpty()
        ->and($media->metadata)->toBeNull()
        ->and($media->conversion('thumb')->is($media))->toBeTrue();
});

it('rejects files the collection does not accept', function (UploadedFile $file) {
    $user = User::factory()->create();

    expect(fn () => $user->addMedia($file, 'avatar'))->toThrow(ValidationException::class);

    expect(Media::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'wrong type' => fn () => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    'too large' => fn () => UploadedFile::fake()->image('big.jpg', 200, 200)->size(3000),
    'too small' => fn () => UploadedFile::fake()->image('tiny.jpg', 50, 50),
]);

it('replaces the previous file in a single-file collection, deleting it from disk', function () {
    $user = User::factory()->create();

    $old = $user->addMedia(UploadedFile::fake()->image('old.jpg', 200, 200), 'avatar');
    $oldPaths = [$old->path, ...$old->conversions->pluck('path')];

    $new = $user->addMedia(UploadedFile::fake()->image('new.jpg', 200, 200), 'avatar');

    expect($user->getMedia('avatar')->pluck('id')->all())->toBe([$new->id])
        ->and(Media::whereKey($old->id)->exists())->toBeFalse();

    Storage::disk('local')->assertMissing($oldPaths);
});

it('keeps every file in a multi-file collection, in upload order', function () {
    $user = User::factory()->create();

    $first = $user->addMedia(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
    $second = $user->addMedia(UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'));

    expect($user->getMedia()->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($second->order)->toBe($first->order + 1);
});

it('resolves the disk: explicit argument, then the collection, then the default', function () {
    $user = User::factory()->create();
    $pdf = fn () => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf');

    expect($user->addMedia($pdf())->disk)->toBe('local');

    config(['media.collections.default.disk' => 'public']);
    expect($user->addMedia($pdf())->disk)->toBe('public');

    expect($user->addMedia($pdf(), 'default', 'local')->disk)->toBe('local');
});

it('deletes the file and its conversions when a media record is deleted', function () {
    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 200, 200), 'avatar');
    $paths = [$media->path, ...$media->conversions->pluck('path')];

    $media->delete();

    expect(Media::count())->toBe(0);
    Storage::disk('local')->assertMissing($paths);
});

it('deletes a model\'s files when the model is deleted', function () {
    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 200, 200), 'avatar');

    $user->delete();

    expect(Media::count())->toBe(0);
    Storage::disk('local')->assertMissing($media->path);
});

it('removes an anonymized account\'s photos', function () {
    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 200, 200), 'avatar');

    UserAnonymized::dispatch($user);

    expect(Media::count())->toBe(0);
    Storage::disk('local')->assertMissing($media->path);
});

it('removes the photo when an account deletion is processed', function () {
    Notification::fake();

    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 200, 200), 'avatar');

    $service = app(AccountDeletionService::class);
    $service->process($service->request($user));

    expect(User::whereKey($user->id)->exists())->toBeTrue()
        ->and(Media::count())->toBe(0);
    Storage::disk('local')->assertMissing($media->path);
});

it('serves private files through expiring temporary URLs', function () {
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('photo.jpg', 200, 200), 'avatar');

    expect($user->getFirstMediaUrl('avatar', 'thumb'))->toContain('expiration=')
        ->and($user->getFirstMediaUrl('missing'))->toBeNull();
});
