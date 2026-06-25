<?php
$items = $reportData['items'] ?? [];
$typeLabels = [
    'asset' => 'Aset',
    'liability' => 'Kewajiban',
    'equity' => 'Ekuitas',
    'revenue' => 'Pendapatan',
    'expense' => 'Beban',
];
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
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Chart of Accounts</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">Daftar akun default yang digunakan untuk jurnal otomatis dari invoice dan operasional.</p>
    </div>

    <?php if (! ($reportData['ok'] ?? false)): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800"><?= e($reportData['error'] ?? 'Gagal memuat COA.') ?></div>
    <?php else: ?>
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Akun</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Akun Neraca</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= count(array_filter($items, static fn($i) => in_array($i['type'] ?? '', ['asset', 'liability', 'equity'], true))) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Akun Laba Rugi</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= count(array_filter($items, static fn($i) => in_array($i['type'] ?? '', ['revenue', 'expense'], true))) ?></p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Kode</th>
                            <th class="px-4 py-3 font-semibold">Nama Akun</th>
                            <th class="px-4 py-3 font-semibold">Tipe</th>
                            <th class="px-4 py-3 font-semibold">Saldo Normal</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-stone-50">
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-brand"><?= e($item['code'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= e($item['name'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($typeLabels[$item['type'] ?? ''] ?? ($item['type'] ?? '')) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(ucfirst((string) ($item['normal_balance'] ?? ''))) ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><?= ((int) ($item['is_active'] ?? 0)) === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
