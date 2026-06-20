<?php
$items = $reportData['items'] ?? [];
$total_hutang = $reportData['total_hutang'] ?? 0.0;
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
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Hutang Dagang (Payables)</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Daftar tagihan pembelian barang kepada vendor/supplier atas invoice yang belum dilunasi sepenuhnya.
            </p>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Outstanding Hutang</p>
            <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah($total_hutang) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Jumlah Invoice Belum Lunas (Hutang)</p>
            <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
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
        </div>
    </div>
</section>
