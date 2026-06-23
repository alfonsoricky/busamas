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
            <!-- Update Terbaru (Aman) -->
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-100">
                        <svg class="h-5 w-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-ink">Update Terbaru</h2>
                </div>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Sinkronisasi data komisi, PNL, pembelian barang, dan pengeluaran operasional dari file Excel terbaru. <strong class="text-ink">Invoice yang sudah ada di database tidak akan dihapus.</strong>
                </p>

                <div class="mt-4 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm leading-6 text-teal-900">
                    <strong>PENTING:</strong> Pastikan file <span class="font-semibold text-ink">PENJUALAN-2026.xlsx</span> terbaru sudah diunggah ke folder <span class="font-semibold text-ink">storage/</span> sebelum menjalankan update ini.
                </div>

                <form method="post" action="<?= e(url('/db-maintenance')) ?>" class="mt-5">
                    <input type="hidden" name="action" value="update-latest">
                    <button class="rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                        Jalankan Update Terbaru
                    </button>
                </form>
            </div>

            <!-- Reset & Update Hosting (Truncate + Seed Ulang) -->
            <div class="rounded-lg border border-orange-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100">
                        <svg class="h-5 w-5 text-orange-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-ink">Reset & Update Hosting</h2>
                </div>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Hapus semua tabel dan buat ulang dari seed snapshot terbaru, lalu sinkronisasi data dari Excel. <strong class="text-orange-700">Semua invoice yang dibuat langsung di hosting (di luar seed) akan terhapus.</strong>
                </p>

                <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm leading-6 text-orange-900">
                    <strong>PERINGATAN:</strong> Gunakan ini hanya jika database hosting perlu di-reset total. Pastikan seed snapshot sudah diperbarui.
                </div>

                <form method="post" action="<?= e(url('/db-maintenance')) ?>" class="mt-5" onsubmit="return confirm('Yakin ingin mereset database? Semua data yang belum di-seed akan hilang.')">
                    <input type="hidden" name="action" value="update-hosting">
                    <button class="rounded-lg bg-orange-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                        Reset & Jalankan Ulang Database
                    </button>
                </form>
            </div>

            <!-- Uji Coba & Hapus Data Test -->
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-100">
                        <svg class="h-5 w-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-ink">Uji Coba & Hapus Data Test</h2>
                </div>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Gunakan alat ini untuk membuat data invoice test baru langsung di database hosting dan menyinkronkannya ke Google Drive & Sheets, serta membersihkannya kembali setelah selesai pengujian.
                </p>

                <div class="mt-5 flex flex-wrap gap-4">
                    <form method="post" action="<?= e(url('/db-maintenance')) ?>">
                        <input type="hidden" name="action" value="create-test-data">
                        <button class="rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                            Buat Data Test Baru
                        </button>
                    </form>

                    <form method="post" action="<?= e(url('/db-maintenance')) ?>" onsubmit="return confirm('Yakin ingin menghapus semua invoice uji coba/test dari database, Google Drive, dan Google Sheets?')">
                        <input type="hidden" name="action" value="delete-test-data">
                        <button class="rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            Hapus Semua Data Test
                        </button>
                    </form>
                </div>
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
