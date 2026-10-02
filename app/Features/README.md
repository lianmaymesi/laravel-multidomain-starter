# Feature flags

Runtime switches for a feature *inside* the app, built on
[Laravel Pennant](https://laravel.com/docs/pennant). One class per flag in this
folder; every class here is discovered at boot and listed on
**Backoffice → Feature Flags**.

## Module or feature flag?

| | Module (`app/Modules`) | Feature flag (`app/Features`) |
|---|---|---|
| Question it answers | Does this feature exist in the app at all? | Does *this portal / this user* see it today? |
| Granularity | Whole app | Per portal or per user |
| What switching off does | Removes routes, nav, middleware, jobs | Hides one code path; everything stays registered |
| Applies | Next request (modules register at boot) | Next check (read per request) |
| Managed by | Super Admin (`modules.manage`) | Admin and Super Admin (`feature-flags.manage`) |

A module is the coarse outer gate; a flag lives inside an enabled module (or
core). A flag checked inside a disabled module simply never runs. See
[app/Modules/README.md](../Modules/README.md).

## Add a flag

```bash
php artisan make:flag NewCurrencyPicker          # per portal
php artisan make:flag BetaDashboard --user       # per user
```

```php
class NewCurrencyPicker extends PortalFlag
{
    public string $name = 'new-currency-picker';    // stored name

    public function label(): string { return 'New currency picker'; }

    public function portals(): array { return ['backoffice']; }   // optional, default: all

    // Value for a portal nothing is stored for yet.
    protected function initial(Portal $portal): mixed
    {
        return false;                    // ship dark
        // return Lottery::odds(1, 10);  // or roll out gradually
    }
}
```

`UserFlag` is the same, with `initial(?User $user)` (`null` = guest). With a
`Lottery`, each portal's/user's result is stored the first time it is checked,
so it sticks.

## Check a flag

```php
use App\Support\Features\Flags;

Flags::active(NewCurrencyPicker::class);   // current portal, or signed-in user
```

```blade
@flag(\App\Features\NewCurrencyPicker::class)
    …
@endflag
```

`Flags::active()` / `@flag` pick the scope from the flag's type, so call sites
never choose one. Unknown flags count as off. Avoid a bare `Feature::active()`
or `@feature` for a portal flag — Pennant's default scope is the signed-in user,
so it would always answer false.

For a specific scope, use Pennant directly:

```php
use App\Support\Features\Portal;
use Laravel\Pennant\Feature;

Feature::for(Portal::named('app'))->activate(NewCurrencyPicker::class);
Feature::for($user)->deactivate(BetaDashboard::class);
```

## How values are stored

Pennant's `database` store (`config/pennant.php`, table `features`). A portal
is stored as scope `portal:app`; a user as `App\Models\User|<id>`.

The first check for a scope stores `initial()`'s answer. After that the stored
value wins, so **changing `initial()` does not affect portals/users already
decided** — press *Reset* on the Feature Flags page (or run
`php artisan pennant:purge new-currency-picker`) to have everyone decided again.

On the Feature Flags page:

- **Portal flag** — a switch per portal, *All on* / *All off*, *Reset*.
- **User flag** — *All on* / *All off* apply to users already decided; users
  seen for the first time are still decided by `initial()`. *Reset* forgets
  every stored value.

## Removing a flag

Once a feature is fully rolled out: delete the class, remove the `@flag` /
`Flags::active()` checks, and run `php artisan pennant:purge <name>`.
