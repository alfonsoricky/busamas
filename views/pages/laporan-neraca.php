<?php
$groups = $reportData['groups'] ?? [];
$summary = $reportData['summary'] ?? [];
$asOfDate = $reportData['as_of_date'] ?? date('Y-m-d');
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
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Neraca</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">Posisi aset, kewajiban, dan ekuitas berdasarkan jurnal otomatis.</p>
    </div>

    <form method="GET" action="" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Per Tanggal</label>
            <input type="date" name="date" value="<?= e($asOfDate) ?>" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-48">
        </div>
        <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Tampilkan</button>
    </form>

    <?php if (! ($reportData['ok'] ?? false)): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800"><?= e($reportData['error'] ?? 'Gagal memuat neraca.') ?></div>
    <?php else: ?>
        <div class="mb-6 grid gap-4 sm:grid-cols-4">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Total Aset</p><p class="mt-2 text-2xl font-bold text-ink"><?= rupiah($summary['asset_total'] ?? 0) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Total Kewajiban</p><p class="mt-2 text-2xl font-bold text-coral"><?= rupiah($summary['liability_total'] ?? 0) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Total Ekuitas</p><p class="mt-2 text-2xl font-bold text-brand"><?= rupiah($summary['equity_total'] ?? 0) ?></p></div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-stone-500">Selisih</p><p class="mt-2 text-2xl font-bold <?= abs((float) ($summary['difference'] ?? 0)) < 0.01 ? 'text-emerald-700' : 'text-red-700' ?>"><?= rupiah($summary['difference'] ?? 0) ?></p></div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <?php foreach (['asset', 'liability', 'equity'] as $type): ?>
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm <?= $type === 'asset' ? 'lg:row-span-2' : '' ?>">
                    <div class="flex items-center justify-between border-b border-stone-200 bg-stone-50 px-4 py-3">
                        <h2 class="font-bold text-ink"><?= e($groups[$type]['label'] ?? '') ?></h2>
                        <span class="text-sm font-bold text-brand"><?= rupiah($groups[$type]['total'] ?? 0) ?></span>
                    </div>
                    <table class="min-w-full divide-y divide-stone-100 text-sm">
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach (($groups[$type]['items'] ?? []) as $item): ?>
                                <?php if (abs((float) ($item['balance'] ?? 0)) < 0.01) continue; ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['code'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-ink"><?= e($item['name'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-ink"><?= rupiah($item['balance'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
