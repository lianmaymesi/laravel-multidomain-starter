# Config Reference

## `config/multidomain.php`

| Key | Env | Default | Purpose |
|---|---|---|---|
| `main_domain` | `APP_MAIN_DOMAIN` | `localhost` | Base domain every portal is derived from |
| `single_domain` | `APP_SINGLE_DOMAIN` | `false` | Collapse all portals onto `main_domain` — see [Single vs Multi Domain](/guide/single-vs-multi-domain) |
| `sub_domains` | — | derived | Array of portal key → resolved domain (`app`, `backoffice`, `landing`, `account`, `auth`, `api`) |

## `config/verification.php`

Demo-app-specific settings for the example auth flow (email verification grace period, OTP), not part of the core multidomain routing:

| Key | Env | Default |
|---|---|---|
| `email_verification_grace_days` | `EMAIL_VERIFICATION_GRACE_DAYS` | `7` |
| `otp.expires_minutes` | `OTP_EXPIRES_MINUTES` | `10` |
| `otp.max_attempts` | — | `5` |
| `otp.resend_cooldown` | — | `60` (seconds) |
| `otp.resend_max_attempts` | — | `3` |
| `otp.resend_lockout_seconds` | — | `86400` |

## Portal → role mapping

`App\Http\Middleware\EnsurePortalAccess` (alias `portal:{user|staff}`) redirects a request to the correct portal if the authenticated user's role doesn't match. It relies on two methods on your user model:

```php
$user->isStaff(): bool
$user->redirect(): string   // route name/URL to send a mismatched user to
```

`App\Models\User` implements both today via a `privilege` column — swap these out for your own role system, the middleware doesn't care how they're implemented.

## Core vs demo middleware

Registered in `bootstrap/app.php`:

| Alias | Class | |
|---|---|---|
| `guest` | `App\Http\Middleware\RedirectIfAuthenticated` | core |
| `portal` | `App\Http\Middleware\EnsurePortalAccess` | core |
| `phone.verified` | `App\Http\Middleware\Demo\EnsurePhoneIsVerified` | demo/example |
| `email.grace` | `App\Http\Middleware\Demo\EnsureEmailVerificationNotExpired` | demo/example |
| `staff` | `App\Http\Middleware\Demo\EnsureIsStaff` | demo/example |

The "demo" ones implement this starter kit's worked example (phone OTP + email grace period) — replace or delete them for your own app; they're not required for the portal/subdomain mechanism itself.
