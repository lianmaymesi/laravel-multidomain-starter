# Environment Variables

## Multidomain

| Var | Default | Purpose |
|---|---|---|
| `APP_MAIN_DOMAIN` | `localhost` | Base domain (e.g. `yourapp.test`) |
| `APP_SINGLE_DOMAIN` | `false` | Collapse all portals onto one host |
| `SESSION_DOMAIN` | `null` | Shared session cookie domain for SSO, e.g. `.yourapp.test`. Leave `null` in single-domain mode |

## Demo auth feature (Twilio SMS OTP)

Not required by the starter kit core — only needed if you keep the phone-verification demo flow.

| Var | Purpose |
|---|---|
| `TWILIO_ACCOUNT_SID` | Twilio account SID |
| `TWILIO_AUTH_TOKEN` | Twilio auth token |
| `TWILIO_PHONE_NUMBER` | Sending number for OTP SMS |

## Verification settings

| Var | Default |
|---|---|
| `EMAIL_VERIFICATION_GRACE_DAYS` | `7` |
| `OTP_EXPIRES_MINUTES` | `10` |

See `.env.example` for the full list of standard Laravel env vars (database, mail, queue, etc.) — unchanged from a standard Laravel install.
