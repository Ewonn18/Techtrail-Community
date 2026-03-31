<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('warning', 'Please use the logout button to sign out safely.');
    redirect('/dashboard.php');
}

csrf_verify();
session_destroy_clean();

session_init();
flash_set('success', 'You have been logged out successfully.');
redirect('/login.php');