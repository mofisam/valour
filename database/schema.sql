-- Valour Empire Laundry Services — improved schema (MySQL 8 / MariaDB 10.4+)
CREATE DATABASE IF NOT EXISTS valour_empire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE valour_empire;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles & users (replaces profiles + user_roles) ---------------------------
CREATE TABLE roles (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
    label       VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role_id        TINYINT UNSIGNED NOT NULL,
    avatar_url     VARCHAR(500) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at  DATETIME NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- 2. Business settings (key/value: name, logo, bank details, invoice template…)
CREATE TABLE settings (
    `key`       VARCHAR(100) PRIMARY KEY,
    `value`     TEXT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Customers ---------------------------------------------------------------
-- total_amount_spent is intentionally NOT stored: see view customer_spending.
CREATE TABLE customers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    phone_number  VARCHAR(30) NULL,
    email         VARCHAR(255) NULL,
    address       TEXT NULL,
    birthday      DATE NULL,
    notes         TEXT NULL,
    deleted_at    DATETIME NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers_name (full_name),
    INDEX idx_customers_phone (phone_number),
    INDEX idx_customers_deleted (deleted_at)
) ENGINE=InnoDB;

-- 4. Catalogue: services & clothing types -------------------------------------
CREATE TABLE services (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL UNIQUE,
    pricing_model  ENUM('per_item','per_kg','per_hour') NOT NULL DEFAULT 'per_item',   -- per_hour = machine time
    price_per_kg   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    price_per_hour DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    -- Which statuses a job of this service passes through, in order (comma separated).
    -- e.g. an ironing-only service skips 'washing'.
    workflow       VARCHAR(255) NOT NULL DEFAULT 'pending,washing,ready,delivered',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE clothing_types (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    display_name    VARCHAR(150) NULL,
    price_per_item  DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- fallback price when a service has no specific price for this item
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4b. Price list: price of each clothing type PER SERVICE (the main source of job prices)
CREATE TABLE service_prices (
    service_id        INT UNSIGNED NOT NULL,
    clothing_type_id  INT UNSIGNED NOT NULL,
    price             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (service_id, clothing_type_id),
    CONSTRAINT fk_sp_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sp_type    FOREIGN KEY (clothing_type_id) REFERENCES clothing_types(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. Invoice sequences (atomic, restarts every month: VE-26-10-001) ------------
CREATE TABLE invoice_sequences (
    period       CHAR(5) PRIMARY KEY,                    -- 'YY-MM', e.g. '26-10'
    last_number  INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Jobs ---------------------------------------------------------------------
CREATE TABLE jobs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number   VARCHAR(50) NULL UNIQUE,              -- NULL while draft
    customer_id      INT UNSIGNED NULL,
    service_id       INT UNSIGNED NULL,
    status           ENUM('pending','in_use','washing','dry_cleaning','ironing','ready','delivered','cancelled') NOT NULL DEFAULT 'pending',
    needs_pricing    TINYINT(1) NOT NULL DEFAULT 0,        -- 1 = created by staff, waiting for the admin to set the price
    payment_status   ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    is_draft         TINYINT(1) NOT NULL DEFAULT 0,
    job_date         DATE NOT NULL,
    due_date         DATE NULL,
    total_clothes    INT UNSIGNED NOT NULL DEFAULT 0,
    total_kg         DECIMAL(10,3) NULL,
    price_per_kg     DECIMAL(12,2) NULL,
    duration_minutes INT UNSIGNED NULL,                    -- machine-time jobs
    price_per_hour   DECIMAL(12,2) NULL,
    subtotal         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_type    ENUM('none','percent','fixed') NOT NULL DEFAULT 'none',
    discount_value   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid      DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- cached SUM(payments.amount), maintained by Payment model
    notes            TEXT NULL,
    created_by       INT UNSIGNED NULL,
    deleted_at       DATETIME NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_customer (customer_id),
    INDEX idx_jobs_date (job_date),
    INDEX idx_jobs_status (status),
    INDEX idx_jobs_needs_pricing (needs_pricing),
    INDEX idx_jobs_payment_status (payment_status),
    INDEX idx_jobs_deleted (deleted_at),
    CONSTRAINT fk_jobs_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_jobs_service  FOREIGN KEY (service_id)  REFERENCES services(id)  ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_jobs_user     FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_items (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id            INT UNSIGNED NOT NULL,
    clothing_type_id  INT UNSIGNED NULL,
    color             ENUM('white','coloured') NULL,
    length            ENUM('long','short','big','small','nil') NULL,
    quantity          INT UNSIGNED NOT NULL DEFAULT 1,
    price_per_unit    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_items_job (job_id),
    INDEX idx_job_items_type (clothing_type_id),
    CONSTRAINT fk_job_items_job  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_job_items_type FOREIGN KEY (clothing_type_id) REFERENCES clothing_types(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. Payments (supports part-payments) ----------------------------------------
CREATE TABLE payments (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id       INT UNSIGNED NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    method       ENUM('cash','transfer','pos','other') NOT NULL DEFAULT 'cash',
    reference    VARCHAR(150) NULL,
    paid_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by  INT UNSIGNED NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payments_job (job_id),
    INDEX idx_payments_paid_at (paid_at),
    CONSTRAINT fk_payments_job  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_user FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 8. Expenses -----------------------------------------------------------------
CREATE TABLE expense_categories (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL UNIQUE,
    is_direct_cost   TINYINT(1) NOT NULL DEFAULT 0       -- 1 = cost of service (used for gross profit)
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_date  DATE NOT NULL,
    category_id   INT UNSIGNED NULL,
    description   TEXT NULL,
    amount        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_from     ENUM('cash','bank') NOT NULL DEFAULT 'cash',   -- where the money came out of
    created_by    INT UNSIGNED NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expenses_date (expense_date),
    CONSTRAINT fk_expenses_category FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expenses_user     FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 9. Audit trail --------------------------------------------------------------
CREATE TABLE activity_logs (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NULL,
    action       VARCHAR(100) NOT NULL,
    entity_type  VARCHAR(50) NULL,
    entity_id    INT UNSIGNED NULL,
    details      TEXT NULL,
    ip_address   VARCHAR(45) NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_user (user_id),
    INDEX idx_logs_entity (entity_type, entity_id),
    INDEX idx_logs_created (created_at),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 9a. In-app notifications (e.g. "a job needs pricing") ---------------------
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(50) NOT NULL,
    title       VARCHAR(255) NOT NULL,
    body        TEXT NULL,
    link        VARCHAR(255) NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id, is_read, id),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 9b. Admin reminders --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reminders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(255) NOT NULL,
    notes         TEXT NULL,
    remind_at     DATETIME NOT NULL,
    repeat_rule   ENUM('none','daily','weekly','monthly') NOT NULL DEFAULT 'none',
    is_done       TINYINT(1) NOT NULL DEFAULT 0,
    completed_at  DATETIME NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reminders_due (user_id, is_done, remind_at),
    CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- 10. Computed customer spending (replaces the stored total_amount_spent) -----
CREATE OR REPLACE VIEW customer_spending AS
SELECT c.id AS customer_id,
       COUNT(j.id)                      AS job_count,
       COALESCE(SUM(j.amount_paid), 0)  AS total_paid,
       COALESCE(SUM(j.total), 0)        AS total_billed
FROM customers c
LEFT JOIN jobs j ON j.customer_id = c.id AND j.deleted_at IS NULL AND j.is_draft = 0 AND j.status <> 'cancelled'
GROUP BY c.id;
