<?php

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaLibrary;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);
});

function libraryItem(string $name, ?User $uploader = null, string $mime = 'application/pdf'): Media
{
    $file = str_starts_with($mime, 'image/')
        ? UploadedFile::fake()->image("{$name}.jpg", 300, 300)
        : UploadedFile::fake()->create("{$name}.pdf", 10, $mime);

    return app(MediaLibrary::class)->upload($file, $uploader);
}

it('is open to staff with media.view and linked from the sidebar', function () {
    Livewire::actingAs(adminActor())->test('media::library')->assertOk();
    Livewire::actingAs(staffUser())->test('media::library')->assertStatus(403);

    $this->actingAs(adminActor())->get(route('backoffice.dashboard'))
        ->assertSee(route('backoffice.media.index'), false);
});

it('uploads a batch of dropped files, reporting the bad ones without losing the good ones', function () {
    Livewire::actingAs(adminActor())
        ->test('media::library')
        ->set('uploads', [
            UploadedFile::fake()->image('one.jpg', 300, 300),
            UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            UploadedFile::fake()->create('two.pdf', 10, 'application/pdf'),
        ])
        ->assertSet('uploads', [])
        ->assertSet('status', '2 files uploaded.')
        ->assertSet('uploadErrors', fn (array $errors) => count($errors) === 1 && str_starts_with($errors[0], 'virus.exe'));

    expect(Media::originals()->pluck('name')->sort()->values()->all())->toBe(['one', 'two']);
});

it('searches and filters by type', function () {
    libraryItem('Invoice March');
    libraryItem('Beach', null, 'image/jpeg');

    $page = Livewire::actingAs(adminActor())->test('media::library');

    expect($page->set('search', 'invoice')->instance()->libraryItems()->pluck('name')->all())->toBe(['Invoice March'])
        ->and($page->set('search', '')->set('type', 'image')->instance()->libraryItems()->pluck('name')->all())->toBe(['Beach'])
        ->and($page->set('type', 'document')->instance()->libraryItems()->pluck('name')->all())->toBe(['Invoice March']);
});

it('pages with load more', function () {
    config(['media.per_page' => 2]);
    collect(range(1, 3))->each(fn ($i) => libraryItem("file {$i}"));

    $page = Livewire::actingAs(adminActor())->test('media::library')->assertSee('Load more');

    expect($page->instance()->libraryItems())->toHaveCount(3); // limit + 1 → "more"

    $page->call('loadMore')->assertDontSee('Load more');
});

it('shows details and saves title and alt text', function () {
    $media = libraryItem('photo', null, 'image/jpeg');

    Livewire::actingAs(adminActor())
        ->test('media::library')
        ->call('select', $media->id)
        ->assertSet('name', 'photo')
        ->assertSee('Not used anywhere yet.')
        ->set('name', 'Team photo')
        ->set('alt', 'The team at the 2026 offsite')
        ->call('save')
        ->assertSet('status', 'Saved.');

    expect($media->fresh())->name->toBe('Team photo')->alt->toBe('The team at the 2026 offsite');
});

it('deletes a file and every use of it', function () {
    $media = libraryItem('photo', null, 'image/jpeg');
    $user = User::factory()->create();
    $user->attachMedia($media, 'avatar');

    Livewire::actingAs(adminActor())
        ->test('media::library')
        ->call('select', $media->id)
        ->assertSee('Used in')
        ->call('delete', $media->id)
        ->assertSet('activeId', null);

    expect(Media::count())->toBe(0)
        ->and($user->getMedia('avatar'))->toBeEmpty();
    Storage::disk('local')->assertMissing($media->path);
});

it('bulk deletes only what the user may delete', function () {
    $viewer = staffUser();
    $viewer->givePermissionTo('media.view');

    $own = libraryItem('own', $viewer);
    $foreign = libraryItem('foreign', adminActor());

    Livewire::actingAs($viewer)
        ->test('media::library')
        ->call('toggleBulk')
        ->call('toggleCheck', $own->id)
        ->call('toggleCheck', $foreign->id)
        ->call('deleteChecked')
        ->assertSet('status', fn (string $status) => str_contains($status, '1 file deleted') && str_contains($status, '1 file skipped'));

    expect(Media::whereKey($own->id)->exists())->toBeFalse()
        ->and(Media::whereKey($foreign->id)->exists())->toBeTrue();
});

it('forbids editing someone else\'s file without media.manage', function () {
    $viewer = staffUser();
    $viewer->givePermissionTo('media.view');
    $foreign = libraryItem('foreign', adminActor());

    Livewire::actingAs($viewer)
        ->test('media::library')
        ->call('select', $foreign->id)
        ->call('delete', $foreign->id)
        ->assertForbidden();

    expect(Media::whereKey($foreign->id)->exists())->toBeTrue();
});
