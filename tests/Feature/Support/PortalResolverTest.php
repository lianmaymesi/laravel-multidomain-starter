<?php

use Atrium\Core\Contracts\Languages;
use Atrium\Core\Models\AppSetting;
use Atrium\Core\Support\NullLanguages;
use Atrium\Core\Support\PortalResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/*
 * PortalResolver decides which portal a request belongs to — it drives error
 * pages, maintenance mode and locale handling, so it gets direct coverage for
 * both domain modes.
 */

function resolvePortal(string $url): string
{
    return app(PortalResolver::class)->resolve(Request::create($url));
}

function useSingleDomain(): void
{
    config([
        'multidomain.single_domain' => true,
        'multidomain.sub_domains' => array_fill_keys(['app', 'backoffice', 'landing', 'account', 'auth', 'api'], 'example.test'),
    ]);
}

function withActiveLanguages(array $codes): void
{
    app()->instance(Languages::class, new class($codes) extends NullLanguages
    {
        public function __construct(private array $codes) {}

        public function activeCodes(): array
        {
            return $this->codes;
        }
    });
}

// ── Multi-domain (one host per portal) ───────────────────────────────

it('resolves each portal from its own host', function (string $portal) {
    $host = config("multidomain.sub_domains.{$portal}");

    expect(resolvePortal("https://{$host}/anything/deep"))->toBe($portal);
})->with(['app', 'backoffice', 'account', 'auth', 'landing']);

it('falls back to landing for an unknown host', function () {
    expect(resolvePortal('https://somewhere-else.test/'))->toBe('landing');
});

// ── Single domain (portal = first path segment) ──────────────────────

it('resolves the portal from the first path segment in single-domain mode', function () {
    useSingleDomain();

    expect(resolvePortal('https://example.test/backoffice/users'))->toBe('backoffice')
        ->and(resolvePortal('https://example.test/app'))->toBe('app')
        ->and(resolvePortal('https://example.test/login'))->toBe('landing')
        ->and(resolvePortal('https://example.test/'))->toBe('landing');
});

it('skips a leading locale segment in path mode', function () {
    useSingleDomain();
    withActiveLanguages(['en', 'ar']);
    AppSetting::set(AppSetting::URL_MODE, AppSetting::MODE_PATH);

    expect(resolvePortal('https://example.test/ar/backoffice/users'))->toBe('backoffice')
        ->and(resolvePortal('https://example.test/ar'))->toBe('landing');
});

it('does not treat a locale-looking segment as a locale in query mode', function () {
    useSingleDomain();
    withActiveLanguages(['en', 'ar']);
    AppSetting::set(AppSetting::URL_MODE, AppSetting::MODE_QUERY);

    expect(resolvePortal('https://example.test/ar/backoffice'))->toBe('landing');
});
