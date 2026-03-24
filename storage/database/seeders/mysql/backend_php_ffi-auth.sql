DELIMITER //

CREATE OR REPLACE PROCEDURE sp_upsert_permission(
    IN p_id INT,
    IN p_name VARCHAR(50),
    IN p_slug VARCHAR(50)
)
BEGIN
    INSERT INTO `permissions` (`id`, `name`, `slug`, `created_at`) 
    VALUES (p_id, p_name, p_slug, NOW())
    ON DUPLICATE KEY UPDATE `name` = p_name, `slug` = p_slug;
END //

CREATE OR REPLACE PROCEDURE sp_upsert_role(
    IN r_id INT,
    IN r_name VARCHAR(50),
    IN r_slug VARCHAR(50),
    IN r_desc TEXT
)
BEGIN
    INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `created_at`) 
    VALUES (r_id, r_name, r_slug, r_desc, NOW())
    ON DUPLICATE KEY UPDATE `name` = r_name, `slug` = r_slug, `description` = r_desc;
END //

CREATE OR REPLACE PROCEDURE sp_assign_permission(
    IN r_id INT,
    IN p_id INT
)
BEGIN
    INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) 
    VALUES (r_id, p_id);
END //

DELIMITER ;