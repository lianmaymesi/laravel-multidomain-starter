<?php

namespace App\Modules\Language\Services;

use App\Modules\Language\Models\LanguageLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class TranslationScannerService
{
    /**
     * Directories/files scanned per scope — first match wins. Anything
     * outside these falls back to 'common' (shared across every portal:
     * components, livewire views, app/, mail, etc.).
     *
     * @var array<string, array<int, string>>
     */
    private const SCOPE_PATHS = [
        LanguageLine::SCOPE_LANDING => [
            'resources/views/pages/landing',
            'resources/views/pages/auth',
            'resources/views/layouts/landing.blade.php',
            'resources/views/layouts/auth.blade.php',
        ],
        LanguageLine::SCOPE_PORTAL => [
            'resources/views/pages/app',
            'resources/views/pages/account',
            'resources/views/pages/backoffice',
            'resources/views/layouts/app.blade.php',
            'resources/views/layouts/accounts.blade.php',
            'resources/views/layouts/backoffice.blade.php',
        ],
    ];

    /**
     * Every translatable string literal found under app/ and
     * resources/views, deduped by string — each tagged with the scope it
     * would be created under if synced (first file it's found in wins).
     *
     * @return Collection<int, array{key: string, scope: string}>
     */
    public function scan(): Collection
    {
        $found = collect();

        foreach ($this->files() as $file) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $scope = $this->scopeFor($relative);

            foreach ($this->extractKeys(File::get($file->getPathname())) as $key) {
                if (! $found->has($key)) {
                    $found->put($key, ['key' => $key, 'scope' => $scope]);
                }
            }
        }

        return $found->values();
    }

    /**
     * Scanned strings that don't have a matching LanguageLine row yet.
     *
     * @return Collection<int, array{key: string, scope: string}>
     */
    public function pending(): Collection
    {
        $existing = LanguageLine::where('group', '*')->pluck('key')->all();

        return $this->scan()->reject(fn (array $line) => in_array($line['key'], $existing, true))->values();
    }

    /**
     * Creates a LanguageLine for every pending key whose scope the caller is
     * allowed to edit — silently skips the rest rather than failing the
     * whole sync over one out-of-reach scope.
     *
     * English is seeded automatically (`text.en` = the literal key) since
     * the key IS the English string by convention — regardless of which
     * language is marked primary, English is never left blank for a
     * translator to fill in by hand.
     *
     * @param  array<int, string>  $allowedScopes
     */
    public function sync(array $allowedScopes): int
    {
        $created = 0;

        foreach ($this->pending() as $line) {
            if (! in_array($line['scope'], $allowedScopes, true)) {
                continue;
            }

            LanguageLine::create([
                'group' => '*',
                'key' => $line['key'],
                'scope' => $line['scope'],
                'text' => ['en' => $line['key']],
            ]);

            $created++;
        }

        return $created;
    }

    private function scopeFor(string $relativePath): string
    {
        $normalized = str_replace('\\', '/', $relativePath);

        foreach (self::SCOPE_PATHS as $scope => $paths) {
            foreach ($paths as $path) {
                $matches = str_ends_with($path, '.php')
                    ? $normalized === $path
                    : str_starts_with($normalized, $path.'/');

                if ($matches) {
                    return $scope;
                }
            }
        }

        return LanguageLine::SCOPE_COMMON;
    }

    /**
     * @return Collection<int, \SplFileInfo>
     */
    private function files(): Collection
    {
        return collect(File::allFiles(base_path('app')))
            ->merge(File::allFiles(base_path('resources/views')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            // Excludes this file itself — its own doc comments describe the
            // very pattern being matched, which would otherwise self-match.
            ->reject(fn ($file) => $file->getPathname() === __FILE__);
    }

    /**
     * @return array<int, string>
     */
    private function extractKeys(string $contents): array
    {
        preg_match_all('/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/', $contents, $matches);

        return array_map(
            fn (string $raw, string $quote) => str_replace(['\\'.$quote, '\\\\'], [$quote, '\\'], $raw),
            $matches[2],
            $matches[1],
        );
    }
}
