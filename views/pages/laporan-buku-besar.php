<?php
$accounts = $reportData['accounts'] ?? [];
$items = $reportData['items'] ?? [];
$selected = $reportData['selected'] ?? null;
$summary = $reportData['summary'] ?? [];
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="<?= e(url('/laporan')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Kembali ke Laporan Utama
        </a>
    </div>

    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Akuntansi</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Buku Besar</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">Mutasi debit kredit dan saldo berjalan per akun COA.</p>
    </div>

    <form method="GET" action="" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Akun</label>
            <select name="account" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-72">
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= e($account['code'] ?? '') ?>" <?= ($selected['code'] ?? '') === ($account['code'] ?? '') ? 'selected' : '' ?>>
                        <?= e(($account['code'] ?? '') . ' - ' . ($account['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php $selectedMonth = $_GET['month'] ?? ''; $selectedYear = $_GET['year'] ?? date('Y'); $months = invoice_months(); ?>
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Bulan</label>
            <select name="month" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-40">
                <option value="">Semua Bulan</option>
                <?php foreach ($months as $num => $name): ?><option value="<?= $num ?>" <?= (string) $selectedMonth === (string) $num ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tahun</label>
            <select name="year" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-36">
                <option value="">Semua Tahun</option>
                <option value="2026" <?= (string) $selectedYear === '2026' ? 'selected' : '' ?>>2026</option>
                <option value="2025" <?= (string) $selectedYear === '2025' ? 'selected' : '' ?>>2025</option>
            </select>
        </div>
        <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Terapkan Filter</button>
    </form>

    <?php if (! ($reportData['ok'] ?? false)): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800"><?= e($reportData['error'] ?? 'Gagal memuat buku besar.') ?></div>
    <?php else: ?>
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Akun</p><p class="mt-2 text-xl font-bold text-ink"><?= e(($selected['code'] ?? '-') . ' ' . ($selected['name'] ?? '')) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Total Mutasi</p><p class="mt-2 text-2xl font-bold text-brand"><?= rupiah(($summary['debit_total'] ?? 0) + ($summary['credit_total'] ?? 0)) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Saldo Akhir</p><p class="mt-2 text-2xl font-bold text-coral"><?= rupiah($summary['ending_balance'] ?? 0) ?></p></div>
        </div>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Sumber</th>
                            <th class="px-4 py-3 font-semibold">Keterangan</th>
                            <th class="px-4 py-3 text-right font-semibold">Debit</th>
                            <th class="px-4 py-3 text-right font-semibold">Kredit</th>
                            <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">Belum ada mutasi untuk akun ini.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-stone-50">
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(date('d-m-Y', strtotime((string) $item['entry_date']))) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e(($item['source_type'] ?? '') . ' / ' . ($item['source_id'] ?? '')) ?></td>
                                <td class="px-4 py-3 text-ink"><?= e($item['memo'] ?: ($item['description'] ?? '')) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right"><?= ((float) ($item['debit'] ?? 0)) > 0 ? rupiah($item['debit']) : '-' ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right"><?= ((float) ($item['credit'] ?? 0)) > 0 ? rupiah($item['credit']) : '-' ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-ink"><?= rupiah($item['balance'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
