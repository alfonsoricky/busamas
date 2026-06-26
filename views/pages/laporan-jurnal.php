<?php
$entries = $reportData['entries'] ?? [];
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
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Jurnal Umum</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">Daftar jurnal otomatis dari invoice dan pengeluaran operasional.</p>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <?php if (! ($reportData['ok'] ?? false)): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800"><?= e($reportData['error'] ?? 'Gagal memuat jurnal.') ?></div>
    <?php else: ?>
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Jumlah Jurnal</p><p class="mt-2 text-3xl font-bold text-ink"><?= e((string) ($summary['entry_count'] ?? 0)) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Total Debit</p><p class="mt-2 text-3xl font-bold text-brand"><?= rupiah($summary['debit_total'] ?? 0) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Total Kredit</p><p class="mt-2 text-3xl font-bold text-coral"><?= rupiah($summary['credit_total'] ?? 0) ?></p></div>
        </div>

        <div class="space-y-4">
            <?php if (empty($entries)): ?>
                <div class="rounded-lg border border-stone-200 bg-white p-8 text-center text-stone-500">Belum ada jurnal pada filter ini.</div>
            <?php endif; ?>
            <?php foreach ($entries as $entry): ?>
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 bg-stone-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-bold text-ink"><?= e($entry['description'] ?? '') ?></p>
                            <p class="mt-1 text-xs text-stone-500">
                                Transaksi <?= e(date('d-m-Y', strtotime((string) $entry['entry_date']))) ?>
                                <?php if (! empty($entry['posted_at'])): ?>
                                    · Posting <?= e(date('d-m-Y H:i', strtotime((string) $entry['posted_at']))) ?>
                                <?php endif; ?>
                                · <?= e($entry['source_type'] ?? '') ?> / <?= e($entry['source_id'] ?? '') ?>
                            </p>
                        </div>
                        <div class="text-right text-xs font-semibold text-stone-600">Debit <?= rupiah($entry['debit_total'] ?? 0) ?> · Kredit <?= rupiah($entry['credit_total'] ?? 0) ?></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm">
                            <tbody class="divide-y divide-stone-100">
                                <?php foreach (($entry['lines'] ?? []) as $line): ?>
                                    <tr class="hover:bg-stone-50">
                                        <td class="whitespace-nowrap px-4 py-2.5 font-semibold text-brand"><?= e($line['code'] ?? '') ?></td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-ink"><?= e($line['account_name'] ?? '') ?></td>
                                        <td class="px-4 py-2.5 text-stone-500"><?= e($line['memo'] ?? '') ?></td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold text-ink"><?= ((float) ($line['debit'] ?? 0)) > 0 ? rupiah($line['debit']) : '-' ?></td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold text-ink"><?= ((float) ($line['credit'] ?? 0)) > 0 ? rupiah($line['credit']) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
