<?php
$group = $reportData['type'] ?? 'produk';
$items = $reportData['items'] ?? [];

$total_penjualan = array_sum(array_column($items, 'total_penjualan'));
$total_hpp = array_sum(array_column($items, 'total_hpp'));
$total_profit = array_sum(array_column($items, 'total_profit'));
$margin_persen = $total_penjualan > 0 ? ($total_profit / $total_penjualan) * 100 : 0;
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
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Analisis Profitabilitas</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Laporan Profit Penjualan</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Analisis margin laba kotor and total keuntungan bersih (Gross Profit) dikelompokkan Per Produk dan Per Customer.
            </p>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <?php
            $tabs = [
                'produk' => 'Profit Per Produk',
                'customer' => 'Profit Per Customer',
            ];
            foreach ($tabs as $key => $label):
                $isActive = $group === $key;
                $url = url('/laporan/profit?group=' . $key);
            ?>
                <a href="<?= e($url) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $isActive ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Penjualan</p>
            <p class="mt-2 text-2xl font-bold text-ink"><?= rupiah($total_penjualan) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total HPP</p>
            <p class="mt-2 text-2xl font-bold text-stone-700"><?= rupiah($total_hpp) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm border-brand/20 bg-teal-50/50">
            <p class="text-sm font-medium text-brand">Total Profit</p>
            <p class="mt-2 text-2xl font-bold text-brand"><?= rupiah($total_profit) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm border-coral/20 bg-orange-50/50">
            <p class="text-sm font-medium text-coral">Rata-rata Margin %</p>
            <p class="mt-2 text-2xl font-bold text-coral"><?= number_format($margin_persen, 2, ',', '.') ?>%</p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <?php if ($group === 'produk'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Ukuran</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Qty</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Penjualan</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total HPP</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Laba Kotor</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-stone-500">Tidak ada data profit produk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $itemPenjualan = (float)($item['total_penjualan'] ?? 0);
                                $itemHpp = (float)($item['total_hpp'] ?? 0);
                                $itemProfit = (float)($item['total_profit'] ?? 0);
                                $itemMargin = $itemPenjualan > 0 ? ($itemProfit / $itemPenjualan) * 100 : 0;
                                ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_barang'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($item['ukuran'] ?? '') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($itemPenjualan) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($itemHpp) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= rupiah($itemProfit) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-bold text-coral"><?= number_format($itemMargin, 2, ',', '.') ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'customer'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Customer / Laundry</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Pembelian</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total HPP</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Laba Kotor</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">Tidak ada data profit customer.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $itemPenjualan = (float)($item['total_penjualan'] ?? 0);
                                $itemHpp = (float)($item['total_hpp'] ?? 0);
                                $itemProfit = (float)($item['total_profit'] ?? 0);
                                $itemMargin = $itemPenjualan > 0 ? ($itemProfit / $itemPenjualan) * 100 : 0;
                                ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_customer'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? 'Unknown Customer') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($itemPenjualan) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($itemHpp) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= rupiah($itemProfit) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-bold text-coral"><?= number_format($itemMargin, 2, ',', '.') ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</section>
