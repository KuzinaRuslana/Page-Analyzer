CREATE TABLE IF NOT EXISTS urls (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks (
    id BIGSERIAL PRIMARY KEY,
    url_id INTEGER NOT NULL REFERENCES urls(id) ON DELETE CASCADE,
    status_code INTEGER NULL,
    h1 VARCHAR(255) NULL,
    title TEXT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL
);