-- Branch or commit ft-PROFILEPRO-23-upsert-timezone-business-location --
-- Login --
CREATE TABLE IF NOT EXISTS pro (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  email         VARCHAR(255) NOT NULL,
  link_slug     VARCHAR(50)  NOT NULL,
  display_name  VARCHAR(120),
  firstname     VARCHAR(50),
  lastname      VARCHAR(50),
  roles         TEXT       NOT NULL DEFAULT '[]',
  notes         TEXT,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at    TIMESTAMPTZ
);

ALTER TABLE pro
  ADD CONSTRAINT uq_pro_email UNIQUE (email);

ALTER TABLE pro
  ADD CONSTRAINT uq_pro_link_slug UNIQUE (link_slug);


-- Create service --
CREATE TABLE IF NOT EXISTS service (
  id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id         BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  name           VARCHAR(50) NOT NULL,
  duration_min   INT,
  created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at     TIMESTAMPTZ
);

-- Create appointment --
CREATE TABLE IF NOT EXISTS appointment (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id       BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  service_id   BIGINT REFERENCES service(id) ON UPDATE CASCADE ON DELETE SET NULL,
  start_dt     TIMESTAMPTZ NOT NULL,
  end_dt       TIMESTAMPTZ,
  last_name    VARCHAR(50),
  first_name   VARCHAR(50),
  email        VARCHAR(255),
  phone        VARCHAR(40),
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at   TIMESTAMPTZ
);

-- Create client lead (minimal info for callback)
CREATE TABLE IF NOT EXISTS lead (
  id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id     BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  firstname  VARCHAR(50),
  lastname   VARCHAR(50),
  phone      VARCHAR(40) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ft-AVAIL-15-create-avail --
CREATE TABLE IF NOT EXISTS availability (
  id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id          BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  week_start_date DATE NOT NULL,
  week_end_date   DATE NOT NULL,
  day_of_week     SMALLINT NOT NULL,
  start_time      TIME(0) WITHOUT TIME ZONE NOT NULL,
  end_time        TIME(0) WITHOUT TIME ZONE NOT NULL,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ft-SERVICE-5-create-category --
CREATE TABLE IF NOT EXISTS service_category (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id      BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  name        VARCHAR(150) NOT NULL,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at  TIMESTAMPTZ
);

-- ft-SERVICE-7-create-service --
ALTER TABLE service
  ADD COLUMN IF NOT EXISTS category_id BIGINT;

ALTER TABLE service
  ADD COLUMN IF NOT EXISTS description TEXT;

ALTER TABLE service
  ADD COLUMN IF NOT EXISTS price_cents INT;

ALTER TABLE service
  ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT FALSE;

-- ft-PROFILEPRO-23-upsert-timezone-business-location --
ALTER TABLE pro
  ADD COLUMN IF NOT EXISTS location_full_text VARCHAR(255);

ALTER TABLE pro
  ADD COLUMN IF NOT EXISTS latitude DOUBLE PRECISION;

ALTER TABLE pro
  ADD COLUMN IF NOT EXISTS longitude DOUBLE PRECISION;

ALTER TABLE pro
  ADD COLUMN IF NOT EXISTS timezone_iana VARCHAR(64);

