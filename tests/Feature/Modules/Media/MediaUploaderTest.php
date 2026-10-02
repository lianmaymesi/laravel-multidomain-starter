<?php

use App\Models\Role;
use App\Models\User;
use App\Modules\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('uploads a picked file into the collection', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('media::uploader', ['model' => $user, 'collection' => 'avatar'])
        ->set('file', UploadedFile::fake()->image('me.jpg', 300, 300))
        ->assertHasNoErrors()
        ->assertDispatched('media-updated')
        ->assertSee('me.jpg');

    expect($user->getMedia('avatar'))->toHaveCount(1);
});

it('shows validation errors and stores nothing for a rejected file', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('media::uploader', ['model' => $user, 'collection' => 'avatar'])
        ->set('file', UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'))
        ->assertHasErrors('file');

    expect(Media::count())->toBe(0);
});

it('removes a file', function () {
    $user = User::factory()->create();
    $media = $user->addMedia(UploadedFile::fake()->image('me.jpg', 300, 300), 'avatar');

    Livewire::actingAs($user)
        ->test('media::uploader', ['model' => $user, 'collection' => 'avatar'])
        ->call('remove', $media->id)
        ->assertDontSee('me.jpg');

    expect(Media::count())->toBe(0);
});

it('cannot remove media belonging to another model or collection', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $theirs = $other->addMedia(UploadedFile::fake()->image('them.jpg', 300, 300), 'avatar');

    Livewire::actingAs($user)
        ->test('media::uploader', ['model' => $user, 'collection' => 'avatar'])
        ->call('remove', $theirs->id)
        ->assertStatus(404);

    expect(Media::whereKey($theirs->id)->exists())->toBeTrue();
});

it('checks the given ability before every change', function () {
    Gate::define('manage-media', fn (User $actor, User $model) => $actor->is($model));

    $user = User::factory()->create();
    $other = User::factory()->create();

    Livewire::actingAs($user)
        ->test('media::uploader', ['model' => $other, 'collection' => 'avatar', 'ability' => 'manage-media'])
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test('media::uploader', ['model' => $user, 'collection' => 'avatar', 'ability' => 'manage-media'])
        ->assertOk();
});

it('refuses a model without HasMedia', function () {
    Livewire::test('media::uploader', ['model' => new Role, 'collection' => 'avatar']);
})->throws('must use App\Modules\Media\Concerns\HasMedia');
