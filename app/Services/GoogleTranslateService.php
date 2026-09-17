<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;

class GoogleTranslateService
{
    public function isConfigured(): bool
    {
        return AppSetting::hasEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY);
    }

    /**
     * One batched request (Google's v2 API accepts a `q` array in a single
     * call) rather than one call per string — kinder to quota/cost.
     *
     * @param  array<int, string>  $texts
     * @return array<int, string> same order as $texts
     */
    public function translateMany(array $texts, string $target, string $source = 'en'): array
    {
        $apiKey = AppSetting::getDecrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY);

        if ($apiKey === null || $texts === []) {
            return [];
        }

        // Google's documented v2 REST request: JSON body, `q` as a JSON
        // array — Laravel's Http::post() sends a JSON body by default, so
        // no manual encoding is needed here.
        $response = Http::post('https://translation.googleapis.com/language/translate/v2?key='.urlencode($apiKey), [
            'q' => array_values($texts),
            'source' => $source,
            'target' => $target,
            'format' => 'text',
        ]);

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('data.translations', []))
            ->pluck('translatedText')
            ->map(fn ($text) => html_entity_decode($text, ENT_QUOTES))
            ->all();
    }
}
