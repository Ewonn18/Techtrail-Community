<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/helpers.php';

session_init();
require_auth();

$userId = (int) $_SESSION['user_id'];
$user   = current_user();

if ($user === null) {
    flash_set('error', 'Session expired. Please log in again.');
    redirect('/login.php');
}

$errors = [];
$values = [
    'bio'          => trim((string) ($user['bio'] ?? '')),
    'school'       => trim((string) ($user['school'] ?? '')),
    'tech_path'    => trim((string) ($user['tech_path'] ?? '')),
    'headline'     => trim((string) ($user['headline'] ?? '')),
    'achievements' => trim((string) ($user['achievements'] ?? '')),
    'social_link'  => trim((string) ($user['social_link'] ?? '')),
];
$removeAvatarChecked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $state = profile_process_edit_submission($userId);
    $values = $state['values'];
    $removeAvatarChecked = (bool) ($state['remove_avatar'] ?? false);

    if ($state['success']) {
        flash_set('success', 'Your profile was updated successfully.');
        redirect('/profile.php?id=' . $userId);
    }

    $errors = $state['errors'];
}

$pageTitle  = 'Edit Profile';
$layoutMode = 'app';
require_once __DIR__ . '/../partials/app-shell-start.php';

$avatarPreview = '';
if (!empty($user['avatar_url']) && is_string($user['avatar_url'])) {
    $avatarPreview = trim($user['avatar_url']);
}
?>

<div class="mx-auto max-w-xl">
    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-10">
        <h1 class="text-2xl font-extrabold tracking-tight text-white">Edit profile</h1>
        <p class="mt-2 text-sm text-gray-500">Update your public profile, bio, and avatar (JPG or PNG, max <?= (int) (AVATAR_MAX_BYTES / 1024 / 1024) ?> MB).</p>

        <?php if (!empty($errors)): ?>
            <div class="mt-6 rounded-2xl border border-red-500/40 bg-red-500/[0.12] px-5 py-4 shadow-lg shadow-red-950/30 ring-1 ring-red-400/15" role="alert">
                <p class="text-xs font-bold uppercase tracking-wider text-red-300">Please fix the following</p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-red-100">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/edit-profile.php')) ?>" enctype="multipart/form-data" class="mt-8 space-y-7" novalidate data-tt-form-submit>
            <?= csrf_field() ?>

            <div>
                <label class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Avatar</label>

                <?php if ($avatarPreview !== ''): ?>
                    <div class="mb-4 flex items-center gap-4">
                        <img
                            src="<?= e($avatarPreview) ?>"
                            alt=""
                            class="h-24 w-24 rounded-2xl border border-white/10 object-cover shadow-lg"
                        >
                        <label class="inline-flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-gray-200">
                            <input
                                type="checkbox"
                                name="remove_avatar"
                                value="1"
                                <?= $removeAvatarChecked ? 'checked' : '' ?>
                                class="h-4 w-4 rounded border-white/20 bg-transparent text-cyan-500 focus:ring-cyan-500"
                            >
                            Remove current avatar
                        </label>
                    </div>
                <?php endif; ?>

                <input
                    type="file"
                    name="avatar"
                    accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-400 file:mr-4 file:rounded-xl file:border-0 file:bg-white/10 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-200 hover:file:bg-white/15"
                >
                <p class="mt-2 text-xs text-gray-600">Upload a new image to replace your current avatar.</p>
            </div>

            <div>
                <label for="headline" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Headline</label>
                <input
                    type="text"
                    id="headline"
                    name="headline"
                    value="<?= e($values['headline']) ?>"
                    maxlength="255"
                    class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white placeholder-gray-600 outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40 invalid:border-red-500/40"
                    placeholder="e.g. Full-stack developer · Open source"
                >
                <p class="mt-1 text-xs text-gray-600">Max 255 characters.</p>
            </div>

            <div>
                <label for="school" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">School</label>
                <input
                    type="text"
                    id="school"
                    name="school"
                    value="<?= e($values['school']) ?>"
                    maxlength="255"
                    class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                    placeholder="University or institution"
                >
            </div>

            <div>
                <label for="tech_path" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Tech path</label>
                <input
                    type="text"
                    id="tech_path"
                    name="tech_path"
                    value="<?= e($values['tech_path']) ?>"
                    maxlength="100"
                    list="tech-path-suggestions"
                    class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                    placeholder="e.g. Web Development, Cybersecurity"
                >
                <datalist id="tech-path-suggestions">
                    <option value="Cybersecurity">
                    <option value="Web Development">
                    <option value="Data Analytics">
                    <option value="Mobile Development">
                    <option value="Cloud &amp; DevOps">
                    <option value="AI / ML">
                </datalist>
                <p class="mt-1 text-xs text-gray-600">Max 100 characters.</p>
            </div>

            <div>
                <label for="social_link" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Social link</label>
                <input
                    type="url"
                    id="social_link"
                    name="social_link"
                    value="<?= e($values['social_link']) ?>"
                    maxlength="255"
                    class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                    placeholder="https://github.com/username or portfolio URL"
                >
                <p class="mt-1 text-xs text-gray-600">Full URL including https:// — max 255 characters.</p>
            </div>

            <div>
                <label for="bio" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Bio</label>
                <p class="mb-2 text-xs text-gray-600">Max <?= (int) PROFILE_BIO_MAX ?> characters.</p>
                <textarea
                    id="bio"
                    name="bio"
                    rows="5"
                    maxlength="<?= (int) PROFILE_BIO_MAX ?>"
                    class="min-h-[100px] w-full resize-y rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                    placeholder="Tell others about yourself..."
                ><?= e($values['bio']) ?></textarea>
            </div>

            <div>
                <label for="achievements" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Achievements</label>
                <p class="mb-2 text-xs text-gray-600">Max <?= (int) PROFILE_ACHIEVEMENTS_MAX ?> characters.</p>
                <textarea
                    id="achievements"
                    name="achievements"
                    rows="5"
                    maxlength="<?= (int) PROFILE_ACHIEVEMENTS_MAX ?>"
                    class="min-h-[100px] w-full resize-y rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                    placeholder="Certifications, hackathons, projects..."
                ><?= e($values['achievements']) ?></textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="submit"
                    data-tt-submit-btn
                    data-tt-loading-text="Saving changes..."
                    class="rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:scale-[1.02] hover:shadow-cyan-400/35"
                >
                    Save changes
                </button>
                <a href="<?= e(url('/profile.php?id=' . $userId)) ?>" class="inline-flex items-center rounded-2xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-gray-200 transition hover:border-white/25 hover:bg-white/10">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app-shell-end.php'; ?>