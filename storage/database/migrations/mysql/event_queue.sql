CREATE TABLE `event_queue` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `event_name` VARCHAR(255) NOT NULL, -- Nama Event (misal: 'user.registered')
    `payload` LONGTEXT NOT NULL,        -- Data Event dalam format JSON
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `attempts` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE `event_queue` 
ADD COLUMN `user_id` BIGINT NULL DEFAULT NULL AFTER `id`,
ADD INDEX (`user_id`); -- Menambahkan index agar pencarian per user cepat

ALTER TABLE `event_queue` ADD INDEX `idx_status_created` (`status`, `created_at`);

ALTER TABLE `event_queue` ADD COLUMN `execution_time` DECIMAL(8,4) DEFAULT 0 AFTER `status`;
