<?php
/**
 * TechTrail Community v2
 * App Shell — Opening partial
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/notifications.php';

session_init();

$pageTitle = $pageTitle ?? APP_NAME;
$layoutMode = $layoutMode ?? 'site'; // auth | app | site
$authPage = $authPage ?? '';
$flashMessages = flash_get();
$loggedIn = is_logged_in();
$user = $loggedIn ? current_user() : null;
$unreadNotifications = $loggedIn && $user ? notification_unread_count((int) $user['id']) : 0;

$mainClass = match ($layoutMode) {
    'auth' => 'flex-1 w-full',
    'app'  => 'flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 sm:py-10',
    default => 'flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 sm:py-10',
};

$isAuthLayout = $layoutMode === 'auth';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= page_title($pageTitle) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    boxShadow: {
                        softxl: '0 20px 60px rgba(0,0,0,.30)',
                        glow: '0 10px 40px rgba(34,211,238,.20)',
                        card: '0 12px 28px rgba(0,0,0,.20)',
                    }
                }
            }
        };
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }

        .site-shell {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.12), transparent 26%),
                radial-gradient(circle at top right, rgba(99, 102, 241, 0.10), transparent 24%),
                radial-gradient(circle at bottom center, rgba(34, 211, 238, 0.10), transparent 20%),
                linear-gradient(180deg, #06101f 0%, #0b1220 48%, #0f172a 100%);
        }

        .glass-panel {
            background: linear-gradient(180deg, rgba(255,255,255,0.09), rgba(255,255,255,0.045));
            border: 1px solid rgba(255,255,255,0.10);
            box-shadow: 0 16px 40px rgba(0,0,0,.22);
            backdrop-filter: blur(16px);
        }

        .premium-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid rgba(255,255,255,0.09);
            box-shadow: 0 12px 28px rgba(0,0,0,.20);
            backdrop-filter: blur(14px);
        }

        .auth-card-enter {
            animation: authCardEnter .55s ease-out both;
        }

        @keyframes authCardEnter {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .float-label {
            transform-origin: left top;
        }

        .float-input:focus + .float-label,
        .float-input:not(:placeholder-shown) + .float-label {
            transform: translateY(-0.7rem) scale(0.82);
            color: rgb(103 232 249);
        }

        .auth-glow::before,
        .auth-glow::after {
            content: '';
            position: absolute;
            border-radius: 9999px;
            filter: blur(80px);
            pointer-events: none;
            opacity: .7;
        }

        .auth-glow::before {
            width: 18rem;
            height: 18rem;
            left: -4rem;
            top: 12%;
            background: rgba(59, 130, 246, 0.22);
        }

        .auth-glow::after {
            width: 22rem;
            height: 22rem;
            right: -6rem;
            bottom: 8%;
            background: rgba(34, 211, 238, 0.18);
        }

        .auth-panel-grid {
            min-height: calc(100vh - 64px);
        }

        .auth-primary-btn {
            position: relative;
            overflow: hidden;
        }

        .auth-primary-btn::before {
            content: '';
            position: absolute;
            inset: auto 10% -18px 10%;
            height: 36px;
            border-radius: 9999px;
            background: rgba(34, 211, 238, 0.42);
            filter: blur(18px);
            opacity: 0;
            transition: opacity .28s ease;
        }

        .auth-primary-btn:hover::before,
        .auth-primary-btn:focus-visible::before {
            opacity: 1;
        }

        .mobile-nav-link-active {
            background: rgba(255,255,255,0.10);
            color: white;
            border-color: rgba(255,255,255,0.14);
        }

        .hero-orb {
            position: absolute;
            border-radius: 9999px;
            filter: blur(80px);
            pointer-events: none;
        }

        .nav-pill {
            border: 1px solid transparent;
        }

        .nav-pill:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.08);
        }

        .nav-pill-active {
            background: rgba(255,255,255,0.10);
            border-color: rgba(255,255,255,0.10);
            color: white;
        }

        .auth-header-shell {
            background:
                linear-gradient(180deg, rgba(2,6,23,.72), rgba(2,6,23,.58));
            border-bottom: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(22px);
        }

        .auth-brand-badge {
            background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 10px 30px rgba(0,0,0,.22);
            backdrop-filter: blur(12px);
        }

        .tt-top-loader {
            transform-origin: left center;
            transition: transform .22s ease, opacity .22s ease;
        }
    </style>
</head>
<body class="site-shell min-h-screen text-gray-100 flex flex-col">

<div id="tt-top-loader" class="tt-top-loader fixed left-0 top-0 z-[100] h-[3px] w-full scale-x-0 bg-gradient-to-r from-cyan-400 via-blue-400 to-indigo-400 opacity-0"></div>

<?php if ($isAuthLayout): ?>
<header class="sticky top-0 z-50 auth-header-shell">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between gap-4">
            <a href="<?= e(url('/index.php')) ?>" class="inline-flex items-center gap-3 text-cyan-100 transition hover:text-white">
                <span class="auth-brand-badge inline-flex h-10 w-10 items-center justify-center rounded-2xl text-cyan-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 3 2 8l10 5 8.2-4.1V15h1.6V8L12 3Zm-6.4 9.3V16c0 1.5 2.8 3 6.4 3s6.4-1.5 6.4-3v-3.7L12 16l-6.4-3.7Z"/>
                    </svg>
                </span>
                <span>
                    <span class="block text-sm font-semibold tracking-wide text-cyan-200/80">Welcome to</span>
                    <span class="block text-lg font-extrabold tracking-tight"><?= e(APP_NAME) ?></span>
                </span>
            </a>

            <div class="flex items-center gap-2">
                <?php if ($authPage === 'login'): ?>
                    <a href="<?= e(url('/register.php')) ?>" class="rounded-xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-900/30 transition hover:scale-[1.03]">
                        Create account
                    </a>
                <?php else: ?>
                    <a href="<?= e(url('/login.php')) ?>" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-200 transition hover:bg-white/10 hover:text-white">
                        Log in
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<?php else: ?>
<header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/70 backdrop-blur-2xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-8">
                <a href="<?= e(url('/index.php')) ?>" class="shrink-0 text-lg sm:text-2xl font-extrabold tracking-tight text-cyan-100 hover:text-white transition">
                    <?= e(APP_NAME) ?>
                </a>

                <nav class="hidden md:flex items-center gap-2 text-sm font-semibold">
                    <a href="<?= e(url('/index.php')) ?>" class="nav-pill rounded-xl px-4 py-2 transition <?= nav_is_active('index.php') ? 'nav-pill-active' : 'text-gray-300' ?>">Home</a>

                    <?php if ($loggedIn && $user): ?>
                        <a href="<?= e(url('/dashboard.php')) ?>" class="nav-pill rounded-xl px-4 py-2 transition <?= nav_is_active('dashboard.php') ? 'nav-pill-active' : 'text-gray-300' ?>">Dashboard</a>
                        <a href="<?= e(url('/community.php')) ?>" class="nav-pill rounded-xl px-4 py-2 transition <?= nav_is_active('community.php') ? 'nav-pill-active' : 'text-gray-300' ?>">Community</a>
                        <a href="<?= e(url('/profile.php?id=' . (int) $user['id'])) ?>" class="nav-pill rounded-xl px-4 py-2 transition <?= nav_is_active('profile.php') ? 'nav-pill-active' : 'text-gray-300' ?>">Profile</a>
                        <a href="<?= e(url('/notifications.php')) ?>" class="nav-pill relative rounded-xl px-4 py-2 transition <?= nav_is_active('notifications.php') ? 'nav-pill-active' : 'text-gray-300' ?>">
                            Notifications
                            <span
                                id="tt-notification-badge-desktop"
                                class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-cyan-400 px-1.5 py-0.5 text-[10px] font-bold text-gray-950 <?= $unreadNotifications > 0 ? '' : 'hidden' ?>"
                            >
                                <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
                            </span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="hidden md:flex items-center gap-3">
                <?php if ($loggedIn && $user): ?>
                    <span class="hidden lg:inline text-sm text-gray-400">
                        Hi, <strong class="text-white"><?= e($user['username']) ?></strong>
                    </span>

                    <form method="POST" action="<?= e(url('/logout.php')) ?>" class="inline" data-tt-form-submit>
                        <?= csrf_field() ?>
                        <button
                            type="submit"
                            data-tt-submit-btn
                            data-tt-loading-text="Logging out..."
                            class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-200 transition hover:bg-white/10 hover:text-white"
                        >
                            Log out
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login.php')) ?>" class="rounded-xl px-3 py-2 text-sm font-semibold text-gray-200 transition hover:bg-white/5 hover:text-white">
                        Log in
                    </a>
                    <a href="<?= e(url('/register.php')) ?>" class="rounded-xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-900/30 transition hover:scale-[1.03]">
                        Register
                    </a>
                <?php endif; ?>
            </div>

            <button
                type="button"
                class="md:hidden inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-gray-200 hover:bg-white/10"
                aria-label="Toggle navigation"
                data-mobile-nav-toggle
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z"/>
                </svg>
            </button>
        </div>

        <div id="mobile-nav-panel" class="md:hidden hidden border-t border-white/10 py-3">
            <nav class="flex flex-col gap-2 text-sm font-semibold">
                <a href="<?= e(url('/index.php')) ?>" class="rounded-xl border px-3 py-2 <?= nav_is_active('index.php') ? 'mobile-nav-link-active' : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white' ?>">Home</a>

                <?php if ($loggedIn && $user): ?>
                    <a href="<?= e(url('/dashboard.php')) ?>" class="rounded-xl border px-3 py-2 <?= nav_is_active('dashboard.php') ? 'mobile-nav-link-active' : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white' ?>">Dashboard</a>
                    <a href="<?= e(url('/community.php')) ?>" class="rounded-xl border px-3 py-2 <?= nav_is_active('community.php') ? 'mobile-nav-link-active' : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white' ?>">Community</a>
                    <a href="<?= e(url('/profile.php?id=' . (int) $user['id'])) ?>" class="rounded-xl border px-3 py-2 <?= nav_is_active('profile.php') ? 'mobile-nav-link-active' : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white' ?>">Profile</a>
                    <a href="<?= e(url('/notifications.php')) ?>" class="rounded-xl border px-3 py-2 <?= nav_is_active('notifications.php') ? 'mobile-nav-link-active' : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                        Notifications
                        <span
                            id="tt-notification-badge-mobile"
                            class="<?= $unreadNotifications > 0 ? '' : 'hidden' ?>"
                        ><?= $unreadNotifications > 0 ? ' (' . ($unreadNotifications > 99 ? '99+' : $unreadNotifications) . ')' : '' ?></span>
                    </a>

                    <form method="POST" action="<?= e(url('/logout.php')) ?>" class="pt-2" data-tt-form-submit>
                        <?= csrf_field() ?>
                        <button
                            type="submit"
                            data-tt-submit-btn
                            data-tt-loading-text="Logging out..."
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-left text-gray-200 hover:bg-white/10"
                        >
                            Log out
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login.php')) ?>" class="rounded-xl border border-transparent px-3 py-2 text-gray-300 hover:bg-white/5 hover:text-white">Log in</a>
                    <a href="<?= e(url('/register.php')) ?>" class="rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-3 py-2.5 text-white">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
<?php endif; ?>

<?php if (!empty($flashMessages)): ?>
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
        <?php foreach ($flashMessages as $type => $message): ?>
            <?php
                $styles = match($type) {
                    'success' => 'bg-green-900/40 border-green-700 text-green-300',
                    'error'   => 'bg-red-900/40 border-red-700 text-red-300',
                    'warning' => 'bg-yellow-900/40 border-yellow-700 text-yellow-300',
                    default   => 'bg-blue-900/40 border-blue-700 text-blue-300',
                };
            ?>
            <div class="rounded-2xl border px-4 py-3 text-sm <?= $styles ?>">
                <?= e($message) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main class="<?= e($mainClass) ?>">
<?php if (!$isAuthLayout): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('[data-mobile-nav-toggle]');
    var panel = document.getElementById('mobile-nav-panel');

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');
        });
    }
});
</script>
<?php endif; ?>

<script>
(function () {
    function startTopLoader() {
        var loader = document.getElementById('tt-top-loader');
        if (!loader) return;
        loader.classList.remove('opacity-0', 'scale-x-0');
        loader.classList.add('opacity-100');
        loader.style.transform = 'scaleX(0.55)';
    }

    function finishTopLoader() {
        var loader = document.getElementById('tt-top-loader');
        if (!loader) return;
        loader.style.transform = 'scaleX(1)';
        setTimeout(function () {
            loader.classList.add('opacity-0');
            loader.style.transform = 'scaleX(0)';
        }, 180);
    }

    document.addEventListener('DOMContentLoaded', finishTopLoader);
    window.addEventListener('pageshow', finishTopLoader);

    document.querySelectorAll('form[data-tt-form-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            startTopLoader();

            var btn = form.querySelector('[data-tt-submit-btn]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.dataset.ttOriginalText = btn.textContent || '';
                btn.textContent = btn.getAttribute('data-tt-loading-text') || 'Please wait...';
            }
        });
    });

    document.querySelectorAll('a[href]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var href = link.getAttribute('href') || '';
            var target = link.getAttribute('target') || '';
            var isHashOnly = href.startsWith('#');
            var isJs = href.startsWith('javascript:');
            var isExternal = /^https?:\/\//i.test(href) && !href.includes(window.location.host);

            if (event.defaultPrevented || target === '_blank' || isHashOnly || isJs || isExternal) {
                return;
            }

            startTopLoader();
        });
    });

    <?php if ($loggedIn && $user): ?>
    (function () {
        var desktopBadge = document.getElementById('tt-notification-badge-desktop');
        var mobileBadge = document.getElementById('tt-notification-badge-mobile');

        function renderBadgeValue(count) {
            if (count > 99) return '99+';
            return String(count);
        }

        function applyBadge(count) {
            if (desktopBadge) {
                if (count > 0) {
                    desktopBadge.textContent = renderBadgeValue(count);
                    desktopBadge.classList.remove('hidden');
                } else {
                    desktopBadge.classList.add('hidden');
                }
            }

            if (mobileBadge) {
                if (count > 0) {
                    mobileBadge.textContent = ' (' + renderBadgeValue(count) + ')';
                    mobileBadge.classList.remove('hidden');
                } else {
                    mobileBadge.textContent = '';
                    mobileBadge.classList.add('hidden');
                }
            }
        }

        function pollNotifications() {
            fetch('<?= e(url('/notifications-poll.php')) ?>', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Polling failed');
                }
                return response.json();
            })
            .then(function (data) {
                if (data && data.success) {
                    applyBadge(Number(data.unread_count || 0));
                    document.dispatchEvent(new CustomEvent('tt:notifications-updated', { detail: data }));
                }
            })
            .catch(function () {});
        }

        pollNotifications();
        setInterval(pollNotifications, 20000);
    })();
    <?php endif; ?>
})();
</script>