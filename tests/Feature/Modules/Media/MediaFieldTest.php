<?php

use App\Models\Role;
use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaLibrary;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function pickable(User $uploader, string $name = 'photo', int $size = 300): Media
{
    return app(MediaLibrary::class)->upload(UploadedFile::fake()->image("{$name}.jpg", $size, $size), $uploader);
}

it('opens the picker on the library tab, or on upload when the library is empty', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->call('openPicker')
        ->assertSet('tab', 'upload')
        ->assertDispatched('modal-show');

    pickable($user);

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->call('openPicker')
        ->assertSet('tab', 'library');
});

it('picks an existing library item and attaches it', function () {
    $user = User::factory()->create();
    $media = pickable($user);

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->call('openPicker')
        ->call('toggle', $media->id)
        ->assertSet('selected', [$media->id])
        ->call('insert')
        ->assertSet('selected', [])
        ->assertDispatched('modal-close')
        ->assertDispatched('media-updated')
        ->assertSeeHtml('data-attached="'.$media->id.'"');

    expect($user->getFirstMedia('avatar')->is($media))->toBeTrue();
});

it('uploads in the picker, then auto-selects the new file on the library tab', function () {
    $user = User::factory()->create();

    $field = Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->call('openPicker')
        ->set('uploads', [UploadedFile::fake()->image('fresh.jpg', 300, 300)])
        ->assertSet('tab', 'library');

    $media = Media::originals()->sole();

    $field->assertSet('selected', [$media->id])->call('insert');

    expect($media->uploaded_by)->toBe($user->id)
        ->and($user->getFirstMedia('avatar')->is($media))->toBeTrue();
});

it('validates uploads in the picker with the collection rules', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->set('uploads', [UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])
        ->assertSet('uploadErrors', fn (array $errors) => count($errors) === 1);

    expect(Media::count())->toBe(0);
});

it('only lists files that fit the collection and the viewer may see', function () {
    $user = User::factory()->create();
    $mine = pickable($user, 'mine');
    app(MediaLibrary::class)->upload(UploadedFile::fake()->create('mine.pdf', 10, 'application/pdf'), $user);
    pickable(User::factory()->create(), 'someone-elses');

    $items = Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->instance()
        ->libraryItems();

    expect($items->pluck('id')->all())->toBe([$mine->id]);
});

it('refuses to insert a file the viewer may not see, whatever the browser sends', function () {
    $user = User::factory()->create();
    $theirs = pickable(User::factory()->create());

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->set('selected', [$theirs->id])
        ->call('insert');

    expect($user->getMedia('avatar'))->toBeEmpty();
});

it('refuses to insert a picked file that breaks the collection rules', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = adminActor();
    $tiny = pickable($admin, 'tiny', 50);

    Livewire::actingAs($admin)
        ->test('media::field', ['model' => $admin, 'collection' => 'avatar'])
        ->set('selected', [$tiny->id])
        ->call('insert')
        ->assertHasErrors('selected');

    expect($admin->getMedia('avatar'))->toBeEmpty();
});

it('selects one file at a time in a single collection, many otherwise', function () {
    $user = User::factory()->create();
    [$a, $b] = [pickable($user, 'a'), pickable($user, 'b')];

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->call('toggle', $a->id)->call('toggle', $b->id)
        ->assertSet('selected', [$b->id]);

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'gallery'])
        ->call('toggle', $a->id)->call('toggle', $b->id)
        ->assertSet('selected', [$a->id, $b->id])
        ->call('insert');

    expect($user->getMedia('gallery')->pluck('id')->all())->toBe([$a->id, $b->id]);
});

it('removes an attached file without deleting it from the library', function () {
    $user = User::factory()->create();
    $media = pickable($user);
    $user->attachMedia($media, 'avatar');

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $user, 'collection' => 'avatar'])
        ->call('remove', $media->id);

    expect($user->getMedia('avatar'))->toBeEmpty()
        ->and(Media::whereKey($media->id)->exists())->toBeTrue();
});

it('works as a plain form input holding ids', function () {
    $user = User::factory()->create();
    [$a, $b] = [pickable($user, 'a'), pickable($user, 'b')];

    Livewire::actingAs($user)
        ->test('media::field', ['collection' => 'gallery'])
        ->call('toggle', $a->id)->call('toggle', $b->id)
        ->call('insert')
        ->assertSet('value', [$a->id, $b->id])
        ->call('remove', $a->id)
        ->assertSet('value', [$b->id]);
});

it('never renders files the viewer may not see, even if their ids are in the value', function () {
    $user = User::factory()->create();
    $theirs = pickable(User::factory()->create());

    $items = Livewire::actingAs($user)
        ->test('media::field', ['collection' => 'gallery'])
        ->set('value', [$theirs->id])
        ->instance()
        ->items();

    expect($items)->toBeEmpty();
});

it('checks the given ability before every change', function () {
    Gate::define('edit-profile', fn (User $actor, User $model) => $actor->is($model));
    [$user, $other] = [User::factory()->create(), User::factory()->create()];

    Livewire::actingAs($user)
        ->test('media::field', ['model' => $other, 'collection' => 'avatar', 'ability' => 'edit-profile'])
        ->assertForbidden();
});

it('shows on the account settings page as the profile photo', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'phone_verified_at' => now()]);

    $this->actingAs($user)->get(route('account.settings'))
        ->assertOk()
        ->assertSee('Profile photo')
        ->assertSee('data-media-field="avatar"', false);
});

it('refuses a model without HasMedia', function () {
    Livewire::test('media::field', ['model' => new Role, 'collection' => 'avatar']);
})->throws('must use App\Modules\Media\Concerns\HasMedia');
