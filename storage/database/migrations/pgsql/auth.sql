CREATE INDEX idx_users_email_status 
ON users (email, status);

CREATE INDEX idx_users_active_email 
ON users (email) 
WHERE status = true;

CREATE UNIQUE INDEX idx_users_ulid_unique 
ON users (ulid) 
WHERE ulid IS NOT NULL;

