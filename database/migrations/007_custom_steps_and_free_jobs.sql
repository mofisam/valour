-- Run once on an existing install (after 006).
USE valour_empire;

-- 1. Process steps become editable data instead of a fixed list
CREATE TABLE IF NOT EXISTS process_steps (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    step_key    VARCHAR(40) NOT NULL UNIQUE,            -- stored on jobs.status, never changes
    label       VARCHAR(60) NOT NULL,                   -- what people see; the admin can rename it
    color       VARCHAR(20) NOT NULL DEFAULT 'sky',
    sort_order  INT NOT NULL DEFAULT 50,
    is_locked   TINYINT(1) NOT NULL DEFAULT 0,          -- Pending and Delivered are part of every service
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO process_steps (step_key, label, color, sort_order, is_locked) VALUES
 ('pending',      'Pending',      'amber',   10, 1),
 ('in_use',       'In use',       'fuchsia', 20, 0),
 ('washing',      'Washing',      'sky',     30, 0),
 ('dry_cleaning', 'Dry cleaning', 'indigo',  40, 0),
 ('ironing',      'Ironing',      'violet',  50, 0),
 ('ready',        'Ready',        'teal',    60, 0),
 ('delivered',    'Delivered',    'emerald', 90, 1);

-- jobs.status was an ENUM; it now holds any step key (existing values are kept)
ALTER TABLE jobs MODIFY status VARCHAR(40) NOT NULL DEFAULT 'pending';

-- 2. Jobs that were fully discounted (total = 0) count as paid
UPDATE jobs SET payment_status = 'paid'
WHERE is_draft = 0 AND needs_pricing = 0 AND subtotal > 0 AND total <= 0.004 AND status <> 'cancelled';
