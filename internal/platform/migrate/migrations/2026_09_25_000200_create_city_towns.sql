-- Towns (kasaba) that belong to a city. The `cities` table already exists.
CREATE TABLE IF NOT EXISTS city_towns (
    id         bigserial PRIMARY KEY,
    city_id    bigint NOT NULL REFERENCES cities(id) ON DELETE CASCADE,
    name       varchar(255) NOT NULL,
    is_active  boolean NOT NULL DEFAULT true,
    created_at timestamp,
    updated_at timestamp,
    deleted_at timestamp
);

CREATE INDEX IF NOT EXISTS city_towns_city_id_idx ON city_towns (city_id);
