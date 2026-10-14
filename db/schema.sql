CREATE TABLE IF NOT EXISTS request_events (
  id BIGSERIAL PRIMARY KEY,
  request_id UUID NOT NULL,
  occurred_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  ip INET NOT NULL,
  method VARCHAR(10) NOT NULL,
  path TEXT NOT NULL,
  user_agent TEXT NOT NULL DEFAULT '',
  session_hash CHAR(64),
  account_hint VARCHAR(255),
  status_code SMALLINT NOT NULL,
  latency_ms INTEGER NOT NULL DEFAULT 0,
  failed_auth BOOLEAN NOT NULL DEFAULT FALSE,
  csrf_valid BOOLEAN,
  token_reused BOOLEAN NOT NULL DEFAULT FALSE,
  honeypot_hit BOOLEAN NOT NULL DEFAULT FALSE,
  features JSONB NOT NULL DEFAULT '{}'::jsonb
);

CREATE INDEX IF NOT EXISTS idx_request_events_occurred_at ON request_events (occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_request_events_ip_time ON request_events (ip, occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_request_events_request_id ON request_events (request_id);

CREATE TABLE IF NOT EXISTS risk_decisions (
  id BIGSERIAL PRIMARY KEY,
  request_event_id BIGINT NOT NULL REFERENCES request_events(id) ON DELETE CASCADE,
  score SMALLINT NOT NULL CHECK (score BETWEEN 0 AND 200),
  level VARCHAR(16) NOT NULL CHECK (level IN ('normal','guarded','suspicious','critical')),
  labyrinth VARCHAR(32) NOT NULL CHECK (labyrinth IN ('none','cancello','arianna','specchio','minotauro','quarantena')),
  reasons JSONB NOT NULL DEFAULT '[]'::jsonb,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_risk_decisions_created_at ON risk_decisions (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_risk_decisions_labyrinth ON risk_decisions (labyrinth);

CREATE TABLE IF NOT EXISTS labyrinth_sessions (
  id BIGSERIAL PRIMARY KEY,
  session_hash CHAR(64) NOT NULL,
  labyrinth VARCHAR(32) NOT NULL,
  state VARCHAR(24) NOT NULL DEFAULT 'active',
  step INTEGER NOT NULL DEFAULT 0,
  started_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  last_seen_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);

CREATE INDEX IF NOT EXISTS idx_labyrinth_sessions_session ON labyrinth_sessions (session_hash, last_seen_at DESC);

CREATE TABLE IF NOT EXISTS decoy_events (
  id BIGSERIAL PRIMARY KEY,
  request_event_id BIGINT REFERENCES request_events(id) ON DELETE SET NULL,
  session_hash CHAR(64),
  decoy_path TEXT NOT NULL,
  action VARCHAR(32) NOT NULL,
  payload JSONB NOT NULL DEFAULT '{}'::jsonb,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_decoy_events_created_at ON decoy_events (created_at DESC);
