# App

The main authenticated application, served on `app.APP_MAIN_DOMAIN`.

- Route file: `routes/app.php`
- Layout: `resources/views/layouts/app.blade.php`
- Pages: `resources/views/pages/app/`
- CSS/JS: `resources/css/app.css`, `resources/js/app.js`

## Access control

Wrapped in `['auth', 'phone.verified', 'email.grace', 'portal:user']`. The `portal:user` alias is `App\Http\Middleware\EnsurePortalAccess` — it redirects staff users to `backoffice.dashboard` instead, keeping end-users and staff in their own portal. This is the core, reusable mechanism (see [Config Reference](/reference/config)); `phone.verified` and `email.grace` are demo-specific and live in `App\Http\Middleware\Demo\`.
