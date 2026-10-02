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

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
