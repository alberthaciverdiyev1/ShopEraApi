-- One-time codes (phone/e-mail). Not wired into routes yet.
CREATE TABLE IF NOT EXISTS otp_emails (
    id            bigserial PRIMARY KEY,
    email         varchar(255) NOT NULL,
    deactive_date timestamp NOT NULL,
    otp_code      smallint NOT NULL,
    created_at    timestamp,
    updated_at    timestamp,
    deleted_at    timestamp
);
CREATE INDEX IF NOT EXISTS otp_emails_email_idx ON otp_emails (email);
