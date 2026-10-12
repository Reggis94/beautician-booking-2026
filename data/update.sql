-- Branch or commit v0 --
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

-- Store appointment datetimes as UTC timestamps without timezone.
-- This alters existing production databases without requiring table recreation.
DO $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = current_schema()
      AND table_name = 'appointment'
      AND column_name = 'start_dt'
      AND data_type = 'timestamp with time zone'
  ) THEN
    ALTER TABLE appointment
      ALTER COLUMN start_dt TYPE TIMESTAMP(0) WITHOUT TIME ZONE
      USING start_dt AT TIME ZONE 'UTC';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = current_schema()
      AND table_name = 'appointment'
      AND column_name = 'end_dt'
      AND data_type = 'timestamp with time zone'
  ) THEN
    ALTER TABLE appointment
      ALTER COLUMN end_dt TYPE TIMESTAMP(0) WITHOUT TIME ZONE
      USING end_dt AT TIME ZONE 'UTC';
  END IF;
END $$;

-- ft-TRACKING-37-cookie-marketing-demo --
CREATE TABLE IF NOT EXISTS tracking_page_visit (
  id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  visitor_id      VARCHAR(20) NOT NULL,
  ip              VARCHAR(45) NOT NULL,
  current_url     TEXT NOT NULL,
  referer         TEXT,
  user_agent      TEXT,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- AUTH-55-passwordless-email-authentication --
-- Tokens and session identifiers are stored as SHA-256 hashes, never in clear.
CREATE TABLE IF NOT EXISTS pro_login_token (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id       BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  token_hash   CHAR(64) NOT NULL,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  consumed_at  TIMESTAMPTZ,
  -- Set when the login email could not be sent: the token is unusable but the attempt still counts in the limits.
  delivery_failed_at  TIMESTAMPTZ,
  CONSTRAINT uq_pro_login_token_hash UNIQUE (token_hash)
);

-- Login link cooldown and rate limit count a beautician's recent tokens.
CREATE INDEX IF NOT EXISTS idx_pro_login_token_pro_created ON pro_login_token (pro_id, created_at);

CREATE TABLE IF NOT EXISTS pro_session (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  pro_id        BIGINT NOT NULL REFERENCES pro(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  session_hash  CHAR(64) NOT NULL,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  revoked_at    TIMESTAMPTZ,
  CONSTRAINT uq_pro_session_hash UNIQUE (session_hash)
);
