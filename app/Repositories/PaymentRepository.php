<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use mysqli;

final class PaymentRepository
{
    public function latest(int $limit = 10): array
    {
        $connection = Database::connection();
        $statement = $connection->prepare(
            'SELECT id, merchant_request_id, checkout_request_id, phone, amount, account_reference, status,
                    result_code, result_desc, mpesa_receipt_number, transaction_date, callback_received,
                    raw_response, created_at
             FROM mpesa_express_requests
             ORDER BY id DESC
             LIMIT ?'
        );
        $statement->bind_param('i', $limit);
        $statement->execute();
        $result = $statement->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function createRequest(array $data): int
    {
        $connection = Database::connection();
        $merchantRequestId = $data['merchant_request_id'];
        $checkoutRequestId = $data['checkout_request_id'];
        $phone = $data['phone'];
        $amount = $data['amount'];
        $accountReference = $data['account_reference'];
        $status = $data['status'];
        $customerMessage = $data['customer_message'];
        $rawResponse = $data['raw_response'];

        $statement = $connection->prepare(
            'INSERT INTO mpesa_express_requests
            (merchant_request_id, checkout_request_id, phone, amount, account_reference, status, customer_message, raw_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->bind_param(
            'sssissss',
            $merchantRequestId,
            $checkoutRequestId,
            $phone,
            $amount,
            $accountReference,
            $status,
            $customerMessage,
            $rawResponse
        );
        $statement->execute();

        return (int) $connection->insert_id;
    }

    public function updateCallbackResult(array $data): void
    {
        $connection = Database::connection();
        $status = $data['status'];
        $resultCode = $data['result_code'];
        $resultDesc = $data['result_desc'];
        $mpesaReceiptNumber = $data['mpesa_receipt_number'];
        $transactionDate = $data['transaction_date'];
        $checkoutRequestId = $data['checkout_request_id'];

        $statement = $connection->prepare(
            'UPDATE mpesa_express_requests
             SET status = ?,
                 result_code = ?,
                 result_desc = ?,
                 mpesa_receipt_number = ?,
                 transaction_date = ?,
                 callback_received = 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE checkout_request_id = ?'
        );
        $statement->bind_param(
            'sissss',
            $status,
            $resultCode,
            $resultDesc,
            $mpesaReceiptNumber,
            $transactionDate,
            $checkoutRequestId
        );
        $statement->execute();
    }

    public function logCallback(array $data): void
    {
        $connection = Database::connection();
        $merchantRequestId = $data['merchant_request_id'];
        $checkoutRequestId = $data['checkout_request_id'];
        $resultCode = $data['result_code'];
        $resultDesc = $data['result_desc'];
        $metadataJson = $data['metadata_json'];
        $rawPayload = $data['raw_payload'];

        $statement = $connection->prepare(
            'INSERT INTO mpesa_express_callbacks
            (merchant_request_id, checkout_request_id, result_code, result_desc, metadata_json, raw_payload)
            VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'ssisss',
            $merchantRequestId,
            $checkoutRequestId,
            $resultCode,
            $resultDesc,
            $metadataJson,
            $rawPayload
        );
        $statement->execute();
    }
}
