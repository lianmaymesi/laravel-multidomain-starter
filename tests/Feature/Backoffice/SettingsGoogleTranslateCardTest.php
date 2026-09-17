<?php

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

function settingsCardStaff(): User
{
    test()->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create(['privilege' => 'staff', 'phone_verified_at' => now(), 'email_verified_at' => now()]);
    $staff->assignRole('Super Admin');

    return $staff;
}

it('saves and encrypts a Google Translate API key without ever displaying it back', function () {
    $staff = settingsCardStaff();

    $response = Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->set('values.'.AppSetting::DEFAULT_TIMEZONE, 'UTC')
        ->set('values.'.AppSetting::GOOGLE_TRANSLATE_API_KEY, 'super-secret-key')
        ->call('save')
        ->assertSet('values.'.AppSetting::GOOGLE_TRANSLATE_API_KEY, '');

    $response->assertDontSee('super-secret-key');

    expect(AppSetting::getDecrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY))->toBe('super-secret-key');

    $stored = AppSetting::where('key', AppSetting::GOOGLE_TRANSLATE_API_KEY)->value('value');
    expect($stored)->not->toBe('super-secret-key');
});

it('removes a stored key', function () {
    $staff = settingsCardStaff();
    AppSetting::setEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY, 'super-secret-key');

    Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->call('removeSecret', AppSetting::GOOGLE_TRANSLATE_API_KEY);

    expect(AppSetting::hasEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY))->toBeFalse();
});

it('never writes the secret value to the activity log', function () {
    $staff = settingsCardStaff();

    Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->set('values.'.AppSetting::DEFAULT_TIMEZONE, 'UTC')
        ->set('values.'.AppSetting::GOOGLE_TRANSLATE_API_KEY, 'super-secret-key')
        ->call('save');

    $properties = Activity::query()
        ->where('subject_type', AppSetting::class)
        ->get()
        ->pluck('properties')
        ->flatten(2)
        ->implode(' ');

    expect($properties)->not->toContain('super-secret-key');
});

it('leaving the key field blank keeps the existing key', function () {
    $staff = settingsCardStaff();
    AppSetting::setEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY, 'original-key');

    Livewire::actingAs($staff)
        ->test('pages::backoffice.settings')
        ->set('values.'.AppSetting::DEFAULT_TIMEZONE, 'UTC')
        ->call('save');

    expect(AppSetting::getDecrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY))->toBe('original-key');
});
