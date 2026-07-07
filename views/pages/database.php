<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Maintenance</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Database</h1>
        <p class="mt-4 max-w-2xl leading-7 text-stone-600">
            Pantau status snapshot database dan jumlah data saat ini.
        </p>
    </div>

    <?php $result = $databaseMaintenance['result'] ?? null; ?>

    <?php if (! ($databaseMaintenance['database_connected'] ?? false)): ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-900">
            <p class="font-semibold">Database belum terkoneksi.</p>
            <p class="mt-1">Buat file <span class="font-semibold">.env</span> di root project hosting, lalu isi DB_HOST, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD sesuai database hosting.</p>
        </div>
    <?php endif; ?>

    <?php if (is_array($result)): ?>
        <div class="mb-6 rounded-lg border <?= ($result['ok'] ?? false) ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-red-200 bg-red-50 text-red-900' ?> p-5 text-sm leading-6">
            <p class="font-semibold"><?= e((string) ($result['message'] ?? '')) ?></p>
            <?php if (($result['ok'] ?? false)): ?>
                <p class="mt-1">Statement dijalankan: <?= e((string) ($result['statements'] ?? 0)) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-[0.85fr_1fr]">
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-ink">Seed Snapshot</h2>
            <?php $seed = $databaseMaintenance['seed_file'] ?? []; ?>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <dt class="font-medium text-stone-500">File</dt>
                    <dd class="font-semibold text-ink"><?= e((string) ($seed['path'] ?? 'database/seed-data.sql')) ?></dd>
                </div>
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <dt class="font-medium text-stone-500">Status</dt>
                    <dd class="font-semibold <?= ($seed['exists'] ?? false) ? 'text-brand' : 'text-red-700' ?>">
                        <?= ($seed['exists'] ?? false) ? 'Tersedia' : 'Belum ada' ?>
                    </dd>
                </div>
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <dt class="font-medium text-stone-500">Ukuran</dt>
                    <dd class="font-semibold text-ink"><?= e(number_format((float) ($seed['size'] ?? 0), 0, ',', '.')) ?> bytes</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="font-medium text-stone-500">Update</dt>
                    <dd class="font-semibold text-ink"><?= e((string) ($seed['updated_at'] ?? '-')) ?></dd>
                </div>
            </dl>
            <div class="mt-5 flex flex-wrap gap-3">
            <form method="post">
                <input type="hidden" name="action" value="seed-today-july-3-2026">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark"
                    data-confirm-title="Jalankan Seeder Update Hari Ini?"
                    data-confirm-message="Seeder ini akan menyamakan update invoice, pembayaran parsial, prive, invoice 472-476, dan jurnal akuntansi terbaru."
                    data-confirm-ok="Jalankan"
                    data-confirm-cancel="Batal"
                >
                    Update Hari Ini
                </button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="seed-krisna-bonus-july-4-2026">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-stone-50"
                    data-confirm-title="Update Bonus Krisna?"
                    data-confirm-message="Seeder ini akan mengubah bonus Krisna pada invoice di screenshot menjadi terbayar tanggal 4 Juli 2026 dan memecah bonus Mei per invoice."
                    data-confirm-ok="Jalankan"
                    data-confirm-cancel="Batal"
                >
                    Bonus Krisna 4 Juli
                </button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="seed-customer-default-discounts">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-stone-50"
                    data-confirm-title="Update Default Diskon Customer?"
                    data-confirm-message="Seeder ini akan mengisi default diskon customer dari file PENJUALAN-2026 (9).xlsx dan mengosongkan diskon customer lain menjadi 0%."
                    data-confirm-ok="Jalankan"
                    data-confirm-cancel="Batal"
                >
                    Default Diskon Customer
                </button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="seed-penjualan-2026-11">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-stone-50"
                    data-confirm-title="Update PENJUALAN-2026 (11)?"
                    data-confirm-message="Seeder ini akan membuat invoice 477-482, update penjualan dan operational terakhir, memperbaiki ongkos campur bulan Juni tetap Hutang, serta mencegah gaji Juni dobel."
                    data-confirm-ok="Jalankan"
                    data-confirm-cancel="Batal"
                >
                    Penjualan 2026 (11)
                </button>
            </form>
            </div>
        </div>

        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-ink">Jumlah Data Saat Ini</h2>
            <?php if (empty($databaseMaintenance['table_counts'] ?? [])): ?>
                <p class="mt-3 text-sm leading-6 text-stone-600">Jumlah data akan muncul setelah koneksi database berhasil.</p>
            <?php else: ?>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <?php foreach (($databaseMaintenance['table_counts'] ?? []) as $table => $count): ?>
                        <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500"><?= e((string) $table) ?></p>
                            <p class="mt-2 text-2xl font-bold text-ink"><?= $count === null ? '-' : e((string) $count) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_array($result) && isset($result['output'])): ?>
        <div class="mt-6 rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-ink">Output Script</h2>
            <pre class="mt-4 overflow-x-auto rounded-lg bg-stone-950 p-4 text-xs leading-6 text-stone-100"><?= e((string) $result['output']) ?></pre>
        </div>
    <?php endif; ?>
</section>
