<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Maintenance</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Database</h1>
        <p class="mt-4 max-w-2xl leading-7 text-stone-600">
            Restore database hosting agar sama persis dengan snapshot database lokal yang tersimpan di <span class="font-semibold text-ink">database/seed-data.sql</span>.
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

    <div class="mb-6 rounded-lg border border-teal-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-100">
                <svg class="h-5 w-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l2.25 2.25L15 12m-9.75 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-ink">Update PNL Komisi Sales</h2>
        </div>
        <p class="mt-3 text-sm leading-6 text-stone-600">
            Terapkan rumus PNL komisi sales terbaru: gunakan nominal komisi sales terbayar + belum terbayar jika tersedia, lalu posting ulang jurnal akuntansi.
        </p>

        <form method="post" action="<?= e(url('/db-maintenance')) ?>" class="mt-5" onsubmit="return confirm('Jalankan update PNL komisi sales sekarang?')">
            <input type="hidden" name="action" value="update-pnl-sales-commission">
            <button class="rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                Update PNL Komisi Sales
            </button>
        </form>
    </div>

    <div class="mb-6 rounded-lg border border-teal-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-100">
                <svg class="h-5 w-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <h2 class="text-lg font-bold text-ink">Update Operational 2026</h2>
        </div>
        <p class="mt-3 text-sm leading-6 text-stone-600">
            Sinkronisasi ulang operational 2026 dari <strong class="text-ink">PENJUALAN-2026.xlsx</strong>. Bulan PNL akan mengikuti blok/subtotal operational di Excel, termasuk transaksi tanggal Februari yang berada di blok Januari.
        </p>

        <form method="post" action="<?= e(url('/db-maintenance')) ?>" class="mt-5" onsubmit="return confirm('Jalankan update operational 2026 dari Excel sekarang?')">
            <input type="hidden" name="action" value="update-2026-operational-latest">
            <button class="rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                Update Operational 2026
            </button>
        </form>
    </div>

    <div class="mb-6 rounded-lg border border-orange-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100">
                <svg class="h-5 w-5 text-orange-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-ink">Restore Snapshot Lokal</h2>
        </div>
        <p class="mt-3 text-sm leading-6 text-stone-600">
            Tombol ini akan drop dan buat ulang tabel hosting, lalu mengisi data dari snapshot lokal terbaru. Semua data hosting yang berbeda dari lokal akan tertimpa.
        </p>

        <form method="post" action="<?= e(url('/db-maintenance')) ?>" class="mt-5" onsubmit="return confirm('Yakin restore database hosting dari snapshot lokal? Semua data hosting saat ini akan tertimpa.')">
            <input type="hidden" name="action" value="restore-local-snapshot">
            <button class="rounded-lg bg-orange-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                Restore Database dari Snapshot Lokal
            </button>
        </form>
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
