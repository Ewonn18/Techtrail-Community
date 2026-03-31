<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/functions.php';

session_init();
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

csrf_verify();

$followerId  = (int) $_SESSION['user_id'];
$followingId = (int) ($_POST['following_id'] ?? 0);

if ($followingId < 1 || $followerId === $followingId) {
    flash_set('error', 'Invalid unfollow action.');
    redirect('/index.php');
}

unfollow_user($followerId, $followingId);
flash_set('success', 'You unfollowed this user.');
redirect('/profile.php?id=' . $followingId);