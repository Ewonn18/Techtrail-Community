<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/community.php';
require_once dirname(__DIR__) . '/includes/functions.php';

session_init();
require_auth();

$user = current_user();
if ($user === null) {
    redirect('/login.php');
}

$uid   = (int) $user['id'];
$stats = community_user_dashboard_stats($uid);
$recentPosts = community_user_posts_with_likes($uid, 3);

$followerCount = follower_count($uid);
$followingCount = following_count($uid);

$techPath = trim((string) ($user['tech_path'] ?? ''));
$headline = trim((string) ($user['headline'] ?? ''));
$bio = trim((string) ($user['bio'] ?? ''));

$pageTitle  = 'Dashboard';
$layoutMode = 'app';
require_once dirname(__DIR__) . '/partials/app-shell-start.php';
?>

<div class="mx-auto max-w-7xl space-y-8 sm:space-y-10">
    <section class="relative overflow-hidden rounded-[2rem] premium-card p-6 sm:p-8 lg:p-10">
        <div class="hero-orb -right-10 -top-10 h-56 w-56 bg-cyan-500/12"></div>
        <div class="hero-orb -left-10 bottom-0 h-48 w-48 bg-indigo-500/10"></div>

        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-cyan-300/80">Your workspace</p>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Welcome back,
                    <span class="bg-gradient-to-r from-cyan-300 to-blue-300 bg-clip-text text-transparent">
                        <?= e((string) $user['username']) ?>
                    </span>
                </h1>

                <?php if ($headline !== ''): ?>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-gray-300"><?= e($headline) ?></p>
                <?php else: ?>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-gray-400">
                        Complete your profile headline so your dashboard feels more personal and professional.
                    </p>
                <?php endif; ?>

                <div class="mt-5 flex flex-wrap gap-3">
                    <?php if ($techPath !== ''): ?>
                        <span class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-4 py-1.5 text-sm font-medium text-cyan-200">
                            <span class="text-cyan-300/70">Path</span>
                            <?= e($techPath) ?>
                        </span>
                    <?php endif; ?>

                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm text-gray-300">
                        <?= number_format($followerCount) ?> followers
                    </span>

                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm text-gray-300">
                        <?= number_format($followingCount) ?> following
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <a
                    href="<?= e(url('/community.php#create-post')) ?>"
                    class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-500 to-cyan-400 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:scale-[1.02]"
                >
                    Create post
                </a>
                <a
                    href="<?= e(url('/edit-profile.php')) ?>"
                    class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-gray-200 transition hover:border-white/25 hover:bg-white/10"
                >
                    Edit profile
                </a>
            </div>
        </div>
    </section>

    <section>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="premium-card rounded-[1.5rem] p-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total posts</p>
                <p class="mt-3 text-4xl font-extrabold tabular-nums text-white"><?= number_format($stats['total_posts']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Published to the feed</p>
            </div>

            <div class="premium-card rounded-[1.5rem] p-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Likes received</p>
                <p class="mt-3 text-4xl font-extrabold tabular-nums bg-gradient-to-r from-emerald-300 to-cyan-300 bg-clip-text text-transparent"><?= number_format($stats['likes_received']) ?></p>
                <p class="mt-1 text-xs text-gray-500">From your posts</p>
            </div>

            <div class="premium-card rounded-[1.5rem] p-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Comments made</p>
                <p class="mt-3 text-4xl font-extrabold tabular-nums text-indigo-200"><?= number_format($stats['comments_made']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Across the community</p>
            </div>

            <div class="rounded-[1.5rem] border border-cyan-500/30 bg-cyan-500/10 p-6 shadow-card backdrop-blur-sm">
                <p class="text-[10px] font-bold uppercase tracking-widest text-cyan-200">Followers</p>
                <p class="mt-3 text-4xl font-extrabold tabular-nums text-white"><?= number_format($followerCount) ?></p>
                <p class="mt-1 text-xs text-cyan-100/70">People following you</p>
            </div>

            <div class="rounded-[1.5rem] border border-blue-500/30 bg-blue-500/10 p-6 shadow-card backdrop-blur-sm">
                <p class="text-[10px] font-bold uppercase tracking-widest text-blue-200">Following</p>
                <p class="mt-3 text-4xl font-extrabold tabular-nums text-white"><?= number_format($followingCount) ?></p>
                <p class="mt-1 text-xs text-blue-100/70">People you follow</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[.9fr_1.1fr]">
        <div class="premium-card rounded-[1.75rem] p-6 sm:p-8">
            <h2 class="text-lg font-bold text-white">Quick actions</h2>
            <p class="mt-1 text-xs text-gray-500">Everything you need is one click away</p>

            <div class="mt-6 flex flex-col gap-3">
                <a
                    href="<?= e(url('/community.php')) ?>"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-5 py-3.5 text-sm font-semibold text-gray-100 transition hover:scale-[1.02] hover:border-cyan-500/30 hover:bg-cyan-500/10"
                >
                    Go to Community
                </a>
                <a
                    href="<?= e(url('/profile.php?id=' . $uid)) ?>"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-5 py-3.5 text-sm font-semibold text-gray-100 transition hover:scale-[1.02] hover:border-white/25 hover:bg-white/10"
                >
                    View Profile
                </a>
                <a
                    href="<?= e(url('/edit-profile.php')) ?>"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition hover:scale-[1.02]"
                >
                    Edit Profile
                </a>
            </div>
        </div>

        <div class="premium-card rounded-[1.75rem] p-6 sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Recent activity</h2>
                    <p class="mt-1 text-xs text-gray-500">Your latest posts</p>
                </div>
                <a href="<?= e(url('/community.php')) ?>" class="text-sm font-semibold text-cyan-400 transition hover:text-cyan-300">
                    View community →
                </a>
            </div>

            <?php if ($recentPosts === []): ?>
                <div class="mt-8 rounded-2xl border border-dashed border-white/15 bg-black/20 px-6 py-12 text-center">
                    <p class="text-base font-semibold text-gray-300">No posts yet.</p>
                    <p class="mt-2 text-sm text-gray-500">Start by creating your first post in the community feed.</p>
                    <a
                        href="<?= e(url('/community.php#create-post')) ?>"
                        class="mt-6 inline-flex rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition hover:scale-[1.02]"
                    >
                        Create your first post
                    </a>
                </div>
            <?php else: ?>
                <ul class="mt-6 space-y-3">
                    <?php foreach ($recentPosts as $rp): ?>
                        <li>
                            <a
                                href="<?= e(url('/community.php#post-' . (int) $rp['id'])) ?>"
                                class="group flex flex-col gap-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-4 transition hover:border-cyan-500/30 hover:bg-white/[0.04] sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-white group-hover:text-cyan-200"><?= e((string) $rp['title']) ?></p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        <?= e(format_date((string) $rp['created_at'], 'M j, Y · g:i A')) ?>
                                    </p>
                                </div>
                                <span class="shrink-0 self-start rounded-lg border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-gray-400 tabular-nums sm:self-center">
                                    <?= (int) ($rp['like_count'] ?? 0) ?> ♥
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
        <div class="premium-card rounded-[1.75rem] p-6 sm:p-8">
            <h2 class="text-lg font-bold text-white">Profile snapshot</h2>
            <p class="mt-1 text-xs text-gray-500">Your public-facing identity</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Email</p>
                    <p class="mt-2 break-all text-sm font-medium text-gray-200"><?= e((string) $user['email']) ?></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Role</p>
                    <p class="mt-2 text-sm font-medium capitalize text-gray-200"><?= e((string) ($user['role'] ?? 'member')) ?></p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-black/20 p-4 sm:col-span-2">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Bio</p>
                    <p class="mt-2 text-sm leading-7 text-gray-300">
                        <?= $bio !== '' ? e($bio) : 'No bio yet. Add one in your profile so people can know you better.' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="premium-card rounded-[1.75rem] p-6 sm:p-8">
            <h2 class="text-lg font-bold text-white">Account</h2>
            <p class="mt-1 text-xs text-gray-500">Member details</p>

            <div class="mt-6 space-y-4">
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Member since</p>
                    <p class="mt-2 text-sm font-medium text-gray-200">
                        <?= !empty($user['created_at']) ? e(format_date((string) $user['created_at'], 'F j, Y')) : '—' ?>
                    </p>
                </div>

                <div class="rounded-2xl border border-cyan-500/20 bg-cyan-500/10 p-4">
                    <p class="text-xs uppercase tracking-wider text-cyan-100/80">Next step</p>
                    <p class="mt-2 text-sm text-cyan-50">
                        Complete your profile and keep posting to build stronger presence in the community.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once dirname(__DIR__) . '/partials/app-shell-end.php'; ?>