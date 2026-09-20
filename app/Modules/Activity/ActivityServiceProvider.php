<?php

namespace App\Modules\Activity;

use App\Modules\Activity\Livewire\ActivityTimeline;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class ActivityServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'activity';
    }

    protected function label(): string
    {
        return 'Activity log';
    }

    protected function description(): string
    {
        return 'Audit trail of who changed what, with comments and reactions.';
    }

    protected function icon(): string
    {
        return 'clock';
    }

    protected function permissions(): array
    {
        return ['activity.view', 'activity.comment'];
    }

    protected function registerDisabled(): void
    {
        // Core models (User, Role, Permission, ...) use spatie's LogsActivity
        // trait directly. Switching the package off is what actually stops
        // them writing to activity_log while this module is off — the table
        // and its rows stay untouched.
        config(['activitylog.enabled' => false]);
    }

    protected function bootModule(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'activity');

        Livewire::addNamespace('activity', viewPath: $this->modulePath('resources/views/livewire'));
        Livewire::component('activity-timeline', ActivityTimeline::class);
        Blade::anonymousComponentPath($this->modulePath('resources/views/components'), 'activity');

        Module::contribute('backoffice.nav', [[
            'label' => 'Activity Log',
            'route' => 'backoffice.activity.index',
            'icon' => 'clock',
            'permission' => 'activity.view',
            'order' => 15,
        ]]);
    }
}
