<?php
/**
 * TechTrail Community v2
 * Supabase Auth helper
 */
require_once dirname(__DIR__) . '/config/app.php';

/**
 * @return array<string, mixed>
 */
function supabase_request(string $endpoint, string $method = 'POST', array $data = []): array
{
    if (SUPABASE_URL === '' || SUPABASE_ANON_KEY === '') {
        throw new RuntimeException('Supabase is not configured. Check SUPABASE_URL and SUPABASE_ANON_KEY in .env.');
    }

    $url = SUPABASE_URL . $endpoint;

    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
    ];

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Could not initialize cURL.');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($data !== []) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Supabase request error: ' . $error);
    }

    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    /** @var array<string, mixed>|null $decoded */
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    if ($statusCode >= 400) {
        $message = (string) ($decoded['msg'] ?? $decoded['message'] ?? 'Unknown Supabase error.');
        throw new RuntimeException('Supabase error: ' . $message);
    }

    return $decoded;
}

/**
 * Create an email/password user in Supabase Auth.
 *
 * @return array<string, mixed>
 */
function supabase_auth_signup(string $email, string $password): array
{
    return supabase_request('/auth/v1/signup', 'POST', [
        'email' => $email,
        'password' => $password,
    ]);
}

/**
 * Verify email/password against Supabase Auth.
 *
 * @return array<string, mixed>
 */
function supabase_auth_signin(string $email, string $password): array
{
    return supabase_request('/auth/v1/token?grant_type=password', 'POST', [
        'email' => $email,
        'password' => $password,
    ]);
}