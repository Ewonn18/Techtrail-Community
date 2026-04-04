<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

session_init();

header('Content-Type: application/json; charset=UTF-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.',
    ]);
    exit;
}

$user = current_user();
if ($user === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.',
    ]);
    exit;
}

$userId = (int) $user['id'];
$items = notifications_for_user($userId, 20);

$payload = array_map(
    static fn (array $item): array => notification_to_payload($item),
    $items
);

echo json_encode([
    'success' => true,
    'unread_count' => notification_unread_count($userId),
    'notifications' => $payload,
    'server_time' => gmdate(DATE_ATOM),
], JSON_UNESCAPED_SLASHES);