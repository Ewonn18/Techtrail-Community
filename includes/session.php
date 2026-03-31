<?php
/**
 * TechTrail Community v2
 * Session Management
 */
require_once dirname(__DIR__) . '/config/app.php';

/**
 * Detect HTTPS for secure cookies.
 */
function session_request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
        return true;
    }

    if (TRUST_PROXY_HEADERS) {
        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto === 'https') {
            return true;
        }

        $forwardedSsl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
        if ($forwardedSsl === 'on') {
            return true;
        }

        $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
        if ($cfVisitor !== '' && str_contains($cfVisitor, '"scheme":"https"')) {
            return true;
        }
    }

    return false;
}

/**
 * Initialise the session with secure settings.
 */
function session_init(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secureCookie = SESSION_SECURE || session_request_is_https();

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secureCookie,
        'httponly' => SESSION_HTTPONLY,
        'samesite' => SESSION_SAMESITE,
    ]);

    session_start();

    if (!isset($_SESSION['_last_regeneration'])) {
        $_SESSION['_last_regeneration'] = time();
    } elseif (time() - (int) $_SESSION['_last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
}

/**
 * Destroy the session completely.
 */
function session_destroy_clean(): void
{
    session_init();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $params['path'] ?: '/',
                'domain'   => $params['domain'] ?? '',
                'secure'   => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? SESSION_SAMESITE,
            ]
        );
    }

    session_destroy();
}

/**
 * Store a one-time flash message.
 */
function flash_set(string $type, string $message): void
{
    session_init();
    $_SESSION['_flash'][$type] = $message;
}

/**
 * Retrieve and clear all flash messages.
 *
 * @return array<string, string>
 */
function flash_get(): array
{
    session_init();
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/**
 * Check whether any flash messages are pending.
 */
function flash_has(): bool
{
    session_init();
    return !empty($_SESSION['_flash']);
}