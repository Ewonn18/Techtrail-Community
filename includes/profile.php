<?php
/**
 * TechTrail Community v2 — user profiles
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cloudinary.php';

/**
 * @return array<string, mixed>|null
 */
function profile_get_by_id(int $id): ?array
{
    if ($id < 1) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT id, username, email, bio, avatar_url, school, tech_path, headline, achievements, social_link, role, created_at
           FROM users
          WHERE id = :id AND is_active = TRUE'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * @return string[]
 */
function profile_validate_bio(string $bio): array
{
    $errors = [];
    if (strlen($bio) > PROFILE_BIO_MAX) {
        $errors[] = 'Bio must be at most ' . PROFILE_BIO_MAX . ' characters.';
    }
    return $errors;
}

/**
 * @return string[]
 */
function profile_validate_extended_profile(
    string $school,
    string $tech_path,
    string $headline,
    string $achievements,
    string $social_link
): array {
    $errors = [];

    if (strlen($school) > 255) {
        $errors[] = 'School must be at most 255 characters.';
    }

    if (strlen($tech_path) > 100) {
        $errors[] = 'Tech path must be at most 100 characters.';
    }

    if (strlen($headline) > 255) {
        $errors[] = 'Headline must be at most 255 characters.';
    }

    if (strlen($achievements) > PROFILE_ACHIEVEMENTS_MAX) {
        $errors[] = 'Achievements must be at most ' . PROFILE_ACHIEVEMENTS_MAX . ' characters.';
    }

    if (strlen($social_link) > 255) {
        $errors[] = 'Social link must be at most 255 characters.';
    }

    if ($social_link !== '' && !filter_var($social_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'Social link must be a valid URL (e.g. https://...).';
    }

    return $errors;
}

/**
 * Process avatar upload; returns Cloudinary URL or null if no file.
 *
 * @return array{errors: string[], path: ?string}
 */
function profile_process_avatar_upload(): array
{
    if (!isset($_FILES['avatar'])) {
        return ['errors' => [], 'path' => null];
    }

    $file = $_FILES['avatar'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['errors' => [], 'path' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['errors' => ['Could not upload the image. Please try again.'], 'path' => null];
    }

    if (($file['size'] ?? 0) > AVATAR_MAX_BYTES) {
        return ['errors' => ['Image must be ' . (AVATAR_MAX_BYTES / 1024 / 1024) . ' MB or smaller.'], 'path' => null];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['errors' => ['Invalid upload.'], 'path' => null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmp) ?: '';

    $allowedMime = ['image/jpeg', 'image/png'];
    if (!in_array($mime, $allowedMime, true)) {
        return ['errors' => ['Only JPG and PNG images are allowed.'], 'path' => null];
    }

    $info = @getimagesize($tmp);
    if ($info === false || !in_array($info[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        return ['errors' => ['The file is not a valid JPG or PNG image.'], 'path' => null];
    }

    if (!cloudinary_is_configured()) {
        return ['errors' => ['Cloudinary is not configured yet.'], 'path' => null];
    }

    try {
        $uploaded = cloudinary_upload_image($tmp, 'techtrail/avatars');
        return ['errors' => [], 'path' => $uploaded['secure_url']];
    } catch (Throwable $e) {
        if (APP_DEBUG) {
            return ['errors' => ['Cloudinary upload failed: ' . $e->getMessage()], 'path' => null];
        }

        return ['errors' => ['Could not upload the image right now. Please try again later.'], 'path' => null];
    }
}

/**
 * Resolve old local avatar path — retained only for backwards compatibility.
 */
function profile_avatar_fs_path(?string $webPath): ?string
{
    return null;
}

/**
 * Delete avatar image from Cloudinary when possible.
 */
function profile_delete_avatar_file(?string $webPath): void
{
    if ($webPath === null || $webPath === '') {
        return;
    }

    $publicId = cloudinary_public_id_from_url($webPath);
    if ($publicId !== null) {
        cloudinary_delete_image($publicId);
    }
}

/**
 * @return array{success: bool, errors: string[]}
 */
function profile_update_for_user(
    int $userId,
    string $bio,
    string $school,
    string $tech_path,
    string $headline,
    string $achievements,
    string $social_link,
    ?string $newAvatarWebPath,
    bool $removeAvatar = false
): array {
    $errors = array_merge(
        profile_validate_bio($bio),
        profile_validate_extended_profile($school, $tech_path, $headline, $achievements, $social_link)
    );
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $existing = profile_get_by_id($userId);
    if ($existing === null) {
        return ['success' => false, 'errors' => ['User not found.']];
    }

    $oldAvatar = is_string($existing['avatar_url'] ?? null) ? $existing['avatar_url'] : null;

    if ($removeAvatar && $newAvatarWebPath !== null) {
        $removeAvatar = false;
    }

    if ($newAvatarWebPath !== null) {
        $stmt = db()->prepare(
            'UPDATE users SET
                bio = :bio,
                school = :school,
                tech_path = :tech_path,
                headline = :headline,
                achievements = :achievements,
                social_link = :social_link,
                avatar_url = :avatar
             WHERE id = :id AND is_active = TRUE'
        );
        $stmt->execute([
            ':bio'          => $bio,
            ':school'       => $school,
            ':tech_path'    => $tech_path,
            ':headline'     => $headline,
            ':achievements' => $achievements,
            ':social_link'  => $social_link,
            ':avatar'       => $newAvatarWebPath,
            ':id'           => $userId,
        ]);

        if ($oldAvatar !== null && $oldAvatar !== $newAvatarWebPath) {
            profile_delete_avatar_file($oldAvatar);
        }

        return ['success' => true, 'errors' => []];
    }

    if ($removeAvatar) {
        $stmt = db()->prepare(
            'UPDATE users SET
                bio = :bio,
                school = :school,
                tech_path = :tech_path,
                headline = :headline,
                achievements = :achievements,
                social_link = :social_link,
                avatar_url = NULL
             WHERE id = :id AND is_active = TRUE'
        );
        $stmt->execute([
            ':bio'          => $bio,
            ':school'       => $school,
            ':tech_path'    => $tech_path,
            ':headline'     => $headline,
            ':achievements' => $achievements,
            ':social_link'  => $social_link,
            ':id'           => $userId,
        ]);

        if ($oldAvatar !== null) {
            profile_delete_avatar_file($oldAvatar);
        }

        return ['success' => true, 'errors' => []];
    }

    $stmt = db()->prepare(
        'UPDATE users SET
            bio = :bio,
            school = :school,
            tech_path = :tech_path,
            headline = :headline,
            achievements = :achievements,
            social_link = :social_link
         WHERE id = :id AND is_active = TRUE'
    );
    $stmt->execute([
        ':bio'          => $bio,
        ':school'       => $school,
        ':tech_path'    => $tech_path,
        ':headline'     => $headline,
        ':achievements' => $achievements,
        ':social_link'  => $social_link,
        ':id'           => $userId,
    ]);

    return ['success' => true, 'errors' => []];
}

/**
 * @return array{
 *   success: bool,
 *   errors: string[],
 *   values: array{bio: string, school: string, tech_path: string, headline: string, achievements: string, social_link: string},
 *   remove_avatar: bool
 * }
 */
function profile_process_edit_submission(int $userId): array
{
    $bio          = trim($_POST['bio'] ?? '');
    $school       = trim($_POST['school'] ?? '');
    $tech_path    = trim($_POST['tech_path'] ?? '');
    $headline     = trim($_POST['headline'] ?? '');
    $achievements = trim($_POST['achievements'] ?? '');
    $social_link  = trim($_POST['social_link'] ?? '');
    $removeAvatar = !empty($_POST['remove_avatar']);

    $values = [
        'bio'          => $bio,
        'school'       => $school,
        'tech_path'    => $tech_path,
        'headline'     => $headline,
        'achievements' => $achievements,
        'social_link'  => $social_link,
    ];

    $allErrors = array_merge(
        profile_validate_bio($bio),
        profile_validate_extended_profile($school, $tech_path, $headline, $achievements, $social_link)
    );
    if (!empty($allErrors)) {
        return [
            'success' => false,
            'errors' => $allErrors,
            'values' => $values,
            'remove_avatar' => $removeAvatar,
        ];
    }

    $up = profile_process_avatar_upload();
    if (!empty($up['errors'])) {
        return [
            'success' => false,
            'errors' => $up['errors'],
            'values' => $values,
            'remove_avatar' => $removeAvatar,
        ];
    }

    $result = profile_update_for_user(
        $userId,
        $bio,
        $school,
        $tech_path,
        $headline,
        $achievements,
        $social_link,
        $up['path'],
        $removeAvatar
    );

    if (!$result['success'] && $up['path'] !== null) {
        profile_delete_avatar_file($up['path']);
    }

    return [
        'success' => $result['success'],
        'errors'  => $result['errors'],
        'values'  => $values,
        'remove_avatar' => $removeAvatar,
    ];
}