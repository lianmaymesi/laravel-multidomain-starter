<?php

namespace App\Modules\Language\Seeders;

use App\Modules\Language\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Locale;

/**
 * Makes sure there is a primary language: the app's locale (APP_LOCALE).
 * Does nothing once any language exists, so it never overrides what an admin
 * set up on the Languages page.
 */
class LanguageSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Scripts written right-to-left. */
    private const RTL = ['ar', 'arc', 'dv', 'fa', 'ha', 'he', 'khw', 'ks', 'ku', 'ps', 'sd', 'ur', 'yi'];

    public function run(): void
    {
        if (Language::query()->exists()) {
            return;
        }

        $code = strtolower(str((string) config('app.locale', 'en'))->before('_')->before('-')->toString()) ?: 'en';

        Language::create([
            'code' => $code,
            'name' => $this->displayName($code, 'en'),
            'native_name' => $this->displayName($code, $code),
            'direction' => in_array($code, self::RTL, true) ? 'rtl' : 'ltr',
            'is_primary' => true,
            'is_active' => true,
            'order' => 1,
        ]);

        $this->command?->info("Primary language: {$code}");
    }

    private function displayName(string $code, string $inLocale): string
    {
        $name = class_exists(Locale::class) ? Locale::getDisplayLanguage($code, $inLocale) : '';

        return $name !== '' && $name !== $code ? mb_convert_case($name, MB_CASE_TITLE) : strtoupper($code);
    }
}
