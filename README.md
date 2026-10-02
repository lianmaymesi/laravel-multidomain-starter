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
- **Modules add their own:** e.g. Maintenance warns about portals left in maintenance

Also:
- `php artisan health:check` (`--json`, `--strict` for deploy scripts; exit 1 on failure)
- **Backoffice → System health** (`health.view`)

To get alerts, set `HEALTH_NOTIFY_MAIL=ops@example.com`. The scheduler then emails whenever the overall status changes, not on every check. Point an uptime monitor (Better Uptime, UptimeRobot, Pingdom…) at `https://<your-domain>/health` with the token header, and alert on non-200.

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
