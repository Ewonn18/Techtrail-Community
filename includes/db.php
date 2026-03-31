<?php
/**
 * TechTrail Community v2
 * PDO PostgreSQL Database Connection (Singleton)
 */
require_once dirname(__DIR__) . '/config/app.php';

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Returns the shared PDO instance, creating it on first call.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    /**
     * Create PDO connection.
     */
    private static function connect(): PDO
    {
        if (DB_PASS === '') {
            throw new RuntimeException(
                'Database password is not configured. Set DB_PASS in your .env file.'
            );
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_SSLMODE
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', DB_SCHEMA)) {
                throw new RuntimeException('Invalid database schema name.');
            }

            $pdo->exec('SET search_path TO "' . DB_SCHEMA . '"');

            return $pdo;
        } catch (PDOException $e) {
            $message = $e->getMessage();

            if (APP_DEBUG) {
                if (stripos($message, 'password authentication failed') !== false) {
                    throw new RuntimeException(
                        'Database connection failed: PostgreSQL rejected the username/password. Check DB_USER and DB_PASS in your .env file.',
                        (int) $e->getCode(),
                        $e
                    );
                }

                if (stripos($message, 'could not connect to server') !== false) {
                    throw new RuntimeException(
                        'Database connection failed: PostgreSQL server is not reachable. Check DB_HOST and DB_PORT.',
                        (int) $e->getCode(),
                        $e
                    );
                }

                if (stripos($message, 'could not translate host name') !== false) {
                    throw new RuntimeException(
                        'Database connection failed: DB_HOST is invalid or cannot be resolved.',
                        (int) $e->getCode(),
                        $e
                    );
                }

                throw new RuntimeException(
                    'Database connection failed: ' . $message,
                    (int) $e->getCode(),
                    $e
                );
            }

            throw new RuntimeException('A database error occurred. Please try again later.');
        } catch (Throwable $e) {
            if (APP_DEBUG) {
                throw new RuntimeException(
                    'Database connection failed: ' . $e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }

            throw new RuntimeException('A database error occurred. Please try again later.');
        }
    }

    /**
     * Convenience wrapper — returns the PDO instance directly.
     */
    public static function pdo(): PDO
    {
        return self::getInstance();
    }
}

/**
 * Module-level helper so callers can write db() instead of Database::pdo().
 */
function db(): PDO
{
    return Database::pdo();
}