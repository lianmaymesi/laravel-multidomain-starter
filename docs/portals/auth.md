# Auth

Guest-facing authentication flows, served on `auth.APP_MAIN_DOMAIN`.

- Route file: `routes/auth.php`
- Layout: `resources/views/layouts/auth.blade.php`
- Pages: `resources/views/pages/auth/` — login, register, forgot/reset password, 2FA challenge, email/phone verification
- CSS/JS: `resources/css/auth.css`, `resources/js/auth.js`

## Portal redirect

Already-authenticated users hitting this subdomain get redirected to their portal by `App\Http\Middleware\RedirectIfAuthenticated` (the `guest` middleware alias), which checks phone verification state and then calls `$user->redirect()` — see [Config Reference](/reference/config) for how that maps roles to portals.

## Demo-only pieces

The phone OTP flow (Twilio SMS) and email verification grace period in this portal are **example** functionality, not core to the multidomain routing. See `app/Http/Middleware/Demo/` — replace or remove these for your own app.
