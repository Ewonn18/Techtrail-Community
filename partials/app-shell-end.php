<?php
/**
 * TechTrail Community v2
 * App Shell — Closing partial
 */
$isAuthLayout = isset($layoutMode) && $layoutMode === 'auth';
?>
</main>

<?php if ($isAuthLayout): ?>
<footer class="mt-auto border-t border-white/8 bg-slate-950/35 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
        <div class="flex items-center gap-4">
            <a href="<?= e(url('/index.php')) ?>" class="hover:text-gray-300 transition">Back to home</a>
        </div>
    </div>
</footer>
<?php else: ?>
<footer class="mt-auto border-t border-white/10 bg-slate-950/60 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-3 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — v<?= e(APP_VERSION) ?></p>
        <nav class="flex flex-wrap items-center gap-4">
            <a href="<?= e(url('/index.php')) ?>" class="hover:text-gray-300 transition">Home</a>
            <a href="<?= e(url('/login.php')) ?>" class="hover:text-gray-300 transition">Login</a>
            <a href="<?= e(url('/register.php')) ?>" class="hover:text-gray-300 transition">Register</a>
        </nav>
    </div>
</footer>
<?php endif; ?>

</body>
</html>