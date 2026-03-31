<?php
require_once __DIR__ . '/db.php';

/**
 * Create a notification for a user.
 */
function notification_create(
    int $userId,
    ?int $actorId,
    string $type,
    ?int $referenceId,
    string $message
): bool {
    if ($userId < 1 || $message === '') {
        return false;
    }

    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, actor_id, type, reference_id, message)
         VALUES (:user_id, :actor_id, :type, :reference_id, :message)'
    );

    return $stmt->execute([
        ':user_id'      => $userId,
        ':actor_id'     => $actorId,
        ':type'         => $type,
        ':reference_id' => $referenceId,
        ':message'      => $message,
    ]);
}

/**
 * Count unread notifications for a user.
 */
function notification_unread_count(int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)::int
           FROM notifications
          WHERE user_id = :user_id
            AND is_read = FALSE'
    );
    $stmt->execute([':user_id' => $userId]);

    return (int) $stmt->fetchColumn();
}

/**
 * Get latest notifications for a user.
 *
 * @return list<array<string, mixed>>
 */
function notifications_for_user(int $userId, int $limit = 20): array
{
    if ($userId < 1) {
        return [];
    }

    $limit = max(1, min(100, $limit));

    $sql = '
        SELECT n.*, u.username AS actor_username
          FROM notifications n
          LEFT JOIN users u ON u.id = n.actor_id
         WHERE n.user_id = :user_id
         ORDER BY n.created_at DESC
         LIMIT ' . (int) $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetchAll() ?: [];
}

/**
 * Mark all notifications as read for a user.
 */
function notifications_mark_all_read(int $userId): bool
{
    if ($userId < 1) {
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE notifications
            SET is_read = TRUE
          WHERE user_id = :user_id
            AND is_read = FALSE'
    );

    return $stmt->execute([':user_id' => $userId]);
}

/**
 * Fetch owner of a post.
 */
function notification_post_owner_id(int $postId): ?int
{
    if ($postId < 1) {
        return null;
    }

    $stmt = db()->prepare('SELECT user_id FROM posts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $postId]);
    $ownerId = $stmt->fetchColumn();

    return $ownerId !== false ? (int) $ownerId : null;
}