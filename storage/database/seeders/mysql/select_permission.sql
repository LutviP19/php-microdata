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