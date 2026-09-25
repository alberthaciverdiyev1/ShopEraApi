-- Revocable JWT refresh tokens (SHA-256 hashed).
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id         bigserial PRIMARY KEY,
    user_id    bigint NOT NULL,
    token      varchar(64) NOT NULL,
    expires_at timestamp NOT NULL,
    revoked_at timestamp,
    created_at timestamp,
    updated_at timestamp
);
CREATE UNIQUE INDEX IF NOT EXISTS refresh_tokens_token_idx ON refresh_tokens (token);
CREATE INDEX IF NOT EXISTS refresh_tokens_user_id_idx ON refresh_tokens (user_id);
