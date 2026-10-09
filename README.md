# Atrium

[![CI](https://github.com/atrium-php/atrium/actions/workflows/ci.yml/badge.svg)](https://github.com/atrium-php/atrium/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/github/v/release/atrium-php/atrium?include_prereleases)](https://github.com/atrium-php/atrium/releases)
[![License](https://img.shields.io/github/license/atrium-php/atrium)](LICENSE)

_A free, modular, upgradable Laravel platform for multi-portal apps. Build modules, sell them, keep upgrading._

Laravel 13 + Livewire 4, split across dedicated `auth`, `account`, `app`, `backoffice`, and `landing` portals, with an optional single-domain mode for apps that don't need the split. Atrium is MIT-licensed, and so is every first-party module.

> **Formerly `lianmaymesi/laravel-multidomain-starter`.** Atrium is moving from a starter kit to installed, upgradable packages (`atrium/core`, `atrium/billing`, …) plus a thin `atrium/skeleton`. Until the first tagged release, install from this repository.

Full docs (setup, single-vs-multi domain, SSO, the `make:subdomain` command): **[docs site](https://atrium-php.github.io/atrium-docs/)**

## Requirements

- PHP 8.4+
- A **[Flux UI Pro](https://fluxui.dev/pricing) license**. The UI uses Pro components (charts, sortable/paginated tables, toasts, …), and `livewire/flux-pro` is installed from Flux's private Composer repository. Give Composer your credentials once, before installing:

  ```bash
  composer config --global http-basic.composer.fluxui.dev "your-license-email" "your-license-key"
  ```

  For CI, add `FLUX_USERNAME` and `FLUX_LICENSE_KEY` as GitHub Actions secrets (see `.github/workflows/tests.yml`). Never commit an `auth.json`.

## Quick start

```bash
git clone https://github.com/atrium-php/atrium.git my-app && cd my-app && composer setup
```

Set `APP_MAIN_DOMAIN` in `.env` to your local dev domain (e.g. `yourapp.test` via [Laravel Herd](https://herd.laravel.com)), then visit `auth.yourapp.test`, `app.yourapp.test`, `account.yourapp.test`, `backoffice.yourapp.test`, or the main domain for the landing page.

Set `APP_SINGLE_DOMAIN=true` to run everything on one domain instead — see the docs for details.

## First-run setup

One command gets a fresh clone ready (also run by `composer setup`):

```bash
php artisan app:setup          # app key, migrations, roles, Super Admin, defaults
php artisan app:setup --demo   # + a demo account for every portal
```

It finishes with every portal's URL and who to sign in as:

- **Super Admin**: `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`. Leave the password empty and a strong one is generated and **printed once**. There is no default password. Re-running never resets an existing admin's password unless `ADMIN_PASSWORD` is set.
- **`--demo`**: `demo-admin@example.com` (backoffice, Admin role), `demo-staff@example.com` (backoffice), `demo-user@example.com` (app). They share `DEMO_PASSWORD`, or a generated one. It also adds a little data for enabled modules: EUR/GBP turned on, and Arabic added (inactive) to try RTL. Demo accounts are refused in production.
- **Defaults**: roles and permissions, the primary language from `APP_LOCALE`, and USD as the primary currency.

Safe to run again at any time. `--fresh` drops everything first (asks before it does).

## Code quality

```bash
composer analyse          # Larastan (PHPStan level 5)
composer format           # fix code style (Pint, Laravel preset)
composer format:check     # check style without changing files
php artisan test
```

- **Static analysis** uses a baseline (`phpstan-baseline.neon`): errors that existed when it was introduced are listed there, and everything new must pass. After fixing baselined errors, run `composer analyse:baseline` to shrink it.
- **CI** runs Larastan on every push and PR, plus Pint on the files a PR changes, so older files can be reformatted on their own schedule with `composer format`.

## Continuous integration

`.github/workflows/ci.yml` runs on every pull request, on pushes to `main`, and on demand:

| Job | What | Blocks merge |
|---|---|---|
| Code style (Pint) | Pint on the files the PR changes | yes |
| Static analysis (Larastan) | PHPStan, once `phpstan.neon` exists | yes |
| Frontend build | `npm ci && npm run build`, shared with the test jobs | yes |
| Tests (PHP 8.4 / 8.5) | Pest (Unit, Feature, Arch) in parallel; coverage on 8.4 | yes |
| Dependency audit | `composer audit` + `npm audit` | no (report only) |

Tests only start once style, analysis and the build pass. A new push cancels the run already in progress. Dependabot (`.github/dependabot.yml`) opens weekly, grouped update PRs for Actions, Composer and npm.

**Secrets** (Settings → Secrets and variables → Actions): `FLUX_USERNAME` and `FLUX_LICENSE_KEY`, needed once `livewire/flux-pro` is required. Without them, that step is skipped.

**Branch protection** is a manual, one-time step. In Settings → Branches → add a rule for `main`, require a pull request, and require these status checks: *Code style (Pint)*, *Static analysis (Larastan)*, *Frontend build*, *Tests (PHP 8.4)*, *Tests (PHP 8.5)*.

## API (optional module)

A versioned, token-authenticated JSON API (Laravel Sanctum) on the `api` subdomain, e.g. `https://api.yourapp.test/v1`. It is **off by default**, since not every app needs one. Turn it on with `MODULE_API=true` or on Backoffice → Modules.

- **Super Admin decides who gets API access** on Backoffice → **API Access**:
  - who may create their own tokens: nobody (default), backoffice staff, everyone, or specific roles, plus which abilities, the longest lifetime and how many tokens per user
  - issue tokens to any user (integrations, partners) with any ability, including full access
  - see, search and revoke every token, or revoke them all at once
- **Users** see Account → **API tokens** only when the policy includes them, or when an admin issued them a token.
- **Enforced on every request:** tightening the policy disables affected self-made tokens immediately. A token never grants more than its user's permissions.

```bash
curl https://api.yourapp.test/v1/me        -H "Authorization: Bearer <token>"
curl https://api.yourapp.test/v1/languages -H "Authorization: Bearer <token>"
```

Endpoints, abilities and how modules add their own: [app/Modules/Api/README.md](app/Modules/Api/README.md).

## Health checks

| Endpoint | Purpose |
|---|---|
| `GET /up` | **Liveness:** the app boots. No dependencies touched, for load balancers and container restarts. |
| `GET /health` | **Readiness:** runs every check. **200** when ok or warning, **503** when something failed. The public gets only `{"status": …}`; send `X-Health-Token: <HEALTH_TOKEN>` (or `?token=`) to see each check. |

Checks:
- **Database:** connection, latency, and migrations a deploy forgot to run
- **Cache:** round-trip
- **Queue:** backlog, stuck jobs (is a worker running?), failed jobs, and sync driver in production
- **Storage:** write/read/delete on each disk
- **Scheduler:** heartbeat (is `schedule:run` in cron?)
- **Disk space**
- **Portals:** every configured domain has a host and its routes
- **Environment:** debug in production, app key, config/route cache, https
- **Backups:** each backup disk is reachable, and the newest backup is recent and under the size cap (see [Backups](#backups))
- **Modules add their own:** e.g. Maintenance warns about portals left in maintenance

Also:
- `php artisan health:check` (`--json`, `--strict` for deploy scripts; exit 1 on failure)
- **Backoffice → System health** (`health.view`)

To get alerts, set `HEALTH_NOTIFY_MAIL=ops@example.com`. The scheduler then emails whenever the overall status changes, not on every check. Point an uptime monitor (Better Uptime, UptimeRobot, Pingdom…) at `https://<your-domain>/health` with the token header, and alert on non-200.

## Backups

Disaster recovery with [spatie/laravel-backup](https://spatie.be/docs/laravel-backup). Each run writes one zip holding:
- a dump of the whole database (every portal shares it)
- `storage/app`: uploads, media and avatars. Temporary files are left out: upload staging, health probes and expiring account exports.

Code isn't included, because it lives in git. This is separate from the activity log, which records who did what.

Turn backups on in `.env`:

```dotenv
BACKUP_ENABLED=true
BACKUP_DISKS=backups,s3          # filesystem disks; keep one off the server
BACKUP_NOTIFY_MAIL=ops@example.com   # falls back to HEALTH_NOTIFY_MAIL
BACKUP_ARCHIVE_PASSWORD=…        # AES-256 zip; store it outside the server too
```

The scheduler then runs:

| When | Command | What it does |
|---|---|---|
| 01:00 | `backup:clean` | Applies the retention policy: everything for 7 days, then dailies for 16 days, weeklies for 8 weeks, monthlies for 4 months and yearlies for 2 years. It never deletes the newest backup. |
| 01:30 | `backup:run` | Takes the backup and re-opens the zip to verify it. |
| 09:00 | `backup:monitor` | Emails if the newest backup is older than a day or the backups use more than `BACKUP_MAX_STORAGE_MB`. |

Change the times with `BACKUP_CLEAN_AT`, `BACKUP_RUN_AT` and `BACKUP_MONITOR_AT`. Failures are emailed; successes are not.

The **Backups** health check runs the same monitor on every `/health` run, so an uptime monitor sees stale backups too. With backups off, it warns in production.

The `backups` disk is `storage/app/backups`. On its own it's only good for undoing mistakes: it is lost if the server is.

**Backoffice → Backups** is for Super Admin only (`backups.manage`), because a backup is the whole database. It shows:
- the schedule, destinations, alert recipients and encryption
- every backup on each disk, with its health and size

From there you can take a backup on the queue (database + files, database only, or files only), download one, or delete one. Each request, download and deletion is recorded in the activity log under `backups`, with the user. Downloads also record the IP.

Commands:
- `php artisan backup:run`: take a backup now. Add `--only-db` or `--only-files` to limit it.
- `php artisan backup:list`: backups on each disk, with health and size.

### Restoring

A backup that has never been restored isn't proven. Rehearse this on a staging copy after setup and every few months.

1. **Pick and fetch** a backup. Download it from Backoffice → Backups, or run `php artisan backup:list` and fetch `<disk>/<APP_NAME>/<Y-m-d-H-i-s>.zip`.
2. **Unzip** it. You need `BACKUP_ARCHIVE_PASSWORD` if it was set. Inside are `db-dumps/<driver>-<database>.sql` (e.g. `mysql-laravel.sql`) and `private/…` / `public/…`.
3. **Stop traffic and jobs:** `php artisan down`, and stop queue workers.
4. **Restore the database** into an empty database:
   - MySQL / MariaDB: `mysql -u <user> -p <database> < db-dumps/mysql-<database>.sql` (`mariadb-…` for MariaDB)
   - PostgreSQL: `psql -U <user> -d <database> -f db-dumps/postgresql-<database>.sql`
   - SQLite: `sqlite3 database/database.sqlite < db-dumps/sqlite-sqlite-database.sql`
5. **Restore files:** copy `private/` and `public/` back into `storage/app/`, then run `php artisan storage:link`.
6. **Bring it back:**
   1. Run `php artisan migrate --force` for migrations newer than the backup.
   2. Run `php artisan optimize:clear`.
   3. Restart the workers.
   4. Run `php artisan up`.
   5. Check `php artisan health:check`.

The same `APP_KEY` is required. Without it, 2FA secrets and encrypted settings (API keys) can't be read, so keep `.env` in your secrets manager, not only on the server.

## Search

The search boxes on **Backoffice → Users** and **Backoffice → Activity Log** go through [Laravel Scout](https://laravel.com/docs/scout). By default it uses the `database` driver, which needs no extra service and no index:

```dotenv
SCOUT_DRIVER=database
```

The driver searches the model's own table with `LIKE` (`ILIKE` on PostgreSQL). It matches the columns in the model's `toSearchableArray()`:

| Model | Matches on |
|---|---|
| `App\Models\User` | name, email, or an exact id |
| `App\Modules\Activity\Models\Activity` | description, event, log name |

Only a typed search goes through Scout. A blank search box is a plain database query, so the lists keep working even when an external index is empty.

To make another model searchable, add the `Laravel\Scout\Searchable` trait and a `toSearchableArray()` that returns only real text columns. Every key you return is searched with `LIKE`. A model owned by a module should also return `Module::enabled('<module>')` from `shouldBeSearchable()`, like `Activity` does. A switched-off module then never writes to an external index.

On MySQL/MariaDB or PostgreSQL, a full-text index makes large text columns faster to search. Add the index in a migration (`$table->fullText('description')`), then mark the column on `toSearchableArray()` with `#[SearchUsingFullText(['description'])]`. Full-text matches whole words, not substrings.

### Switching to Meilisearch

Once `LIKE` queries get slow (roughly hundreds of thousands of rows), switch to [Meilisearch](https://www.meilisearch.com). It adds typo tolerance and relevance ranking. Nothing in the pages changes.

1. Install the client: `composer require meilisearch/meilisearch-php http-interop/http-factory-guzzle`
2. Run Meilisearch, for example with `docker run -p 7700:7700 getmeili/meilisearch`.
3. Set the driver:
   ```dotenv
   SCOUT_DRIVER=meilisearch
   MEILISEARCH_HOST=http://127.0.0.1:7700
   MEILISEARCH_KEY=your-master-key
   SCOUT_QUEUE=true   # index on the queue, not in the request
   ```
4. In `config/scout.php`, uncomment the `users` and `activity_log` entries under `meilisearch.index-settings`. The Activity Log filters by record and model, and both lists sort, so these fields must be filterable and sortable.
5. Push the settings and import the existing rows:
   ```bash
   php artisan scout:sync-index-settings
   php artisan scout:import "App\Models\User"
   php artisan scout:import "App\Modules\Activity\Models\Activity"
   ```

From then on, Scout keeps the index up to date as rows are saved and deleted. Re-run `scout:import` after a restore, after turning a module back on, or after any bulk change that skips Eloquent events (for example `DB::table()->update()`).

## Billing

An optional module for selling subscriptions with Stripe, using [Laravel Cashier](https://laravel.com/docs/billing). It is **off by default**. Each user subscribes on their own; there are no team or seat subscriptions.

Card details never reach this app:
- **Subscribing** goes through Stripe Checkout.
- **Cards, invoices, plan changes and cancelling** go through the Stripe Billing Portal.
- **Stripe webhooks** keep the local `subscriptions` table in sync with what happened in Stripe.

### Setup

1. In the Stripe dashboard, create a product with a recurring price for each plan.
2. Turn the module on and add your keys in `.env`:
   ```dotenv
   MODULE_BILLING=true
   STRIPE_KEY=pk_live_…
   STRIPE_SECRET=sk_live_…
   BILLING_STARTER_PRICE=price_…   # Stripe Price ids; a plan without one is hidden
   BILLING_PRO_PRICE=price_…
   BILLING_TRIAL_DAYS=0
   ```
3. Create the webhook endpoint with `php artisan cashier:webhook`. It points at `https://<APP_URL>/stripe/webhook` and subscribes to the events Cashier handles. Then copy its signing secret into `STRIPE_WEBHOOK_SECRET`. The route answers on any host and needs no CSRF token. Without a secret, every webhook is refused with a 503, so Stripe keeps retrying until you set it.
4. In Stripe → Settings → Billing → Customer portal, turn on the actions you want customers to have, such as updating cards, switching plans and cancelling. To allow plan switching, add your plan prices to the portal's product catalogue.
5. Run `php artisan db:seed --class=RolePermissionSeeder` to create the `billing.view` and `billing.manage` permissions.

For local development, forward webhooks with `stripe listen --forward-to <your-local-url>/stripe/webhook` and use the `whsec_…` secret it prints.

Plans, their display price and their feature list are defined in `app/Modules/Billing/config.php`. Prices themselves always come from Stripe; the `amount` there is only a label.

### What you get

- **Account → Billing** (every signed-in user):
  - plan cards that lead to Checkout
  - after subscribing, the current plan and its status: active, trial, payment failed, or cancelled until a date
  - cancel at period end, and resume
  - a "Payment & invoices" button that opens the Billing Portal
- **Backoffice → Billing** (`billing.view`):
  - every subscription, with search, a status filter and counts (active, on trial, cancelling, past due)
  - for Super Admins only (`billing.manage`): cancel at period end, end now, or resume. Each action is recorded on the customer's activity timeline under `billing`.
- **Activity log:** subscriptions that start, change status or plan, or end show on the user's timeline. Routine renewals are not logged.
- **Health check** (`/health`): fails when a Stripe key, the webhook secret or every plan price is missing. Warns when a subscription is past due. It never calls Stripe.
- **Account deletion:** subscriptions that would renew are cancelled in Stripe before the account is anonymized. Card brand and last four digits are cleared. `stripe_id` is kept, so invoices and refunds can still be found in Stripe. If Stripe can't be reached, the deletion stops and the job retries.

Refunds, coupons, tax and invoice changes are handled in the Stripe dashboard. To use Paddle instead of Stripe, replace `laravel/cashier` with `laravel/cashier-paddle` and adapt the module. The two packages share most of their API, but webhooks and checkout differ.

Turning the module off removes the pages, the nav links and the `/stripe/*` routes. The tables and Stripe ids stay, so turning it back on needs no setup. Stripe keeps billing existing subscribers while the module is off. Webhooks get a 404 in the meantime: Stripe retries them for up to three days, and after that they are lost. Turn the module off only when no one is subscribed.

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
