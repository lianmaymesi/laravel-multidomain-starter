# Account

Authenticated self-service area, served on `account.APP_MAIN_DOMAIN`, wrapped in `auth` middleware.

- Route file: `routes/account.php`
- Layout: `resources/views/layouts/accounts.blade.php`
- Pages: `resources/views/pages/accounts/` — profile, security, data export, settings, 2FA setup
- CSS/JS: `resources/css/account.css`, `resources/js/account.js`

## Data export download

A separate, unauthenticated route lives directly in `routes/web.php` (not `routes/account.php`) for downloading a completed data export via a signed, single-use token — deliberately not gated by session auth, since the download link may be opened from an email client without an active session.

Both end users and staff can access this portal — there's no `portal:*` middleware restriction here, unlike `app`/`backoffice`.
