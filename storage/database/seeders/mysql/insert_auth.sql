
-- 1. Data Roles & Permissions
-- Insert Permissions
INSERT INTO `permissions` (`name`, `slug`, `created_at`) VALUES
('Create Asset', 'asset-create', NOW()),
('View Asset', 'asset-view', NOW()),
('Edit Asset', 'asset-edit', NOW()),
('Delete Asset', 'asset-delete', NOW()),
('Manage Users', 'user-manage', NOW()),
('View Reports', 'report-view', NOW());

-- Insert Roles
INSERT INTO `roles` (`name`, `slug`, `description`, `created_at`) VALUES
('Super Admin', 'super-admin', 'Akses penuh ke seluruh sistem', NOW()),
('Manager', 'manager', 'Mengelola aset dan melihat laporan di grup tertentu', NOW()),
('Staff', 'staff', 'Hanya bisa melihat dan membuat aset', NOW());

-- Mapping Role ke Permissions
-- Super Admin (Semua Izin: ID 1-6)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 1, id FROM `permissions`;

-- Manager (View, Create, Edit, Report: ID 1, 2, 3, 6)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES 
(2, 1), (2, 2), (2, 3), (2, 6);

-- Staff (View, Create: ID 1, 2)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES 
(3, 1), (3, 2);


-- 2. Data Groups (Teams)
INSERT INTO `groups` (`name`, `code`, `owner_id`, `created_at`) VALUES
('Gudang Jakarta', 'WH-JKT', 1, NOW()),
('Cabang Bandung', 'BR-BND', 2, NOW());


-- 3. Data Users
INSERT INTO `users` (`name`, `email`, `password`, `status`, `created_at`) VALUES
('Lutvi Admin', 'admin@lutvip19.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW()),
('Budi Manager', 'budi@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW()),
('Siti Staff', 'siti@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW());


-- 4. Mapping User ke Roles & Groups
INSERT INTO `user_roles` (`user_id`, `role_id`, `group_id`) VALUES
-- Lutvi adalah Super Admin secara Global (group_id NULL)
(1, 1, NULL), 

-- Budi adalah Manager hanya di Gudang Jakarta (ID 1)
(2, 2, 1), 

-- Siti adalah Staff di Cabang Bandung (ID 2)
(3, 3, 2);
