# Backoffice

The staff-only admin area, served on `backoffice.APP_MAIN_DOMAIN`.

- Route file: `routes/backoffice.php`
- Layout: `resources/views/layouts/backoffice.blade.php`
- Pages: `resources/views/pages/backoffice/`
- CSS/JS: `resources/css/backoffice.css`, `resources/js/backoffice.js`

## Access control

Wrapped in `['auth', 'phone.verified', 'email.grace', 'portal:staff']`. `portal:staff` redirects non-staff users to `app.dashboard`. Whether a user is staff is decided by `User::isStaff()` (currently a `privilege` column) — adapt this to your own role system; `EnsurePortalAccess` just needs `$user->isStaff()` and `$user->redirect()` to exist.
