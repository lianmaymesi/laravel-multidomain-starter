<?php

use Atrium\Core\Models\AppSetting;
use Atrium\Core\Services\SettingsRegistry;
use Atrium\Core\Support\Modules\Module;
use Atrium\Core\Support\Settings\SettingField;
use Atrium\Core\Support\Toast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    /**
     * Keyed by SettingField::$key. A secret field's entry is always blank
     * on load — the decrypted value is never round-tripped into the page;
     * a non-empty value here on save means "replace the stored key," not
     * "here's what's currently stored."
     *
     * @var array<string, mixed>
     */
    public array $values = [];

    public function mount(): void
    {
        abort_unless($this->fields()->isNotEmpty() || $this->cards() !== [], 403);

        foreach ($this->fields() as $field) {
            $this->values[$field->key] = $field->type === SettingField::TYPE_SECRET ? '' : AppSetting::value($field);
        }
    }

    /**
     * @return Collection<int, SettingField>
     */
    public function fields()
    {
        return app(SettingsRegistry::class)->all()->filter(fn (SettingField $field) => Gate::allows($field->permission))->values();
    }

    /**
     * Livewire cards contributed by feature modules ('backoffice.settings.cards'),
     * limited to the ones the viewer may use. Each card is its own component
     * with its own Save button.
     *
     * @return array<int, array{component: string, permission?: string, order?: int}>
     */
    public function cards(): array
    {
        return collect(Module::contributions('backoffice.settings.cards'))
            ->filter(fn (array $card) => ! isset($card['permission']) || Gate::allows($card['permission']))
            ->sortBy(fn (array $card) => $card['order'] ?? 100)
            ->values()
            ->all();
    }

    public function isConfigured(string $key): bool
    {
        return AppSetting::hasEncrypted($key);
    }

    public function save(): void
    {
        $fields = $this->fields();
        abort_unless($fields->isNotEmpty(), 403);

        $rules = [];
        foreach ($fields as $field) {
            if ($field->rules !== []) {
                $rules['values.'.$field->key] = $field->rules;
            }
        }

        $this->validate($rules);

        foreach ($fields as $field) {
            if ($field->type === SettingField::TYPE_SECRET && ($this->values[$field->key] ?? '') === '') {
                continue;
            }

            AppSetting::saveField($field, $this->values[$field->key] ?? null);

            if ($field->type === SettingField::TYPE_SECRET) {
                $this->values[$field->key] = '';
            }
        }

        Toast::success(__('Saved.'));
    }

    public function removeSecret(string $key): void
    {
        $field = app(SettingsRegistry::class)->find($key);
        abort_unless($field !== null && $field->type === SettingField::TYPE_SECRET && Gate::allows($field->permission), 403);

        AppSetting::setEncrypted($key, null);

        Toast::success(__('Saved.'));
    }
};
