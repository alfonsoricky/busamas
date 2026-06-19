<section class="bg-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-20">
        <div class="flex flex-col justify-center">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Starter project</p>
            <h1 class="max-w-3xl text-4xl font-bold leading-tight text-ink sm:text-5xl">
                PHP native yang bersih untuk mulai membangun Busamas.
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-stone-600">
                Struktur ini memisahkan konfigurasi, helper, layout, partial, dan halaman supaya perubahan kecil tidak membuat kode cepat berantakan.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?= e(url('/about')) ?>" class="rounded-lg bg-brand px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                    Lihat Struktur
                </a>
                <a href="<?= e(url('/contact')) ?>" class="rounded-lg border border-stone-300 px-5 py-3 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Hubungi Kami
                </a>
            </div>
        </div>

        <div class="rounded-lg border border-stone-200 bg-stone-50 p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <p class="text-sm font-semibold text-ink">Ringkasan Project</p>
                    <p class="text-sm text-stone-500">Siap dikembangkan bertahap</p>
                </div>
                <span class="rounded-md bg-teal-100 px-3 py-1 text-xs font-semibold text-brand">Online</span>
            </div>

            <div class="grid gap-3">
                <?php
                    $items = [
                        ['label' => 'Routing', 'value' => 'public/index.php'],
                        ['label' => 'Layout', 'value' => 'views/layouts/app.php'],
                        ['label' => 'Helper', 'value' => 'app/helpers.php'],
                        ['label' => 'Styling', 'value' => 'Tailwind CDN'],
                    ];
                ?>

                <?php foreach ($items as $item): ?>
                    <div class="flex items-center justify-between rounded-lg border border-stone-200 bg-white px-4 py-3">
                        <span class="text-sm font-medium text-stone-600"><?= e($item['label']) ?></span>
                        <span class="text-sm font-semibold text-ink"><?= e($item['value']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="border-y border-stone-200 bg-stone-100">
    <div class="mx-auto grid max-w-6xl gap-4 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-2xl font-bold text-brand">01</p>
            <h2 class="mt-3 text-lg font-semibold">Mudah dibaca</h2>
            <p class="mt-2 text-sm leading-6 text-stone-600">Setiap bagian punya folder dan tanggung jawab yang jelas.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-2xl font-bold text-coral">02</p>
            <h2 class="mt-3 text-lg font-semibold">Mudah diubah</h2>
            <p class="mt-2 text-sm leading-6 text-stone-600">Navbar, footer, dan layout bisa diedit sekali untuk semua halaman.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-2xl font-bold text-ink">03</p>
            <h2 class="mt-3 text-lg font-semibold">Mudah ditambah</h2>
            <p class="mt-2 text-sm leading-6 text-stone-600">Tambah route baru di satu array, lalu buat file view-nya.</p>
        </div>
    </div>
</section>
