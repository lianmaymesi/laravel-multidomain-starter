<?php

use App\Models\User;
use App\Modules\Billing\Health\BillingCheck;
use Atrium\Core\Events\AccountDeleting;
use Atrium\Core\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('is off by default', function () {
    expect(Module::enabled('billing'))->toBeFalse();
});

it('wires up routes, nav, webhook and health check while the billing module is enabled', function () {
    $this->enableModules('billing');
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    expect(Module::enabled('billing'))->toBeTrue()
        ->and(Route::has('account.billing'))->toBeTrue()
        ->and(Route::has('backoffice.billing.index'))->toBeTrue()
        ->and(Route::has('cashier.webhook'))->toBeTrue()
        ->and(Route::has('cashier.payment'))->toBeTrue()
        ->and(collect(Module::contributions('account.nav'))->pluck('route'))->toContain('account.billing')
        ->and(collect(Module::contributions('backoffice.nav'))->pluck('route'))->toContain('backoffice.billing.index')
        ->and(Module::contributions('health.checks'))->toContain(BillingCheck::class)
        ->and(Event::hasListeners(AccountDeleting::class))->toBeTrue();

    $actor = superAdminActor();

    $this->actingAs($actor)->get(route('account.index'))->assertOk()->assertSee(route('account.billing'));
    $this->actingAs($actor)->get(route('account.billing'))->assertOk();
    $this->actingAs($actor)->get(route('backoffice.billing.index'))->assertOk();
});

it('breaks nothing when the billing module is disabled', function () {
    $this->disableModules('billing');
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    expect(Module::enabled('billing'))->toBeFalse()
        ->and(Route::has('account.billing'))->toBeFalse()
        ->and(Route::has('backoffice.billing.index'))->toBeFalse()
        // Cashier's own routes stay unregistered too.
        ->and(Route::has('cashier.webhook'))->toBeFalse()
        ->and(Route::has('cashier.payment'))->toBeFalse()
        ->and(Event::hasListeners(AccountDeleting::class))->toBeFalse()
        // Schema and permissions survive so re-enabling needs no reseed.
        ->and(Schema::hasTable('subscriptions'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'stripe_id'))->toBeTrue()
        ->and(Permission::where('name', 'billing.manage')->exists())->toBeTrue();

    $actor = superAdminActor();

    $this->post('/stripe/webhook', [])->assertNotFound();
    $this->actingAs($actor)->get(backofficeUrl('billing'))->assertNotFound();
    $this->actingAs($actor)->get(route('account.index'))->assertOk()->assertDontSee('/billing');
    $this->actingAs($actor)->get(route('backoffice.dashboard'))->assertOk()->assertDontSee('/billing');

    // A Billable user is still an ordinary user.
    expect(User::factory()->create()->subscribed())->toBeFalse();
});
