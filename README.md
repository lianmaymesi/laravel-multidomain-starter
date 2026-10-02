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

## Code quality

```bash
composer analyse          # Larastan (PHPStan level 5)
composer format           # fix code style (Pint, Laravel preset)
composer format:check     # check style without changing files
php artisan test
```

- **Static analysis** uses a baseline (`phpstan-baseline.neon`): errors that existed when it was introduced are listed there, and everything new must pass. After fixing baselined errors, run `composer analyse:baseline` to shrink it.
- **CI** runs Larastan on every push and PR, plus Pint on the files a PR changes, so older files can be reformatted on their own schedule with `composer format`.

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
