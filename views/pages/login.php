<section class="mx-auto grid min-h-[calc(100vh-9rem)] max-w-md place-items-center px-4 py-10 sm:px-6 lg:px-8">
    <div class="w-full rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Busamas ERP</p>
            <h1 class="text-3xl font-bold text-ink">Login</h1>
            <p class="mt-2 text-sm leading-6 text-stone-600">Masuk menggunakan akun yang sudah terdaftar.</p>
        </div>

        <?php if (! empty($loginError)): ?>
            <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
                <?= e($loginError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/login')) ?>" class="space-y-4">
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-stone-700">Email</span>
                <input
                    type="email"
                    name="email"
                    value="<?= e($loginEmail ?? '') ?>"
                    required
                    autofocus
                    autocomplete="email"
                    class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                >
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-stone-700">Password</span>
                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                >
            </label>

            <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800">
                Login
            </button>
        </form>
    </div>
</section>
