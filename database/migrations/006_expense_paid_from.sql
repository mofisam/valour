-- Run once on an existing install (after 005).
USE valour_empire;

-- Where each expense was paid from. Existing expenses are marked 'cash'; review them if some came from the bank.
ALTER TABLE expenses
    ADD COLUMN paid_from ENUM('cash','bank') NOT NULL DEFAULT 'cash' AFTER amount;
