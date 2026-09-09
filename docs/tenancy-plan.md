# Multi-Tenancy for laravel-multidomain-starter

## Context

This starter kit currently ships **multi-portal**, not **multi-tenant**: `config/multidomain.php` maps fixed portal keys (app, backoffice, account, auth, landing, api) to subdomains, each backed by the *same* single-tenant database. There is one `users` table, one set of roles, one dataset.

The ask is to turn this into a genuine multi-tenant starter kit that ships as **zero-dependency, custom-built tenancy** (no `stancl/tenancy` or similar package) — so anyone using the kit gets tenancy "for free" without adopting a heavy package's opinions.

Three decisions the user gave explicitly:
1. **No package** — build resolution + isolation ourselves, reusing the kit's existing patterns (driver-contract style like `SmsService`, config-driven toggles like `single_domain`, Termwind-styled artisan scaffolding like `make:subdomain`).
2. **Support every identification strategy** (subdomain, custom domain, path-prefix) — configurable, so the *installer* decides, not the kit.
3. **All portals become tenant-scoped** (app, backoffice, account, auth) **except** a brand-new **`central`** subdomain, which sits above tenants entirely and manages them (create/suspend/delete tenants, manage domains) — analogous to how `backoffice` today is separate from `app`, but one level higher.

Working branch: `feature/tenancy` (created off `main`, local-only).

### Key architectural calls (flagged for review — push back on any of these)

- **Isolation model**: single database, shared schema, `tenant_id` column + global scope on tenant-owned tables. Simplest to self-host, DB-agnostic (MySQL/Postgres/SQLite all fine), matches "no package" constraint. Schema-per-tenant or DB-per-tenant would need a package-grade migration runner to stay maintainable — out of scope for a boilerplate.
- **Users become tenant-siloed**: `users.tenant_id` nullable (`null` = central/platform user). Email uniqueness moves from globally-unique to unique-per-tenant. Central users are just `User` rows with `tenant_id = null`, reusing the existing `privilege`/role infrastructure rather than a parallel model — keeps OTP, 2FA, account deletion, data export all working unchanged since they hang off `user_id`.
- **`auth` portal becomes tenant-aware**: today `auth.<domain>` is one shared login for every portal. Under tenancy it must resolve the tenant the same way app/backoffice/account do, so login queries the right tenant's user pool. `central.<domain>` gets its own separate login, guarding only `tenant_id = null` users.
- **Roles/permissions stay global** (not tenant-scoped) — `spatie/laravel-permission` tables are structural (portal access, staff/user split), not tenant data. Per-tenant custom roles are called out as a documented future extension, not built now.

---

## Phase 1 — Foundation: Tenant model & context

- `database/migrations/xxxx_create_tenants_table.php`: `id`, `name`, `slug` (unique), `status` (enum: active/suspended), `data` (json, free-form settings), timestamps.
- `app/Models/Tenant.php`.
- `config/tenancy.php` (new file, sibling to `config/multidomain.php`):
  - `identification` — `subdomain` | `domain` | `path` (installer picks one; mirrors the `single_domain` boolean pattern already in `multidomain.php`).
  - `central_domain` — e.g. `central.` + `APP_MAIN_DOMAIN`.
  - `.env.example` additions: `TENANT_IDENTIFICATION`, `CENTRAL_DOMAIN`.
- `app/Services/Tenancy/TenantContext.php` — simple singleton bound in a service provider: `current(): ?Tenant`, `set(Tenant $t)`, `check(): bool`. This is what request code and Eloquent scopes read from.
- `app/Concerns/BelongsToTenant.php` — trait for tenant-owned models: adds a global scope filtering by `TenantContext::current()->id`, and a `creating` listener that auto-fills `tenant_id`.

## Phase 2 — Tenant identification (pluggable resolvers)

Driver-contract style, matching `app/Contracts/SmsService.php`'s pattern:

- `app/Contracts/TenantResolver.php` — `resolve(Request $request): ?Tenant`.
- `app/Services/Tenancy/Resolvers/SubdomainTenantResolver.php` — leftmost label of host, look up `Tenant` by `slug`.
- `app/Services/Tenancy/Resolvers/DomainTenantResolver.php` — full host lookup against a new `tenant_domains` table (supports custom/mapped domains, several per tenant).
- `app/Services/Tenancy/Resolvers/PathTenantResolver.php` — first URI segment, for single-domain-style installs (mirrors the existing `$prefixed` closure in `routes/web.php`).
- Bind the configured resolver in a provider based on `config('tenancy.identification')`.
- `database/migrations/xxxx_create_tenant_domains_table.php`: `tenant_id`, `domain` (unique), `is_primary`, `verified_at`.
- `app/Http/Middleware/IdentifyTenant.php` — runs resolver, sets `TenantContext`, aborts 404 (or redirects to a "tenant not found" landing page) if nothing resolves. Registered on app/backoffice/account/auth domain groups — **not** on `central`.

## Phase 3 — Users & auth integration

- Migration: add nullable `tenant_id` to `users`, drop the global `unique('email')`, add composite `unique(['tenant_id', 'email'])`.
  - Note: MySQL/Postgres unique indexes treat `NULL` as distinct, so composite uniqueness won't itself stop two central users sharing an email — add an app-level `Rule::unique('users')->whereNull('tenant_id')` check in the central registration path to close that gap.
- `App\Models\User` gets `belongsTo(Tenant::class)` + the `BelongsToTenant` trait (skipped/bypassed for `tenant_id = null` rows — scope should no-op when `TenantContext::current()` is null, i.e. on `central`).
- `routes/auth.php` / login-register Livewire pages: no route changes needed since `IdentifyTenant` middleware runs ahead of them on the `auth` domain group — they just need to query users through the tenant-scoped `User` model as normal.
- `User::redirect()` (`app/Models/User.php:162`) unaffected — role-based redirect logic still works per tenant.

## Phase 4 — Route & config wiring

- `routes/web.php`: add `IdentifyTenant` to the `app`, `backoffice`, `account`, `auth` domain groups' middleware stacks (alongside existing `auth`, `phone.verified`, `email.grace`, `portal:*`).
- New `Route::domain(config('tenancy.central_domain'))->name('central.')` group including `routes/central.php` — **no** `IdentifyTenant` middleware.
- `config/multidomain.php` gains a short doc-comment cross-reference to `config/tenancy.php` rather than merging the two files, keeping portal config and tenancy config separable (an installer could theoretically use one without the other).

## Phase 5 — Central portal

- `routes/central.php`, `resources/views/layouts/central.blade.php`, `resources/css/central.css`, `resources/js/central.js` — scaffolded by hand once, following the same shape `make:subdomain` produces.
- Central auth: reuse `web` guard + `User` model (`tenant_id = null`), gated by a `central` role (via existing `EnsurePortalAccess` middleware — `portal:central` already works with no changes since it's a generic role check).
- Livewire pages: `central/tenants` (list/create/suspend/delete), `central/tenants/{tenant}/domains` (add/verify/remove domains against `tenant_domains`).
- Tenant creation flow creates the `Tenant` row + its first tenant-admin `User` (`tenant_id` set) in one step.
- Tenant deletion: soft-delete or hard-delete decision deferred to review — flag as an open question before building (data-export/anonymization patterns already exist in `AccountDeletionService` and could be mirrored per-tenant).

## Phase 6 — Console tooling

Termwind-styled commands matching `MakeSubdomainCommand`'s UX:
- `php artisan tenant:create` — interactive (Laravel Prompts) create tenant + first admin.
- `php artisan tenant:list`
- `php artisan tenant:suspend {tenant}` / `tenant:activate {tenant}`
- `php artisan tenant:domain:add {tenant} {domain}`

## Phase 7 — Tests (Pest)

- Resolver unit tests: each of subdomain/domain/path resolvers against fixture requests.
- Isolation test: two tenants, assert tenant A's authenticated user/query never sees tenant B's rows (global scope enforcement).
- Central portal: inaccessible on tenant subdomains; accessible only to `tenant_id = null` + `central` role users.
- Auth: same email can register once per tenant but is rejected as duplicate within the same tenant; central email uniqueness enforced across `tenant_id = null` rows.
- Unresolved tenant (unknown subdomain/domain) → 404, doesn't leak into central or another tenant's context.

## Phase 8 — Docs

Kept isolated to the main repo only — **do not** touch the nested `laravel-multidomain-starter-docs/` repo (separate remote, out of scope for this feature).

- `docs/tenancy.md` (new): identification modes, wildcard DNS/cert setup for subdomain & custom-domain modes, local hosts-file setup for `central.*`, how to add a new tenant-owned table (`BelongsToTenant` usage), how central tenant management works.
- Root `README.md`: one-line mention that tenancy is opt-in via `config/tenancy.php`, linking to `docs/tenancy.md`.
- `.env.example` additions documented inline.

---

## Verification

- `composer test` (Pest) after each phase touching models/middleware/routes.
- Manual smoke test per phase via `php artisan serve` + hosts-file entries for `app.test`, `backoffice.test`, `central.test`, plus two seeded tenants — confirm cross-tenant data never bleeds, central can manage tenants, unresolved host 404s cleanly.
- `vendor/bin/pint` before each commit (matches existing repo convention).
