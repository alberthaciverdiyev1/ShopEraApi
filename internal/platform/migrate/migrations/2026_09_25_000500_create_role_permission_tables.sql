-- spatie/laravel-permission tables + default roles/permissions (guard: sanctum).

CREATE TABLE IF NOT EXISTS roles (
    id         bigserial PRIMARY KEY,
    name       varchar(255) NOT NULL,
    guard_name varchar(255) NOT NULL,
    created_at timestamp,
    updated_at timestamp,
    UNIQUE (name, guard_name)
);

CREATE TABLE IF NOT EXISTS permissions (
    id         bigserial PRIMARY KEY,
    name       varchar(255) NOT NULL,
    guard_name varchar(255) NOT NULL,
    created_at timestamp,
    updated_at timestamp,
    UNIQUE (name, guard_name)
);

CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id bigint NOT NULL,
    model_type    varchar(255) NOT NULL,
    model_id      bigint NOT NULL,
    PRIMARY KEY (permission_id, model_type, model_id)
);

CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id    bigint NOT NULL,
    model_type varchar(255) NOT NULL,
    model_id   bigint NOT NULL,
    PRIMARY KEY (role_id, model_type, model_id)
);

CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id bigint NOT NULL,
    role_id       bigint NOT NULL,
    PRIMARY KEY (permission_id, role_id)
);

INSERT INTO roles (name, guard_name, created_at, updated_at) VALUES
    ('admin', 'sanctum', NOW(), NOW()),
    ('user', 'sanctum', NOW(), NOW()),
    ('developer', 'sanctum', NOW(), NOW()),
    ('manager', 'sanctum', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
    ('full chat access', 'sanctum', NOW(), NOW()),
    ('has access', 'sanctum', NOW(), NOW()),
    ('add popup', 'sanctum', NOW(), NOW()),
    ('delete popup', 'sanctum', NOW(), NOW()),
    ('active popup', 'sanctum', NOW(), NOW()),
    ('manage-roles', 'sanctum', NOW(), NOW()),
    ('manage-permissions', 'sanctum', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p CROSS JOIN roles r
ON CONFLICT (permission_id, role_id) DO NOTHING;
