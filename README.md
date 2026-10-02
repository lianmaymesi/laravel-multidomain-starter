# Laravel Multidomain Starter

[![Tests](https://github.com/lianmaymesi/laravel-multidomain-starter/actions/workflows/tests.yml/badge.svg)](https://github.com/lianmaymesi/laravel-multidomain-starter/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/github/v/release/lianmaymesi/laravel-multidomain-starter?include_prereleases)](https://github.com/lianmaymesi/laravel-multidomain-starter/releases)
[![Installs](https://img.shields.io/github/downloads/lianmaymesi/laravel-multidomain-starter/total)](https://github.com/lianmaymesi/laravel-multidomain-starter/releases)
[![License](https://img.shields.io/github/license/lianmaymesi/laravel-multidomain-starter)](LICENSE)

A Laravel 13 + Livewire 4 starter kit for apps split across multiple subdomains — dedicated `auth`, `account`, `app`, `backoffice`, and `landing` portals — with an optional single-domain mode for apps that don't need the split.

Full docs (setup, single-vs-multi domain, SSO, the `make:subdomain` command): **[docs site](https://lianmaymesi.github.io/laravel-multidomain-starter-docs/)**

## Quick start

```bash
laravel new my-app --using=lianmaymesi/laravel-multidomain-starter
```

Set `APP_MAIN_DOMAIN` in `.env` to your local dev domain (e.g. `yourapp.test` via [Laravel Herd](https://herd.laravel.com)), then visit `auth.yourapp.test`, `app.yourapp.test`, `account.yourapp.test`, `backoffice.yourapp.test`, or the main domain for the landing page.

Set `APP_SINGLE_DOMAIN=true` to run everything on one domain instead — see the docs for details.

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

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
