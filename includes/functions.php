<?php
require_once __DIR__ . '/db.php';

/**
 * Follow another user.
 */
function follow_user(int $followerId, int $followingId): bool
{
    if ($followerId < 1 || $followingId < 1 || $followerId === $followingId) {
        return false;
    }

    $stmt = db()->prepare(
        'INSERT INTO user_follows (follower_id, following_id)
         VALUES (:follower_id, :following_id)
         ON CONFLICT (follower_id, following_id) DO NOTHING'
    );

    return $stmt->execute([
        ':follower_id'  => $followerId,
        ':following_id' => $followingId,
    ]);
}

/**
 * Unfollow a user.
 */
function unfollow_user(int $followerId, int $followingId): bool
{
    if ($followerId < 1 || $followingId < 1 || $followerId === $followingId) {
        return false;
    }

    $stmt = db()->prepare(
        'DELETE FROM user_follows
         WHERE follower_id = :follower_id
           AND following_id = :following_id'
    );

    return $stmt->execute([
        ':follower_id'  => $followerId,
        ':following_id' => $followingId,
    ]);
}

/**
 * Check if viewer is following profile user.
 */
function is_following(int $viewerId, int $profileUserId): bool
{
    if ($viewerId < 1 || $profileUserId < 1 || $viewerId === $profileUserId) {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT 1
           FROM user_follows
          WHERE follower_id = :viewer_id
            AND following_id = :profile_user_id
          LIMIT 1'
    );

    $stmt->execute([
        ':viewer_id'       => $viewerId,
        ':profile_user_id' => $profileUserId,
    ]);

    return (bool) $stmt->fetchColumn();
}

/**
 * Count followers of a user.
 */
function follower_count(int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)::int
           FROM user_follows
          WHERE following_id = :user_id'
    );
    $stmt->execute([':user_id' => $userId]);

    return (int) $stmt->fetchColumn();
}

/**
 * Count users followed by this user.
 */
function following_count(int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)::int
           FROM user_follows
          WHERE follower_id = :user_id'
    );
    $stmt->execute([':user_id' => $userId]);

    return (int) $stmt->fetchColumn();
}