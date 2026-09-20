<?php

namespace App\Modules\Maintenance;

use App\Modules\Maintenance\Http\Middleware\CheckMaintenance;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class MaintenanceServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'maintenance';
    }

    protected function permissions(): array
    {
        return ['maintenance.view', 'maintenance.update'];
    }

    protected function bootModule(): void
    {
        // Appended after the app's own web-group middleware (SetLocale), the
        // same position it held when registered in bootstrap/app.php.
        Route::pushMiddlewareToGroup('web', CheckMaintenance::class);

        Livewire::addNamespace('maintenance', viewPath: $this->modulePath('resources/views/livewire'));

        Module::contribute('backoffice.nav', [[
            'label' => 'Maintenance',
            'route' => 'backoffice.maintenance.index',
            'icon' => 'wrench',
            'permission' => 'maintenance.view',
            'order' => 10,
            'mobile' => true,
        ]]);
    }
}
