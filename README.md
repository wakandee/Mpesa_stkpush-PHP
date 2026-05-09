# M-Pesa Express (STK Push) Middleware in Pure PHP

Lightweight standalone Safaricom Daraja M-Pesa Express (STK Push) integration built with pure PHP for XAMPP environments.

The project is intentionally minimal, framework-independent, and designed to act as a reusable M-Pesa middleware layer that can later plug into existing client systems with minimal dependencies.

---

## Features

* M-Pesa Express (STK Push) integration
* Centralized callback handling
* Transaction status tracking
* Request/response payload logging
* Lightweight MVC-style structure
* Sandbox and production configuration via `.env`
* Local MySQL transaction storage
* Simple admin/testing dashboard
* Reusable account/reference codes for invoices, products, bookings, subscriptions, or services

---

## Project Structure

```text
index.php                  Front controller

app/
├── Controllers/           Request handlers
├── Services/              Daraja STK Push integration
├── Repositories/          Database operations
├── Support/               Helpers, router, env loader
├── Views/                 Dashboard UI

database/
└── schema.sql             Database tables
```

---

## Available Routes

| Route                     | Description                  |
| ------------------------- | ---------------------------- |
| `/`                       | Redirects to dashboard       |
| `/app`                    | STK Push dashboard           |
| `/app/setup`              | Creates database tables      |
| `/app/mpesa-express/push` | Initiates STK Push           |
| `/callback`               | Receives Safaricom callbacks |

---

## Quick Setup

### 1. Start XAMPP

Start:

* Apache
* MySQL

---

### 2. Create Database

```sql
CREATE DATABASE mpesa_demo;
```

---

### 3. Configure `.env`

Copy:

```text
.env.example
```

to:

```text
.env
```

Then update:

* consumer key
* consumer secret
* shortcode
* passkey
* callback URL

---

### 4. Expose Callback URL

Safaricom requires a public HTTPS callback URL.

For local development, use:

* Ngrok
* Cloudflare Tunnel

Example:

```text
https://your-ngrok-url.ngrok-free.app/mpesa/callback
```

---

### 5. Initialize Database

Open:

```text
http://localhost/mpesa/app/setup
```

---

### 6. Open Dashboard

```text
http://localhost/mpesa/app
```

---

## Sandbox Notes

Common sandbox values:

| Variable         | Value                   |
| ---------------- | ----------------------- |
| Shortcode        | `174379`                |
| Transaction Type | `CustomerPayBillOnline` |

The STK password is generated using:

```text
BusinessShortCode + Passkey + Timestamp
```

If Safaricom returns:

```text
500.001.1001 Wrong credentials
```

confirm:

* the passkey is valid
* the shortcode matches the passkey
* sandbox credentials are correct

---

## Database Tables

The project uses:

* `mpesa_express_requests`
* `mpesa_express_callbacks`

These tables support:

* transaction logging
* reconciliation
* callback tracking
* debugging
* request lifecycle monitoring

---

## Project Scope

This project currently focuses only on:

* M-Pesa Express (STK Push)

The following APIs are intentionally excluded from the initial release:

* C2B
* B2C
* B2B
* Reversals
* Transaction Status

The goal is to keep the middleware lightweight, portable, and easy to integrate into existing systems before expanding into additional Daraja services.

---

## Future Improvements

Possible future modules:

* C2B integration
* Webhook retry handling
* Transaction reconciliation tools
* Multi-client support
* API authentication layer
* Queue processing
* Callback replay tools
* REST API layer

---

## License

MIT License
