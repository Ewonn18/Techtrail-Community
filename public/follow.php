<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

session_init();
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

csrf_verify();

$followerId  = (int) $_SESSION['user_id'];
$followingId = (int) ($_POST['following_id'] ?? 0);

if ($followingId < 1 || $followerId === $followingId) {
    flash_set('error', 'Invalid follow action.');
    redirect('/index.php');
}

follow_user($followerId, $followingId);

notification_create(
    $followingId,
    $followerId,
    'new_follower',
    $followerId,
    'started following you.'
);

flash_set('success', 'You are now following this user.');
redirect('/profile.php?id=' . $followingId);