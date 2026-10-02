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

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
