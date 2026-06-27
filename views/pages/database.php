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

    <div class="mb-6 rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-ink mb-4">Aksi Database</h2>
        <div class="divide-y divide-stone-100">
            <!-- Update Data 2026 Terbaru -->
            <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between first:pt-0">
                <div class="max-w-2xl">
                    <h3 class="font-semibold text-ink text-sm">Update Data 2026 Terbaru</h3>
                    <p class="mt-1 text-xs leading-5 text-stone-600">Sinkronisasi invoice 2026, detail barang invoice 463/464, operasional, dan posting ulang jurnal dari file Excel.</p>
                </div>
                <form method="POST" action="<?= e(url('/db-maintenance')) ?>" data-confirm-message="Jalankan update data 2026 terbaru di database ini?">
                    <input type="hidden" name="action" value="update-2026-latest-final">
                    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                        Jalankan Update
                    </button>
                </form>
            </div>

            <!-- Reset & Load Seeder -->
            <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl">
                    <h3 class="font-semibold text-ink text-sm">Jalankan Migrate & Seed (Reset Data)</h3>
                    <p class="mt-1 text-xs leading-5 text-stone-600">Menghapus semua tabel dan memulihkan data dari file snapshot seeder (<code class="bg-stone-100 px-1 py-0.5 rounded text-coral">database/seed-data.sql</code>).</p>
                </div>
                <form method="POST" action="<?= e(url('/db-maintenance')) ?>" data-confirm-message="PERHATIAN! Tindakan ini akan menghapus dan menimpa database saat ini dengan file seeder. Lanjutkan?">
                    <input type="hidden" name="action" value="migrate-seed">
                    <button type="submit" class="rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-850">
                        Jalankan Migrate & Seed
                    </button>
                </form>
            </div>

            <!-- Ekspor Database ke Seeder -->
            <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between last:pb-0">
                <div class="max-w-2xl">
                    <h3 class="font-semibold text-ink text-sm">Ekspor Database ke Seeder (Snapshot)</h3>
                    <p class="mt-1 text-xs leading-5 text-stone-600">Mengambil snapshot kondisi database saat ini dan menyimpannya kembali ke file seeder (<code class="bg-stone-100 px-1 py-0.5 rounded text-brand">database/seed-data.sql</code>) agar bisa di-commit ke Git.</p>
                </div>
                <form method="POST" action="<?= e(url('/db-maintenance')) ?>" data-confirm-message="Simpan snapshot database saat ini ke file seeder lokal?">
                    <input type="hidden" name="action" value="export-seeder">
                    <button type="submit" class="rounded-lg bg-stone-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-stone-900">
                        Ekspor ke Seeder
                    </button>
                </form>
            </div>
        </div>
    </div>

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
