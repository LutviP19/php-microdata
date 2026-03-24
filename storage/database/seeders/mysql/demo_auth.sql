-- Hapus data lama jika ada (Urutan penting karena Foreign Key)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE role_permissions;
TRUNCATE TABLE user_roles;
TRUNCATE TABLE permissions;
TRUNCATE TABLE roles;
TRUNCATE TABLE groups;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Data Permissions
INSERT INTO `permissions` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'Create Asset', 'asset-create', NOW()),
(2, 'View Asset', 'asset-view', NOW()),
(3, 'Edit Asset', 'asset-edit', NOW()),
(4, 'Delete Asset', 'asset-delete', NOW()),
(5, 'Manage Users', 'user-manage', NOW()),
(6, 'View Reports', 'report-view', NOW());

-- Data Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Super Admin', 'super-admin', 'Akses penuh seluruh sistem & grup', NOW()),
(2, 'Manager', 'manager', 'Kelola aset & laporan dalam grup', NOW()),
(3, 'Staff', 'staff', 'Hanya operasi dasar aset', NOW()),
(4, 'Guest', 'guest', 'Hanya lihat data tanpa ubah', NOW());

-- Mapping Role ke Permissions
-- Super Admin (Semua Izin)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM permissions;

-- Manager (View, Create, Edit, Report)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,1), (2,2), (2,3), (2,6);

-- Staff (View, Create)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,1), (3,2);

-- Guest (View Only)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,2);


-- Data Groups (Tenant)
-- Kita buat dua lokasi atau departemen yang berbeda.
INSERT INTO `groups` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'Super Admin Pusat', 'SA-HQ-JKT', NOW()),
(2, 'Kantor Pusat Jakarta', 'HQ-JKT', NOW()),
(3, 'Gudang Surabaya', 'WH-SUB', NOW());


-- Data Users
-- Password semua user adalah password (terenkripsi bcrypt).
INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`, `created_at`) VALUES
(1, 'Ahmad Admin', 'admin@demo.local', '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', 1, NOW()),
(2, 'Budi Manager', 'budi@demo.local', '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', 1, NOW()),
(3, 'Siti Staff', 'siti@demo.local', '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', 1, NOW()),
(4, 'Dodi Freelance', 'dodi@demo.local', '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', 1, NOW());


-- Mapping Relasi (The Magic Part)
-- Di sini kita mendemokan bagaimana satu user bisa punya akses berbeda.

INSERT INTO `user_roles` (`user_id`, `role_id`, `group_id`) VALUES
-- Ahmad adalah Super Admin Global (Tanpa terikat grup spesifik)
(1, 1, 1), 

-- Budi adalah Manager di Kantor Pusat Jakarta
(2, 2, 2), 

-- Siti adalah Staff di Gudang Surabaya
(3, 3, 3), 

-- Dodi adalah Guest di Jakarta, TAPI Staff di Surabaya
-- (Skenario User dengan akses berbeda tiap cabang)
(4, 4, 2), 
(4, 3, 3);


