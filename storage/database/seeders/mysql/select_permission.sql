SELECT DISTINCT 
    p.slug AS permission_slug,
    r.slug AS role_slug,
    g.code AS group_code
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
LEFT JOIN groups g ON ur.group_id = g.id
WHERE u.id = :user_id 
  AND (ur.group_id = :current_group_id OR ur.group_id IS NULL)
  AND u.status = 1;


CREATE OR REPLACE VIEW v_user_permissions AS
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    p.id AS permission_id,
    p.slug AS permission_slug,
    p.name AS permission_name,
    r.slug AS role_slug,
    ur.group_id,
    g.name AS group_name
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
LEFT JOIN groups g ON ur.group_id = g.id
WHERE u.status = 1; -- Hanya user aktif


-- Cara Cek Data (Menggunakan View)
-- Untuk mengetes apakah data sudah benar, jalankan query ini untuk melihat apa yang bisa dilakukan Dodi di dua tempat berbeda:

-- cek super admin
SELECT permission_slug FROM v_user_permissions 
WHERE user_id = 1

-- Cek akses Dodi di Jakarta (Harusnya hanya view)
SELECT permission_slug FROM v_user_permissions 
WHERE user_id = 4 AND (group_id = 2 OR group_id IS NULL);

-- Cek akses Dodi di Surabaya (Harusnya bisa view & create)
SELECT permission_slug FROM v_user_permissions 
WHERE user_id = 4 AND (group_id = 3 OR group_id IS NULL);

SELECT permission_slug FROM v_user_permissions 
WHERE user_id = 3 AND group_id = 3