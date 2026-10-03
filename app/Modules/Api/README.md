# API module

A versioned, token-authenticated JSON API (Laravel Sanctum) on the `api`
subdomain: `https://api.<APP_MAIN_DOMAIN>/v1`, or `/api/v1` in single-domain
mode. **Off by default** (`MODULE_API=false`), since not every app needs an API.
Switch it on with `.env` or on Backoffice → Modules.

## Who gets API access

A Super Admin decides on **Backoffice → API Access** (`api.manage`):

| Policy | Who can create their own tokens (Account → API tokens) |
|---|---|
| **Nobody** (default) | no one; the page doesn't exist for users. Only admins issue tokens |
| **Backoffice staff** | staff accounts |
| **Everyone** | every signed-in user, in any portal |
| **Specific roles** | users with one of the chosen roles (e.g. a `partner` role) |

For self-service tokens the policy also sets which **abilities** users may
pick, the **longest lifetime**, and **how many tokens** each user may hold. The
page shows how many users the policy currently covers.

**Enforced on every request** (`EnsureApiAccess`): when a user falls outside
the policy (policy tightened, role removed), the tokens they created
themselves stop working immediately (403). They don't keep working until
they expire.

**Admin-issued tokens** (Backoffice → API Access → *Issue a token*) go to any
user, e.g. an integration or service account, with any ability including full
access (`*`) and any lifetime. They are not affected by the self-service
policy. The owner sees them, read-only if they can't self-serve, labelled
*Issued by …*.

Whatever the token: **it never grants more than its user's permissions**.
Abilities only narrow it down.

## Oversight

- **All tokens:** every token with its owner, abilities, issuer (*Self* or the
  admin), last used and expiry. Search by user or token name, and revoke any.
- **Revoke all:** an emergency stop for every token.
- **Audit:** creating and revoking tokens, and policy changes, are written to the
  activity log (log `api`).
- **Account deletion** revokes the account's tokens.

## Abilities

Resource-scoped, e.g. `profile:read`, `languages:read`, `languages:write`; `*` =
everything. Core's are in [config.php](config.php); a module adds its own:

```php
// in the module's bootModule()
Module::contribute('api.abilities', [
    ['ability' => 'invoices:read', 'description' => 'Read invoices'],
]);
```

## Adding endpoints from a module

Put them in the module's `routes/api.php`. It is loaded inside the
authenticated `/v1` group (Sanctum token, JSON responses, rate limit, policy
check), only while both the API module and your module are on:

```php
Route::get('invoices', [InvoiceController::class, 'index'])
    ->middleware('ability:invoices:read')
    ->name('invoices.index');   // → route('api.v1.invoices.index')
```

Check the user's permission in the controller or form request (`Gate::authorize`,
`authorize()`). The Language module (`app/Modules/Language/routes/api.php`) is
the complete example: CRUD, resource, form requests, pagination.

Create tokens through `App\Modules\Api\Services\TokenIssuer` (`issueForSelf`,
`issueFor`), never with a bare `$user->createToken()`. A token with no recorded
issuer counts as self-issued, so the self-service policy applies to it.

## Configuration ([config.php](config.php))

| Key | |
|---|---|
| `version` | URL prefix (`v1`) |
| `rate_limit` / `API_RATE_LIMIT` | requests per minute per token user (default 60) |
| `max_per_page` | upper bound for `?per_page=` |
| `abilities` | core abilities (modules add more) |
| `token_expiry_days` | lifetime choices on both forms (`null` = never) |
| `policy` | defaults for the access policy until a Super Admin saves one |

Errors on the API host are always JSON (401, 403, 404 including unknown
routes, 422 with field errors, 429 with `Retry-After`). A portal session
never authenticates an API request (`sanctum.guard = []`).
