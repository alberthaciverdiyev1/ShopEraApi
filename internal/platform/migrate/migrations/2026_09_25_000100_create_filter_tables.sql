-- Dynamic category filters (filters + category_filters + product_filters),
-- mirroring the TicarXCaspian approach.
CREATE TABLE IF NOT EXISTS filters (
    id         bigserial PRIMARY KEY,
    title      jsonb,
    type       varchar(16) NOT NULL DEFAULT 'select', -- input | boolean | select
    options    jsonb,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE IF NOT EXISTS category_filters (
    id          bigserial PRIMARY KEY,
    filter_id   bigint NOT NULL REFERENCES filters(id) ON DELETE CASCADE,
    category_id bigint NOT NULL,
    created_at  timestamp,
    updated_at  timestamp
);

CREATE TABLE IF NOT EXISTS product_filters (
    id         bigserial PRIMARY KEY,
    product_id bigint NOT NULL,
    filter_id  bigint NOT NULL REFERENCES filters(id) ON DELETE CASCADE,
    value      varchar(255),
    created_at timestamp,
    updated_at timestamp
);

CREATE INDEX IF NOT EXISTS category_filters_category_id_idx ON category_filters (category_id);
CREATE INDEX IF NOT EXISTS product_filters_product_id_idx ON product_filters (product_id);
CREATE INDEX IF NOT EXISTS product_filters_filter_id_idx ON product_filters (filter_id);
