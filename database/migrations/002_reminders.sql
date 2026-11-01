-- Run this if you already imported the first schema.
USE valour_empire;

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

INSERT IGNORE INTO settings (`key`, `value`) VALUES ('business_tagline', '');
