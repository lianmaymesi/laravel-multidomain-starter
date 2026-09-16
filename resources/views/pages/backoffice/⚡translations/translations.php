<?php

use App\Models\LanguageLine;
use App\Services\LanguageService;
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

        $line->text = array_filter($this->values[$id] ?? [], fn ($value) => $value !== '');
        $line->save();

        session()->flash('status', 'Saved.');
    }

    public function addLine(): void
    {
        abort_unless(Gate::allows("translations.{$this->scope}"), 403);

        $this->validate([
            'newGroup' => 'required|in:*,validation',
            'newKey' => 'required|string|max:255',
        ]);

        if (LanguageLine::where('group', $this->newGroup)->where('key', $this->newKey)->where('scope', $this->scope)->exists()) {
            $this->addError('newKey', 'That key already exists in this scope.');

            return;
        }

        LanguageLine::create([
            'group' => $this->newGroup,
            'key' => $this->newKey,
            'scope' => $this->scope,
            'text' => array_filter($this->newValues),
        ]);

        $this->resetNewForm();
        $this->loadValues();

        session()->flash('status', 'Line added.');
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows("translations.{$this->scope}"), 403);

        $line = LanguageLine::findOrFail($id);
        abort_unless($line->scope === $this->scope, 403);

        $line->delete();
        unset($this->values[$id]);

        session()->flash('status', 'Line removed.');
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
