<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

session_init();

$pageTitle = 'Welcome';
$layoutMode = 'site';
require_once dirname(__DIR__) . '/partials/app-shell-start.php';
?>

<section class="relative overflow-hidden rounded-[2rem] premium-card px-6 py-14 sm:px-10 sm:py-16 lg:px-14 lg:py-20">
    <div class="hero-orb left-[-4rem] top-[-4rem] h-56 w-56 bg-cyan-500/15"></div>
    <div class="hero-orb right-[-5rem] top-[10%] h-72 w-72 bg-indigo-500/12"></div>
    <div class="hero-orb bottom-[-5rem] left-[35%] h-64 w-64 bg-blue-500/12"></div>

    <div class="relative grid items-center gap-12 lg:grid-cols-[1.15fr_.85fr]">
        <div class="max-w-3xl">
            <span class="inline-flex items-center rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-cyan-300">
                Premium Developer Community
            </span>

            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                Build your network.
                <span class="block bg-gradient-to-r from-cyan-300 via-blue-300 to-indigo-300 bg-clip-text text-transparent">
                    Share your progress.
                </span>
                <span class="block">Grow with TechTrail.</span>
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-8 text-gray-300 sm:text-lg">
                A polished community platform for developers to post achievements, connect with other builders,
                showcase profiles, and stay active in a modern collaborative space.
            </p>

            <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                <?php if (is_logged_in()): ?>
                    <a href="<?= e(url('/dashboard.php')) ?>" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-500 to-cyan-400 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:scale-[1.02]">
                        Open Dashboard
                    </a>
                    <a href="<?= e(url('/community.php')) ?>" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-7 py-3.5 text-sm font-semibold text-gray-200 transition hover:border-white/25 hover:bg-white/10">
                        View Community
                    </a>
                <?php else: ?>
                    <a href="<?= e(url('/register.php')) ?>" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-500 to-cyan-400 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:scale-[1.02]">
                        Join the Community
                    </a>
                    <a href="<?= e(url('/login.php')) ?>" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-7 py-3.5 text-sm font-semibold text-gray-200 transition hover:border-white/25 hover:bg-white/10">
                        Log In
                    </a>
                <?php endif; ?>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="glass-panel rounded-2xl px-5 py-4">
                    <p class="text-sm font-semibold text-cyan-300">Profiles</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">Custom</p>
                    <p class="mt-1 text-xs text-gray-400">Showcase who you are</p>
                </div>
                <div class="glass-panel rounded-2xl px-5 py-4">
                    <p class="text-sm font-semibold text-cyan-300">Community</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">Active</p>
                    <p class="mt-1 text-xs text-gray-400">Posts, likes, comments</p>
                </div>
                <div class="glass-panel rounded-2xl px-5 py-4">
                    <p class="text-sm font-semibold text-cyan-300">Growth</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">Focused</p>
                    <p class="mt-1 text-xs text-gray-400">Track your journey</p>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-[2rem] p-6 sm:p-8">
            <div class="rounded-[1.5rem] border border-white/10 bg-black/20 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-white">Inside TechTrail</p>
                        <p class="mt-1 text-xs text-gray-500">Built for a cleaner developer experience</p>
                    </div>
                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-300">
                        Smooth UI
                    </span>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <p class="text-sm font-semibold text-white">Modern dashboard</p>
                        <p class="mt-2 text-xs leading-6 text-gray-400">
                            See your stats, recent activity, followers, and profile progress in one place.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Auth</p>
                            <p class="mt-2 text-lg font-bold text-white">Secure</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500">Profiles</p>
                            <p class="mt-2 text-lg font-bold text-white">Premium</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-cyan-500/20 bg-cyan-500/10 p-4">
                        <p class="text-xs uppercase tracking-wider text-cyan-200/80">Next upgrade</p>
                        <p class="mt-2 text-sm font-medium text-cyan-100">
                            Supabase, Cloudflare, and image storage integration are ready for the next phase.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3">
    <div class="premium-card rounded-[1.75rem] p-6">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/15 text-2xl">🧵</div>
        <h2 class="mt-5 text-xl font-bold text-white">Discussions</h2>
        <p class="mt-3 text-sm leading-7 text-gray-300">
            Start thoughtful conversations around technology, progress, and developer life.
        </p>
    </div>

    <div class="premium-card rounded-[1.75rem] p-6">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/15 text-2xl">🚀</div>
        <h2 class="mt-5 text-xl font-bold text-white">Projects</h2>
        <p class="mt-3 text-sm leading-7 text-gray-300">
            Share what you are building and let your work become part of your public profile.
        </p>
    </div>

    <div class="premium-card rounded-[1.75rem] p-6">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/15 text-2xl">🏆</div>
        <h2 class="mt-5 text-xl font-bold text-white">Growth</h2>
        <p class="mt-3 text-sm leading-7 text-gray-300">
            Build momentum through posts, achievements, and stronger community engagement.
        </p>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/partials/app-shell-end.php'; ?>