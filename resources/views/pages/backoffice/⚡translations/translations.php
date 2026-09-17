<?php

use App\Models\LanguageLine;
use App\Services\GoogleTranslateService;
use App\Services\LanguageService;
use App\Services\TranslationScannerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    public string $scope = '';

    public string $search = '';

    /** @var array<int, array<string, string>> */
    public array $values = [];

    public string $newGroup = '*';

    public string $newKey = '';

    /** @var array<string, string> */
    public array $newValues = [];

    /**
     * Which single language's textarea is shown per line right now — a
     * page-wide toggle rather than one field per language, so a translator
     * only ever sees (and can only ever edit) their own language.
     */
    public string $editingLocale = '';

    public function mount(?string $scope = null): void
    {
        if ($scope !== null) {
            abort_unless(in_array($scope, LanguageLine::scopes(), true), 404);
            abort_unless(Gate::allows("translations.{$scope}"), 403);
        } else {
            $scope = $this->allowedScopes()->first();
            abort_unless($scope !== null, 403);
        }

        $this->scope = $scope;
        $this->loadValues();

        $activeCodes = $this->languages()->pluck('code')->all();
        $primaryCode = app(LanguageService::class)->primaryCode();
        $this->editingLocale = in_array($primaryCode, $activeCodes, true) ? $primaryCode : ($activeCodes[0] ?? 'en');
    }

    public function setEditingLocale(string $code): void
    {
        if (in_array($code, $this->languages()->pluck('code')->all(), true)) {
            $this->editingLocale = $code;
        }
    }

    /**
     * Never offered for English — the key already IS the English text, so
     * there's nothing to machine-translate it from/to. Entirely optional:
     * this only ever returns true when an admin has actually configured a
     * Google Translate API key in Settings.
     */
    public function canUseGoogleTranslate(): bool
    {
        return $this->editingLocale !== 'en' && app(GoogleTranslateService::class)->isConfigured();
    }

    /**
     * Machine-translates every string in this scope that's still missing a
     * value for the currently-selected language — never overwrites an
     * existing (presumably human-reviewed) translation, and never saves a
     * result that dropped a required :placeholder (see
     * `missingPlaceholders()`) so a bad machine translation can't silently
     * break a string. Entirely opt-in — this only ever runs when the admin
     * clicks the button themselves.
     */
    public function translateWithGoogle(GoogleTranslateService $translator): void
    {
        abort_unless(Gate::allows("translations.{$this->scope}"), 403);

        if (! $this->canUseGoogleTranslate()) {
            return;
        }

        $pending = LanguageLine::where('scope', $this->scope)
            ->where('group', '*')
            ->get()
            ->filter(fn (LanguageLine $line) => ($line->text[$this->editingLocale] ?? '') === '')
            ->values();

        if ($pending->isEmpty()) {
            session()->flash('status', __('Nothing to translate.'));

            return;
        }

        $translated = $translator->translateMany($pending->pluck('key')->all(), $this->editingLocale);

        if ($translated === []) {
            session()->flash('error', __('Google Translate request failed — check the API key in Settings.'));

            return;
        }

        $saved = 0;
        $skipped = 0;

        foreach ($pending as $index => $line) {
            $result = $translated[$index] ?? '';

            if ($result === '' || $this->missingPlaceholders($line->placeholders(), $result) !== []) {
                $skipped++;

                continue;
            }

            $text = $line->text ?? [];
            $text[$this->editingLocale] = $result;
            $line->update(['text' => $text]);
            $saved++;
        }

        $this->loadValues();

        session()->flash('status', $skipped > 0
            ? __('Translated :saved string(s), skipped :skipped (placeholder mismatch).', ['saved' => $saved, 'skipped' => $skipped])
            : __('Translated :saved string(s).', ['saved' => $saved]));
    }

    /**
     * @return Collection<int, string>
     */
    public function allowedScopes()
    {
        return collect(LanguageLine::scopes())->filter(fn (string $scope) => Gate::allows("translations.{$scope}"))->values();
    }

    public function languages()
    {
        return app(LanguageService::class)->active();
    }

    /**
     * Page-wide totals shown in the analytics cards — deliberately not
     * scoped to the current tab, so they read as an overview of the whole
     * translation catalog regardless of which scope you're looking at.
     *
     * @return array{total: int, missing: int, pending: int, languages: int}
     */
    public function stats(): array
    {
        $activeCodes = app(LanguageService::class)->activeCodes();

        return [
            'total' => LanguageLine::count(),
            'missing' => LanguageLine::all()
                ->filter(fn (LanguageLine $line) => array_diff($activeCodes, array_keys($line->text ?? [])) !== [])
                ->count(),
            'pending' => app(TranslationScannerService::class)->pending()->count(),
            'languages' => count($activeCodes),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function scopeCounts(): array
    {
        return LanguageLine::query()
            ->selectRaw('scope, count(*) as aggregate')
            ->groupBy('scope')
            ->pluck('aggregate', 'scope')
            ->all();
    }

    public function syncPending(TranslationScannerService $scanner): void
    {
        $created = $scanner->sync($this->allowedScopes()->all());

        $this->loadValues();

        session()->flash('status', $created > 0 ? __('Synced :count new string(s).', ['count' => $created]) : __('Nothing to sync.'));
    }

    public function lines()
    {
        return LanguageLine::where('scope', $this->scope)
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';

                // `text` is a json column — this matches against the raw
                // serialized value, so it's a substring search across every
                // locale's translation, not a precise per-locale lookup.
                $query->where(fn ($q) => $q->where('key', 'like', $term)->orWhere('text', 'like', $term));
            })
            ->orderBy('group')->orderBy('key')->get();
    }

    public function save(int $id): void
    {
        abort_unless(Gate::allows("translations.{$this->scope}"), 403);

        $line = LanguageLine::findOrFail($id);
        abort_unless($line->scope === $this->scope, 403);

        $value = $this->values[$id][$this->editingLocale] ?? '';
        $missing = $this->missingPlaceholders($line->placeholders(), $value);

        if ($missing !== []) {
            $this->addError('values.'.$id.'.'.$this->editingLocale, __('Missing placeholder(s) from the original text: :list', ['list' => implode(', ', $missing)]));

            return;
        }

        $line->text = array_filter($this->values[$id] ?? [], fn ($v) => $v !== '');
        $line->save();

        session()->flash('status', __('Saved.'));
    }

    public function addLine(): void
    {
        abort_unless(Gate::allows("translations.{$this->scope}"), 403);

        $this->validate([
            'newGroup' => 'required|in:*,validation',
            'newKey' => 'required|string|max:255',
        ]);

        if (LanguageLine::where('group', $this->newGroup)->where('key', $this->newKey)->where('scope', $this->scope)->exists()) {
            $this->addError('newKey', __('That key already exists in this scope.'));

            return;
        }

        $missing = $this->missingPlaceholders(LanguageLine::extractPlaceholders($this->newKey), $this->newValues[$this->editingLocale] ?? '');

        if ($missing !== []) {
            $this->addError('newValues.'.$this->editingLocale, __('Missing placeholder(s) from the key: :list', ['list' => implode(', ', $missing)]));

            return;
        }

        $values = array_filter($this->newValues);

        // The key IS the English string by convention — never leave it
        // blank for a translator to fill in, regardless of which language
        // is currently being edited.
        if ($this->newGroup === '*' && ($values['en'] ?? '') === '') {
            $values['en'] = $this->newKey;
        }

        LanguageLine::create([
            'group' => $this->newGroup,
            'key' => $this->newKey,
            'scope' => $this->scope,
            'text' => $values,
        ]);

        $this->resetNewForm();
        $this->loadValues();

        session()->flash('status', __('Line added.'));
    }

    /**
     * @param  array<int, string>  $required
     * @return array<int, string>
     */
    private function missingPlaceholders(array $required, string $value): array
    {
        if ($required === [] || $value === '') {
            return [];
        }

        return array_values(array_diff($required, LanguageLine::extractPlaceholders($value)));
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows("translations.{$this->scope}"), 403);

        $line = LanguageLine::findOrFail($id);
        abort_unless($line->scope === $this->scope, 403);

        $line->delete();
        unset($this->values[$id]);

        session()->flash('status', __('Line removed.'));
    }

    private function loadValues(): void
    {
        $this->values = $this->lines()
            ->mapWithKeys(fn (LanguageLine $line) => [$line->id => $line->text ?? []])
            ->all();
    }

    private function resetNewForm(): void
    {
        $this->reset(['newKey', 'newValues']);
        $this->newGroup = '*';
    }
};
