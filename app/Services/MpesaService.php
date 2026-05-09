<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Env;
use RuntimeException;

final class MpesaService
{
    public function stkPush(int $amount, string $phone, string $accountReference): array
    {
        $timestamp = date('YmdHis');
        $shortCode = Env::get('MPESA_SHORTCODE');
        $passkey = Env::get('MPESA_PASSKEY');
        $callbackUrl = Env::get('MPESA_CALLBACK_URL');

        if ($shortCode === null || $passkey === null || $callbackUrl === null) {
            throw new RuntimeException('Missing M-PESA environment configuration.');
        }

        if (str_contains($passkey, 'your_') || strlen($passkey) < 20) {
            throw new RuntimeException('MPESA_PASSKEY is not configured correctly. Use the Lipa Na M-Pesa Online passkey from the same Daraja app as your sandbox shortcode.');
        }

        $payload = [
            'BusinessShortCode' => $shortCode,
            'Password' => base64_encode($shortCode . $passkey . $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => Env::get('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
            'Amount' => $amount,
            'PartyA' => $this->formatPhone($phone),
            'PartyB' => $shortCode,
            'PhoneNumber' => $this->formatPhone($phone),
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => Env::get('MPESA_TRANSACTION_DESC', 'STK Push Payment'),
        ];

        return $this->request('/mpesa/stkpush/v1/processrequest', $payload);
    }

    public function accessToken(): string
    {
        $consumerKey = Env::get('MPESA_CONSUMER_KEY');
        $consumerSecret = Env::get('MPESA_CONSUMER_SECRET');

        if ($consumerKey === null || $consumerSecret === null) {
            throw new RuntimeException('Missing Daraja consumer credentials.');
        }

        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

        $response = $this->sendCurlRequest(
            $this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials',
            [
                'Authorization: Basic ' . $credentials,
            ]
        );

        $decoded = json_decode($response, true);

        if (!is_array($decoded) || empty($decoded['access_token'])) {
            throw new RuntimeException('Unable to fetch access token. Response: ' . $response);
        }

        return $decoded['access_token'];
    }

    public function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254' . substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '254' . $digits;
        }

        throw new RuntimeException('Phone number must be Kenyan format like 0712345678 or 254712345678.');
    }

    private function request(string $path, array $payload): array
    {
        $response = $this->sendCurlRequest(
            $this->baseUrl() . $path,
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken(),
            ],
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON response from M-PESA: ' . $response);
        }

        return $decoded;
    }

    private function sendCurlRequest(string $url, array $headers, ?string $payload = null): string
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('cURL request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode >= 400) {
            throw new RuntimeException('M-PESA request failed with HTTP ' . $statusCode . ': ' . $response);
        }

        return $response;
    }

    private function baseUrl(): string
    {
        return Env::get('MPESA_ENV', 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }
}
