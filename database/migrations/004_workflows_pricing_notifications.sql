-- Run once on an existing install (after 003).
USE valour_empire;

-- 1. More job statuses + "waiting for the admin to price it" flag
ALTER TABLE jobs
    MODIFY status ENUM('pending','washing','dry_cleaning','ironing','ready','delivered','cancelled') NOT NULL DEFAULT 'pending',
    ADD COLUMN needs_pricing TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD INDEX idx_jobs_needs_pricing (needs_pricing);

-- 2. Per-service workflow (which statuses a job passes through)
ALTER TABLE services ADD COLUMN workflow VARCHAR(255) NOT NULL DEFAULT 'pending,washing,ready,delivered' AFTER price_per_kg;
UPDATE services SET workflow = 'pending,washing,ironing,ready,delivered'     WHERE name = 'Wash & Iron';
UPDATE services SET workflow = 'pending,ironing,ready,delivered'             WHERE name = 'Iron Only';
UPDATE services SET workflow = 'pending,dry_cleaning,ironing,ready,delivered' WHERE name = 'Dry Cleaning';

-- 3. Price list: price of each clothing type per service
CREATE TABLE IF NOT EXISTS service_prices (
    service_id        INT UNSIGNED NOT NULL,
    clothing_type_id  INT UNSIGNED NOT NULL,
    price             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (service_id, clothing_type_id),
    CONSTRAINT fk_sp_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sp_type    FOREIGN KEY (clothing_type_id) REFERENCES clothing_types(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Start the price list from the existing per-item prices so nothing becomes 0 by surprise
INSERT IGNORE INTO service_prices (service_id, clothing_type_id, price)
SELECT s.id, c.id, c.price_per_item FROM services s CROSS JOIN clothing_types c WHERE s.pricing_model = 'per_item' AND c.price_per_item > 0;

-- 4. Notifications
CREATE TABLE IF NOT EXISTS notifications (
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
