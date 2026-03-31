<?php
/**
 * TechTrail Community v2
 * General-purpose helpers
 */
require_once dirname(__DIR__) . '/config/app.php';

/**
 * Safely escape a string for output inside HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Detect whether current request is HTTPS.
 */
function request_is_https(): bool
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
 * Build current app base URL dynamically when possible.
 */
function current_base_url(): string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return APP_URL;
    }

    $scheme = request_is_https() ? 'https' : 'http';

    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    return $scheme . '://' . $host . $dir;
}

/**
 * Build a full app URL from a relative path.
 * Example: url('/login.php')
 */
function url(string $path = ''): string
{
    $base = APP_URL !== '' ? rtrim(APP_URL, '/') : rtrim(current_base_url(), '/');
    $path = '/' . ltrim($path, '/');

    return $base . $path;
}

/**
 * Full URL for a path stored relative to the public root.
 */
function public_asset_url(string $webPath): string
{
    if (preg_match('#^https?://#i', $webPath)) {
        return $webPath;
    }

    return url($webPath);
}

/**
 * Perform an HTTP redirect and terminate execution.
 */
function redirect(string $path, int $code = 302): never
{
    $destination = str_starts_with($path, 'http') ? $path : url($path);
    header('Location: ' . $destination, true, $code);
    exit;
}

/**
 * Return a safe page title string for use in <title>.
 */
function page_title(string $title): string
{
    return e($title) . ' — ' . e(APP_NAME);
}

/**
 * Whether the current request matches a public script name.
 */
function nav_is_active(string $filename): bool
{
    $current = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

    return $current === basename($filename);
}

/**
 * Determine the active nav link class.
 */
function nav_active(string $filename): string
{
    return nav_is_active($filename)
        ? 'text-indigo-400 font-semibold'
        : 'text-gray-300 hover:text-white';
}

/**
 * Sidebar nav item classes.
 */
function nav_sidebar_item(string $filename): string
{
    $base = 'flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 border ';
    if (nav_is_active($filename)) {
        return $base . 'bg-gradient-to-r from-blue-600/25 to-cyan-600/15 text-white border-cyan-500/35 shadow-lg shadow-cyan-900/30';
    }

    return $base . 'border-transparent text-gray-400 hover:bg-white/5 hover:text-white';
}

/**
 * Top header nav link classes.
 */
function nav_header_link(string $filename): string
{
    $base = 'rounded-lg px-3 py-2 text-sm transition-all duration-200 ';
    if (nav_is_active($filename)) {
        return $base . 'bg-white/15 text-white font-semibold ring-1 ring-white/20';
    }

    return $base . 'text-gray-400 hover:text-white hover:bg-white/10';
}

/**
 * Format a date string for display.
 */
function format_date(string $datetime, string $format = 'M j, Y'): string
{
    try {
        $dt = new DateTimeImmutable($datetime);
        return $dt->format($format);
    } catch (Exception) {
        return $datetime;
    }
}