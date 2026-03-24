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



-- BULK INSERT dengan strore precedure
START TRANSACTION;

    -- Gunakan CALL ke prosedur helper tadi agar kode bersih
    
    -- 1. Permissions
    CALL sp_upsert_permission(1, 'Create Asset', 'asset-create');
    CALL sp_upsert_permission(2, 'View Asset', 'asset-view');
    CALL sp_upsert_permission(3, 'Edit Asset', 'asset-edit');
    CALL sp_upsert_permission(4, 'Delete Asset', 'asset-delete');
    CALL sp_upsert_permission(5, 'Manage Users', 'user-manage');
    CALL sp_upsert_permission(6, 'View Reports', 'report-view');

    -- 2. Roles
    CALL sp_upsert_role(1, 'Super Admin', 'super-admin', 'Full Access');
    CALL sp_upsert_role(2, 'Manager', 'manager', 'Group Management');
    CALL sp_upsert_role(3, 'Staff', 'staff', 'Basic Operations');

    -- 3. Mapping Dinamis
    -- Contoh: Berikan semua permission ke Super Admin (ID 1)
    INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
    SELECT 1, id FROM `permissions`;

    -- Contoh: Manager (ID 2) mendapatkan permission tertentu
    CALL sp_assign_permission(2, 1); -- create
    CALL sp_assign_permission(2, 2); -- view
    CALL sp_assign_permission(2, 3); -- edit
    CALL sp_assign_permission(2, 6); -- report

COMMIT;

