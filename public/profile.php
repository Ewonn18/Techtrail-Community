<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/community.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/functions.php';

session_init();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id < 1 && is_logged_in()) {
    $id = (int) $_SESSION['user_id'];
}

if ($id < 1) {
    flash_set('error', 'Profile not found.');
    redirect('/index.php');
}

$profile = profile_get_by_id($id);
if ($profile === null) {
    flash_set('error', 'Profile not found.');
    redirect('/index.php');
}

$isOwner = is_logged_in() && (int) $_SESSION['user_id'] === (int) $profile['id'];
$isFollowing = is_logged_in() && is_following((int) $_SESSION['user_id'], (int) $profile['id']);

$pageTitle = 'Profile — ' . $profile['username'];
$layoutMode = is_logged_in() ? 'app' : 'site';
require_once __DIR__ . '/../partials/app-shell-start.php';

$avatarUrl = '';
if (!empty($profile['avatar_url']) && is_string($profile['avatar_url'])) {
    $avatarUrl = trim($profile['avatar_url']);
}

$initials = strtoupper(substr((string) $profile['username'], 0, 1));
$school = trim((string) ($profile['school'] ?? ''));
$tech_path = trim((string) ($profile['tech_path'] ?? ''));
$headline = trim((string) ($profile['headline'] ?? ''));
$achievements = trim((string) ($profile['achievements'] ?? ''));
$social_link = trim((string) ($profile['social_link'] ?? ''));
$recentByUser = community_user_posts_with_likes((int) $profile['id'], 5);
$followerCount = follower_count((int) $profile['id']);
$followingCount = following_count((int) $profile['id']);
?>

<div class="mx-auto max-w-2xl">
    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-10">
        <div class="flex flex-col items-center text-center">
            <?php if ($avatarUrl !== ''): ?>
                <img
                    src="<?= e($avatarUrl) ?>"
                    alt=""
                    class="h-32 w-32 rounded-full border-4 border-white/10 object-cover shadow-xl shadow-cyan-500/10 ring-2 ring-cyan-500/20"
                    width="128"
                    height="128"
                >
            <?php else: ?>
                <div class="flex h-32 w-32 items-center justify-center rounded-full border-4 border-white/10 bg-gradient-to-br from-indigo-600/40 to-cyan-600/30 text-4xl font-extrabold text-cyan-200 shadow-xl ring-2 ring-cyan-500/20">
                    <?= e($initials) ?>
                </div>
            <?php endif; ?>

            <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-white"><?= e((string) $profile['username']) ?></h1>

            <?php if ($headline !== ''): ?>
                <p class="mt-2 text-sm font-medium text-cyan-400/90"><?= e($headline) ?></p>
            <?php endif; ?>

            <p class="mt-1 text-sm capitalize text-gray-500"><?= e((string) ($profile['role'] ?? 'member')) ?></p>
            <p class="mt-3 max-w-full break-all text-sm text-gray-400"><?= e((string) $profile['email']) ?></p>

            <div class="mt-6 flex space-x-6">
                <span class="text-sm text-gray-400"><?= number_format($followerCount) ?> Followers</span>
                <span class="text-sm text-gray-400"><?= number_format($followingCount) ?> Following</span>
            </div>

            <?php if (!$isOwner && is_logged_in()): ?>
                <div class="mt-6 w-full flex justify-center gap-4">
                    <?php if ($isFollowing): ?>
                        <form method="POST" action="<?= e(url('/unfollow.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="following_id" value="<?= (int) $profile['id'] ?>">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-red-500/50 text-sm text-white px-6 py-3 transition-all duration-200 hover:scale-[1.02] hover:bg-red-500/70"
                            >
                                Unfollow
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?= e(url('/follow.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="following_id" value="<?= (int) $profile['id'] ?>">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 text-sm text-white px-6 py-3 transition-all duration-200 hover:scale-[1.02] hover:bg-cyan-500/70"
                            >
                                Follow
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($school !== '' || $tech_path !== ''): ?>
                <div class="mt-6 flex w-full flex-wrap justify-center gap-2 text-left">
                    <?php if ($school !== ''): ?>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-gray-300"><?= e($school) ?></span>
                    <?php endif; ?>

                    <?php if ($tech_path !== ''): ?>
                        <span class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-300"><?= e($tech_path) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($social_link !== ''): ?>
                <div class="mt-6 w-full">
                    <a
                        href="<?= e($social_link) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-cyan-400 transition hover:border-cyan-500/40 hover:bg-cyan-500/10"
                    >
                        Social link <span class="text-xs opacity-70">↗</span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-8 w-full border-t border-white/10 pt-8 text-left">
                <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Bio</h2>
                <?php if (trim((string) ($profile['bio'] ?? '')) !== ''): ?>
                    <p class="mt-3 text-sm leading-relaxed text-gray-300 whitespace-pre-wrap"><?= e((string) $profile['bio']) ?></p>
                <?php else: ?>
                    <p class="mt-3 text-sm italic text-gray-600">No bio yet.</p>
                <?php endif; ?>
            </div>

            <div class="mt-8 w-full border-t border-white/10 pt-8 text-left">
                <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Achievements</h2>
                <?php if ($achievements !== ''): ?>
                    <p class="mt-3 text-sm leading-relaxed text-gray-300 whitespace-pre-wrap"><?= e($achievements) ?></p>
                <?php else: ?>
                    <p class="mt-3 text-sm italic text-gray-600">No achievements listed yet.</p>
                <?php endif; ?>
            </div>

            <div class="mt-8 w-full border-t border-white/10 pt-8 text-left">
                <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Recent posts by user</h2>
                <?php if ($recentByUser === []): ?>
                    <p class="mt-4 text-sm italic text-gray-600">No community posts yet.</p>
                <?php else: ?>
                    <ul class="mt-4 space-y-3">
                        <?php foreach ($recentByUser as $rp): ?>
                            <li>
                                <a
                                    href="<?= e(url('/community.php#post-' . (int) $rp['id'])) ?>"
                                    class="group flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 transition-all duration-200 hover:border-cyan-500/35 hover:bg-white/[0.06]"
                                >
                                    <span class="min-w-0 flex-1 truncate font-medium text-gray-200 group-hover:text-cyan-200"><?= e((string) $rp['title']) ?></span>
                                    <span class="shrink-0 text-xs tabular-nums text-gray-500"><?= (int) ($rp['like_count'] ?? 0) ?> likes</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($profile['created_at'])): ?>
                <p class="mt-8 text-xs text-gray-600">Member since <?= e(format_date((string) $profile['created_at'], 'F j, Y')) ?></p>
            <?php endif; ?>

            <?php if ($isOwner): ?>
                <div class="mt-8 flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
                    <a
                        href="<?= e(url('/edit-profile.php')) ?>"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.02] hover:shadow-cyan-400/35 sm:flex-none"
                    >
                        Edit profile
                    </a>
                    <a
                        href="<?= e(url('/dashboard.php')) ?>"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-gray-200 transition-all duration-200 hover:scale-[1.02] hover:border-white/25 hover:bg-white/10 sm:flex-none"
                    >
                        Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app-shell-end.php'; ?>