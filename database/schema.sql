CREATE DATABASE IF NOT EXISTS solestride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solestride_db;

-- ============================================================
-- USERS (Buyers)
-- ============================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- ADMINS (Seller/Admin dashboard accounts)
-- ============================================================
CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'inventory_manager', 'staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admins_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PRODUCTS
-- ============================================================
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('Sneakers', 'Boots', 'Formal', 'Athletic') NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CART (session or account-tied)
-- ============================================================
CREATE TABLE cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ORDERS (checkout header)
-- ============================================================
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    shipping_address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ORDER_ITEMS (checkout line items)
-- ============================================================
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- AUDIT LOGS (admin action ledger)
-- ============================================================
CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_table VARCHAR(50) DEFAULT NULL,
    target_id INT UNSIGNED DEFAULT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- INDEXES for common lookups
-- ============================================================
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_session ON cart(session_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_audit_admin ON audit_logs(admin_id);