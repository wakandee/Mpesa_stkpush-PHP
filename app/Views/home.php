<?php

declare(strict_types=1);

use App\Support\Env;
use function App\Support\base_url;
use function App\Support\e;

$appName = Env::get('APP_NAME', 'M-Pesa Express Demo');
$callbackUrl = Env::get('MPESA_CALLBACK_URL', 'Not configured');
$appUrl = Env::get('APP_URL', 'http://localhost/mpesa');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appName) ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7f1;
            --card: #ffffff;
            --line: #d7e2d2;
            --text: #17331f;
            --muted: #5a6f60;
            --brand: #1f8f45;
            --brand-dark: #14642f;
            --danger: #aa2e25;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top right, rgba(31, 143, 69, 0.12), transparent 30%),
                linear-gradient(180deg, #f8fbf7 0%, var(--bg) 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 18px 48px;
        }
        .hero, .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(23, 51, 31, 0.08);
        }
        .hero {
            padding: 28px;
            margin-bottom: 24px;
        }
        .hero h1 {
            margin: 0 0 12px;
            font-size: clamp(2rem, 5vw, 3.4rem);
            line-height: 1;
        }
        .hero p, .meta, td, th, label, input, button {
            font-family: "Segoe UI", Tahoma, sans-serif;
        }
        .grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
        }
        .card { padding: 22px; }
        .meta {
            display: grid;
            gap: 8px;
            margin-top: 18px;
            color: var(--muted);
            font-size: 0.95rem;
        }
        form {
            display: grid;
            gap: 14px;
        }
        label {
            display: grid;
            gap: 6px;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            font-size: 1rem;
        }
        button, .btn {
            display: inline-block;
            background: var(--brand);
            color: #fff;
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        button:hover, .btn:hover { background: var(--brand-dark); }
        .helper {
            color: var(--muted);
            font-size: 0.95rem;
        }
        .warning {
            color: var(--danger);
            font-family: "Segoe UI", Tahoma, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            font-size: 0.94rem;
        }
        .pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e9f6ec;
            color: var(--brand-dark);
            font-weight: 700;
            font-size: 0.85rem;
        }
        @media (max-width: 860px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <section class="hero">
        <h1><?= e($appName) ?></h1>
        <p>Pure PHP M-Pesa Express checkout for XAMPP, with `.env` configuration and localhost MySQL storage.</p>
        <div class="meta">
            <div><strong>App URL:</strong> <?= e($appUrl) ?></div>
            <div><strong>Callback URL:</strong> <?= e($callbackUrl) ?></div>
            <div><strong>Database:</strong> <?= e(Env::get('DB_DATABASE', 'mpesa_demo')) ?></div>
        </div>
    </section>

    <div class="grid">
        <section class="card">
            <h2>M-Pesa Express Checkout</h2>
            <p class="helper">Initiate an STK Push from the platform. Manual C2B payments can be added later as a separate module.</p>
            <?php if (!is_file(__DIR__ . '/../../.env')): ?>
                <p class="warning">`.env` is missing. Copy `.env.example` to `.env` first, then fill in your Daraja and database values.</p>
            <?php endif; ?>
            <?php if (str_contains((string) Env::get('MPESA_PASSKEY', ''), 'your_')): ?>
                <p class="warning">`MPESA_PASSKEY` is still a placeholder. This causes Safaricom to return `Wrong credentials` even when the consumer key and secret are correct.</p>
            <?php endif; ?>
            <form method="post" action="<?= e(base_url('app/mpesa-express/push')) ?>">
                <label>
                    Amount
                    <input type="number" name="amount" min="1" value="1" required>
                </label>
                <label>
                    Phone Number
                    <input type="text" name="phone" placeholder="0712345678" required>
                </label>
                <label>
                    Account Reference
                    <input type="text" name="account_reference" value="<?= e(Env::get('MPESA_ACCOUNT_REFERENCE', 'INV-1001')) ?>" required>
                </label>
                <button type="submit">Initiate Express Checkout</button>
            </form>
            <p class="helper">If this is your first run, create the database tables first.</p>
            <a class="btn" href="<?= e(base_url('app/setup')) ?>">Run Database Setup</a>
            <?php if (!$databaseReady): ?>
                <p class="warning">Database is not ready yet: <?= e((string) $error) ?></p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Express Checklist</h2>
            <p class="helper">Safaricom must reach your callback over a public HTTPS URL. For localhost testing, point `MPESA_CALLBACK_URL` to a tunnel like ngrok or Cloudflared.</p>
            <table>
                <tr><th>Step</th><th>Status</th></tr>
                <tr><td>Create `.env` from `.env.example`</td><td>Required</td></tr>
                <tr><td>Update Daraja consumer key, secret, shortcode and passkey</td><td>Required</td></tr>
                <tr><td>Set public callback URL ending with `/callback`</td><td>Required</td></tr>
                <tr><td>Open `/app/setup` once to create tables</td><td>Required</td></tr>
            </table>
        </section>
    </div>

    <section class="card" style="margin-top: 24px;">
        <h2>Recent Express Requests</h2>
        <?php if ($databaseReady && $payments !== []): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Result</th>
                    <th>Receipt</th>
                    <th>Callback</th>
                    <th>Created</th>
                </tr>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e((string) $payment['id']) ?></td>
                        <td><?= e((string) $payment['phone']) ?></td>
                        <td><?= e((string) $payment['amount']) ?></td>
                        <td><?= e((string) $payment['account_reference']) ?></td>
                        <td><span class="pill"><?= e((string) $payment['status']) ?></span></td>
                        <td><?= e($payment['result_code'] === null ? '-' : (string) $payment['result_code']) ?></td>
                        <td><?= e((string) ($payment['mpesa_receipt_number'] ?: '-')) ?></td>
                        <td><?= ((int) $payment['callback_received'] === 1) ? 'Received' : 'Waiting' ?></td>
                        <td><?= e((string) $payment['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif ($databaseReady): ?>
            <p class="helper">No STK requests stored yet.</p>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
