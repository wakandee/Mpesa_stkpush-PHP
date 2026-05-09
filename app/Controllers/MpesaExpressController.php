<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PaymentRepository;
use App\Services\MpesaService;
use App\Support\Response;

use function App\Support\base_url;

final class MpesaExpressController
{
    public function push(): void
    {
        $amount = isset($_POST['amount']) ? (int) $_POST['amount'] : 0;
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $accountReference = trim((string) ($_POST['account_reference'] ?? ''));

        if ($amount < 1 || $phone === '' || $accountReference === '') {
            Response::html('<p>Amount, phone and account reference are required.</p><p><a href="' . base_url('app') . '">Go back</a></p>', 422);
        }

        $service = new MpesaService();
        $repository = new PaymentRepository();

        try {
            $response = $service->stkPush($amount, $phone, $accountReference);
            $status = isset($response['ResponseCode']) && $response['ResponseCode'] === '0'
                ? 'PENDING'
                : 'FAILED';

            $repository->createRequest([
                'merchant_request_id' => $this->nullableString($response['MerchantRequestID'] ?? null),
                'checkout_request_id' => $this->nullableString($response['CheckoutRequestID'] ?? null),
                'phone' => $service->formatPhone($phone),
                'amount' => $amount,
                'account_reference' => $accountReference,
                'status' => $status,
                'customer_message' => (string) ($response['CustomerMessage'] ?? ''),
                'raw_response' => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]);

            Response::html(
                '<h2>STK Push sent</h2><p>' .
                htmlspecialchars((string) ($response['CustomerMessage'] ?? 'Request submitted.'), ENT_QUOTES, 'UTF-8') .
                '</p><p><a href="' . base_url('app') . '">Return to dashboard</a></p>'
            );
        } catch (\Throwable $exception) {
            Response::html(
                '<h2>Request failed</h2><p>' .
                htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') .
                '</p><p><a href="' . base_url('app') . '">Return to dashboard</a></p>',
                500
            );
        }
    }

    public function callback(): void
    {
        $rawPayload = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawPayload, true);

        if (!is_array($payload)) {
            Response::json([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid callback payload.',
            ], 400);
        }

        $callback = $payload['Body']['stkCallback'] ?? [];
        $metadata = $callback['CallbackMetadata']['Item'] ?? [];
        $metadataMap = $this->metadataToMap($metadata);

        $repository = new PaymentRepository();
        $resultCode = (int) ($callback['ResultCode'] ?? 1);
        $resultDesc = (string) ($callback['ResultDesc'] ?? 'Unknown callback result');

        $repository->logCallback([
            'merchant_request_id' => (string) ($callback['MerchantRequestID'] ?? ''),
            'checkout_request_id' => (string) ($callback['CheckoutRequestID'] ?? ''),
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'metadata_json' => json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'raw_payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        $repository->updateCallbackResult([
            'checkout_request_id' => (string) ($callback['CheckoutRequestID'] ?? ''),
            'status' => $resultCode === 0 ? 'SUCCESS' : 'FAILED',
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'mpesa_receipt_number' => isset($metadataMap['MpesaReceiptNumber'])
                ? (string) $metadataMap['MpesaReceiptNumber']
                : null,
            'transaction_date' => $this->formatTransactionDate($metadataMap['TransactionDate'] ?? null),
        ]);

        Response::json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    private function metadataToMap(array $metadata): array
    {
        $map = [];

        foreach ($metadata as $item) {
            if (isset($item['Name'])) {
                $map[(string) $item['Name']] = $item['Value'] ?? null;
            }
        }

        return $map;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function formatTransactionDate(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === null || strlen($digits) !== 14) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('YmdHis', $digits);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d H:i:s') : null;
    }
}
