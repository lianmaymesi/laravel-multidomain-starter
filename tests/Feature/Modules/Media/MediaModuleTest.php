<?php

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Models\MediaAttachment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('disabling the media module breaks nothing', function () {
    $this->disableModules('media');
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);

    // A photo set back when the module was on (written directly — the module
    // can't store anything now).
    $user = User::factory()->create(['email_verified_at' => now(), 'phone_verified_at' => now()]);
    Storage::disk('local')->put('2026/10/old.jpg', 'x');
    $media = Media::create([
        'uuid' => (string) Str::uuid(),
        'disk' => 'local',
        'path' => '2026/10/old.jpg',
        'name' => 'old',
        'original_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1,
    ]);
    MediaAttachment::create(['media_id' => $media->id, 'mediable_type' => $user->getMorphClass(), 'mediable_id' => $user->id, 'collection' => 'avatar']);

    // Invisible while the module is off, and nothing throws.
    expect($user->addMedia(UploadedFile::fake()->image('new.jpg', 200, 200), 'avatar'))->toBeNull()
        ->and($user->getMedia('avatar'))->toBeEmpty()
        ->and($user->getFirstMediaUrl('avatar'))->toBeNull()
        ->and(Media::count())->toBe(1);

    $this->actingAs($user)->get(route('account.settings'))
        ->assertOk()
        ->assertDontSee('Profile photo');

    $this->actingAs(superAdminActor())->get(route('backoffice.dashboard'))
        ->assertOk()
        ->assertDontSee('Media Library');

    expect(Route::has('backoffice.media.index'))->toBeFalse();

    // Disabling is not uninstalling: deleting the user keeps every row and file.
    $user->delete();

    expect(MediaAttachment::count())->toBe(1);
    Storage::disk('local')->assertExists($media->path);
});
