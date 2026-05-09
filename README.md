# M-Pesa Express Checkout in Pure PHP

This is a small MVC-style pure PHP project for Safaricom Daraja M-Pesa Express checkout. It is designed for XAMPP and reads account, database, and Daraja variables from `.env` so the project can move between machines without code edits.

## Project Structure

- `index.php` is the front controller.
- `app/Controllers` contains request handlers.
- `app/Services` contains the Daraja M-Pesa Express integration.
- `app/Repositories` contains database writes and reads.
- `app/Views` contains the dashboard UI.
- `database/schema.sql` contains the local MySQL tables: `mpesa_express_requests` and `mpesa_express_callbacks`.

## Routes

- `/` redirects to `/app`.
- `/app` opens the M-Pesa Express dashboard.
- `/app/setup` creates the database tables.
- `/app/mpesa-express/push` initiates STK Push.
- `/callback` receives Safaricom callback payloads.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Confirm `.env` has your sandbox values.
3. Open `http://localhost/mpesa/app/setup` once.
4. Open `http://localhost/mpesa/app`.
5. Enter the amount, phone number, and account reference, then initiate checkout.

## Fixing `Wrong credentials`

For M-Pesa Express, Daraja builds the request password from:

```text
BusinessShortCode + MPESA_PASSKEY + Timestamp
```

If Safaricom returns `500.001.1001 Wrong credentials`, check that `MPESA_PASSKEY` is the Lipa Na M-Pesa Online passkey from the same Daraja sandbox app as your consumer key and secret. The default sandbox shortcode is usually `174379`, but the passkey must still be the real sandbox passkey, not a placeholder.

Callbacks require a public HTTPS URL. For local XAMPP testing, use a tunnel and set `MPESA_CALLBACK_URL` to that public URL ending with `/mpesa/callback`.

## Project Brief

Build a lightweight reusable M-Pesa middleware layer focused only on M-Pesa Express (STK Push) for the initial release. The platform should remain simple, portable, and easy to plug into future client systems without bringing in unnecessary dependencies or extra Daraja APIs.

The first version should support STK Push initiation, centralized callback handling, transaction status tracking, request and response payload logging, basic transaction monitoring, sandbox/production configuration, and simple account/reference codes that can represent products, invoices, bookings, subscriptions, or services.

C2B and other Daraja APIs are intentionally out of scope for now. They can be added later as separate modules once the core M-Pesa Express layer is stable and easy to integrate.
