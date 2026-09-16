<?php

namespace App\Services;

use App\Models\Language;
use App\Models\LocaleSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LanguageService
{
    /**
     * @return Collection<int, Language>
     */
    public function active(): Collection
    {
        return Language::active();
    }

    /**
     * @return array<int, string>
     */
    public function activeCodes(): array
    {
        return $this->active()->pluck('code')->all();
    }

    /**
     * Multi-language UI (switcher, negotiation, etc.) only makes sense once
     * there's something to switch between — one active language means the
     * whole feature stays invisible and the primary locale is forced.
     */
    public function isMultiLanguageEnabled(): bool
    {
        return $this->active()->count() >= 2;
    }

    public function primary(): ?Language
    {
        return Language::primary();
    }

    public function primaryCode(): string
    {
        return $this->primary()?->code ?? config('app.locale');
    }

    public function current(): ?Language
    {
        return $this->active()->firstWhere('code', app()->getLocale());
    }

    public function currentDirection(): string
    {
        return $this->current()?->direction ?? 'ltr';
    }

    public function isValidCode(string $code): bool
    {
        return in_array($code, $this->activeCodes(), true);
    }

    /**
     * Builds the URL for the current request under a different locale —
     * strips any existing locale segment from the path and prepends the
     * target one (none, for the primary language). A plain `?lang=` query
     * param won't do here: routes/web.php already registers this request's
     * routes under a locale-prefixed URI when one is present, and that
     * path segment outranks the query string in SetLocale's resolution —
     * so switching would silently no-op on a page that's already prefixed.
     */
    public function urlForLocale(Request $request, string $code): string
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), fn ($s) => $s !== ''));

        if (($segments[0] ?? null) !== null && in_array($segments[0], $this->activeCodes(), true)) {
            array_shift($segments);
        }

        if ($code !== $this->primaryCode()) {
            array_unshift($segments, $code);
        }

        $url = rtrim($request->getSchemeAndHttpHost().'/'.implode('/', $segments), '/');
        $query = $request->getQueryString();

        return $query ? "{$url}?{$query}" : $url;
    }

    /**
     * The link a switcher should point at for the given locale — only one
     * URL mechanism is ever active (see backoffice Languages page → URL
     * mode), so this picks whichever one SetLocale middleware is actually
     * honoring right now rather than leaving callers to guess.
     */
    public function switchUrl(Request $request, string $code): string
    {
        if (LocaleSetting::isPathMode()) {
            return $this->urlForLocale($request, $code);
        }

        // Mirrors path mode's own rule: the primary language never carries
        // a URL marker. `?lang=en` would otherwise sit in the address bar
        // for the default language, which reads like it needs the param to
        // work rather than just being what happens without one.
        return $code === $this->primaryCode()
            ? $request->fullUrlWithoutQuery(['lang'])
            : $request->fullUrlWithQuery(['lang' => $code]);
    }

    /**
     * Shape mcamara's LaravelLocalization::setSupportedLocales() expects:
     * code => [name, native, dir] — feeds getCurrentLocaleDirection() etc.
     * directly from the Language table instead of a static config array.
     *
     * @return array<string, array{name: string, native: string, dir: string}>
     */
    public function supportedLocalesArray(): array
    {
        return $this->active()
            ->mapWithKeys(fn (Language $language) => [
                $language->code => [
                    'name' => $language->name,
                    'native' => $language->native_name,
                    'dir' => $language->direction,
                ],
            ])
            ->all();
    }
}
