<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('searches users through scout on the database driver by name, email or id', function () {
    expect(config('scout.driver'))->toBe('database');

    $actor = adminActor();
    $alice = User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.test']);
    $bob = User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@builder.test']);

    $search = fn (string $term) => Livewire::actingAs($actor)
        ->test('pages::backoffice.users')
        ->set('search', $term)
        ->instance()
        ->users()
        ->pluck('id')
        ->all();

    expect($search('wonder'))->toBe([$alice->id])
        ->and($search('builder.test'))->toBe([$bob->id])
        ->and($search((string) $bob->id))->toContain($bob->id)
        ->and($search('no-such-user'))->toBe([]);
});

it('lists every user, ordered by name, when the search is blank', function () {
    $actor = adminActor();
    User::factory()->create(['name' => 'Zed']);
    User::factory()->create(['name' => 'Amy']);

    $names = Livewire::actingAs($actor)
        ->test('pages::backoffice.users')
        ->set('search', '   ')
        ->instance()
        ->users()
        ->pluck('name');

    expect($names->count())->toBe(User::count())
        ->and($names->all())->toBe($names->sort()->values()->all());
});

it('renders search results on the page', function () {
    User::factory()->create(['name' => 'Searchable Sam']);
    User::factory()->create(['name' => 'Hidden Harry']);

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->set('search', 'Sam')
        ->assertSee('Searchable Sam')
        ->assertDontSee('Hidden Harry');
});
