-- Saga E-commerce Database

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(36) PRIMARY KEY,
    customer_id VARCHAR(36) NOT NULL,
    items JSON NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('PENDING', 'RESERVING_INVENTORY', 'PROCESSING_PAYMENT', 'COMPLETED', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    saga_id VARCHAR(36),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status)
);

-- Inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(36) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    status ENUM('AVAILABLE', 'RESERVED', 'RELEASED') NOT NULL DEFAULT 'AVAILABLE',
    order_id VARCHAR(36),
    reserved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_order_id (order_id)
);

-- Insert seed inventory data
INSERT INTO inventory (product_id, product_name, quantity, status) VALUES 
    ('PROD-001', 'Laptop', 100, 'AVAILABLE'),
    ('PROD-002', 'Phone', 50, 'AVAILABLE'),
    ('PROD-003', 'Tablet', 30, 'AVAILABLE')
ON DUPLICATE KEY UPDATE quantity = quantity;

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id VARCHAR(36) PRIMARY KEY,
    order_id VARCHAR(36) NOT NULL,
    customer_id VARCHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
    transaction_id VARCHAR(36),
    refund_id VARCHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_customer_id (customer_id)
);

-- Sagas table
CREATE TABLE IF NOT EXISTS sagas (
    id VARCHAR(36) PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    data JSON NOT NULL,
    steps JSON NOT NULL,
    current_step INT NOT NULL DEFAULT 0,
    retry_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status)
);