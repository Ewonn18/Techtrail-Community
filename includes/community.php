<?php
/**
 * TechTrail Community v2 — community posts, likes, comments
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/notifications.php';

const COMMUNITY_POST_TITLE_MAX   = 255;
const COMMUNITY_POST_CONTENT_MAX = 20000;
const COMMUNITY_CATEGORY_MAX     = 100;
const COMMUNITY_COMMENT_MAX      = 2000;

/** @return string[] */
function community_allowed_post_types(): array
{
    return ['discussion', 'achievement', 'mentor_tip'];
}

/**
 * Normalise feed filter type from query string.
 */
function community_normalize_filter_type(string $type): string
{
    $type = trim($type);

    if ($type === '' || $type === 'all') {
        return 'all';
    }

    return in_array($type, community_allowed_post_types(), true) ? $type : 'all';
}

/**
 * @param array{title: string, content: string, post_type: string, category: string} $data
 * @return string[]
 */
function community_validate_post(array $data): array
{
    $errors = [];
    $title  = $data['title'];
    $body   = $data['content'];
    $type   = $data['post_type'];
    $cat    = $data['category'];

    if ($title === '' || strlen($title) > COMMUNITY_POST_TITLE_MAX) {
        $errors[] = 'Title is required and must be at most ' . COMMUNITY_POST_TITLE_MAX . ' characters.';
    }

    if ($body === '' || strlen($body) > COMMUNITY_POST_CONTENT_MAX) {
        $errors[] = 'Content is required and must be at most ' . COMMUNITY_POST_CONTENT_MAX . ' characters.';
    }

    if (!in_array($type, community_allowed_post_types(), true)) {
        $errors[] = 'Invalid post type.';
    }

    if (strlen($cat) > COMMUNITY_CATEGORY_MAX) {
        $errors[] = 'Category must be at most ' . COMMUNITY_CATEGORY_MAX . ' characters.';
    }

    return $errors;
}

function community_is_mentor_post_type(string $postType): bool
{
    return $postType === 'mentor_tip';
}

/**
 * @return array<string, mixed>|null
 */
function community_post_owned_by(int $postId, int $userId): ?array
{
    if ($postId < 1) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT * FROM posts WHERE id = :id AND user_id = :uid LIMIT 1'
    );
    $stmt->execute([':id' => $postId, ':uid' => $userId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Community feed with optional search + type filter.
 *
 * @return list<array<string, mixed>>
 */
function community_posts_feed(int $currentUserId, string $search = '', string $postType = 'all'): array
{
    $search   = trim($search);
    $postType = community_normalize_filter_type($postType);

    $sql = '
        SELECT p.*, u.username,
               (SELECT COUNT(*)::int FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
               (SELECT COUNT(*)::int FROM post_comments pc WHERE pc.post_id = p.id) AS comment_count,
               EXISTS(
                   SELECT 1 FROM post_likes pl2
                   WHERE pl2.post_id = p.id AND pl2.user_id = :uid
               ) AS liked_by_me
          FROM posts p
          JOIN users u ON u.id = p.user_id AND u.is_active = TRUE
         WHERE 1 = 1';

    $params = [':uid' => $currentUserId];

    if ($postType !== 'all') {
        $sql .= ' AND p.post_type = :post_type';
        $params[':post_type'] = $postType;
    }

    if ($search !== '') {
        $sql .= ' AND (
            p.title ILIKE :search
            OR p.content ILIKE :search
            OR u.username ILIKE :search
        )';
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

/**
 * @return array{success: bool, errors: string[]}
 */
function community_create_post(int $userId, string $title, string $content, string $postType, string $category): array
{
    $category = trim($category);
    $data = [
        'title'     => trim($title),
        'content'   => trim($content),
        'post_type' => trim($postType),
        'category'  => $category,
    ];

    $errors = community_validate_post($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $isMentor = community_is_mentor_post_type($data['post_type']);

    $stmt = db()->prepare(
        'INSERT INTO posts (user_id, title, content, post_type, is_mentor_post, category)
         VALUES (:uid, :title, :content, :ptype, :mentor, :cat)'
    );

    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
    $stmt->bindValue(':content', $data['content'], PDO::PARAM_STR);
    $stmt->bindValue(':ptype', $data['post_type'], PDO::PARAM_STR);
    $stmt->bindValue(':mentor', $isMentor, PDO::PARAM_BOOL);

    if ($category === '') {
        $stmt->bindValue(':cat', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':cat', $category, PDO::PARAM_STR);
    }

    $stmt->execute();

    return ['success' => true, 'errors' => []];
}

/**
 * @return array{success: bool, errors: string[]}
 */
function community_update_post(int $userId, int $postId, string $title, string $content, string $postType, string $category): array
{
    if (community_post_owned_by($postId, $userId) === null) {
        return ['success' => false, 'errors' => ['Post not found or you cannot edit it.']];
    }

    $category = trim($category);
    $data = [
        'title'     => trim($title),
        'content'   => trim($content),
        'post_type' => trim($postType),
        'category'  => $category,
    ];

    $errors = community_validate_post($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $isMentor = community_is_mentor_post_type($data['post_type']);

    $stmt = db()->prepare(
        'UPDATE posts
            SET title = :title,
                content = :content,
                post_type = :ptype,
                is_mentor_post = :mentor,
                category = :cat
          WHERE id = :id AND user_id = :uid'
    );

    $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
    $stmt->bindValue(':content', $data['content'], PDO::PARAM_STR);
    $stmt->bindValue(':ptype', $data['post_type'], PDO::PARAM_STR);
    $stmt->bindValue(':mentor', $isMentor, PDO::PARAM_BOOL);

    if ($category === '') {
        $stmt->bindValue(':cat', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':cat', $category, PDO::PARAM_STR);
    }

    $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);

    $stmt->execute();

    return ['success' => true, 'errors' => []];
}

/**
 * @return array{success: bool, errors: string[]}
 */
function community_delete_post(int $userId, int $postId): array
{
    if (community_post_owned_by($postId, $userId) === null) {
        return ['success' => false, 'errors' => ['Post not found or you cannot delete it.']];
    }

    $stmt = db()->prepare('DELETE FROM posts WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $postId, ':uid' => $userId]);

    return ['success' => true, 'errors' => []];
}

/**
 * @return array{success: bool, errors: string[], liked: bool}
 */
function community_toggle_like(int $userId, int $postId): array
{
    if ($postId < 1) {
        return ['success' => false, 'errors' => ['Invalid post.'], 'liked' => false];
    }

    $check = db()->prepare('SELECT 1 FROM posts WHERE id = :id LIMIT 1');
    $check->execute([':id' => $postId]);
    if (!$check->fetch()) {
        return ['success' => false, 'errors' => ['Post not found.'], 'liked' => false];
    }

    $stmt = db()->prepare(
        'SELECT 1 FROM post_likes WHERE post_id = :pid AND user_id = :uid'
    );
    $stmt->execute([':pid' => $postId, ':uid' => $userId]);

    if ($stmt->fetch()) {
        $del = db()->prepare('DELETE FROM post_likes WHERE post_id = :pid AND user_id = :uid');
        $del->execute([':pid' => $postId, ':uid' => $userId]);
        return ['success' => true, 'errors' => [], 'liked' => false];
    }

    $ins = db()->prepare(
        'INSERT INTO post_likes (post_id, user_id) VALUES (:pid, :uid)'
    );
    $ins->execute([':pid' => $postId, ':uid' => $userId]);

    $ownerId = notification_post_owner_id($postId);
    if ($ownerId !== null && $ownerId !== $userId) {
        notification_create(
            $ownerId,
            $userId,
            'post_like',
            $postId,
            'liked your post.'
        );
    }

    return ['success' => true, 'errors' => [], 'liked' => true];
}

/**
 * @return array{success: bool, errors: string[]}
 */
function community_add_comment(int $userId, int $postId, string $content): array
{
    $content = trim($content);

    if ($content === '') {
        return ['success' => false, 'errors' => ['Comment cannot be empty.']];
    }

    if (strlen($content) > COMMUNITY_COMMENT_MAX) {
        return ['success' => false, 'errors' => ['Comment must be at most ' . COMMUNITY_COMMENT_MAX . ' characters.']];
    }

    $check = db()->prepare('SELECT 1 FROM posts WHERE id = :id LIMIT 1');
    $check->execute([':id' => $postId]);
    if (!$check->fetch()) {
        return ['success' => false, 'errors' => ['Post not found.']];
    }

    $stmt = db()->prepare(
        'INSERT INTO post_comments (post_id, user_id, content) VALUES (:pid, :uid, :content)'
    );
    $stmt->execute([
        ':pid'     => $postId,
        ':uid'     => $userId,
        ':content' => $content,
    ]);

    $ownerId = notification_post_owner_id($postId);
    if ($ownerId !== null && $ownerId !== $userId) {
        notification_create(
            $ownerId,
            $userId,
            'post_comment',
            $postId,
            'commented on your post.'
        );
    }

    return ['success' => true, 'errors' => []];
}

/**
 * @return list<array<string, mixed>>
 */
function community_comments_for_post(int $postId): array
{
    $stmt = db()->prepare(
        'SELECT c.id, c.content, c.created_at, u.username
           FROM post_comments c
           JOIN users u ON u.id = c.user_id AND u.is_active = TRUE
          WHERE c.post_id = :pid
          ORDER BY c.created_at ASC'
    );
    $stmt->execute([':pid' => $postId]);

    return $stmt->fetchAll() ?: [];
}

/**
 * @param list<int|string> $postIds
 * @return array<int, list<array<string, mixed>>>
 */
function community_comments_map_for_posts(array $postIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $postIds))));
    if ($ids === []) {
        return [];
    }

    $map = [];
    foreach ($ids as $id) {
        $map[$id] = [];
    }

    $placeholders = [];
    $params = [];

    foreach ($ids as $i => $pid) {
        $key = ':p' . $i;
        $placeholders[] = $key;
        $params[$key] = $pid;
    }

    $sql = '
        SELECT c.post_id, c.id, c.content, c.created_at, u.username
          FROM post_comments c
          JOIN users u ON u.id = c.user_id AND u.is_active = TRUE
         WHERE c.post_id IN (' . implode(',', $placeholders) . ')
         ORDER BY c.post_id ASC, c.created_at ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch()) {
        $pid = (int) $row['post_id'];
        if (isset($map[$pid])) {
            $map[$pid][] = $row;
        }
    }

    return $map;
}

/**
 * @return list<array<string, mixed>>
 */
function community_featured_mentor_posts(int $currentUserId, int $limit = 3): array
{
    $limit = max(1, min(10, $limit));

    $sql = '
        SELECT p.*, u.username,
               (SELECT COUNT(*)::int FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
               (SELECT COUNT(*)::int FROM post_comments pc WHERE pc.post_id = p.id) AS comment_count,
               EXISTS(
                   SELECT 1 FROM post_likes pl2
                   WHERE pl2.post_id = p.id AND pl2.user_id = :uid
               ) AS liked_by_me
          FROM posts p
          JOIN users u ON u.id = p.user_id AND u.is_active = TRUE
         WHERE p.post_type = \'mentor_tip\' OR p.is_mentor_post = TRUE
         ORDER BY p.created_at DESC
         LIMIT ' . (int) $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute([':uid' => $currentUserId]);

    return $stmt->fetchAll() ?: [];
}

/**
 * @return array{total_posts: int, likes_received: int, comments_made: int}
 */
function community_user_dashboard_stats(int $userId): array
{
    $defaults = [
        'total_posts'    => 0,
        'likes_received' => 0,
        'comments_made'  => 0,
    ];

    try {
        $q1 = db()->prepare('SELECT COUNT(*)::int AS c FROM posts WHERE user_id = :uid');
        $q1->execute([':uid' => $userId]);
        $defaults['total_posts'] = (int) ($q1->fetch()['c'] ?? 0);

        $q2 = db()->prepare(
            'SELECT COUNT(*)::int AS c FROM post_likes pl
             INNER JOIN posts p ON p.id = pl.post_id
             WHERE p.user_id = :uid'
        );
        $q2->execute([':uid' => $userId]);
        $defaults['likes_received'] = (int) ($q2->fetch()['c'] ?? 0);

        $q3 = db()->prepare('SELECT COUNT(*)::int AS c FROM post_comments WHERE user_id = :uid');
        $q3->execute([':uid' => $userId]);
        $defaults['comments_made'] = (int) ($q3->fetch()['c'] ?? 0);
    } catch (Throwable $e) {
        // keep zeros
    }

    return $defaults;
}

/**
 * @return list<array<string, mixed>>
 */
function community_user_posts_with_likes(int $userId, int $limit = 5): array
{
    $limit = max(1, min(50, $limit));

    try {
        $sql = '
            SELECT p.id, p.title, p.created_at, p.post_type,
                   (SELECT COUNT(*)::int FROM post_likes pl WHERE pl.post_id = p.id) AS like_count
              FROM posts p
             WHERE p.user_id = :uid
             ORDER BY p.created_at DESC
             LIMIT ' . (int) $limit;

        $stmt = db()->prepare($sql);
        $stmt->execute([':uid' => $userId]);

        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}