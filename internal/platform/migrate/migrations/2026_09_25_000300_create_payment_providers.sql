-- Payment providers selectable/configured from the admin panel.
CREATE TABLE IF NOT EXISTS payment_providers (
    id          bigserial PRIMARY KEY,
    key         varchar(64) NOT NULL UNIQUE,
    name        varchar(255) NOT NULL,
    is_active   boolean NOT NULL DEFAULT true,
    sort_order  integer NOT NULL DEFAULT 0,
    config      jsonb,
    created_at  timestamp,
    updated_at  timestamp,
    deleted_at  timestamp
);

-- Seed the Epoint provider (credentials filled from the admin panel).
INSERT INTO payment_providers (key, name, is_active, sort_order, config, created_at, updated_at)
SELECT 'epoint', 'Epoint', true, 1, '{"public_key":"","private_key":""}'::jsonb, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM payment_providers WHERE key = 'epoint');
