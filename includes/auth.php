<?php
/**
 * TechTrail Community v2
 * Authentication
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/supabase.php';

/**
 * Returns true when a user is logged in.
 */
function is_logged_in(): bool
{
    session_init();
    return !empty($_SESSION['user_id']);
}

/**
 * Returns the currently authenticated user's data, or null.
 *
 * @return array<string, mixed>|null
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $stmt = db()->prepare(
        'SELECT id, username, email, role, bio, avatar_url, school, tech_path, headline, achievements, social_link, created_at, supabase_auth_id
           FROM users
          WHERE id = :id
            AND is_active = TRUE'
    );
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

/**
 * Redirect to login if not authenticated.
 */
function require_auth(): void
{
    session_init();

    if (!is_logged_in()) {
        flash_set('error', 'You must be logged in to access that page.');
        redirect('/login.php');
    }
}

/**
 * Redirect authenticated users away from guest-only pages.
 */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect('/dashboard.php');
    }
}

/**
 * Strong password rules.
 *
 * @return string[]
 */
function validate_password_policy(string $password): array
{
    $errors = [];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }

    if ($password !== '' && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }

    if ($password !== '' && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }

    return $errors;
}

/**
 * Convert Supabase errors into user-friendly messages.
 */
function auth_humanize_supabase_error(Throwable $e): string
{
    $message = strtolower(trim($e->getMessage()));

    if (str_contains($message, 'email rate limit exceeded')) {
        return 'TEST MARKER: Too many signup attempts were made recently. Please wait 5 minutes before trying again.';
    }

    if (str_contains($message, 'user already registered')) {
        return 'This email is already registered.';
    }

    if (str_contains($message, 'signup is disabled')) {
        return 'New account registration is currently unavailable.';
    }

    if (str_contains($message, 'invalid email')) {
        return 'Please enter a valid email address.';
    }

    if (str_contains($message, 'password')) {
        return 'The password was rejected by the authentication provider.';
    }

    return APP_DEBUG
        ? 'Could not create the account right now: ' . $e->getMessage()
        : 'Could not create the account right now. Please try again later.';
}

/**
 * Process login POST.
 *
 * @return array{success: bool, errors: string[], identifier: string}
 */
function login_process_submission(): array
{
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    $result = login_user($identifier, $password);

    return [
        'success'    => $result['success'],
        'errors'     => $result['errors'],
        'identifier' => $identifier,
    ];
}

/**
 * Process register POST.
 *
 * @return array{success: bool, errors: string[], username: string, email: string, redirect: bool}
 */
function register_process_submission(): array
{
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        return [
            'success'  => false,
            'errors'   => ['Passwords do not match.'],
            'username' => $username,
            'email'    => $email,
            'redirect' => false,
        ];
    }

    $result = register_user($username, $email, $password);

    return [
        'success'  => $result['success'],
        'errors'   => $result['errors'],
        'username' => $username,
        'email'    => $email,
        'redirect' => $result['success'],
    ];
}

/**
 * Register a new user locally and sync to Supabase Auth.
 *
 * @return array{success: bool, errors: string[]}
 */
function register_user(string $username, string $email, string $password): array
{
    $errors = [];

    $username = trim($username);
    $email = trim($email);
    $emailLower = strtolower($email);

    if ($username === '' || strlen($username) < 3 || strlen($username) > 32) {
        $errors[] = 'Username must be between 3 and 32 characters.';
    }

    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, underscores, and hyphens.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    $errors = array_merge($errors, validate_password_policy($password));

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = db()->prepare(
        'SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1'
    );
    $stmt->execute([
        ':username' => $username,
        ':email'    => $emailLower,
    ]);

    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ['That username or email is already registered.']];
    }

    $hash = password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, is_active, created_at)
             VALUES (:username, :email, :password_hash, :role, TRUE, NOW())
             RETURNING id'
        );

        $stmt->execute([
            ':username'      => $username,
            ':email'         => $emailLower,
            ':password_hash' => $hash,
            ':role'          => 'member',
        ]);

        $localUserId = (int) ($stmt->fetch()['id'] ?? 0);

        if ($localUserId < 1) {
            throw new RuntimeException('Local user could not be created.');
        }

        try {
            $response = supabase_auth_signup($emailLower, $password);
            $supabaseId = (string) ($response['user']['id'] ?? '');

            if ($supabaseId !== '') {
                $update = $pdo->prepare(
                    'UPDATE users
                        SET supabase_auth_id = :supabase_auth_id
                      WHERE id = :id'
                );
                $update->execute([
                    ':supabase_auth_id' => $supabaseId,
                    ':id'               => $localUserId,
                ]);
            }
        } catch (Throwable $e) {
            throw new RuntimeException(auth_humanize_supabase_error($e), 0, $e);
        }

        $pdo->commit();
        return ['success' => true, 'errors' => []];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (APP_DEBUG) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }

        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

/**
 * Create local app session after successful credential verification.
 *
 * @param array<string, mixed> $user
 * @return array{success: bool, errors: string[]}
 */
function login_finalize_user(array $user, string $password): array
{
    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ALGO, PASSWORD_OPTIONS)) {
        $newHash = password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS);
        $update = db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $update->execute([
            ':hash' => $newHash,
            ':id'   => $user['id'],
        ]);
    }

    session_init();
    session_regenerate_id(true);

    unset($_SESSION['_user_cache']);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];

    if (!empty($user['supabase_auth_id'])) {
        $_SESSION['supabase_auth_id'] = $user['supabase_auth_id'];
    }

    $updateLogin = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $updateLogin->execute([':id' => $user['id']]);

    return ['success' => true, 'errors' => []];
}

/**
 * Attempt local password verification.
 *
 * @return array{success: bool, errors: string[]}
 */
function login_user_local(array|false $user, string $password): array
{
    $active = $user !== false && (bool) $user['is_active'];
    $hash   = ($user !== false && $active) ? $user['password_hash'] : AUTH_TIMING_DUMMY_HASH;

    if (!password_verify($password, $hash)) {
        return ['success' => false, 'errors' => ['Invalid credentials. Please try again.']];
    }

    if (!is_array($user) || !(bool) ($user['is_active'] ?? false)) {
        return ['success' => false, 'errors' => ['Invalid credentials. Please try again.']];
    }

    return login_finalize_user($user, $password);
}

/**
 * Attempt to log in with username or email and password.
 *
 * @return array{success: bool, errors: string[]}
 */
function login_user(string $identifier, string $password): array
{
    $identifier = trim($identifier);

    if ($identifier === '' || $password === '') {
        return ['success' => false, 'errors' => ['Please fill in all fields.']];
    }

    $stmt = db()->prepare(
        'SELECT id, username, email, password_hash, role, is_active, supabase_auth_id
           FROM users
          WHERE username = :i1 OR LOWER(email) = LOWER(:i2)
          LIMIT 1'
    );
    $stmt->execute([
        ':i1' => $identifier,
        ':i2' => $identifier,
    ]);
    $user = $stmt->fetch();

    if (!is_array($user)) {
        password_verify($password, AUTH_TIMING_DUMMY_HASH);
        return ['success' => false, 'errors' => ['Invalid credentials. Please try again.']];
    }

    if (!(bool) ($user['is_active'] ?? false)) {
        password_verify($password, AUTH_TIMING_DUMMY_HASH);
        return ['success' => false, 'errors' => ['Invalid credentials. Please try again.']];
    }

    if (!empty($user['supabase_auth_id'])) {
        try {
            supabase_auth_signin((string) $user['email'], $password);
            return login_finalize_user($user, $password);
        } catch (Throwable $e) {
            return login_user_local($user, $password);
        }
    }

    return login_user_local($user, $password);
}