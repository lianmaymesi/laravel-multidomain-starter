<?php

namespace App\Modules\Maintenance;

use App\Modules\Maintenance\Health\MaintenanceCheck;
use App\Modules\Maintenance\Http\Middleware\CheckMaintenance;
use Atrium\Core\Support\Modules\Module;
use Atrium\Core\Support\Modules\ModuleProvider;
use Livewire\Livewire;

class MaintenanceServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'maintenance';
    }

    protected function label(): string
    {
        return 'Maintenance';
    }

    protected function description(): string
    {
        return 'Take individual portals offline behind a branded maintenance page.';
    }

    protected function icon(): string
    {
        return 'wrench';
    }

    protected function permissions(): array
    {
        return ['maintenance.view', 'maintenance.update'];
    }

    protected function bootModule(): void
    {
        // Portals left in maintenance show up as a warning on the health report.
        Module::contribute('health.checks', [MaintenanceCheck::class]);

        // Appended after the app's own web-group middleware (SetLocale), the
        // same position it held when registered in bootstrap/app.php.
        $this->appendMiddlewareToGroup('web', CheckMaintenance::class);

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
