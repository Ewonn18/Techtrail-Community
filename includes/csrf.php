<?php
/**
 * TechTrail Community v2 — CSRF protection
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

/**
 * Get the current CSRF token (creates one in session if missing).
 */
function csrf_token(): string
{
    session_init();
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes((int) (CSRF_TOKEN_LENGTH / 2)));
    }
    return $_SESSION[CSRF_SESSION_KEY];
}

/**
 * Hidden input field for forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token on POST; exit with 403 if invalid.
 */
function csrf_verify(): void
{
    session_init();
    $submitted = $_POST['_csrf_token'] ?? '';
    $stored    = $_SESSION[CSRF_SESSION_KEY] ?? '';
    if ($stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('CSRF validation failed.');
    }
}
