<?php

use App\Models\User;
use Atrium\Core\Models\AccountDeletionRequest;
use Atrium\Core\Models\Role;
use Atrium\Core\Models\User as AtriumUser;
use Atrium\Core\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the project user model registered in AppServiceProvider', function () {
    expect(Platform::userModel())->toBe(User::class)
        ->and(config('auth.providers.users.model'))->toBe(User::class)
        ->and(is_subclass_of(User::class, AtriumUser::class))->toBeTrue();
});

it('refuses a user model that does not extend the core one', function () {
    Platform::useUserModel(Role::class);
})->throws(InvalidArgumentException::class);

it('relates core models to the project user model, not the base class', function () {
    $user = User::factory()->create();
    $request = AccountDeletionRequest::create([
        'user_id' => $user->id,
        'status' => 'pending',
        'requested_at' => now(),
        'scheduled_at' => now()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS),
    ]);

    expect($request->user)->toBeInstanceOf(User::class);
});
