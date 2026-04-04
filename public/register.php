<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_init();
require_guest();

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $state = register_process_submission();
    if ($state['redirect']) {
        flash_set('success', 'Account created successfully. You can now log in.');
        redirect('/login.php');
    }

    $errors = $state['errors'];
    $username = $state['username'];
    $email = $state['email'];
}

$pageTitle = 'Register';
$layoutMode = 'auth';
$authPage = 'register';
require_once __DIR__ . '/../partials/app-shell-start.php';
?>

<div class="relative flex min-h-[calc(100vh-4rem)] items-center justify-center px-6 py-12 sm:px-8">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute left-[8%] top-[12%] h-40 w-40 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute bottom-[8%] right-[10%] h-52 w-52 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="absolute left-1/2 top-1/3 h-44 w-44 -translate-x-1/2 rounded-full bg-blue-500/10 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <div class="rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.10),rgba(255,255,255,0.04))] p-8 shadow-[0_30px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl sm:p-10">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-400/20 bg-cyan-400/10 text-cyan-300 shadow-lg shadow-cyan-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2a5 5 0 0 1 5 5v1h.5A2.5 2.5 0 0 1 20 10.5v9A2.5 2.5 0 0 1 17.5 22h-11A2.5 2.5 0 0 1 4 19.5v-9A2.5 2.5 0 0 1 6.5 8H7V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v1h6V7a3 3 0 0 0-3-3Zm-5.5 6v9h11v-9h-11Z"/>
                    </svg>
                </div>

                <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Create account</h1>
                <p class="mt-3 text-sm leading-7 text-gray-400">
                    Join <?= e(APP_NAME) ?> and start building with the community.
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3">
                    <div class="space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <p class="text-sm text-red-200"><?= e($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(url('/register.php')) ?>" class="space-y-5" novalidate data-tt-form-submit>
                <?= csrf_field() ?>

                <div>
                    <label for="username" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                        Username
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= e($username) ?>"
                            required
                            minlength="3"
                            maxlength="32"
                            autocomplete="username"
                            class="w-full rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5 text-sm text-white outline-none transition duration-200 placeholder:text-gray-600 focus:border-cyan-400 focus:bg-white/[0.06] focus:ring-4 focus:ring-cyan-500/15"
                            placeholder="Choose a username"
                        >
                    </div>
                </div>

                <div>
                    <label for="email" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                        Email
                    </label>
                    <div class="relative">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($email) ?>"
                            required
                            autocomplete="email"
                            class="w-full rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5 text-sm text-white outline-none transition duration-200 placeholder:text-gray-600 focus:border-cyan-400 focus:bg-white/[0.06] focus:ring-4 focus:ring-cyan-500/15"
                            placeholder="Enter your email"
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="<?= PASSWORD_MIN_LENGTH ?>"
                            pattern="(?=.*[A-Z])(?=.*[0-9]).{<?= (int) PASSWORD_MIN_LENGTH ?>,}"
                            title="Minimum <?= (int) PASSWORD_MIN_LENGTH ?> characters with one uppercase and one number"
                            autocomplete="new-password"
                            class="w-full rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5 pr-14 text-sm text-white outline-none transition duration-200 placeholder:text-gray-600 focus:border-cyan-400 focus:bg-white/[0.06] focus:ring-4 focus:ring-cyan-500/15"
                            placeholder="Create a password"
                        >

                        <button
                            type="button"
                            class="absolute inset-y-0 right-2 my-auto inline-flex h-10 w-10 items-center justify-center rounded-xl text-gray-400 transition hover:bg-white/5 hover:text-cyan-300"
                            data-toggle-password="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 password-eye-open" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 5c5.23 0 9.27 3.36 10.82 6.47a1.2 1.2 0 0 1 0 1.06C21.27 15.64 17.23 19 12 19S2.73 15.64 1.18 12.53a1.2 1.2 0 0 1 0-1.06C2.73 8.36 6.77 5 12 5Zm0 2C7.79 7 4.42 9.55 3.2 12 4.42 14.45 7.79 17 12 17s7.58-2.55 8.8-5C19.58 9.55 16.21 7 12 7Zm0 1.75A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Zm0 2A1.25 1.25 0 1 0 13.25 12 1.25 1.25 0 0 0 12 10.75Z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5 password-eye-closed" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3.53 2.47 2.47 3.53l3.02 3.02C3.61 7.85 2.1 9.76 1.18 11.47a1.2 1.2 0 0 0 0 1.06C2.73 15.64 6.77 19 12 19a11.7 11.7 0 0 0 4.83-.98l3.64 3.64 1.06-1.06L3.53 2.47ZM12 17c-4.21 0-7.58-2.55-8.8-5 .73-1.46 1.98-3.11 3.75-4.26l1.6 1.6A3.99 3.99 0 0 0 12 16a3.94 3.94 0 0 0 1.65-.36l1.63 1.63A9.7 9.7 0 0 1 12 17Zm0-10c5.23 0 9.27 3.36 10.82 6.47a1.2 1.2 0 0 1 0 1.06 14.34 14.34 0 0 1-2.95 3.69l-1.43-1.43A12.09 12.09 0 0 0 20.8 12C19.58 9.55 16.21 7 12 7a9.78 9.78 0 0 0-2.6.34L7.74 5.68A11.65 11.65 0 0 1 12 5Zm-.14 2.01 4.27 4.27A3.95 3.95 0 0 0 16 12a4 4 0 0 0-4-4c-.05 0-.09 0-.14.01Zm-2 1.13 3 3A1.98 1.98 0 0 1 9.86 10.14Z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        At least <?= (int) PASSWORD_MIN_LENGTH ?> characters, with one uppercase letter and one number.
                    </p>
                </div>

                <div>
                    <label for="confirm" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="confirm"
                            name="confirm"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5 pr-14 text-sm text-white outline-none transition duration-200 placeholder:text-gray-600 focus:border-cyan-400 focus:bg-white/[0.06] focus:ring-4 focus:ring-cyan-500/15"
                            placeholder="Confirm your password"
                        >

                        <button
                            type="button"
                            class="absolute inset-y-0 right-2 my-auto inline-flex h-10 w-10 items-center justify-center rounded-xl text-gray-400 transition hover:bg-white/5 hover:text-cyan-300"
                            data-toggle-password="confirm"
                            aria-label="Show confirm password"
                            aria-pressed="false"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 password-eye-open" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 5c5.23 0 9.27 3.36 10.82 6.47a1.2 1.2 0 0 1 0 1.06C21.27 15.64 17.23 19 12 19S2.73 15.64 1.18 12.53a1.2 1.2 0 0 1 0-1.06C2.73 8.36 6.77 5 12 5Zm0 2C7.79 7 4.42 9.55 3.2 12 4.42 14.45 7.79 17 12 17s7.58-2.55 8.8-5C19.58 9.55 16.21 7 12 7Zm0 1.75A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Zm0 2A1.25 1.25 0 1 0 13.25 12 1.25 1.25 0 0 0 12 10.75Z"/>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5 password-eye-closed" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3.53 2.47 2.47 3.53l3.02 3.02C3.61 7.85 2.1 9.76 1.18 11.47a1.2 1.2 0 0 0 0 1.06C2.73 15.64 6.77 19 12 19a11.7 11.7 0 0 0 4.83-.98l3.64 3.64 1.06-1.06L3.53 2.47ZM12 17c-4.21 0-7.58-2.55-8.8-5 .73-1.46 1.98-3.11 3.75-4.26l1.6 1.6A3.99 3.99 0 0 0 12 16a3.94 3.94 0 0 0 1.65-.36l1.63 1.63A9.7 9.7 0 0 1 12 17Zm0-10c5.23 0 9.27 3.36 10.82 6.47a1.2 1.2 0 0 1 0 1.06 14.34 14.34 0 0 1-2.95 3.69l-1.43-1.43A12.09 12.09 0 0 0 20.8 12C19.58 9.55 16.21 7 12 7a9.78 9.78 0 0 0-2.6.34L7.74 5.68A11.65 11.65 0 0 1 12 5Zm-.14 2.01 4.27 4.27A3.95 3.95 0 0 0 16 12a4 4 0 0 0-4-4c-.05 0-.09 0-.14.01Zm-2 1.13 3 3A1.98 1.98 0 0 1 9.86 10.14Z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    data-tt-submit-btn
                    data-tt-loading-text="Creating account..."
                    class="w-full rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-500 to-cyan-400 py-3.5 text-sm font-bold text-white shadow-[0_12px_30px_rgba(6,182,212,0.28)] transition duration-300 hover:scale-[1.015] hover:shadow-[0_18px_38px_rgba(6,182,212,0.34)] active:scale-[0.99]"
                >
                    Create account
                </button>
            </form>

            <div class="mt-8 border-t border-white/10 pt-6 text-center">
                <p class="text-sm text-gray-400">
                    Already onboard?
                    <a href="<?= e(url('/login.php')) ?>" class="font-semibold text-cyan-400 transition hover:text-cyan-300">
                        Log in
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var inputId = button.getAttribute('data-toggle-password');
            var input = document.getElementById(inputId);
            if (!input) {
                return;
            }

            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');

            var openIcon = button.querySelector('.password-eye-open');
            var closedIcon = button.querySelector('.password-eye-closed');

            if (openIcon && closedIcon) {
                openIcon.classList.toggle('hidden', isPassword);
                closedIcon.classList.toggle('hidden', !isPassword);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../partials/app-shell-end.php'; ?>