# Landing

The public marketing/entry point, served on `APP_MAIN_DOMAIN` directly (no subdomain).

- Route file: `routes/landing.php`
- Layout: `resources/views/layouts/landing.blade.php`
- Pages: `resources/views/pages/landing/`
- CSS/JS: `resources/css/landing.css`, `resources/js/landing.js`

Unauthenticated by default — no middleware applied to this group in `routes/web.php`.
