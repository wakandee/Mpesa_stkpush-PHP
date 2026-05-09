CREATE TABLE IF NOT EXISTS mpesa_express_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_request_id VARCHAR(100) NULL,
    checkout_request_id VARCHAR(100) NULL,
    phone VARCHAR(20) NOT NULL,
    amount INT NOT NULL,
    account_reference VARCHAR(100) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    customer_message TEXT NULL,
    result_code INT NULL,
    result_desc TEXT NULL,
    mpesa_receipt_number VARCHAR(100) NULL,
    transaction_date DATETIME NULL,
    callback_received TINYINT(1) NOT NULL DEFAULT 0,
    raw_response LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_checkout_request_id (checkout_request_id),
    INDEX idx_status (status),
    INDEX idx_phone (phone),
    INDEX idx_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS mpesa_express_callbacks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_request_id VARCHAR(100) NULL,
    checkout_request_id VARCHAR(100) NULL,
    result_code INT NOT NULL DEFAULT 1,
    result_desc TEXT NULL,
    metadata_json LONGTEXT NULL,
    raw_payload LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_callback_checkout_request_id (checkout_request_id)
);
