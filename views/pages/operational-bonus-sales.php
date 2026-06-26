<?php
$items = $bonusSales['items'] ?? [];
$salesSummary = $bonusSales['sales_summary'] ?? [];
$summary = $bonusSales['summary'] ?? [];
$rules = $bonusSales['rules'] ?? ['target' => 30000000, 'rate' => 0.05];
$filters = $bonusSales['filters'] ?? [];
$selectedMonth = (string) ($filters['month'] ?? date('n'));
$selectedYear = (string) ($filters['year'] ?? date('Y'));
$selectedSales = (string) ($filters['sales'] ?? '');
$selectedCustomerStatus = (string) ($filters['customer_status'] ?? '');
$selectedBonusStatus = (string) ($filters['bonus_status'] ?? '');
$months = invoice_months();
$flash = $_SESSION['bonus_sales_flash'] ?? null;
unset($_SESSION['bonus_sales_flash']);
$dateLabel = static function (?string $date): string {
    $normalized = date_input_value((string) ($date ?? ''));
    return $normalized !== '' ? date('d-m-Y', strtotime($normalized)) : '-';
};
?>

<section class="mx-auto max-w-[90rem] px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="<?= e(url('/operational')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Kembali ke Operasional
        </a>
    </div>

    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Operasional</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Bonus / Insentif Tim Sales</h1>
        <p class="mt-2 max-w-3xl leading-7 text-stone-600">
            Hak bonus dihitung dari total omzet bulanan Krisna dan Wira. Bonus per invoice bisa ditandai terbayar ke sales setelah invoice customer berstatus lunas.
        </p>
    </div>

    <?php if (is_array($flash)): ?>
        <?php $flashOk = (bool) ($flash['ok'] ?? false); ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= $flashOk ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (! ($bonusSales['ok'] ?? false)): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-semibold">Gagal memuat bonus sales internal.</p>
            <p class="mt-1"><?= e($bonusSales['error'] ?? 'Terjadi kesalahan sistem.') ?></p>
        </div>
    <?php else: ?>
        <form method="GET" action="<?= e(url('/operational/bonus-sales')) ?>" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
            <label class="block w-full sm:w-auto">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Bulan</span>
                <select name="month" class="w-full sm:w-44 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= e((string) $num) ?>" <?= $selectedMonth === (string) $num ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block w-full sm:w-auto">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tahun</span>
                <select name="year" class="w-full sm:w-36 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <?php foreach ([2026, 2025] as $year): ?>
                        <option value="<?= e((string) $year) ?>" <?= $selectedYear === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block w-full sm:w-auto">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Sales</span>
                <select name="sales" class="w-full sm:w-40 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="" <?= $selectedSales === '' ? 'selected' : '' ?>>Semua Sales</option>
                    <?php foreach (($rules['sales'] ?? []) as $salesOption): ?>
                        <option value="<?= e((string) $salesOption) ?>" <?= strcasecmp($selectedSales, (string) $salesOption) === 0 ? 'selected' : '' ?>><?= e((string) $salesOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block w-full sm:w-auto">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status Customer</span>
                <select name="customer_status" class="w-full sm:w-44 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="" <?= $selectedCustomerStatus === '' ? 'selected' : '' ?>>Semua Customer</option>
                    <option value="paid" <?= $selectedCustomerStatus === 'paid' ? 'selected' : '' ?>>Customer Lunas</option>
                    <option value="unpaid" <?= $selectedCustomerStatus === 'unpaid' ? 'selected' : '' ?>>Customer Belum Lunas</option>
                </select>
            </label>

            <label class="block w-full sm:w-auto">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status Bonus Sales</span>
                <select name="bonus_status" class="w-full sm:w-44 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="" <?= $selectedBonusStatus === '' ? 'selected' : '' ?>>Semua Bonus</option>
                    <option value="unpaid" <?= $selectedBonusStatus === 'unpaid' ? 'selected' : '' ?>>Belum Dibayar</option>
                    <option value="paid" <?= $selectedBonusStatus === 'paid' ? 'selected' : '' ?>>Terbayar</option>
                </select>
            </label>

            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Filter</button>
            <a href="<?= e(url('/operational/bonus-sales')) ?>" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:bg-stone-50">Reset</a>
        </form>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Target per Sales</p>
                <p class="mt-2 text-2xl font-bold text-ink"><?= rupiah($rules['target'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Omzet</p>
                <p class="mt-2 text-2xl font-bold text-brand"><?= rupiah($summary['omzet'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Bonus Total</p>
                <p class="mt-2 text-2xl font-bold text-ink"><?= rupiah($summary['bonus'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Terbayar ke Sales</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700"><?= rupiah($summary['paid_to_sales_bonus'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Belum Dibayar</p>
                <p class="mt-2 text-2xl font-bold text-coral"><?= rupiah($summary['unpaid_to_sales_bonus'] ?? 0) ?></p>
            </div>
        </div>

        <div class="mb-6 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
            <div class="border-b border-stone-200 px-4 py-3">
                <h2 class="font-semibold text-ink">Ringkasan Target Sales</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Sales</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Omzet</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Omzet Lunas</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center font-semibold">Status Target</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($salesSummary as $sales): ?>
                            <?php $eligible = (bool) ($sales['eligible'] ?? false); ?>
                            <tr>
                                <td class="px-4 py-3 font-bold text-ink"><?= e($sales['sales'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-stone-700"><?= e((string) ($sales['invoice_count'] ?? 0)) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-ink"><?= rupiah($sales['omzet'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-emerald-700"><?= rupiah($sales['paid_omzet'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= $eligible ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-stone-100 text-stone-600 ring-stone-200' ?>">
                                        <?= e($eligible ? 'Dapat Bonus' : 'Belum Target') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
            <div class="border-b border-stone-200 px-4 py-3">
                <h2 class="font-semibold text-ink">Log Bonus per Invoice</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Sales</th>
                            <th class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Penjualan</th>
                            <th class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Bonus 5%</th>
                            <th class="whitespace-nowrap px-4 py-3.5 text-center font-semibold">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3.5 text-center font-semibold">Bonus Sales</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-stone-500">Belum ada invoice bonus pada periode ini.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($items as $item): ?>
                            <?php
                                $eligible = (bool) ($item['eligible'] ?? false);
                                $customerPaid = (bool) ($item['is_invoice_paid'] ?? false);
                                $bonusPaid = (string) ($item['bonus_status'] ?? '') === 'Terbayar';
                                $canUpdate = $eligible && (float) ($item['bonus'] ?? 0) > 0;
                            ?>
                            <tr class="<?= $canUpdate && ! $bonusPaid ? 'bg-rose-50/40 hover:bg-rose-50' : 'hover:bg-stone-50' ?>">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                    <a href="<?= e(url('/invoice-create?code=' . ($item['kode_invoice'] ?? ''))) ?>" class="hover:underline"><?= e($item['nomor_invoice'] ?? '') ?></a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($dateLabel($item['tanggal_invoice'] ?? '')) ?></td>
                                <td class="min-w-56 px-4 py-3 text-ink"><?= e($item['customer'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= e($item['sales'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-ink"><?= rupiah($item['omzet'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-emerald-700"><?= rupiah($item['bonus'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= $customerPaid ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-amber-100 text-amber-800 ring-amber-200' ?>">
                                        <?= e($customerPaid ? 'Lunas' : 'Belum Lunas') ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= $bonusPaid ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-rose-100 text-rose-800 ring-rose-200' ?>">
                                        <?= e($bonusPaid ? 'Terbayar' : 'Belum Dibayar') ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <?php if ($canUpdate): ?>
                                        <form method="POST" action="<?= e(url('/operational/bonus-sales-update')) ?>" class="flex min-w-[24rem] items-center gap-2">
                                            <input type="hidden" name="month" value="<?= e($selectedMonth) ?>">
                                            <input type="hidden" name="year" value="<?= e($selectedYear) ?>">
                                            <input type="hidden" name="filter_sales" value="<?= e($selectedSales) ?>">
                                            <input type="hidden" name="customer_status" value="<?= e($selectedCustomerStatus) ?>">
                                            <input type="hidden" name="bonus_status" value="<?= e($selectedBonusStatus) ?>">
                                            <input type="hidden" name="kode_invoice" value="<?= e($item['kode_invoice'] ?? '') ?>">
                                            <input type="hidden" name="sales" value="<?= e($item['sales'] ?? '') ?>">
                                            <select name="status_bonus" class="w-36 rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink outline-none focus:border-brand">
                                                <option value="Belum Dibayar" <?= ! $bonusPaid ? 'selected' : '' ?>>Belum Dibayar</option>
                                                <option value="Terbayar" <?= $bonusPaid ? 'selected' : '' ?> <?= ! $customerPaid ? 'disabled' : '' ?>>Terbayar</option>
                                            </select>
                                            <input type="date" name="tanggal_bayar_bonus" value="<?= e($item['bonus_paid_date'] ?? '') ?>" class="w-36 rounded-lg border border-stone-300 px-2 py-1.5 text-xs text-ink outline-none focus:border-brand">
                                            <button type="submit" class="rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-teal-800">Simpan</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-stone-400"><?= e($eligible ? '-' : 'Belum target') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
