<?php
/**
 * TechTrail Community v2
 * Cloudinary helper
 */
require_once dirname(__DIR__) . '/config/app.php';

/**
 * Whether Cloudinary is configured.
 */
function cloudinary_is_configured(): bool
{
    return CLOUDINARY_CLOUD_NAME !== ''
        && CLOUDINARY_API_KEY !== ''
        && CLOUDINARY_API_SECRET !== '';
}

/**
 * Generate Cloudinary API signature.
 *
 * @param array<string, string> $params
 */
function cloudinary_sign_params(array $params): string
{
    ksort($params);

    $pairs = [];
    foreach ($params as $key => $value) {
        if ($value === '') {
            continue;
        }
        $pairs[] = $key . '=' . $value;
    }

    $toSign = implode('&', $pairs) . CLOUDINARY_API_SECRET;

    return sha1($toSign);
}

/**
 * Upload image file to Cloudinary.
 *
 * @return array{secure_url: string, public_id: string}
 */
function cloudinary_upload_image(string $filePath, string $folder = 'techtrail/avatars'): array
{
    if (!cloudinary_is_configured()) {
        throw new RuntimeException('Cloudinary is not configured.');
    }

    if (!is_file($filePath)) {
        throw new RuntimeException('Upload file was not found.');
    }

    $timestamp = (string) time();
    $paramsToSign = [
        'folder' => $folder,
        'timestamp' => $timestamp,
    ];

    $signature = cloudinary_sign_params($paramsToSign);
    $endpoint = 'https://api.cloudinary.com/v1_1/' . rawurlencode(CLOUDINARY_CLOUD_NAME) . '/image/upload';

    $postFields = [
        'file' => new CURLFile($filePath),
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'folder' => $folder,
        'signature' => $signature,
    ];

    $ch = curl_init($endpoint);
    if ($ch === false) {
        throw new RuntimeException('Could not initialize Cloudinary upload request.');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Cloudinary upload error: ' . $error);
    }

    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    /** @var array<string, mixed>|null $decoded */
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    if ($statusCode >= 400) {
        $message = 'Cloudinary upload failed.';
        if (isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
            $message = $decoded['error']['message'];
        }
        throw new RuntimeException($message);
    }

    $secureUrl = (string) ($decoded['secure_url'] ?? '');
    $publicId = (string) ($decoded['public_id'] ?? '');

    if ($secureUrl === '' || $publicId === '') {
        throw new RuntimeException('Cloudinary did not return a valid upload response.');
    }

    return [
        'secure_url' => $secureUrl,
        'public_id' => $publicId,
    ];
}

/**
 * Delete image from Cloudinary by public ID.
 */
function cloudinary_delete_image(string $publicId): bool
{
    if (!cloudinary_is_configured() || $publicId === '') {
        return false;
    }

    $timestamp = (string) time();
    $paramsToSign = [
        'public_id' => $publicId,
        'timestamp' => $timestamp,
    ];

    $signature = cloudinary_sign_params($paramsToSign);
    $endpoint = 'https://api.cloudinary.com/v1_1/' . rawurlencode(CLOUDINARY_CLOUD_NAME) . '/image/destroy';

    $postFields = [
        'public_id' => $publicId,
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ];

    $ch = curl_init($endpoint);
    if ($ch === false) {
        return false;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return false;
    }

    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    /** @var array<string, mixed>|null $decoded */
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    return $statusCode < 400 && (($decoded['result'] ?? '') === 'ok' || ($decoded['result'] ?? '') === 'not found');
}

/**
 * Extract Cloudinary public ID from a Cloudinary secure URL.
 */
function cloudinary_public_id_from_url(?string $url): ?string
{
    if ($url === null || $url === '') {
        return null;
    }

    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || !str_contains($host, 'res.cloudinary.com')) {
        return null;
    }

    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return null;
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $uploadIndex = array_search('upload', $segments, true);

    if ($uploadIndex === false) {
        return null;
    }

    $publicSegments = array_slice($segments, $uploadIndex + 1);

    if ($publicSegments === []) {
        return null;
    }

    if (isset($publicSegments[0]) && preg_match('/^v\d+$/', $publicSegments[0])) {
        array_shift($publicSegments);
    }

    if ($publicSegments === []) {
        return null;
    }

    $last = array_pop($publicSegments);
    if (!is_string($last)) {
        return null;
    }

    $lastWithoutExt = preg_replace('/\.[a-zA-Z0-9]+$/', '', $last);
    if (!is_string($lastWithoutExt) || $lastWithoutExt === '') {
        return null;
    }

    $publicSegments[] = $lastWithoutExt;

    return implode('/', $publicSegments);
}