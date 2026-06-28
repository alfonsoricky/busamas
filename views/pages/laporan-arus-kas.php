<?php
$period = $reportData['period'] ?? [];
$summary = $reportData['summary'] ?? [];
$groups = $reportData['groups'] ?? [];
$items = $reportData['items'] ?? [];
$selectedMonth = (string) ($period['month'] ?? ($_GET['month'] ?? ''));
$selectedYear = (string) ($period['year'] ?? ($_GET['year'] ?? date('Y')));
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$sourceLabels = [
    'invoice' => 'Invoice',
    'operational_expense' => 'Operasional',
    'partner_prive' => 'Prive Partner',
    'legacy_2025_transition' => 'Legacy',
];
?>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="<?= e(url('/laporan')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Kembali ke Laporan Utama
        </a>
    </div>

    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Akuntansi</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Laporan Arus Kas</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">Ringkasan kas masuk dan kas keluar berdasarkan mutasi akun Kas / Bank dari jurnal akuntansi.</p>
    </div>

    <form method="GET" action="" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Bulan</label>
            <select name="month" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-44">
                <option value="">Semua Bulan</option>
                <?php foreach ($monthNames as $value => $label): ?>
                    <option value="<?= e((string) $value) ?>" <?= (string) $value === $selectedMonth ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tahun</label>
            <input type="number" name="year" value="<?= e($selectedYear) ?>" min="2020" max="2100" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-32">
        </div>
        <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Tampilkan</button>
    </form>

    <?php if (! ($reportData['ok'] ?? false)): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800"><?= e($reportData['error'] ?? 'Gagal memuat laporan arus kas.') ?></div>
    <?php else: ?>
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Periode</p>
                <p class="mt-2 text-xl font-bold text-ink"><?= e($period['label'] ?? '-') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Saldo Awal</p>
                <p class="mt-2 text-xl font-bold text-ink"><?= rupiah($summary['opening_balance'] ?? 0) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Kas Masuk</p>
                <p class="mt-2 text-xl font-bold text-brand"><?= rupiah($summary['cash_in'] ?? 0) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Kas Keluar</p>
                <p class="mt-2 text-xl font-bold text-coral"><?= rupiah($summary['cash_out'] ?? 0) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Saldo Akhir</p>
                <p class="mt-2 text-xl font-bold text-ink"><?= rupiah($summary['ending_balance'] ?? 0) ?></p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <?php foreach ($groups as $group): ?>
                <?php if (abs((float) ($group['cash_in'] ?? 0)) < 0.01 && abs((float) ($group['cash_out'] ?? 0)) < 0.01) continue; ?>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-bold text-ink"><?= e($group['label'] ?? '') ?></h2>
                        <span class="whitespace-nowrap rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-brand"><?= rupiah($group['net'] ?? 0) ?></span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3 text-stone-600">
                            <span>Kas masuk</span>
                            <span class="font-semibold text-brand"><?= rupiah($group['cash_in'] ?? 0) ?></span>
                        </div>
                        <div class="flex justify-between gap-3 text-stone-600">
                            <span>Kas keluar</span>
                            <span class="font-semibold text-coral"><?= rupiah($group['cash_out'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-stone-200 bg-stone-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-bold text-ink">Detail Mutasi Kas</h2>
                <span class="text-sm font-medium text-stone-500"><?= count($items) ?> transaksi</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-100 text-sm" data-simple-datatable data-dt-unit="transaksi" data-dt-empty="Tidak ada mutasi kas yang cocok.">
                    <thead class="bg-white text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-4 py-3" data-sort-type="date">Tanggal Kas</th>
                            <th class="px-4 py-3" data-sort-type="text">Sumber</th>
                            <th class="px-4 py-3" data-sort-type="text">Keterangan</th>
                            <th class="px-4 py-3 text-right" data-sort-type="number">Kas Masuk</th>
                            <th class="px-4 py-3 text-right" data-sort-type="number">Kas Keluar</th>
                            <th class="px-4 py-3 text-right" data-sort-type="number">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if ($items === []): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">Belum ada mutasi kas pada periode ini.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-stone-50" data-dt-row>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e(date('d-m-Y', strtotime((string) ($item['entry_date'] ?? 'now')))) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                    <?= e($sourceLabels[$item['source_type'] ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($item['source_type'] ?? '-')))) ?>
                                    <?php if (($item['source_id'] ?? '') !== ''): ?>
                                        <span class="block text-xs text-stone-400"><?= e($item['source_id']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="min-w-64 px-4 py-3 text-stone-700">
                                    <span class="font-medium text-ink"><?= e($item['memo'] ?: ($item['description'] ?? '')) ?></span>
                                    <?php if (($item['description'] ?? '') !== '' && ($item['memo'] ?? '') !== ($item['description'] ?? '')): ?>
                                        <span class="mt-1 block text-xs text-stone-500"><?= e($item['description']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-brand"><?= ((float) ($item['cash_in'] ?? 0)) > 0 ? rupiah($item['cash_in']) : '-' ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-coral"><?= ((float) ($item['cash_out'] ?? 0)) > 0 ? rupiah($item['cash_out']) : '-' ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-ink"><?= rupiah($item['balance'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/partials/simple-datatable.php'; ?>
