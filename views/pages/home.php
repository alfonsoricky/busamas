<?php
$data = $dashboardData ?? [];
$revenue = $data['revenue'] ?? 0.0;
$profit = $data['profit'] ?? 0.0;
$piutang = $data['piutang'] ?? 0.0;
$hutang = $data['hutang'] ?? 0.0;
$top_produk = $data['top_produk'] ?? [];
$top_customer = $data['top_customer'] ?? [];
$recent_invoices = $data['recent_invoices'] ?? [];
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-brand">Ringkasan Eksekutif</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Dashboard Busamas</h1>
            <p class="mt-2 text-sm text-stone-600">Selamat datang kembali! Berikut ringkasan performa bisnis Anda berdasarkan data database saat ini.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-brand">
            <span class="h-1.5 w-1.5 rounded-full bg-brand animate-pulse"></span>
            Database Terkoneksi
        </span>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- KPI Summary Grid -->
    <div class="mb-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Revenue Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-stone-500">Total Omset</span>
                <span class="rounded bg-teal-50 p-2 text-brand">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h.007v.008H3.75V4.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3 15.75h.007v.008H3v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 18.75V15.75m0 3h-.75m.75 0h9m-9 0V18.75m3 0v-5.25m0 5.25h.75m-.75 0h-3m3 0H12m-3 0v-2.25m0 2.25h.75m-.75 0h-3m3 0H15m-3 0v-8.25m0 8.25h.75m-.75 0h-3m3 0h6m-3 0v-11.25m0 11.25h.75m-.75 0h-3m3 0h3" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-2xl font-bold text-ink"><?= rupiah($revenue) ?></p>
            <p class="mt-1 text-xs text-stone-400">Total pendapatan bruto</p>
        </div>

        <!-- Profit Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-stone-500">Estimasi Laba Bersih</span>
                <span class="rounded bg-teal-50 p-2 text-brand">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-2xl font-bold text-brand"><?= rupiah($profit) ?></p>
            <p class="mt-1 text-xs text-stone-400">Pendapatan dikurangi HPP & Komisi</p>
        </div>

        <!-- Piutang Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-stone-500">Total Piutang Dagang</span>
                <span class="rounded bg-orange-50 p-2 text-coral">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-2xl font-bold text-ink"><?= rupiah($piutang) ?></p>
            <p class="mt-1 text-xs text-stone-400">Tagihan customer unpaid</p>
        </div>

        <!-- Hutang Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-stone-500">Total Hutang Dagang</span>
                <span class="rounded bg-orange-50 p-2 text-coral">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-2xl font-bold text-coral"><?= rupiah($hutang) ?></p>
            <p class="mt-1 text-xs text-stone-400">Pembelian HPP belum lunas</p>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="mb-8 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-ink mb-4">Akses Cepat Operasional</h2>
        <div class="flex flex-wrap gap-3">
            <a href="<?= e(url('/invoice-create')) ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat Invoice Baru
            </a>
            <a href="<?= e(url('/laporan')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink shadow-sm transition hover:border-brand hover:text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V19.5a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25v-3.75ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V19.5A2.25 2.25 0 0 1 18 21.75h-2.25a2.25 2.25 0 0 1-2.25-2.25v-3.75Z" />
                </svg>
                Buka Menu Laporan
            </a>
            <a href="<?= e(url('/db-maintenance')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink shadow-sm transition hover:border-brand hover:text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-3.75m16.5 0v3.75m-16.5-3.75v3.75C3.75 16.153 7.444 18 12 18s8.25-1.847 8.25-4.125v-3.75" />
                </svg>
                Database Maintenance
            </a>
            <?php if (is_admin()): ?>
                <a href="<?= e(url('/settings')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink shadow-sm transition hover:border-brand hover:text-brand" title="Settings">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.592c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.075.041.149.084.222.129.325.199.73.229 1.076.091l1.186-.474a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.007.827c-.293.24-.438.613-.431.992a7.723 7.723 0 0 1 0 .352c-.007.379.138.752.431.992l1.007.827c.424.348.534.955.26 1.431l-1.296 2.247a1.125 1.125 0 0 1-1.37.49l-1.186-.474c-.346-.138-.751-.108-1.076.091a6.986 6.986 0 0 1-.222.129c-.332.184-.582.496-.645.87l-.213 1.281c-.09.542-.56.94-1.11.94h-2.592c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a6.956 6.956 0 0 1-.222-.129c-.325-.199-.73-.229-1.076-.091l-1.186.474a1.125 1.125 0 0 1-1.37-.49l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.007-.827c.293-.24.438-.613.431-.992a8.002 8.002 0 0 1 0-.352c.007-.379-.138-.752-.431-.992l-1.007-.827a1.125 1.125 0 0 1-.26-1.431l1.296-2.247a1.125 1.125 0 0 1 1.37-.49l1.186.474c.346.138.751.108 1.076-.091.073-.045.147-.088.222-.129.332-.184.582-.496.645-.87l.213-1.281Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Settings
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Recent Invoices Table (Col Span 2) -->
        <div class="lg:col-span-2 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-ink">5 Invoice Terkini</h2>
                <a href="<?= e(url('/invoices')) ?>" class="text-xs font-semibold text-brand hover:underline">Lihat Semua Invoice &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="bg-stone-50 uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-3 py-2.5 font-semibold">No. Invoice</th>
                            <th class="px-3 py-2.5 font-semibold">Tanggal</th>
                            <th class="px-3 py-2.5 font-semibold">Customer</th>
                            <th class="text-right px-3 py-2.5 font-semibold">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($recent_invoices)): ?>
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-stone-400">Belum ada invoice tercatat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_invoices as $inv): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-3 py-3 font-semibold text-brand">
                                        <a href="<?= e(url('/invoice-view?code=' . ($inv['nomor_invoice'] ?? ''))) ?>" class="hover:underline">
                                            <?= e($inv['nomor_invoice'] ?? '') ?>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-stone-600"><?= e($inv['tanggal_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-3 py-3 font-medium text-ink truncate max-w-[150px]"><?= e($inv['nama_customer'] ?? '') ?></td>
                                    <td class="text-right whitespace-nowrap px-3 py-3 font-bold text-ink"><?= rupiah($inv['total_harga_jual'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Performing Lists (Col Span 1) -->
        <div class="flex flex-col gap-6">
            <!-- Top Products -->
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-ink">Top 5 Produk Terlaris</h2>
                    <a href="<?= e(url('/laporan/profit?group=produk')) ?>" class="text-[10px] font-semibold text-brand hover:underline">Detail</a>
                </div>
                <div class="space-y-3">
                    <?php if (empty($top_produk)): ?>
                        <p class="text-xs text-stone-400 py-3 text-center">Belum ada data barang terjual.</p>
                    <?php else: ?>
                        <?php
                        $maxProdVal = count($top_produk) > 0 ? (float)($top_produk[0]['total_penjualan'] ?? 1.0) : 1.0;
                        foreach ($top_produk as $prod):
                            $val = (float)($prod['total_penjualan'] ?? 0);
                            $pct = $maxProdVal > 0 ? ($val / $maxProdVal) * 100 : 0;
                        ?>
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-ink truncate max-w-[180px]" title="<?= e(($prod['nama_barang_master'] ?? '') . ' ' . ($prod['ukuran_master'] ?? '')) ?>">
                                        <?= e($prod['nama_barang_master'] ?? 'Product') ?> <?= e($prod['ukuran_master'] ?? '') ?>
                                    </span>
                                    <span class="font-semibold text-stone-700"><?= rupiah($val) ?></span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-stone-100">
                                    <div style="width: <?= $pct ?>%" class="h-1.5 rounded-full bg-brand"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-ink">Top 5 Customer Terbesar</h2>
                    <a href="<?= e(url('/laporan/penjualan?group=customer')) ?>" class="text-[10px] font-semibold text-brand hover:underline">Detail</a>
                </div>
                <div class="space-y-3">
                    <?php if (empty($top_customer)): ?>
                        <p class="text-xs text-stone-400 py-3 text-center">Belum ada transaksi customer.</p>
                    <?php else: ?>
                        <?php
                        $maxCustVal = count($top_customer) > 0 ? (float)($top_customer[0]['total_penjualan'] ?? 1.0) : 1.0;
                        foreach ($top_customer as $cust):
                            $val = (float)($cust['total_penjualan'] ?? 0);
                            $pct = $maxCustVal > 0 ? ($val / $maxCustVal) * 100 : 0;
                        ?>
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-ink truncate max-w-[180px]"><?= e($cust['nama_customer'] ?? 'Customer') ?></span>
                                    <span class="font-semibold text-stone-700"><?= rupiah($val) ?></span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-stone-100">
                                    <div style="width: <?= $pct ?>%" class="h-1.5 rounded-full bg-coral"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
