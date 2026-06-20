<?php
$group = $reportData['type'] ?? 'invoice';
$items = $reportData['items'] ?? [];
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
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Laporan Penjualan</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Penjualan Busamas</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Laporan penjualan dari data invoice, dikelompokkan berdasarkan tipe laporan yang Anda pilih di bawah ini.
            </p>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <?php
            $tabs = [
                'invoice' => 'Per Invoice',
                'customer' => 'Per Customer',
                'produk' => 'Per Produk',
                'sales' => 'Per Sales',
            ];
            foreach ($tabs as $key => $label):
                $isActive = $group === $key;
                $url = url('/laporan/penjualan?group=' . $key);
            ?>
                <a href="<?= e($url) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $isActive ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <?php if ($group === 'invoice'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Qty Terjual</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= number_format(array_sum(array_column($items, 'total_qty')), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Penjualan Bersih</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_harga_jual'))) ?></p>
            </div>
        <?php elseif ($group === 'customer'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Customer</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Qty Dibeli</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= number_format(array_sum(array_column($items, 'total_qty')), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Nilai Pembelian</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_penjualan'))) ?></p>
            </div>
        <?php elseif ($group === 'produk'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Produk Terjual</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Volume Terjual</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= number_format(array_sum(array_column($items, 'total_qty')), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Omset Produk</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_penjualan'))) ?></p>
            </div>
        <?php elseif ($group === 'sales'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Sales Agent</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Penjualan Sales</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= rupiah(array_sum(array_column($items, 'total_penjualan'))) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Estimasi Komisi</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_komisi'))) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <?php if ($group === 'invoice'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">No. Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Sales</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Qty</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Subtotal</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Diskon</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-stone-500">Tidak ada data penjualan.</td>
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
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($item['nama_sales_1'] ?? '-') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($item['subtotal'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($item['discount_amount'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_harga_jual'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'customer'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Customer / Laundry</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Jumlah Invoice</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Qty Dibeli</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Nilai Pembelian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada data customer.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_customer'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? 'Unknown Customer') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['jumlah_invoice'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'produk'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Ukuran</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Qty Terjual</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Penjualan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada data produk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_barang'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_barang_master'] ?? 'Unknown Product') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($item['ukuran_master'] ?? '') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'sales'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Sales</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Sales Agent</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Jumlah Invoice</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Penjualan (Rp)</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Estimasi Komisi (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada data sales.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_sales'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_sales'] ?? 'Unknown Sales') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['jumlah_invoice'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-coral"><?= rupiah($item['total_komisi'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</section>
