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

## API

A versioned, token-authenticated JSON API (Laravel Sanctum) on the `api` subdomain, e.g. `https://api.yourapp.test/v1` (or `/api/v1` in single-domain mode).

1. **Get a token:** Account → **API tokens**. Name it, pick abilities (`read`, `write`) and an expiry. The token is shown once.
2. **Call the API:**

   ```bash
   curl https://api.yourapp.test/v1/me -H "Authorization: Bearer <token>"
   curl https://api.yourapp.test/v1/languages -H "Authorization: Bearer <token>"
   ```

| Endpoint | Ability | Permission |
|---|---|---|
| `GET /v1` | none (public) | — |
| `GET /v1/me` | `read` | — |
| `GET /v1/languages`, `GET /v1/languages/{code}` | `read` | `languages.view` |
| `POST /v1/languages`, `PATCH /v1/languages/{code}` | `write` | `languages.create` / `languages.edit` |
| `DELETE /v1/languages/{code}` | `write` | `languages.delete` |

- **Tokens never grant more than their user.** Abilities only narrow down what a token may do.
- **Errors are always JSON:** 401, 403, 404 and 422 (with field errors).
- **Rate limit:** `API_RATE_LIMIT` requests per minute (default 60), answered with 429 and `Retry-After`.
- **Disabled modules:** a module's endpoints disappear (404).
- **Account deletion** revokes the account's tokens.
- **Session auth never applies to the API.**

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
