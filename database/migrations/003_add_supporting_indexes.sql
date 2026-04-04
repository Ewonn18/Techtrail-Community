CREATE INDEX IF NOT EXISTS idx_users_username_search
ON users (username);

CREATE INDEX IF NOT EXISTS idx_posts_title
ON posts (title);

CREATE INDEX IF NOT EXISTS idx_posts_post_type
ON posts (post_type);

CREATE INDEX IF NOT EXISTS idx_user_follows_following_id
ON user_follows (following_id);

CREATE INDEX IF NOT EXISTS idx_user_follows_follower_id
ON user_follows (follower_id);

CREATE INDEX IF NOT EXISTS idx_post_likes_user_id
ON post_likes (user_id);

CREATE INDEX IF NOT EXISTS idx_post_comments_post_id
ON post_comments (post_id);