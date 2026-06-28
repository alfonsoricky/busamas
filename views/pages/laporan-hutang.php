<?php
$items = $reportData['items'] ?? [];
$total_hutang = $reportData['total_hutang'] ?? 0.0;
$activeTab = $reportData['type'] ?? 'dagang';
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <!-- Breadcrumbs -->
    <div class="mb-4">
        <a href="<?= e(url('/laporan')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Kembali ke Laporan Utama
        </a>
    </div>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Laporan Hutang</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">
                <?= $activeTab === 'operational' ? 'Hutang Operasional &amp; Bonus' : 'Hutang Dagang (Payables)' ?>
            </h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                <?= $activeTab === 'operational' 
                    ? 'Daftar pengeluaran operasional dan komisi/bonus sales berstatus "Hutang" yang belum diselesaikan.' 
                    : 'Daftar tagihan pembelian barang kepada vendor/supplier atas invoice yang belum dilunasi sepenuhnya.' ?>
            </p>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <?php
            $tabs = [
                'dagang' => 'Hutang Dagang (Vendor)',
                'operational' => 'Hutang Operasional &amp; Bonus',
            ];
            foreach ($tabs as $key => $label):
                $isActive = $activeTab === $key;
                // Preserve filters when switching tabs
                $queryParams = $_GET;
                $queryParams['tab'] = $key;
                $url = url('/laporan/hutang?' . http_build_query($queryParams));
            ?>
                <a href="<?= e($url) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $isActive ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Outstanding Hutang</p>
            <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah($total_hutang) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">
                <?= $activeTab === 'operational' ? 'Jumlah Catatan Pengeluaran' : 'Jumlah Invoice Belum Lunas' ?>
            </p>
            <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <?php if ($activeTab === 'operational'): ?>
                <!-- Operational Debt Table -->
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Periode PNL</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kategori</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Pengeluaran</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Jumlah Hutang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">Tidak ada hutang operasional yang outstanding. Semua pengeluaran lunas!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                        <?= e($item['tanggal'] ?? '') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                        <?= e(invoice_months()[(int)($item['bulan_pnl'] ?? 0)] ?? '-') ?> <?= e($item['tahun_pnl'] ?? '') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <?php if (($item['kategori'] ?? '') === 'bonus'): ?>
                                            <span class="rounded bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800">
                                                Bonus
                                            </span>
                                        <?php else: ?>
                                            <span class="rounded bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800">
                                                Operational
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink">
                                        <?= e($item['nama_pengeluaran'] ?? '') ?>
                                    </td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-bold text-coral">
                                        <?= rupiah($item['jumlah'] ?? 0) ?>
                                    </td>
                                    <td class="px-4 py-3 text-stone-600 max-w-xs truncate" title="<?= e($item['keterangan'] ?? '') ?>">
                                        <?= e($item['keterangan'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Trade Debt Table (Original) -->
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">No. Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer / Laundry</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Modal (COGS)</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Sisa Hutang (HPP)</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Status Pembelian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">Tidak ada hutang dagang yang outstanding. Semua tagihan pembelian lunas!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                        <a href="<?= e(url('/invoice-view?code=' . ($item['nomor_invoice'] ?? ''))) ?>" class="hover:underline">
                                            <?= e($item['nomor_invoice'] ?? '') ?>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['tanggal_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? '') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($item['total_pembelian_barang'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-bold text-coral"><?= rupiah($item['total_utang_pembelian_barang'] ?? 0) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="rounded bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800">
                                            <?= e($item['status_pembelian_barang'] ?? 'Belum Lunas') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>
