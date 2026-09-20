<?php

use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Models\ActivityComment;
use App\Modules\Activity\Models\ActivityCommentReaction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('logs a role update with the correct causer and attribute diff', function () {
    $actor = superAdminActor();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    $this->actingAs($actor);
    $role->update(['name' => 'Senior Editor']);

    $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($actor->id)
        ->and($activity->properties['old']['name'])->toBe('Editor')
        ->and($activity->properties['attributes']['name'])->toBe('Senior Editor');
});

it('never logs sensitive User fields', function () {
    $this->actingAs(superAdminActor());
    $user = User::factory()->create();

    $user->update(['password' => bcrypt('new-password'), 'name' => 'Renamed']);

    $activity = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->latest()->first();

    expect($activity)->not->toBeNull();

    $properties = $activity->properties->toArray();
    $flat = json_encode($properties);

    expect($flat)->not->toContain('password')
        ->and($properties['attributes'] ?? [])->not->toHaveKey('password')
        ->and($properties['attributes'] ?? [])->toHaveKey('name');
});

it('logs a manual entry when a permission is toggled on a role', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $permission = Permission::where('name', 'roles.view')->firstOrFail();

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.roles.permissions', ['role' => $role])
        ->call('togglePermission', $permission->id);

    $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->where('description', 'permission granted')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['permission'])->toBe('roles.view');
});

it('logs a manual entry on both sides when a user\'s roles are synced', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $target = User::factory()->create();

    Livewire::actingAs(adminActor())
        ->test('pages::backoffice.users')
        ->call('editRoles', $target->id)
        ->set('selectedRoles', [$role->id])
        ->call('save');

    $userActivity = Activity::where('subject_type', User::class)->where('subject_id', $target->id)->where('description', 'roles synced')->first();

    expect($userActivity)->not->toBeNull()
        ->and($userActivity->properties['added'])->toBe(['Editor'])
        ->and($userActivity->properties['removed'])->toBe([]);

    // Mirrored onto the role's own timeline too, not just the user's.
    $roleActivity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->where('description', 'role assigned')->first();

    expect($roleActivity)->not->toBeNull()
        ->and($roleActivity->properties['user'])->toBe($target->name);
});

it('blocks a staff user without activity.view from the activity log page', function () {
    Livewire::actingAs(staffUser())
        ->test('activity::index')
        ->assertStatus(403);
});

it('blocks a staff user without activity.view from the activity-timeline component', function () {
    Livewire::actingAs(staffUser())
        ->test('activity-timeline')
        ->assertStatus(403);
});

it('scopes a record timeline to only that record\'s activity', function () {
    $actor = superAdminActor();
    $this->actingAs($actor);

    $roleA = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $roleB = Role::create(['name' => 'Publisher', 'guard_name' => 'web']);
    $roleA->update(['name' => 'Senior Editor']);
    $roleB->update(['name' => 'Senior Publisher']);

    $ids = Livewire::actingAs($actor)
        ->test('activity-timeline', ['model' => $roleA])
        ->instance()
        ->days()
        ->flatten(1)
        ->pluck('activity.subject_id');

    expect($ids->unique()->all())->toBe([$roleA->id]);
});

it('collapses 3 consecutive identical events into "show similar activities"', function () {
    $actor = superAdminActor();
    $this->actingAs($actor);
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $permission = Permission::where('name', 'roles.view')->firstOrFail();

    // Grant, revoke, grant again — three consecutive "permission granted"/"permission revoked" pairs
    // collapse into runs; toggle the SAME permission on three times in a row via direct activity() calls
    // to produce three identical consecutive "permission granted" entries.
    foreach (range(1, 3) as $i) {
        activity()->causedBy($actor)->performedOn($role)->withProperties(['permission' => $permission->name])->log('permission granted');
    }

    $groups = Livewire::actingAs($actor)
        ->test('activity-timeline', ['model' => $role])
        ->instance()
        ->days()
        ->flatten(1);

    $grantGroup = $groups->first(fn ($group) => $group['activity']->description === 'permission granted');

    expect($grantGroup['similar'])->toHaveCount(2);
});

it('lists distinct subject types for the global filter dropdown', function () {
    $actor = superAdminActor();
    $this->actingAs($actor);
    Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    User::factory()->create()->update(['name' => 'Renamed']);

    $models = Livewire::actingAs($actor)
        ->test('activity-timeline')
        ->instance()
        ->availableModels();

    expect($models->keys()->all())->toContain(Role::class, User::class);
});

it('lets a viewer post a comment on an activity and see it rendered', function () {
    $actor = superAdminActor();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->firstOrFail();

    Livewire::actingAs($actor)
        ->test('activity-timeline', ['model' => $role])
        ->set("commentDrafts.{$activity->id}", 'Needed for the Q3 campaign.')
        ->call('postComment', $activity->id)
        ->assertSee('Needed for the Q3 campaign.');

    expect(ActivityComment::where('activity_id', $activity->id)->count())->toBe(1);
});

it('blocks posting a comment without the activity.comment permission', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->firstOrFail();

    // A custom role with activity.view but not activity.comment.
    $viewerRole = Role::create(['name' => 'Viewer', 'guard_name' => 'web']);
    $viewerRole->givePermissionTo('activity.view');
    $viewer = User::factory()->create(['privilege' => 'staff', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
    $viewer->assignRole($viewerRole);

    Livewire::actingAs($viewer)
        ->test('activity-timeline', ['model' => $role])
        ->set("commentDrafts.{$activity->id}", 'Trying anyway.')
        ->call('postComment', $activity->id)
        ->assertStatus(403);
});

it('toggles a like/unlike reaction: like, switch to unlike, then un-react', function () {
    $actor = superAdminActor();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->firstOrFail();
    $comment = ActivityComment::create(['activity_id' => $activity->id, 'user_id' => $actor->id, 'body' => 'Why?']);

    Livewire::actingAs($actor)->test('activity-timeline', ['model' => $role])->call('toggleReaction', $comment->id, true);
    expect(ActivityCommentReaction::where('activity_comment_id', $comment->id)->where('user_id', $actor->id)->first()->is_like)->toBeTrue();

    Livewire::actingAs($actor)->test('activity-timeline', ['model' => $role])->call('toggleReaction', $comment->id, false);
    expect(ActivityCommentReaction::where('activity_comment_id', $comment->id)->where('user_id', $actor->id)->first()->is_like)->toBeFalse();

    Livewire::actingAs($actor)->test('activity-timeline', ['model' => $role])->call('toggleReaction', $comment->id, false);
    expect(ActivityCommentReaction::where('activity_comment_id', $comment->id)->where('user_id', $actor->id)->exists())->toBeFalse();
});

it('enforces one reaction per user per comment', function () {
    $actor = superAdminActor();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->firstOrFail();
    $comment = ActivityComment::create(['activity_id' => $activity->id, 'user_id' => $actor->id, 'body' => 'Why?']);

    expect(fn () => ActivityCommentReaction::insert([
        ['activity_comment_id' => $comment->id, 'user_id' => $actor->id, 'is_like' => true, 'created_at' => now(), 'updated_at' => now()],
        ['activity_comment_id' => $comment->id, 'user_id' => $actor->id, 'is_like' => false, 'created_at' => now(), 'updated_at' => now()],
    ]))->toThrow(QueryException::class);
});

it('renders the activity-log-button trigger and modal for a record', function () {
    $actor = superAdminActor();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)
        ->test('pages::backoffice.roles')
        ->assertSeeHtml('activity-'.str(class_basename($role))->kebab().'-'.$role->id);
});
