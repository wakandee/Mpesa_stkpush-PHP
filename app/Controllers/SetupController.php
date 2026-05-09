<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use function App\Support\base_url;

final class SetupController
{
    public function index(): void
    {
        $databaseName = \App\Support\Env::get('DB_DATABASE', 'mpesa_demo');
        $safeDatabaseName = preg_replace('/[^A-Za-z0-9_]+/', '', $databaseName) ?: 'mpesa_demo';
        $schemaPath = __DIR__ . '/../../database/schema.sql';
        $schema = file_get_contents($schemaPath);

        if ($schema === false) {
            throw new \RuntimeException('Unable to read database schema file.');
        }

        $connection = Database::connectionWithoutDatabase();
        $connection->query('CREATE DATABASE IF NOT EXISTS `' . $safeDatabaseName . '`');
        $connection->close();

        $db = Database::connection();

        if (!$db->multi_query($schema)) {
            throw new \RuntimeException('Schema import failed: ' . $db->error);
        }

        while ($db->more_results() && $db->next_result()) {
            continue;
        }

        $this->upgradeExistingSchema($db);

        \App\Support\Response::html(
            '<h2>Database setup completed.</h2><p><a href="' . base_url('app') . '">Return to app</a></p>'
        );
    }

    private function upgradeExistingSchema(\mysqli $db): void
    {
        $this->addColumnIfMissing($db, 'mpesa_express_requests', 'result_code', 'INT NULL AFTER customer_message');
        $this->addColumnIfMissing($db, 'mpesa_express_requests', 'mpesa_receipt_number', 'VARCHAR(100) NULL AFTER result_desc');
        $this->addColumnIfMissing($db, 'mpesa_express_requests', 'transaction_date', 'DATETIME NULL AFTER mpesa_receipt_number');
        $this->addColumnIfMissing($db, 'mpesa_express_requests', 'callback_received', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER transaction_date');

        $this->addIndexIfMissing($db, 'mpesa_express_requests', 'idx_phone', 'INDEX idx_phone (phone)');
        $this->addIndexIfMissing($db, 'mpesa_express_requests', 'idx_created_at', 'INDEX idx_created_at (created_at)');
        $this->addIndexIfMissing($db, 'mpesa_express_requests', 'uq_checkout_request_id', 'UNIQUE KEY uq_checkout_request_id (checkout_request_id)');
        $this->dropIndexIfExists($db, 'mpesa_express_requests', 'idx_checkout_request_id');
    }

    private function addColumnIfMissing(\mysqli $db, string $table, string $column, string $definition): void
    {
        if ($this->columnExists($db, $table, $column)) {
            return;
        }

        $db->query(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition));

        if ($db->errno !== 0) {
            throw new \RuntimeException('Schema upgrade failed: ' . $db->error);
        }
    }

    private function addIndexIfMissing(\mysqli $db, string $table, string $index, string $definition): void
    {
        if ($this->indexExists($db, $table, $index)) {
            return;
        }

        $db->query(sprintf('ALTER TABLE `%s` ADD %s', $table, $definition));

        if ($db->errno !== 0) {
            throw new \RuntimeException('Schema index upgrade failed: ' . $db->error);
        }
    }

    private function dropIndexIfExists(\mysqli $db, string $table, string $index): void
    {
        if (!$this->indexExists($db, $table, $index)) {
            return;
        }

        $db->query(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $index));

        if ($db->errno !== 0) {
            throw new \RuntimeException('Schema index cleanup failed: ' . $db->error);
        }
    }

    private function columnExists(\mysqli $db, string $table, string $column): bool
    {
        $statement = $db->prepare(
            'SELECT COUNT(*) AS found
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        $result = $statement->get_result()->fetch_assoc();

        return (int) ($result['found'] ?? 0) > 0;
    }

    private function indexExists(\mysqli $db, string $table, string $index): bool
    {
        $statement = $db->prepare(
            'SELECT COUNT(*) AS found
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?'
        );
        $statement->bind_param('ss', $table, $index);
        $statement->execute();
        $result = $statement->get_result()->fetch_assoc();

        return (int) ($result['found'] ?? 0) > 0;
    }
}
