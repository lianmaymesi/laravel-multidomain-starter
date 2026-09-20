<?php

use App\Models\AppSetting;
use App\Modules\Language\Services\GoogleTranslateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('is not configured until a key is stored', function () {
    expect(app(GoogleTranslateService::class)->isConfigured())->toBeFalse();

    AppSetting::setEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY, 'fake-key');

    expect(app(GoogleTranslateService::class)->isConfigured())->toBeTrue();
});

it('sends a JSON body matching the documented v2 API shape and returns translations in order', function () {
    AppSetting::setEncrypted(AppSetting::GOOGLE_TRANSLATE_API_KEY, 'fake-key');

    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'data' => [
                'translations' => [
                    ['translatedText' => 'مرحبا'],
                    ['translatedText' => 'وداعا'],
                ],
            ],
        ]),
    ]);

    $result = app(GoogleTranslateService::class)->translateMany(['Hello', 'Goodbye'], 'ar');

    expect($result)->toBe(['مرحبا', 'وداعا']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'key=fake-key')
            && $request['q'] === ['Hello', 'Goodbye']
            && $request['target'] === 'ar'
            && $request['source'] === 'en'
            && $request->hasHeader('Content-Type', 'application/json');
    });
});

it('returns an empty array without a configured key', function () {
    expect(app(GoogleTranslateService::class)->translateMany(['Hello'], 'ar'))->toBe([]);
});
