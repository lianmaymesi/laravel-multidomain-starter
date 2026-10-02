# Laravel Multidomain Starter

[![CI](https://github.com/lianmaymesi/laravel-multidomain-starter/actions/workflows/ci.yml/badge.svg)](https://github.com/lianmaymesi/laravel-multidomain-starter/actions/workflows/ci.yml)
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

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
