<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notifications.php';

session_init();
require_auth();

$user = current_user();
if ($user === null) {
    redirect('/login.php');
}

$uid = (int) $user['id'];
notifications_mark_all_read($uid);
$notifications = notifications_for_user($uid, 50);

$pageTitle = 'Notifications';
$layoutMode = 'app';
require_once __DIR__ . '/../partials/app-shell-start.php';
?>

<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-white">Notifications</h1>
        <p class="mt-2 text-sm text-gray-400">Your latest activity and updates.</p>
    </div>

    <div class="space-y-4">
        <?php if ($notifications === []): ?>
            <div class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-6 py-14 text-center">
                <p class="text-base font-semibold text-gray-300">No notifications yet.</p>
                <p class="mt-2 text-sm text-gray-500">Likes, comments, and follows will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                    $type = (string) ($n['type'] ?? '');
                    $actor = trim((string) ($n['actor_username'] ?? 'Someone'));
                    $message = trim((string) ($n['message'] ?? 'sent an update.'));
                    $referenceId = (int) ($n['reference_id'] ?? 0);

                    $targetUrl = url('/notifications.php');
                    if ($type === 'post_like' || $type === 'post_comment') {
                        $targetUrl = url('/community.php#post-' . $referenceId);
                    } elseif ($type === 'new_follower' && $referenceId > 0) {
                        $targetUrl = url('/profile.php?id=' . $referenceId);
                    }

                    $badgeClass = match($type) {
                        'post_like'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                        'post_comment' => 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300',
                        'new_follower' => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300',
                        default        => 'border-white/10 bg-white/5 text-gray-300',
                    };

                    $badgeLabel = match($type) {
                        'post_like'    => 'Like',
                        'post_comment' => 'Comment',
                        'new_follower' => 'Follow',
                        default        => 'Update',
                    };
                ?>
                <a
                    href="<?= e($targetUrl) ?>"
                    class="block rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-xl shadow-black/20 backdrop-blur-sm transition-all duration-200 hover:border-cyan-500/30 hover:bg-white/[0.05]"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider <?= $badgeClass ?>">
                            <?= e($badgeLabel) ?>
                        </span>
                        <time class="text-xs text-gray-500" datetime="<?= e((string) $n['created_at']) ?>">
                            <?= e(format_date((string) $n['created_at'], 'M j, Y · g:i A')) ?>
                        </time>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed text-gray-300">
                        <span class="font-semibold text-white"><?= e($actor) ?></span>
                        <?= e(' ' . $message) ?>
                    </p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app-shell-end.php'; ?>