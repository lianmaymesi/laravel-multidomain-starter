# Feature modules

Every optional feature lives in its own folder here and can be switched off with
one env var — no routes, nav entry, middleware or Livewire components, and no
errors anywhere else. Its tables and data are left alone, so switching it back
on restores everything with no reseed.

## Add a module

```bash
php artisan make:module Invoices
```

That creates the folder below, adds the toggle to `config/modules.php` and
`.env.example`, and writes a test that proves the module works on *and* that
disabling it breaks nothing. Providers are auto-discovered — there is nothing to
register by hand.

```
app/Modules/Invoices/
├── InvoicesServiceProvider.php   extends ModuleProvider — the module's entry point
├── config.php                    optional; merged into config('invoices') while enabled
├── routes/backoffice.php         one file per portal (backoffice, app, account, ...)
├── Models/  Services/  Http/  Jobs/  Seeders/  ...   as needed
├── database/migrations/          always loaded, even while the module is off
└── resources/views/livewire/⚡index/       Livewire page, referenced as `invoices::index`
```

Turn it off with `MODULE_INVOICES=false` in `.env`.

## What the provider can do

Override any of these on `ModuleProvider`. All but the last two run **only while
the module is enabled**.

| Hook | Use it for |
|---|---|
| `registerModule()` | container bindings |
| `bootModule()` | middleware, Livewire namespace, listeners, nav/settings contributions |
| `schedule(Schedule $s)` | scheduled jobs and commands |
| `permissions()` | permission names to seed — **always** seeded, so roles keep them across a toggle |
| `seeders()` | seeder classes `DatabaseSeeder` should run — **always** listed |

Migrations always load for the same reason: the schema must exist so enabling
the module later is instant.

## Plugging into core UI: extension points

Core never references a module. A module *contributes* to a named point and core
loops over whatever is there — a disabled module contributes nothing.

```php
Module::contribute('backoffice.nav', [[
    'label' => 'Invoices', 'route' => 'backoffice.invoices.index',
    'icon' => 'document-text', 'permission' => 'invoices.view',
    'order' => 50, 'mobile' => false,
]]);
```

| Point | Read by | Item shape |
|---|---|---|
| `backoffice.nav` | backoffice layout sidebar | `label, route, icon, permission, order, mobile, active` |
| `backoffice.settings.cards` | Settings page | `component, permission, order` |
| `permissions` | `RolePermissionSeeder` | permission name (use `permissions()` instead) |
| `database.seeders` | `DatabaseSeeder` | seeder class (use `seeders()` instead) |

Routes: each portal route file ends with `Module::routes('<portal>')`, which
includes every enabled module's `routes/<portal>.php` inside that portal's
domain, prefix, name and middleware group.

## Rules that keep modules removable

1. **Never call into a module from outside it without a guard.** Use
   `Module::enabled('x')` (or `@module('x') … @endmodule` in Blade), or better,
   contribute through an extension point so no check is needed.
2. **Cross-module communication goes through events**, not direct calls. A
   disabled module's listener is simply never registered; the emitting module
   never breaks.
3. **Where core needs a value from a module, depend on a contract with a
   null-object fallback.** Example: `App\Contracts\Currencies` is bound to
   `NullCurrencies` by default and rebound to the real service by the Currency
   module, so `Money::format()` works either way.
4. **Don't drop tables on disable.** Toggling is a runtime switch, not an uninstall.
5. **Every module ships a "disabling breaks nothing" test** — `make:module`
   generates one; extend it for whatever else your module touches.

Toggles vs. feature flags: modules are the coarse, deploy-time outer gate (is
this feature in the app at all). Runtime per-user/per-portal experiments *inside*
an enabled module belong to feature flags, not here.

## Testing a disabled module

Modules register at boot, so flipping config mid-test is too late. Use the
helper, which rebuilds the app with the toggle already off:

```php
$this->disableModules('invoices');
```
