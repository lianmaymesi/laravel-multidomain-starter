<?php

use App\Models\AppSetting;
use App\Models\Language;
use App\Models\LanguageLine;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function translateButtonStaff(): User
{
    test()->seed(RolePermissionSeeder::class);

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_primary' => true, 'is_active' => true, 'order' => 1]);
    Language::create(['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => true, 'order' => 2]);

    $staff = User::factory()->create(['privilege' => 'staff', 'phone_verified_at' => now(), 'email_verified_at' => now()]);
    $staff->assignRole('Super Admin');

    return $staff;
}

it('hides the Google Translate button until a key is configured', function () {
    $staff = translateButtonStaff();

    Livewire::actingAs($staff)
        ->test('pages::backoffice.translations', ['scope' => 'common'])
        ->assertSet('editingLocale', 'en')
        ->call('setEditingLocale', 'ar')
        ->assertSee('Sync now')
        ->assertDontSee('Translate with Google');
});

it('translates missing strings and skips ones that drop a required placeholder', function () {
    $staff = translateButtonStaff();
    AppSetting::setEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY, 'fake-key');

    $clean = LanguageLine::create(['group' => '*', 'key' => 'Save', 'scope' => 'common', 'text' => ['en' => 'Save']]);
    $withPlaceholder = LanguageLine::create(['group' => '*', 'key' => 'Hello :name', 'scope' => 'common', 'text' => ['en' => 'Hello :name']]);
    $alreadyTranslated = LanguageLine::create(['group' => '*', 'key' => 'Cancel', 'scope' => 'common', 'text' => ['en' => 'Cancel', 'ar' => 'إلغاء']]);

    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'data' => [
                'translations' => [
                    ['translatedText' => 'حفظ'],
                    ['translatedText' => 'مرحبا'], // dropped the :name token
                ],
            ],
        ]),
    ]);

    Livewire::actingAs($staff)
        ->test('pages::backoffice.translations', ['scope' => 'common'])
        ->call('setEditingLocale', 'ar')
        ->call('translateWithGoogle')
        ->assertSet('editingLocale', 'ar');

    expect($clean->fresh()->text['ar'] ?? null)->toBe('حفظ')
        ->and($withPlaceholder->fresh()->text['ar'] ?? null)->toBeNull()
        ->and($alreadyTranslated->fresh()->text['ar'])->toBe('إلغاء');
});
