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
    <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Notifications</h1>
            <p class="mt-2 text-sm text-gray-400">Your latest activity and updates.</p>
        </div>
        <span id="tt-notifications-status" class="text-xs text-gray-500">Live updates every 20 seconds</span>
    </div>

    <div id="tt-notifications-list" class="space-y-4">
        <?php if ($notifications === []): ?>
            <div id="tt-notifications-empty" class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-6 py-14 text-center">
                <p class="text-base font-semibold text-gray-300">No notifications yet.</p>
                <p class="mt-2 text-sm text-gray-500">Likes, comments, and follows will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                    $payload = notification_to_payload($n);
                ?>
                <a
                    href="<?= e((string) $payload['target_url']) ?>"
                    class="block rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-xl shadow-black/20 backdrop-blur-sm transition-all duration-200 hover:border-cyan-500/30 hover:bg-white/[0.05]"
                    data-notification-id="<?= (int) $payload['id'] ?>"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider <?= e((string) $payload['badge_class']) ?>">
                            <?= e((string) $payload['badge_label']) ?>
                        </span>
                        <time class="text-xs text-gray-500" datetime="<?= e((string) $payload['created_at']) ?>">
                            <?= e((string) $payload['created_at_human']) ?>
                        </time>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed text-gray-300">
                        <span class="font-semibold text-white"><?= e((string) $payload['actor']) ?></span>
                        <?= e(' ' . (string) $payload['message']) ?>
                    </p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var list = document.getElementById('tt-notifications-list');
    var status = document.getElementById('tt-notifications-status');

    if (!list) {
        return;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderNotification(item) {
        return [
            '<a href="', escapeHtml(item.target_url), '" class="block rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-xl shadow-black/20 backdrop-blur-sm transition-all duration-200 hover:border-cyan-500/30 hover:bg-white/[0.05]" data-notification-id="', item.id, '">',
                '<div class="flex flex-wrap items-center justify-between gap-3">',
                    '<span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider ', escapeHtml(item.badge_class), '">',
                        escapeHtml(item.badge_label),
                    '</span>',
                    '<time class="text-xs text-gray-500" datetime="', escapeHtml(item.created_at), '">',
                        escapeHtml(item.created_at_human),
                    '</time>',
                '</div>',
                '<p class="mt-3 text-sm leading-relaxed text-gray-300">',
                    '<span class="font-semibold text-white">', escapeHtml(item.actor), '</span> ',
                    escapeHtml(item.message),
                '</p>',
            '</a>'
        ].join('');
    }

    function renderNotifications(items) {
        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML = '' +
                '<div id="tt-notifications-empty" class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-6 py-14 text-center">' +
                    '<p class="text-base font-semibold text-gray-300">No notifications yet.</p>' +
                    '<p class="mt-2 text-sm text-gray-500">Likes, comments, and follows will appear here.</p>' +
                '</div>';
            return;
        }

        list.innerHTML = items.map(renderNotification).join('');
    }

    document.addEventListener('tt:notifications-updated', function (event) {
        var detail = event.detail || {};
        var items = detail.notifications || [];
        renderNotifications(items);

        if (status) {
            status.textContent = 'Last updated just now';
            setTimeout(function () {
                status.textContent = 'Live updates every 20 seconds';
            }, 2500);
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../partials/app-shell-end.php'; ?>