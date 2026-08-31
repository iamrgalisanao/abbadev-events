# ABBADev Events — registration + GCash payment verification

Laravel backend for the ABBADev seminar funnel: it takes a paid registration
from sign-up → receipt upload → manual GCash verification → confirmed + emailed.
Powers the public API used by the abbadev.com marketing site and a Filament
admin for verifying payments. Deploys to **api.abbadev.com**.

Companion to the marketing site (`abbadev`); see
`abbadev/docs/registration-payment-plan.md` for the full architecture.

## Stack

- Laravel 13, PHP 8.4 (`ext-intl` required)
- Filament 5 admin panel
- MySQL in production, SQLite for local dev/tests
- n8n sends the confirmation email + Telegram (called via webhook)

## Local setup

```bash
composer install
php artisan key:generate
php artisan migrate --seed # seeds the seminar event + admin@abbadev.com / password
php artisan serve
```

Admin: <http://localhost:8000/admin> — `admin@abbadev.com` / `password`
(seeded; change in production). Panel access is gated to `@abbadev.com` emails
(`User::canAccessPanel`).

## Public API

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/registrations` | Step 1 — create a pending registration; returns registration number + GCash pay-to details. |
| POST | `/api/registrations/{id}/payment` | Step 2 — multipart `reference_number`, `amount_submitted`, `receipt`; queues for verification, flags `duplicate` / `amount_mismatch`. |
| GET | `/api/registrations/{number}` | Status lookup for the pending screen. |

The event's `expected_amount` is resolved server-side — the client never sets
the price. CORS is restricted via `CORS_ALLOWED_ORIGINS`.

## Admin verification flow

`/admin` → **Payment verification** queue (tabs: For verification / Amount
mismatch / Possible duplicate / Verified / Rejected / All, with live counts).
Open a payment → review registrant, expected vs submitted amount, GCash ref, and
receipt (streamed to admins only via `/admin/receipts/{payment}`). Verify against
the **GCash Business Portal**, then:

- **Confirm Payment & Send Email** — one DB transaction: payment `verified`,
  registration `confirmed`, records `verified_by`/`verified_at`, then calls n8n
  and records `email_status` + `confirmation_email_sent_at`.
- **Reject / Mark Duplicate / Amount Mismatch** — set status + notes, no email.

## n8n

Set `N8N_PAYMENT_CONFIRMED_URL` + `N8N_PAYMENT_CONFIRMED_TOKEN` to a webhook that
sends the "Payment Confirmed" email + Telegram. Payload:

```json
{
  "event": "registration.payment_confirmed",
  "registration_number": "ABBA-SEM-2026-0041",
  "name": "...", "email": "...", "event_name": "...",
  "amount": 399, "payment_method": "gcash", "payment_reference": "..."
}
```

Optional `N8N_REGISTRATION_RECEIVED_*` fires a "payment received, verifying"
email after Step 2.

## Tests

```bash
php artisan test
```

Covers the public API (flags, storage, lookup), the verification service
(confirm transaction + email status + reject), and Filament admin rendering.

## Deployment (api.abbadev.com)

- MySQL: set `DB_*`; run `php artisan migrate --force`.
- Receipts are private (`storage/app/private`); ensure it is writable and backed up.
- Web server: point the vhost at `public/`, HTTPS only, restrict `/admin`.
- Set the `.env` keys (GCash, n8n, CORS) from `.env.example`.
