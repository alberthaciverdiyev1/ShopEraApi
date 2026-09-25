-- Chat: conversations, messages, message attachments and auto replies.
CREATE TABLE IF NOT EXISTS conversations (
    id              bigserial PRIMARY KEY,
    user_id         bigint NOT NULL,
    admin_id        bigint NOT NULL,
    last_message_at timestamp,
    created_at      timestamp,
    updated_at      timestamp
);

CREATE TABLE IF NOT EXISTS messages (
    id              bigserial PRIMARY KEY,
    conversation_id bigint NOT NULL,
    sender_type     varchar(16) NOT NULL,
    sender_id       bigint NOT NULL,
    message         text,
    is_read         boolean NOT NULL DEFAULT false,
    created_at      timestamp,
    updated_at      timestamp
);

CREATE INDEX IF NOT EXISTS messages_conversation_id_idx ON messages (conversation_id);
CREATE INDEX IF NOT EXISTS messages_sender_idx ON messages (sender_type, sender_id);

CREATE TABLE IF NOT EXISTS message_attachments (
    id         bigserial PRIMARY KEY,
    message_id bigint NOT NULL,
    path       varchar(255) NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE IF NOT EXISTS auto_replies (
    id         bigserial PRIMARY KEY,
    question   jsonb,
    answer     jsonb,
    embedding  text,
    created_at timestamp,
    updated_at timestamp,
    deleted_at timestamp
);
