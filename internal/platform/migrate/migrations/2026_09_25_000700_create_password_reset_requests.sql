-- Admin-mediated password reset requests. Not wired into routes yet.
CREATE TABLE IF NOT EXISTS password_reset_requests (
    id          bigserial PRIMARY KEY,
    user_id     bigint NOT NULL,
    phone       varchar(32) NOT NULL,
    status      varchar(20) NOT NULL DEFAULT 'pending',
    note        text,
    resolved_by bigint,
    resolved_at timestamp,
    created_at  timestamp,
    updated_at  timestamp
);
CREATE INDEX IF NOT EXISTS password_reset_requests_user_status_idx ON password_reset_requests (user_id, status);
