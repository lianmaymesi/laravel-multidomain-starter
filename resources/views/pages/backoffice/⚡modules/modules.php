<?php

use App\Models\ModuleSetting;
use App\Support\Modules\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Turn feature modules on and off at runtime. A saved override wins over the
 * MODULE_* default in config/modules.php; "Reset" drops it again. Modules
 * register at boot, so a change takes effect from the next request — the page
 * reloads itself after every action.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    public function mount(): void
    {
        abort_unless(Gate::allows('modules.manage'), 403);
    }

    /**
     * Overrides are stored in module_settings. Until `php artisan migrate` has
     * created it the page still lists modules, but can't change them.
     */
    public function migrated(): bool
    {
        return Schema::hasTable((new ModuleSetting)->getTable());
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, icon: string, enabled: bool, default: bool, overridden: bool}>
     */
    public function modules(): array
    {
        return array_map(fn (string $name) => Module::info($name), Module::names());
    }

    public function toggle(string $module): void
    {
        $this->authorizeFor($module);

        if (! $this->migrated()) {
            return;
        }

        $enabled = ! Module::enabled($module);

        // Only differences from the config default are stored, so "reset" and
        // "toggled back to what .env says" are the same thing.
        if ($enabled === Module::defaultEnabled($module)) {
            ModuleSetting::where('module', $module)->first()?->delete();
        } else {
            ModuleSetting::updateOrCreate(
                ['module' => $module],
                ['enabled' => $enabled, 'updated_by' => auth()->id()],
            );
        }

        $this->applied($enabled
            ? __('":module" is now enabled.', ['module' => Module::info($module)['label']])
            : __('":module" is now disabled.', ['module' => Module::info($module)['label']]));
    }

    public function resetToDefault(string $module): void
    {
        $this->authorizeFor($module);

        if (! $this->migrated()) {
            return;
        }

        ModuleSetting::where('module', $module)->first()?->delete();

        $this->applied(__('":module" is back to its default.', ['module' => Module::info($module)['label']]));
    }

    private function authorizeFor(string $module): void
    {
        abort_unless(Gate::allows('modules.manage'), 403);
        abort_unless(in_array($module, Module::names(), true), 404);
    }

    private function applied(string $message): void
    {
        // A cached route table would keep serving the old module's routes (or
        // keep a newly enabled module's routes missing) until re-cached.
        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
            $message .= ' '.__('Route cache cleared — run "php artisan route:cache" to re-cache.');
        }

        session()->flash('status', $message);

        // Modules register at boot, so only a fresh request sees the change.
        $this->redirectRoute('backoffice.modules.index');
    }
};
