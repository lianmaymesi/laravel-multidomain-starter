<?php

use App\Modules\Activity\Models\Activity;
use Atrium\Core\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function searchedDescriptions(Testable $component): array
{
    return $component->instance()->days()->flatten(1)->pluck('activity.description')->unique()->values()->all();
}

it('narrows the global timeline to activity matching the search', function () {
    $actor = superAdminActor();
    $this->actingAs($actor);

    activity()->performedOn($actor)->log('nightly report exported');
    activity()->performedOn($actor)->log('settings changed');

    $component = Livewire::actingAs($actor)
        ->test('activity-timeline')
        ->set('search', 'report');

    expect(searchedDescriptions($component))->toBe(['nightly report exported'])
        ->and($component->instance()->totalCount())->toBe(1)
        ->and($component->instance()->hasMore())->toBeFalse();

    $component->assertSee('nightly report exported')->assertDontSee('settings changed');
});

it('combines the search with the model filter', function () {
    $actor = superAdminActor();
    $this->actingAs($actor);

    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    activity()->performedOn($role)->log('special role note');
    activity()->performedOn($actor)->log('special user note');

    $component = Livewire::actingAs($actor)
        ->test('activity-timeline')
        ->set('modelFilter', Role::class)
        ->set('search', 'special');

    expect(searchedDescriptions($component))->toBe(['special role note']);
});

it('keeps a record timeline scoped to that record while searching', function () {
    $actor = superAdminActor();
    $this->actingAs($actor);

    $roleA = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $roleB = Role::create(['name' => 'Publisher', 'guard_name' => 'web']);
    $roleA->update(['name' => 'Senior Editor']);
    $roleB->update(['name' => 'Senior Publisher']);

    $ids = Livewire::actingAs($actor)
        ->test('activity-timeline', ['model' => $roleA])
        ->set('search', 'updated')
        ->instance()
        ->days()
        ->flatten(1)
        ->pluck('activity.subject_id');

    expect($ids->unique()->all())->toBe([$roleA->id]);
});

it('resets the loaded window when the search changes', function () {
    Livewire::actingAs(superAdminActor())
        ->test('activity-timeline')
        ->call('loadMore')
        ->assertSet('loaded', 40)
        ->set('search', 'anything')
        ->assertSet('loaded', 20);
});

it('shows a no-match message for an empty search result', function () {
    Livewire::actingAs(superAdminActor())
        ->test('activity-timeline')
        ->set('search', 'zzz-nothing-matches')
        ->assertSee('No activity matches your search.');
});

it('stops activity from being indexed while the activity module is disabled', function () {
    expect((new Activity)->shouldBeSearchable())->toBeTrue();

    $this->disableModules('activity');

    expect((new Activity)->shouldBeSearchable())->toBeFalse();
});
