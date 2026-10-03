<?php

use App\Modules\Maintenance\Models\PortalSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    /** @var array<string, string> */
    public array $messages = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('maintenance.view'), 403);

        foreach ($this->portals() as $portal) {
            $this->messages[$portal] = PortalSetting::messageFor($portal) ?? '';
        }
    }

    /** @return array<int, string> */
    public function portals(): array
    {
        return collect(array_keys(config('multidomain.sub_domains', [])))
            ->reject(fn (string $portal) => in_array($portal, config('maintenance.exempt_portals', []), true))
            ->values()
            ->all();
    }

    public function settings(): Collection
    {
        return PortalSetting::whereIn('portal', $this->portals())->get()->keyBy('portal');
    }

    public function toggle(string $portal): void
    {
        abort_unless(Gate::allows('maintenance.update'), 403);
        abort_unless(in_array($portal, $this->portals(), true), 403);

        $setting = PortalSetting::firstOrNew(['portal' => $portal]);
        $setting->maintenance_mode = ! $setting->maintenance_mode;
        $setting->updated_by = auth()->id();
        $setting->save();

        session()->flash('status', $setting->maintenance_mode
            ? __('":portal" is now under maintenance.', ['portal' => $portal])
            : __('":portal" is back online.', ['portal' => $portal]));
    }

    public function saveMessage(string $portal): void
    {
        abort_unless(Gate::allows('maintenance.update'), 403);
        abort_unless(in_array($portal, $this->portals(), true), 403);

        $setting = PortalSetting::firstOrNew(['portal' => $portal]);
        $setting->message = trim($this->messages[$portal] ?? '') ?: null;
        $setting->updated_by = auth()->id();
        $setting->save();

        session()->flash('status', __('Maintenance message for ":portal" saved.', ['portal' => $portal]));
    }
};
