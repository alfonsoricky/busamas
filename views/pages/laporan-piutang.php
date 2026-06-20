<?php
$tab = $_GET['tab'] ?? 'aging';
$aging = $reportData['aging'] ?? [];
$overdue = $reportData['overdue'] ?? [];
$total_piutang = $reportData['total_piutang'] ?? 0.0;
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
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Laporan Piutang</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Piutang Dagang (Receivables)</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Pemantauan piutang customer berdasarkan umur piutang (Aging) untuk menjaga kelancaran arus kas perusahaan.
            </p>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="<?= e(url('/laporan/piutang?tab=aging')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'aging' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Analisis Aging Piutang
            </a>
            <a href="<?= e(url('/laporan/piutang?tab=overdue')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'overdue' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Invoice Overdue (> 30 Hari)
                <?php if (count($overdue) > 0): ?>
                    <span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-600"><?= count($overdue) ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Piutang Dagang</p>
            <p class="mt-2 text-3xl font-bold text-ink"><?= rupiah($total_piutang) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Piutang Lancar (0-30 Hari)</p>
            <p class="mt-2 text-3xl font-bold text-brand"><?= rupiah($aging['0_30']['total'] ?? 0) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Piutang Overdue (>30 Hari)</p>
            <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah($total_piutang - ($aging['0_30']['total'] ?? 0)) ?></p>
        </div>
    </div>

    <!-- Tab Content -->
    <?php if ($tab === 'aging'): ?>
        <!-- Aging Overview Bar Chart Mockup (Pure Tailwind) -->
        <div class="mb-6 rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink mb-4">Proporsi Umur Piutang</h3>
            <div class="flex h-4 w-full overflow-hidden rounded-full bg-stone-100">
                <?php
                $pct_0_30 = $total_piutang > 0 ? (($aging['0_30']['total'] ?? 0) / $total_piutang) * 100 : 0;
                $pct_31_60 = $total_piutang > 0 ? (($aging['31_60']['total'] ?? 0) / $total_piutang) * 100 : 0;
                $pct_61_90 = $total_piutang > 0 ? (($aging['61_90']['total'] ?? 0) / $total_piutang) * 100 : 0;
                $pct_90_plus = $total_piutang > 0 ? (($aging['90_plus']['total'] ?? 0) / $total_piutang) * 100 : 0;
                ?>
                <div style="width: <?= $pct_0_30 ?>%" class="bg-teal-600" title="0-30 Hari: <?= number_format($pct_0_30, 1) ?>%"></div>
                <div style="width: <?= $pct_31_60 ?>%" class="bg-yellow-500" title="31-60 Hari: <?= number_format($pct_31_60, 1) ?>%"></div>
                <div style="width: <?= $pct_61_90 ?>%" class="bg-orange-500" title="61-90 Hari: <?= number_format($pct_61_90, 1) ?>%"></div>
                <div style="width: <?= $pct_90_plus ?>%" class="bg-red-600" title=">90 Hari: <?= number_format($pct_90_plus, 1) ?>%"></div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-teal-600"></span>
                    <span class="text-stone-600">0 - 30 Hari: <?= number_format($pct_0_30, 1) ?>% (<?= rupiah($aging['0_30']['total'] ?? 0) ?>)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
                    <span class="text-stone-600">31 - 60 Hari: <?= number_format($pct_31_60, 1) ?>% (<?= rupiah($aging['31_60']['total'] ?? 0) ?>)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-orange-500"></span>
                    <span class="text-stone-600">61 - 90 Hari: <?= number_format($pct_61_90, 1) ?>% (<?= rupiah($aging['61_90']['total'] ?? 0) ?>)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-red-600"></span>
                    <span class="text-stone-600">&gt; 90 Hari: <?= number_format($pct_90_plus, 1) ?>% (<?= rupiah($aging['90_plus']['total'] ?? 0) ?>)</span>
                </div>
            </div>
        </div>

        <!-- Grouped Aging Tables -->
        <div class="space-y-8">
            <?php foreach ($aging as $key => $group): ?>
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-stone-200 bg-stone-50 px-4 py-3">
                        <h3 class="font-bold text-ink flex items-center gap-2">
                            <?php
                            $badgeColor = 'bg-stone-200 text-stone-800';
                            if ($key === '0_30') $badgeColor = 'bg-teal-100 text-teal-800';
                            elseif ($key === '31_60') $badgeColor = 'bg-yellow-100 text-yellow-800';
                            elseif ($key === '61_90') $badgeColor = 'bg-orange-100 text-orange-800';
                            elseif ($key === '90_plus') $badgeColor = 'bg-red-100 text-red-800';
                            ?>
                            <span class="rounded px-2.5 py-0.5 text-xs font-bold <?= $badgeColor ?>">
                                <?= e($group['label']) ?>
                            </span>
                        </h3>
                        <span class="text-sm font-semibold text-ink">Total: <?= rupiah($group['total']) ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                            <thead class="bg-stone-100/50 text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="px-4 py-2 font-semibold">No. Invoice</th>
                                    <th class="px-4 py-2 font-semibold">Tanggal Invoice</th>
                                    <th class="px-4 py-2 font-semibold">Customer / Laundry</th>
                                    <th class="text-right px-4 py-2 font-semibold">Umur Invoice (Hari)</th>
                                    <th class="text-right px-4 py-2 font-semibold">Jumlah Piutang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <?php if (empty($group['items'])): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-4 text-center text-xs text-stone-400">Tidak ada piutang di kategori ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($group['items'] as $invoice): ?>
                                        <tr class="hover:bg-stone-50">
                                            <td class="whitespace-nowrap px-4 py-2.5 font-semibold text-brand">
                                                <a href="<?= e(url('/invoice-view?code=' . ($invoice['nomor_invoice'] ?? ''))) ?>" class="hover:underline">
                                                    <?= e($invoice['nomor_invoice'] ?? '') ?>
                                                </a>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-stone-700"><?= e($invoice['tanggal_invoice'] ?? '') ?></td>
                                            <td class="whitespace-nowrap px-4 py-2.5 font-medium text-ink"><?= e($invoice['nama_customer'] ?? '') ?></td>
                                            <td class="text-right whitespace-nowrap px-4 py-2.5 text-stone-700"><?= $invoice['days_overdue'] ?> Hari</td>
                                            <td class="text-right whitespace-nowrap px-4 py-2.5 font-semibold text-ink"><?= rupiah($invoice['total_harga_jual'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($tab === 'overdue'): ?>
        <!-- Overdue Invoices List -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">No. Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer / Laundry</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Hari Keterlambatan</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Nilai Piutang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($overdue)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">Hebat! Tidak ada invoice overdue (>30 Hari). Semua pembayaran lancar!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($overdue as $invoice): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                        <a href="<?= e(url('/invoice-view?code=' . ($invoice['nomor_invoice'] ?? ''))) ?>" class="hover:underline">
                                            <?= e($invoice['nomor_invoice'] ?? '') ?>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($invoice['tanggal_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($invoice['nama_customer'] ?? '') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-red-600 font-bold"><?= $invoice['days_overdue'] ?> Hari Overdue</td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-bold text-ink"><?= rupiah($invoice['total_harga_jual'] ?? 0) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                        <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $invoice['no_telepon'] ?? '')) ?>" target="_blank" class="inline-flex items-center gap-1 rounded bg-teal-600 px-2 py-1 text-xs font-semibold text-white hover:bg-teal-800">
                                            WhatsApp Follow-up
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
