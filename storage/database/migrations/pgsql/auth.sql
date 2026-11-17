CREATE INDEX idx_users_email_status 
ON users (email, status);

CREATE INDEX idx_users_active_email 
ON users (email) 
WHERE status = true;

CREATE UNIQUE INDEX idx_users_ulid_unique 
ON users (ulid) 
WHERE ulid IS NOT NULL;

CREATE TABLE IF NOT EXISTS user_role_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    action VARCHAR(20) NOT NULL,          -- 'ASSIGN' atau 'REVOKE'
    target_user_id BIGINT NOT NULL,
    role_id INT,
    group_id INT,
    permission_ids INT[],                 -- Stores an array of attached permissions
    performed_by VARCHAR(100) NOT NULL,   -- Admin/system that executes
    performed_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

--Constraint to prevent duplication of user and role relationships
ALTER TABLE user_roles ADD CONSTRAINT uk_user_role_group UNIQUE (user_id, role_id, group_id);

-- Constraints for role and permission relationships
ALTER TABLE role_permissions ADD CONSTRAINT uk_role_permission UNIQUE (role_id, permission_id);

CREATE OR REPLACE PROCEDURE sp_assign_user_role_with_audit(
    p_user_id BIGINT,
    p_role_id INT,
    p_group_id INT DEFAULT NULL,
    p_permission_ids INT[] DEFAULT ARRAY[]::INT[],
    p_assigned_by VARCHAR(100) DEFAULT 'system' -- Tracking parameters who grants access
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_perm_id INT;
BEGIN
    -- 1. Validate the ARRAY structure (permission_ids) against the permissions table
    IF p_permission_ids IS NOT NULL AND array_length(p_permission_ids, 1) > 0 THEN
        IF EXISTS (
            SELECT 1 FROM unnest(p_permission_ids) AS input_id
            WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE id = input_id)
        ) THEN
            RAISE EXCEPTION 'Validation Failed: There is a permission_id that is not listed in the permissions table.';
        END IF;
    END IF;

    -- 2. Enter user, role, and group relationships into the user_roles table
    INSERT INTO user_roles (user_id, role_id, group_id)
    VALUES (p_user_id, p_role_id, p_group_id)
    ON CONFLICT (user_id, role_id, group_id) DO NOTHING;

    -- 3. Enter the role relationship and permissions (if any)
    IF p_permission_ids IS NOT NULL AND array_length(p_permission_ids, 1) > 0 THEN
        FOREACH v_perm_id IN ARRAY p_permission_ids
        LOOP
            INSERT INTO role_permissions (role_id, permission_id)
            VALUES (p_role_id, v_perm_id)
            ON CONFLICT (role_id, permission_id) DO NOTHING;
        END LOOP;
    END IF;

    -- 4. Log assignment activities to the audit log table
    INSERT INTO user_role_audit_logs (
        action, 
        target_user_id, 
        role_id, 
        group_id, 
        permission_ids, 
        performed_by
    )
    VALUES (
        'ASSIGN', 
        p_user_id, 
        p_role_id, 
        p_group_id, 
        p_permission_ids, 
        p_assigned_by
    );

    RAISE NOTICE 'Successfully assigned roles and permissions to user ID % by %', p_user_id, p_assigned_by;

EXCEPTION 
    WHEN foreign_key_violation THEN
        RAISE EXCEPTION 'Failed to save relationship: The User ID, Role ID, or Group ID is invalid in the database. (Details: %)', SQLERRM;
END;
$$;


CREATE OR REPLACE PROCEDURE sp_remove_user_role_with_audit(
    p_user_id BIGINT,
    p_role_id INT,               
    p_group_id INT DEFAULT NULL,              -- Opsional: jika NULL, semua grup dicabut
    p_permission_ids INT[] DEFAULT ARRAY[]::INT[], -- Parameter baru: permission yang ingin dicabut dari role
    p_revoked_by VARCHAR(100) DEFAULT 'system'    -- Identitas pelaku pencabutan
)
LANGUAGE plpgsql
AS $$
DECLARE
    r_deleted RECORD;
    v_rows_affected INT := 0;
    v_perm_id INT;
    v_perms_removed INT[] := ARRAY[]::INT[];
BEGIN
    -- 1. Validasi apakah user ada di database
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_user_id) THEN
        RAISE EXCEPTION 'User with ID % not found.', p_user_id;
    END IF;

    -- 2. Jika p_role_id dan p_permission_ids diisi, hapus permission spesifik dari role tersebut
    IF p_role_id IS NOT NULL AND p_permission_ids IS NOT NULL AND array_length(p_permission_ids, 1) > 0 THEN
        FOREACH v_perm_id IN ARRAY p_permission_ids
        LOOP
            DELETE FROM role_permissions 
            WHERE role_id = p_role_id 
              AND permission_id = v_perm_id;
            
            IF FOUND THEN
                v_perms_removed := array_append(v_perms_removed, v_perm_id);
            END IF;
        END LOOP;

        -- Catat ke audit log jika ada permission yang berhasil dicabut
        IF array_length(v_perms_removed, 1) > 0 THEN
            INSERT INTO user_role_audit_logs (
                action, 
                target_user_id, 
                role_id, 
                group_id, 
                permission_ids, 
                performed_by
            )
            VALUES (
                'REVOKE_PERMISSIONS', 
                p_user_id, 
                p_role_id, 
                p_group_id, 
                v_perms_removed, 
                p_revoked_by
            );
        END IF;
    END IF;

    -- 3. Lakukan DELETE pada user_roles untuk mencabut penugasan role/grup
	IF p_role_id IS NOT NULL AND p_group_id IS NOT NULL THEN 
	    FOR r_deleted IN 
	        DELETE FROM user_roles 
	        WHERE user_id = p_user_id 
	          AND (p_role_id IS NULL OR role_id = p_role_id)
	          AND (p_group_id IS NULL OR group_id = p_group_id)
	        RETURNING user_id, role_id, group_id
	    LOOP
	        -- Catat pencabutan role ke audit log terpadu
	        INSERT INTO user_role_audit_logs (
	            action, 
	            target_user_id, 
	            role_id, 
	            group_id, 
	            permission_ids, 
	            performed_by
	        )
	        VALUES (
	            'REVOKE_ROLE', 
	            r_deleted.user_id, 
	            r_deleted.role_id, 
	            r_deleted.group_id, 
	            NULL, 
	            p_revoked_by
	        );
	        
	        v_rows_affected := v_rows_affected + 1;
	    END LOOP;
	END IF;

    RAISE NOTICE 'Successfully processed access revocation for user ID % by % (Role removed: %, Permission revoked: %).', 
        p_user_id, p_revoked_by, v_rows_affected, COALESCE(array_length(v_perms_removed, 1), 0);
END;
$$;

-- Example: Admin 'super_admin_ahmad' gives role ID 2 and permissions to user ID 5
CALL sp_assign_user_role_with_audit(
    p_user_id => 2,
    p_role_id => 2,
    p_group_id => 2,
    p_permission_ids => ARRAY[1, 2, 3, 4, 6],
    p_assigned_by => 'super_admin_ahmad'
);

-- Example: Admin 'super_admin_ahmad' revokes role ID 2 belonging to user ID 5 in group ID 3
CALL sp_remove_user_role_with_audit(
    p_user_id => 2,
    p_role_id => 2,
    p_group_id => null,
    p_permission_ids => ARRAY[2, 3],
    p_revoked_by => 'super_admin_ahmad'
);


CREATE OR REPLACE VIEW public.v_user_role_audit_timeline AS
SELECT 
    al.id AS log_id,
    al.performed_at AS timestamp,
    al.action, -- 'ASSIGN' atau 'REVOKE'
    al.target_user_id,
    u.name AS target_user_name,
    al.role_id,
    r.name AS role_name,
    al.group_id,
    g.name AS group_name,
    al.permission_ids,
    al.performed_by,
    -- Membuat deskripsi kalimat yang mudah dibaca secara dinamis
    CASE 
        WHEN al.action = 'ASSIGN' THEN 
            format('User %s was assigned the role "%s"%s by %s', 
                u.name, 
                COALESCE(r.name, 'Unknown Role'), 
                COALESCE(' (Grup: ' || g.name || ')', ''), 
                al.performed_by
            )
        WHEN al.action = 'REVOKE' THEN 
            format('Role "%s"%s was revoked from user %s by %s', 
                COALESCE(r.name, 'Unknown Role'), 
                COALESCE(' (Grup: ' || g.name || ')', ''), 
                u.name, 
                al.performed_by
            )
        ELSE 
            format('Action %s performed by %s on user %s', al.action, al.performed_by, u.name)
    END AS activity_description
FROM user_role_audit_logs al
JOIN users u ON al.target_user_id = u.id
LEFT JOIN roles r ON al.role_id = r.id
LEFT JOIN groups g ON al.group_id = g.id;


-- Melihat seluruh timeline aktivitas secara urut dari yang paling baru
SELECT timestamp, action, activity_description, performed_by 
FROM v_user_role_audit_timeline 
ORDER BY timestamp DESC;

SELECT timestamp, action, activity_description, performed_by 
FROM v_user_role_audit_timeline 
WHERE target_user_id = 5 
ORDER BY timestamp DESC;

