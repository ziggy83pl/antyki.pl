-- Migration v2: Add last_notified_at to chat_room for email notification throttling
-- Compatible with MySQL 5.x and 8.x
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'chat_room'
      AND COLUMN_NAME  = 'last_notified_at'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `chat_room` ADD COLUMN `last_notified_at` datetime NULL DEFAULT NULL COMMENT \'Timestamp of last email notification sent for this room\'',
    'SELECT 1 -- column already exists'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
