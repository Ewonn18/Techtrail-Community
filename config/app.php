<?php
/**
 * TechTrail Community v2
 * Application Configuration
 */

/**
 * Load a simple .env file from project root.
 * Supports lines like KEY=value
 * Ignores empty lines and comments starting with #
 */
function load_env_file(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

load_env_file(dirname(__DIR__) . '/.env');

/**
 * Read environment variable with fallback.
 */
function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

/**
 * Parse environment variable as boolean.
 */
function env_bool(string $key, bool $default = false): bool
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    $normalized = strtolower(trim((string) $value));

    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

define('APP_NAME', 'TechTrail Community');
define('APP_VERSION', '2.0.0');
define('APP_ENV', env_value('APP_ENV', 'development'));
define('APP_URL', rtrim((string) env_value('APP_URL', 'http://localhost/TechTrail-Community-v2/public'), '/'));
define('APP_DEBUG', env_bool('APP_DEBUG', APP_ENV === 'development'));

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST', (string) env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (string) env_value('DB_PORT', '5432'));
define('DB_NAME', (string) env_value('DB_NAME', 'techtrail_v2'));
define('DB_USER', (string) env_value('DB_USER', 'postgres'));
define('DB_PASS', (string) env_value('DB_PASS', ''));
define('DB_SCHEMA', (string) env_value('DB_SCHEMA', 'public'));
define('DB_SSLMODE', (string) env_value('DB_SSLMODE', APP_ENV === 'production' ? 'require' : 'prefer'));

// ─── Supabase ────────────────────────────────────────────────────────────────
define('SUPABASE_URL', rtrim((string) env_value('SUPABASE_URL', ''), '/'));
define('SUPABASE_ANON_KEY', (string) env_value('SUPABASE_ANON_KEY', ''));

// ─── Cloudinary ──────────────────────────────────────────────────────────────
define('CLOUDINARY_CLOUD_NAME', (string) env_value('CLOUDINARY_CLOUD_NAME', ''));
define('CLOUDINARY_API_KEY', (string) env_value('CLOUDINARY_API_KEY', ''));
define('CLOUDINARY_API_SECRET', (string) env_value('CLOUDINARY_API_SECRET', ''));

// ─── Proxy / HTTPS ───────────────────────────────────────────────────────────
define('TRUST_PROXY_HEADERS', env_bool('TRUST_PROXY_HEADERS', true));

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME', 'techtrail_session');
define('SESSION_LIFETIME', 60 * 60 * 2);
define('SESSION_SECURE', env_bool('SESSION_SECURE', APP_ENV === 'production'));
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', (string) env_value('SESSION_SAMESITE', 'Lax'));

// ─── Security ────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 64);
define('CSRF_SESSION_KEY', '_csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);

define(
    'AUTH_TIMING_DUMMY_HASH',
    '$2y$12$1q0UlOZSAtK1PZXbTuqV8.SLbPmLdRaKLFR/0PcZ89AwN7x7cJ1m2'
);

// ─── Paths ───────────────────────────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('PARTIALS_PATH', BASE_PATH . '/partials');
define('PUBLIC_PATH', BASE_PATH . '/public');

// ─── Upload / Profile Limits ────────────────────────────────────────────────
define('AVATAR_MAX_BYTES', 2 * 1024 * 1024);
define('PROFILE_BIO_MAX', 2000);
define('PROFILE_ACHIEVEMENTS_MAX', 8000);