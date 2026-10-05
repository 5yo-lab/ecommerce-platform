-- E-commerce platform shared schema
-- Table ownership:
--   Laravel migrations: categories, products, inventory
--   NestJS migrations:  users, orders, order_items

CREATE DATABASE IF NOT EXISTS ecommerce
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ecommerce;

-- ─── NestJS-owned tables ───────────────────────────────────────────────────

CREATE TABLE users (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255)        NOT NULL,
    email         VARCHAR(255)        NOT NULL,
    password      VARCHAR(255)        NOT NULL,
    role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at    TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP           NULL,
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number      VARCHAR(50)         NOT NULL,
    user_id           BIGINT UNSIGNED     NOT NULL,
    status            ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled')
                                          NOT NULL DEFAULT 'pending',
    payment_status    ENUM('pending', 'paid', 'failed', 'refunded')
                                          NOT NULL DEFAULT 'pending',
    payment_reference VARCHAR(255)        NULL,
    subtotal          DECIMAL(10, 2)      NOT NULL DEFAULT 0.00,
    total             DECIMAL(10, 2)      NOT NULL DEFAULT 0.00,
    created_at        TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_order_number (order_number),
    INDEX idx_orders_status_created (status, created_at),
    INDEX idx_orders_user_id (user_id),
    CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Laravel-owned tables ──────────────────────────────────────────────────

CREATE TABLE categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   BIGINT UNSIGNED     NULL,
    name        VARCHAR(255)        NOT NULL,
    slug        VARCHAR(255)        NOT NULL,
    created_at  TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP           NULL,
    UNIQUE KEY uq_categories_slug (slug),
    INDEX idx_categories_parent_id (parent_id),
    CONSTRAINT fk_categories_parent_id FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     BIGINT UNSIGNED     NOT NULL,
    sku             VARCHAR(100)        NOT NULL,
    name            VARCHAR(255)        NOT NULL,
    slug            VARCHAR(255)        NOT NULL,
    description     TEXT                NULL,
    price           DECIMAL(10, 2)      NOT NULL,
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    image_path      VARCHAR(500)        NULL,
    thumbnail_path  VARCHAR(500)        NULL,
    created_at      TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP           NULL,
    UNIQUE KEY uq_products_sku (sku),
    UNIQUE KEY uq_products_slug (slug),
    INDEX idx_products_category_price (category_id, price),
    CONSTRAINT fk_products_category_id FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory (
    product_id          BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    quantity            INT UNSIGNED    NOT NULL DEFAULT 0,
    low_stock_threshold INT UNSIGNED    NOT NULL DEFAULT 10,
    updated_at          TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_product_id FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── NestJS-owned (depends on Laravel products) ────────────────────────────

CREATE TABLE order_items (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     BIGINT UNSIGNED NOT NULL,
    product_id   BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255)    NOT NULL,
    unit_price   DECIMAL(10, 2)  NOT NULL,
    quantity     INT UNSIGNED    NOT NULL,
    line_total   DECIMAL(10, 2)  NOT NULL,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id),
    CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product_id FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
