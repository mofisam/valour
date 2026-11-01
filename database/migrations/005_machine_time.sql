-- Run once on an existing install (after 004).
USE valour_empire;

-- Services can be charged by time (customers who use your machines for as long as they want)
ALTER TABLE services
    MODIFY pricing_model ENUM('per_item','per_kg','per_hour') NOT NULL DEFAULT 'per_item',
    ADD COLUMN price_per_hour DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER price_per_kg;

ALTER TABLE jobs
    MODIFY status ENUM('pending','in_use','washing','dry_cleaning','ironing','ready','delivered','cancelled') NOT NULL DEFAULT 'pending',
    ADD COLUMN duration_minutes INT UNSIGNED NULL AFTER price_per_kg,
    ADD COLUMN price_per_hour DECIMAL(12,2) NULL AFTER duration_minutes;

INSERT IGNORE INTO services (name, pricing_model, price_per_hour, workflow)
VALUES ('Machine time (self-service)', 'per_hour', 0, 'pending,in_use,delivered');
