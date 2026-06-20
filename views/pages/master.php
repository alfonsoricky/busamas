<?php
$tab = $tab ?? 'barang';
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Data Master</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Data Master Aplikasi</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Kelola data barang, customer/laundry, dan sales agent yang tersimpan dalam sistem Busamas.
            </p>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="<?= e(url('/master?tab=barang')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'barang' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Barang
            </a>
            <a href="<?= e(url('/master?tab=customer')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'customer' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Customer
            </a>
            <a href="<?= e(url('/master?tab=sales')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'sales' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Sales
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <?php if ($tab === 'barang'): ?>
        <?php if (! ($masterBarang['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master barang belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterBarang['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Barang</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterBarang['summary']['total_barang']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Transaksi</p>
                    <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $masterBarang['summary']['total_transaksi']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                    <p class="mt-2 text-3xl font-bold text-coral"><?= e((string) $masterBarang['summary']['total_invoice']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Barang</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Ukuran</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Harga</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Transaksi</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterBarang['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['ukuran'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(rupiah($item['harga_default'] ?? 0)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_transaksi'] ?? '0') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_invoice'] ?? '0') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'customer'): ?>
        <?php if (! ($masterCustomer['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master customer belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterCustomer['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Customer</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterCustomer['summary']['total_customer']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                    <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $masterCustomer['summary']['total_invoice']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Dengan Telepon</p>
                    <p class="mt-2 text-3xl font-bold text-coral"><?= e((string) $masterCustomer['summary']['total_dengan_telepon']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Customer</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Laundry</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Telepon</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice</th>
                                <th class="px-4 py-3 font-semibold">Alamat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterCustomer['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['nama_laundry'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['no_telepon'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_invoice'] ?? '0') ?></td>
                                    <td class="min-w-72 px-4 py-3 text-stone-600"><?= e($item['alamat_default'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'sales'): ?>
        <?php if (! ($masterSales['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master sales belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterSales['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Sales</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterSales['summary']['total_sales']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterSales['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_sales'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_sales'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
