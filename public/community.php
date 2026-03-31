<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/community.php';
require_once __DIR__ . '/../includes/helpers.php';

session_init();
require_auth();

$userId = (int) $_SESSION['user_id'];

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$filterType  = community_normalize_filter_type((string) ($_GET['type'] ?? 'all'));

$persistParams = [];
if ($searchQuery !== '') {
    $persistParams['q'] = $searchQuery;
}
if ($filterType !== 'all') {
    $persistParams['type'] = $filterType;
}
$persistQueryString = http_build_query($persistParams);

$redirectHash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['community_action'] ?? '');

    if ($action === 'create') {
        $result = community_create_post(
            $userId,
            (string) ($_POST['title'] ?? ''),
            (string) ($_POST['content'] ?? ''),
            (string) ($_POST['post_type'] ?? ''),
            (string) ($_POST['category'] ?? '')
        );
        if ($result['success']) {
            flash_set('success', 'Your post was published successfully.');
        } else {
            flash_set('error', implode(' ', $result['errors']));
        }
    } elseif ($action === 'update') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $result = community_update_post(
            $userId,
            $postId,
            (string) ($_POST['title'] ?? ''),
            (string) ($_POST['content'] ?? ''),
            (string) ($_POST['post_type'] ?? ''),
            (string) ($_POST['category'] ?? '')
        );
        if ($result['success']) {
            flash_set('success', 'Post updated successfully.');
            $redirectHash = '#post-' . $postId;
        } else {
            flash_set('error', implode(' ', $result['errors']));
            $redirectHash = '#post-' . $postId;
        }
    } elseif ($action === 'delete') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $result = community_delete_post($userId, $postId);
        if ($result['success']) {
            flash_set('success', 'Post removed from the feed.');
        } else {
            flash_set('error', implode(' ', $result['errors']));
        }
    } elseif ($action === 'like_toggle') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $result = community_toggle_like($userId, $postId);
        if ($result['success']) {
            $redirectHash = '#post-' . $postId;
        } else {
            flash_set('error', implode(' ', $result['errors']));
            $redirectHash = '#post-' . $postId;
        }
    } elseif ($action === 'comment_add') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $result = community_add_comment($userId, $postId, (string) ($_POST['comment'] ?? ''));
        if ($result['success']) {
            flash_set('success', 'Comment posted.');
            $redirectHash = '#post-' . $postId;
        } else {
            flash_set('error', implode(' ', $result['errors']));
            $redirectHash = '#post-' . $postId;
        }
    }

    $redirectPath = '/community.php';
    if ($persistQueryString !== '') {
        $redirectPath .= '?' . $persistQueryString;
    }
    $redirectPath .= $redirectHash;

    redirect($redirectPath);
}

$posts          = community_posts_feed($userId, $searchQuery, $filterType);
$featuredMentor = community_featured_mentor_posts($userId, 3);
$postIds        = array_map(static fn ($p) => (int) $p['id'], $posts);
$commentsByPost = community_comments_map_for_posts($postIds);

$editId  = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = $editId > 0 ? community_post_owned_by($editId, $userId) : null;

$pageTitle  = 'Community';
$layoutMode = 'app';
require_once __DIR__ . '/../partials/app-shell-start.php';

$typeLabels = [
    'discussion'  => 'Discussion',
    'achievement' => 'Achievement',
    'mentor_tip'  => 'Mentor tip',
];

$filterTabs = [
    'all'         => 'All',
    'mentor_tip'  => 'Mentor Tips',
    'achievement' => 'Achievements',
    'discussion'  => 'Discussions',
];

$shareBase = json_encode(url('/community.php'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$communityBaseUrl = url('/community.php');
$searchActive = ($searchQuery !== '' || $filterType !== 'all');
?>

<div class="mx-auto max-w-3xl">
    <div id="tt-community-skel" class="mb-8 space-y-4" aria-hidden="true">
        <div class="h-10 w-48 rounded-xl bg-white/[0.06] animate-pulse"></div>
        <div class="h-24 rounded-2xl bg-white/[0.04] animate-pulse"></div>
        <div class="h-40 rounded-2xl bg-white/[0.04] animate-pulse"></div>
    </div>

    <div id="tt-community-main" class="opacity-0 transition-opacity duration-500 ease-out">
        <div class="mb-10">
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Community</h1>
            <p class="mt-2 text-sm text-gray-400">Share updates, wins, and tips with the TechTrail crew.</p>
        </div>

        <section class="mb-10 rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl backdrop-blur-xl sm:p-8">
            <div class="flex flex-col gap-5">
                <form method="GET" action="<?= e($communityBaseUrl) ?>" class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <label for="community-search" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">
                            Search posts
                        </label>
                        <input
                            id="community-search"
                            type="text"
                            name="q"
                            value="<?= e($searchQuery) ?>"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white placeholder-gray-600 outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                            placeholder="Search by title, content, or username..."
                        >
                    </div>

                    <div class="sm:w-52">
                        <label for="community-type" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">
                            Filter
                        </label>
                        <select
                            id="community-type"
                            name="type"
                            class="w-full rounded-2xl border border-white/10 bg-gray-950 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <?php foreach ($filterTabs as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $filterType === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.02] hover:shadow-cyan-400/35"
                        >
                            Search
                        </button>

                        <?php if ($searchActive): ?>
                            <a
                                href="<?= e($communityBaseUrl) ?>"
                                class="rounded-2xl border border-white/15 bg-white/5 px-5 py-3.5 text-sm font-semibold text-gray-200 transition-all duration-200 hover:scale-[1.01] hover:border-white/25 hover:bg-white/10"
                            >
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="flex flex-wrap gap-2">
                    <?php foreach ($filterTabs as $value => $label): ?>
                        <?php
                            $tabParams = [];
                            if ($searchQuery !== '') {
                                $tabParams['q'] = $searchQuery;
                            }
                            if ($value !== 'all') {
                                $tabParams['type'] = $value;
                            }
                            $tabUrl = $communityBaseUrl . (empty($tabParams) ? '' : '?' . http_build_query($tabParams));
                            $active = $filterType === $value;
                        ?>
                        <a
                            href="<?= e($tabUrl) ?>"
                            class="rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-wider transition-all duration-200 <?= $active
                                ? 'border-cyan-500/40 bg-cyan-500/15 text-cyan-200 shadow-lg shadow-cyan-900/20'
                                : 'border-white/10 bg-white/5 text-gray-400 hover:border-white/20 hover:bg-white/10 hover:text-white' ?>"
                        >
                            <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($featuredMentor !== [] && $editing === null): ?>
            <section class="mb-10 rounded-3xl border border-amber-500/25 bg-gradient-to-br from-amber-500/[0.07] to-transparent p-6 shadow-xl shadow-amber-950/20 backdrop-blur-xl sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.15em] text-amber-200/90">Featured Mentor Tips</h2>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-400/60">Curated highlights</span>
                </div>
                <ul class="mt-5 space-y-3">
                    <?php foreach ($featuredMentor as $fp):
                        $fpid = (int) $fp['id'];
                        $rawEx = (string) $fp['content'];
                        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                            $excerpt = mb_strlen($rawEx) > 140 ? mb_substr($rawEx, 0, 140) . '…' : $rawEx;
                        } else {
                            $excerpt = strlen($rawEx) > 140 ? substr($rawEx, 0, 140) . '…' : $rawEx;
                        }
                    ?>
                        <li>
                            <a
                                href="<?= e(url('/community.php#post-' . $fpid)) ?>"
                                class="group flex flex-col gap-2 rounded-2xl border border-amber-500/20 bg-black/25 px-4 py-4 transition-all duration-200 hover:border-amber-400/45 hover:bg-amber-500/10 hover:shadow-lg hover:shadow-amber-900/20 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0 flex-1">
                                    <span class="inline-flex rounded-md border border-amber-400/40 bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-200">Mentor Tip</span>
                                    <p class="mt-2 font-semibold text-white group-hover:text-amber-100"><?= e((string) $fp['title']) ?></p>
                                    <p class="mt-1 line-clamp-2 text-xs text-gray-500"><?= e($excerpt) ?></p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                    <span class="text-cyan-400/90"><?= e((string) $fp['username']) ?></span>
                                    <span class="tabular-nums"><?= (int) ($fp['like_count'] ?? 0) ?> likes</span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($editing !== null): ?>
            <div class="mb-10 rounded-3xl border border-cyan-500/30 bg-white/[0.04] p-6 shadow-xl backdrop-blur-xl sm:p-8">
                <h2 class="text-lg font-bold text-white">Edit post</h2>
                <form method="POST" action="<?= e($communityBaseUrl) ?>" class="mt-8 space-y-6" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="community_action" value="update">
                    <input type="hidden" name="post_id" value="<?= (int) $editing['id'] ?>">

                    <div>
                        <label for="edit_title" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Title</label>
                        <input
                            id="edit_title"
                            name="title"
                            required
                            maxlength="<?= COMMUNITY_POST_TITLE_MAX ?>"
                            value="<?= e((string) $editing['title']) ?>"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40 invalid:border-red-500/40"
                        >
                    </div>

                    <div>
                        <label for="edit_post_type" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Type</label>
                        <select
                            id="edit_post_type"
                            name="post_type"
                            class="w-full rounded-2xl border border-white/10 bg-gray-950 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <?php foreach (community_allowed_post_types() as $t): ?>
                                <option value="<?= e($t) ?>" <?= ((string) $editing['post_type'] === $t) ? 'selected' : '' ?>>
                                    <?= e($typeLabels[$t] ?? $t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="edit_category" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Category <span class="font-normal text-gray-600">(optional)</span></label>
                        <input
                            id="edit_category"
                            name="category"
                            maxlength="<?= COMMUNITY_CATEGORY_MAX ?>"
                            value="<?= e((string) ($editing['category'] ?? '')) ?>"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                            placeholder="e.g. React, PostgreSQL"
                        >
                    </div>

                    <div>
                        <label for="edit_content" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Content</label>
                        <textarea
                            id="edit_content"
                            name="content"
                            required
                            rows="8"
                            maxlength="<?= COMMUNITY_POST_CONTENT_MAX ?>"
                            class="min-h-[160px] w-full resize-y rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40 invalid:border-red-500/40"
                        ><?= e((string) $editing['content']) ?></textarea>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.02] hover:shadow-cyan-400/35">
                            Save changes
                        </button>
                        <a href="<?= e($communityBaseUrl . ($persistQueryString !== '' ? '?' . $persistQueryString : '')) ?>" class="inline-flex items-center rounded-2xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-gray-200 transition-all duration-200 hover:scale-[1.01] hover:border-white/25 hover:bg-white/10">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div id="create-post" class="mb-10 scroll-mt-28 rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl backdrop-blur-xl sm:p-8">
                <h2 class="text-lg font-bold text-white">New post</h2>
                <p class="mt-1 text-xs text-gray-500">Community updates, achievements, or mentor-style tips.</p>

                <form method="POST" action="<?= e($communityBaseUrl . ($persistQueryString !== '' ? '?' . $persistQueryString : '')) ?>" class="mt-8 space-y-6" data-tt-form-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="community_action" value="create">

                    <div>
                        <label for="title" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Title</label>
                        <input
                            id="title"
                            name="title"
                            required
                            maxlength="<?= COMMUNITY_POST_TITLE_MAX ?>"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40 invalid:border-red-500/40"
                            placeholder="What are you sharing?"
                        >
                    </div>

                    <div>
                        <label for="post_type" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Type</label>
                        <select
                            id="post_type"
                            name="post_type"
                            class="w-full rounded-2xl border border-white/10 bg-gray-950 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <?php foreach (community_allowed_post_types() as $t): ?>
                                <option value="<?= e($t) ?>"><?= e($typeLabels[$t] ?? $t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="category" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Category <span class="font-normal text-gray-600">(optional)</span></label>
                        <input
                            id="category"
                            name="category"
                            maxlength="<?= COMMUNITY_CATEGORY_MAX ?>"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                            placeholder="Topic or stack"
                        >
                    </div>

                    <div>
                        <label for="content" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Content</label>
                        <textarea
                            id="content"
                            name="content"
                            required
                            rows="6"
                            maxlength="<?= COMMUNITY_POST_CONTENT_MAX ?>"
                            class="min-h-[140px] w-full resize-y rounded-2xl border border-white/10 bg-white/5 px-4 py-3.5 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40 invalid:border-red-500/40"
                            placeholder="Write your post…"
                        ></textarea>
                    </div>

                    <button type="submit" data-tt-submit-btn class="rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.02] hover:shadow-cyan-400/35 disabled:cursor-wait disabled:opacity-70">
                        Publish
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if ($posts === []): ?>
                <div class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-6 py-16 text-center">
                    <?php if ($searchActive): ?>
                        <p class="text-base font-semibold text-gray-300">No posts found.</p>
                        <p class="mt-2 text-sm text-gray-500">Try a different search term or switch the filter.</p>
                        <a href="<?= e($communityBaseUrl) ?>" class="mt-6 inline-flex rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-gray-200 transition-all duration-200 hover:scale-[1.02] hover:border-white/25 hover:bg-white/10">
                            Clear search & filters
                        </a>
                    <?php else: ?>
                        <p class="text-base font-semibold text-gray-300">Be the first to share something!</p>
                        <p class="mt-2 text-sm text-gray-500">The feed is empty — start a discussion or drop a mentor tip.</p>
                        <?php if ($editing === null): ?>
                            <a href="#create-post" class="mt-8 inline-flex rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition-all duration-200 hover:scale-[1.02]">
                                Create a post
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($posts as $post):
                $pid          = (int) $post['id'];
                $isOwner      = (int) $post['user_id'] === $userId;
                $liked        = !empty($post['liked_by_me']);
                $likeCount    = (int) ($post['like_count'] ?? 0);
                $commentCount = (int) ($post['comment_count'] ?? 0);
                $comments     = $commentsByPost[$pid] ?? [];
                $ptype        = (string) ($post['post_type'] ?? '');
                $ptypeLabel   = $typeLabels[$ptype] ?? $ptype;
                $isMentorTip  = $ptype === 'mentor_tip';
                $articleClass = 'scroll-mt-24 rounded-3xl border p-6 shadow-xl backdrop-blur-xl sm:p-8 transition-all duration-300 ';
                if ($isMentorTip) {
                    $articleClass .= 'border-amber-400/45 bg-gradient-to-br from-amber-500/[0.08] to-white/[0.04] ring-2 ring-amber-400/30 shadow-[0_0_45px_-12px_rgba(251,191,36,0.45)]';
                } else {
                    $articleClass .= 'border-white/10 bg-white/[0.04]';
                }
            ?>
                <article id="post-<?= $pid ?>" class="<?= e($articleClass) ?>">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-cyan-400"><?= e((string) $post['username']) ?></p>
                            <h3 class="mt-1 text-xl font-bold text-white"><?= e((string) $post['title']) ?></h3>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wider text-gray-500">
                                <span class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5"><?= e($ptypeLabel) ?></span>
                                <?php if ($isMentorTip): ?>
                                    <span class="rounded-md border border-amber-400/50 bg-amber-500/20 px-2 py-0.5 font-bold text-amber-100">Mentor Tip</span>
                                <?php elseif (!empty($post['is_mentor_post'])): ?>
                                    <span class="rounded-md border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-amber-200">Mentor</span>
                                <?php endif; ?>
                                <?php if (trim((string) ($post['category'] ?? '')) !== ''): ?>
                                    <span class="rounded-md border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 text-cyan-300"><?= e((string) $post['category']) ?></span>
                                <?php endif; ?>
                                <time class="text-gray-600 normal-case" datetime="<?= e((string) $post['created_at']) ?>">
                                    <?= e(format_date((string) $post['created_at'], 'M j, Y · g:i A')) ?>
                                </time>
                            </div>
                        </div>
                        <?php if ($isOwner): ?>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?= e($communityBaseUrl . '?' . http_build_query(array_merge($persistParams, ['edit' => $pid]))) ?>" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-gray-300 transition-all duration-200 hover:scale-105 hover:border-cyan-500/40 hover:bg-white/10">Edit</a>
                                <form method="POST" action="<?= e($communityBaseUrl . ($persistQueryString !== '' ? '?' . $persistQueryString : '')) ?>" class="inline" onsubmit="return confirm('Delete this post?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="community_action" value="delete">
                                    <input type="hidden" name="post_id" value="<?= $pid ?>">
                                    <button type="submit" class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 transition-all duration-200 hover:scale-105 hover:bg-red-500/15">Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-5 text-sm leading-relaxed text-gray-300 whitespace-pre-wrap"><?= e((string) $post['content']) ?></div>

                    <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-white/10 pt-5">
                        <form method="POST" action="<?= e($communityBaseUrl . ($persistQueryString !== '' ? '?' . $persistQueryString : '') . '#post-' . $pid) ?>" class="inline" data-tt-form-submit>
                            <?= csrf_field() ?>
                            <input type="hidden" name="community_action" value="like_toggle">
                            <input type="hidden" name="post_id" value="<?= $pid ?>">
                            <button
                                type="submit"
                                data-tt-submit-btn
                                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-all duration-200 hover:scale-[1.03] disabled:opacity-60 <?= $liked ? 'border-cyan-500/50 bg-cyan-500/15 text-cyan-200' : 'border-white/15 bg-white/5 text-gray-300 hover:border-white/25 hover:bg-white/10' ?>"
                            >
                                <span><?= $liked ? '♥' : '♡' ?></span>
                                <span><?= $likeCount ?> like<?= $likeCount === 1 ? '' : 's' ?></span>
                            </button>
                        </form>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-300 transition-all duration-200 hover:scale-[1.03] hover:border-white/25 hover:bg-white/10"
                            data-share-post="<?= $pid ?>"
                            data-share-title="<?= e((string) $post['title']) ?>"
                        >
                            Share
                        </button>

                        <span class="text-sm text-gray-500"><?= $commentCount ?> comment<?= $commentCount === 1 ? '' : 's' ?></span>
                    </div>

                    <div class="mt-6 rounded-2xl border border-white/5 bg-black/20 p-4">
                        <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Comments</h4>
                        <?php if ($comments === []): ?>
                            <p class="mt-3 text-xs text-gray-600">No comments yet — start the thread.</p>
                        <?php else: ?>
                            <ul class="mt-4 space-y-3">
                                <?php foreach ($comments as $c): ?>
                                    <li class="border-b border-white/5 pb-3 last:border-0 last:pb-0">
                                        <p class="text-xs font-semibold text-gray-400"><?= e((string) $c['username']) ?>
                                            <span class="font-normal text-gray-600">· <?= e(format_date((string) $c['created_at'], 'M j, g:i A')) ?></span>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-300 whitespace-pre-wrap"><?= e((string) $c['content']) ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <form method="POST" action="<?= e($communityBaseUrl . ($persistQueryString !== '' ? '?' . $persistQueryString : '') . '#post-' . $pid) ?>" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end" data-tt-form-submit>
                            <?= csrf_field() ?>
                            <input type="hidden" name="community_action" value="comment_add">
                            <input type="hidden" name="post_id" value="<?= $pid ?>">
                            <input
                                type="text"
                                name="comment"
                                maxlength="<?= COMMUNITY_COMMENT_MAX ?>"
                                required
                                class="flex-1 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition-all duration-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40 invalid:border-red-500/40"
                                placeholder="Write a comment…"
                            >
                            <button type="submit" data-tt-submit-btn class="rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition-all duration-200 hover:scale-[1.02] disabled:opacity-60">
                                Comment
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var base = <?= $shareBase ?>;
    document.querySelectorAll('[data-share-post]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-share-post');
            var title = btn.getAttribute('data-share-title') || 'Post';
            var url = base + '#post-' + id;
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function () {});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    alert('Link copied to clipboard');
                }).catch(function () {
                    prompt('Copy link:', url);
                });
            } else {
                prompt('Copy link:', url);
            }
        });
    });

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        var skel = document.getElementById('tt-community-skel');
        var main = document.getElementById('tt-community-main');
        if (skel) skel.classList.add('hidden');
        if (main) {
            main.classList.remove('opacity-0');
            main.classList.add('opacity-100');
        }
    });

    document.querySelectorAll('form[data-tt-form-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('[data-tt-submit-btn]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                var t = (btn.textContent || '').trim();
                if (t) {
                    btn.dataset.ttWas = t;
                }
                btn.textContent = 'Please wait…';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../partials/app-shell-end.php'; ?>