<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

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

/**
 * Badge label by notification type.
 */
function notification_badge_label(string $type): string
{
    return match($type) {
        'post_like'    => 'Like',
        'post_comment' => 'Comment',
        'new_follower' => 'Follow',
        default        => 'Update',
    };
}

/**
 * Badge class by notification type.
 */
function notification_badge_class(string $type): string
{
    return match($type) {
        'post_like'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'post_comment' => 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300',
        'new_follower' => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300',
        default        => 'border-white/10 bg-white/5 text-gray-300',
    };
}

/**
 * Resolve target URL for a notification.
 */
function notification_target_url(array $notification): string
{
    $type = (string) ($notification['type'] ?? '');
    $referenceId = (int) ($notification['reference_id'] ?? 0);

    if ($type === 'post_like' || $type === 'post_comment') {
        return url('/community.php#post-' . $referenceId);
    }

    if ($type === 'new_follower' && $referenceId > 0) {
        return url('/profile.php?id=' . $referenceId);
    }

    return url('/notifications.php');
}

/**
 * Convert DB notification row to frontend-ready payload.
 *
 * @param array<string, mixed> $notification
 * @return array<string, mixed>
 */
function notification_to_payload(array $notification): array
{
    $type = (string) ($notification['type'] ?? '');
    $actor = trim((string) ($notification['actor_username'] ?? 'Someone'));
    $message = trim((string) ($notification['message'] ?? 'sent an update.'));
    $createdAt = (string) ($notification['created_at'] ?? '');

    return [
        'id' => (int) ($notification['id'] ?? 0),
        'type' => $type,
        'badge_label' => notification_badge_label($type),
        'badge_class' => notification_badge_class($type),
        'actor' => $actor,
        'message' => $message,
        'reference_id' => (int) ($notification['reference_id'] ?? 0),
        'is_read' => !empty($notification['is_read']),
        'created_at' => $createdAt,
        'created_at_human' => $createdAt !== '' ? format_date($createdAt, 'M j, Y · g:i A') : '',
        'target_url' => notification_target_url($notification),
    ];
}