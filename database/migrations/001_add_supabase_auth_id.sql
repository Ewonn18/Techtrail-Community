ALTER TABLE users
ADD COLUMN IF NOT EXISTS supabase_auth_id UUID UNIQUE;

CREATE INDEX IF NOT EXISTS idx_users_supabase_auth_id
ON users (supabase_auth_id);