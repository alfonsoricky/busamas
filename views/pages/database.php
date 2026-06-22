<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Maintenance</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Database</h1>
        <p class="mt-4 max-w-2xl leading-7 text-stone-600">
            Jalankan migrasi schema terbaru dan isi ulang data seed dari snapshot database lokal.
        </p>
    </div>

    <?php $result = $databaseMaintenance['result'] ?? null; ?>

    <?php if (! ($databaseMaintenance['database_connected'] ?? false)): ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-900">
            <p class="font-semibold">Database belum terkoneksi.</p>
            <p class="mt-1">Buat file <span class="font-semibold">.env</span> di root project hosting, lalu isi DB_HOST, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD sesuai database Hostinger.</p>
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

    <div class="grid gap-6 lg:grid-cols-[1fr_0.85fr]">
        <div class="space-y-6">
            <!-- Update Database Hosting -->
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-ink">Update Database Hosting</h2>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Tombol ini akan mensinkronisasikan seluruh data di database hosting Anda. Ini mencakup pembuatan ulang struktur tabel, pengisian data awal dari seed snapshot terbaru, serta penyinkronan data komisi, PNL, pembelian barang, dan pengeluaran operasional secara otomatis dari file Excel di folder storage.
                </p>

                <div class="mt-5 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm leading-6 text-teal-900">
                    <strong>PENTING:</strong> Pastikan Anda telah mengunggah file <span class="font-semibold text-ink">PENJUALAN-2026.xlsx</span> terbaru ke folder <span class="font-semibold text-ink">storage/</span> di hosting sebelum menjalankan update ini.
                </div>

                <form method="post" action="<?= e(url('/db-maintenance')) ?>" class="mt-5">
                    <input type="hidden" name="action" value="update-hosting">
                    <button class="rounded-lg bg-brand px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                        Jalankan Update Database
                    </button>
                </form>
            </div>
        </div>

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
        </div>
    </div>

    <?php if (is_array($result) && isset($result['output'])): ?>
        <div class="mt-6 rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-ink">Output Script</h2>
            <pre class="mt-4 overflow-x-auto rounded-lg bg-stone-950 p-4 text-xs leading-6 text-stone-100"><?= e((string) $result['output']) ?></pre>
        </div>
    <?php endif; ?>

    <div class="mt-6 rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-ink">Jumlah Data Saat Ini</h2>
        <?php if (empty($databaseMaintenance['table_counts'] ?? [])): ?>
            <p class="mt-3 text-sm leading-6 text-stone-600">Jumlah data akan muncul setelah koneksi database berhasil.</p>
        <?php else: ?>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <?php foreach (($databaseMaintenance['table_counts'] ?? []) as $table => $count): ?>
                    <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500"><?= e((string) $table) ?></p>
                        <p class="mt-2 text-2xl font-bold text-ink"><?= $count === null ? '-' : e((string) $count) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
