<?php

use App\Models\User;
use App\Modules\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function mediaAccountUser(): User
{
    return User::factory()->create(['email_verified_at' => now(), 'phone_verified_at' => now()]);
}

it('shows the profile photo card on the account settings page', function () {
    $this->actingAs(mediaAccountUser())
        ->get(route('account.settings'))
        ->assertOk()
        ->assertSee('Profile photo');
});

it('disabling the media module breaks nothing', function () {
    $this->disableModules('media');
    Storage::fake('local');

    // A photo uploaded back when the module was on (written directly — the
    // module can't store anything now).
    $user = mediaAccountUser();
    Storage::disk('local')->put('avatar/user/1/old.jpg', 'x');
    $media = Media::create([
        'uuid' => (string) Str::uuid(),
        'mediable_type' => $user->getMorphClass(),
        'mediable_id' => $user->id,
        'collection' => 'avatar',
        'disk' => 'local',
        'path' => 'avatar/user/1/old.jpg',
        'original_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1,
    ]);

    // Invisible while the module is off, and nothing throws.
    expect($user->addMedia(UploadedFile::fake()->image('new.jpg', 200, 200), 'avatar'))->toBeNull()
        ->and($user->getMedia('avatar'))->toBeEmpty()
        ->and($user->getFirstMediaUrl('avatar'))->toBeNull()
        ->and(Media::count())->toBe(1);

    $this->actingAs($user)
        ->get(route('account.settings'))
        ->assertOk()
        ->assertDontSee('Profile photo');

    // Disabling is not uninstalling: deleting the user keeps the data, as for every module.
    $user->delete();

    expect(Media::whereKey($media->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($media->path);
});
