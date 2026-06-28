<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Dashboard</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Laporan Utama</h1>
        <p class="mt-4 max-w-2xl leading-7 text-stone-600">
            Analisis data penjualan, profit, piutang, hutang, serta laporan laba rugi (profit & loss) secara komprehensif.
        </p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Laporan Penjualan -->
        <a href="<?= e(url('/laporan/penjualan')) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h.007v.008H3.75V4.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3 15.75h.007v.008H3v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 18.75V15.75m0 3h-.75m.75 0h9m-9 0V18.75m3 0v-5.25m0 5.25h.75m-.75 0h-3m3 0H12m-3 0v-2.25m0 2.25h.75m-.75 0h-3m3 0H15m-3 0v-8.25m0 8.25h.75m-.75 0h-3m3 0h6m-3 0v-11.25m0 11.25h.75m-.75 0h-3m3 0h3" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-ink group-hover:text-brand">Laporan Penjualan</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">
                Data transaksi penjualan dikelompokkan per Invoice, per Customer, per Produk, dan per Sales Agent.
            </p>
            <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                Buka Laporan
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>
        </a>

        <!-- Laporan Profit & Loss -->
        <a href="<?= e(url('/laporan/profit-loss')) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18h8.25a.75.75 0 0 1 .75.75v16.5a.75.75 0 0 1-.75.75H12m0-18H3.75a.75.75 0 0 0-.75.75v16.5a.75.75 0 0 0 .75.75H12m0 0V9m0-6v6m0 0h8.25" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-ink group-hover:text-brand">Laporan Profit & Loss</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">
                Pernyataan laba rugi komprehensif yang menampilkan pendapatan bersih, HPP, komisi, dan laba bersih.
            </p>
            <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                Buka Laporan
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>
        </a>

        <!-- Laporan Hutang -->
        <a href="<?= e(url('/laporan/hutang')) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-ink group-hover:text-brand">Laporan Hutang</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">
                Ringkasan kewajiban utang pembelian barang kepada supplier/vendor yang belum dilunasi.
            </p>
            <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                Buka Laporan
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>
        </a>

        <!-- Laporan Piutang -->
        <a href="<?= e(url('/laporan/piutang')) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-ink group-hover:text-brand">Laporan Piutang</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">
                Pemantauan piutang customer lewat analisis umur piutang (Aging Piutang) dan daftar invoice overdue.
            </p>
            <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                Buka Laporan
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>
        </a>

        <!-- Laporan Profit -->
        <a href="<?= e(url('/laporan/profit')) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h.007v.008H3.75V4.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3 15.75h.007v.008H3v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-ink group-hover:text-brand">Laporan Profit</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">
                Analisis margin profit kotor dan persentase keuntungan yang dikelompokkan Per Produk dan Per Customer.
            </p>
            <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                Buka Laporan
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>
        </a>

        <!-- Laporan Komisi -->
        <a href="<?= e(url('/laporan/komisi')) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-ink group-hover:text-brand">Laporan Komisi</h2>
            <p class="mt-2 text-sm leading-6 text-stone-500">
                Pemantauan komisi sales agent, manager, dan admin yang terbayar maupun sisa utang komisi.
            </p>
            <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                Buka Laporan
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>
        </a>
    </div>

    <div class="mt-10">
        <div class="mb-5">
            <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-brand">Akuntansi</p>
            <h2 class="text-2xl font-bold text-ink">COA, Jurnal, dan Neraca</h2>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            $accountingReports = [
                '/laporan/coa' => ['COA', 'Daftar akun default untuk mapping jurnal otomatis.'],
                '/laporan/jurnal' => ['Jurnal Umum', 'Jurnal otomatis dari invoice dan operasional.'],
                '/laporan/buku-besar' => ['Buku Besar', 'Mutasi debit kredit dan saldo berjalan per akun.'],
                '/laporan/neraca' => ['Neraca', 'Posisi aset, kewajiban, dan ekuitas dari jurnal.'],
                '/laporan/arus-kas' => ['Arus Kas', 'Kas masuk, kas keluar, dan saldo kas dari jurnal Kas / Bank.'],
            ];
            ?>
            <?php foreach ($accountingReports as $href => [$label, $description]): ?>
                <a href="<?= e(url($href)) ?>" class="group block rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-brand hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2.25 4.5H6.75A2.25 2.25 0 014.5 18.25V5.75A2.25 2.25 0 016.75 3.5h10.5a2.25 2.25 0 012.25 2.25v12.5a2.25 2.25 0 01-2.25 2.25z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-ink group-hover:text-brand"><?= e($label) ?></h3>
                    <p class="mt-2 text-sm leading-6 text-stone-500"><?= e($description) ?></p>
                    <div class="mt-4 flex items-center gap-1 text-sm font-semibold text-brand">
                        Buka
                        <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
