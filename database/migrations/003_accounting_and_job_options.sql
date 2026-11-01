-- Run once on an existing install (after 002).
USE valour_empire;

-- 1. New invoice numbers: VE-YY-MM-001 (sequence restarts each month). Old invoice numbers are untouched.
CREATE TABLE IF NOT EXISTS invoice_sequences (
    period       CHAR(5) PRIMARY KEY,
    last_number  INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Colour = White / Coloured, Length = Long / Short / Big / Small / Nil
ALTER TABLE job_items MODIFY color VARCHAR(100) NULL, MODIFY length VARCHAR(20) NULL;
UPDATE job_items SET color  = CASE WHEN color IS NULL OR color = '' THEN NULL WHEN LOWER(color) = 'white' THEN 'white' ELSE 'coloured' END;
UPDATE job_items SET length = CASE WHEN LOWER(length) IN ('long','short','big','small','nil') THEN LOWER(length) ELSE 'nil' END WHERE length IS NOT NULL;
ALTER TABLE job_items
    MODIFY color  ENUM('white','coloured') NULL,
    MODIFY length ENUM('long','short','big','small','nil') NULL;

-- 3. Direct costs (cost of service) vs operating expenses, for gross profit
ALTER TABLE expense_categories ADD COLUMN is_direct_cost TINYINT(1) NOT NULL DEFAULT 0;
UPDATE expense_categories SET is_direct_cost = 1 WHERE name IN ('Detergent & supplies','Electricity','Fuel / generator','Water');
