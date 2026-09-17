<?php

namespace App\Support\Settings;

/**
 * A declarative description of one app-level setting — what kind of value
 * it holds and how to render/validate/store it, so the Settings page can
 * render every field generically instead of hand-building a card per
 * setting. Add a new setting by adding one of these to
 * `App\Services\SettingsRegistry`, not by touching the Settings Blade/PHP.
 */
final class SettingField
{
    public const TYPE_TEXT = 'text';

    public const TYPE_SECRET = 'secret';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_JSON = 'json';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_SELECT = 'select';

    /**
     * A small set of mutually-exclusive options where each one benefits
     * from a visible description (not just a label) — rendered as
     * `flux:radio.group variant="cards"` rather than a plain dropdown.
     */
    public const TYPE_RADIO = 'radio';

    /**
     * @param  array<int, string>  $rules  Laravel validation rules
     * @param  array<string, string>|array<string, array{label: string, description: string}>|null  $options
     *                                                                                                        value => label for TYPE_SELECT; value => ['label' => ..., 'description' => ...] for TYPE_RADIO
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly string $label,
        public readonly ?string $description = null,
        public readonly string $permission = 'settings.edit',
        public readonly mixed $default = null,
        public readonly array $rules = [],
        public readonly ?array $options = null,
        public readonly ?string $helpText = null,
        public readonly ?string $helpUrl = null,
    ) {}
}
