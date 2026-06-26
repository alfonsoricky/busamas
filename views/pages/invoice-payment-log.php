<?php
$items = $paymentLog['items'] ?? [];
$summary = $paymentLog['summary'] ?? [];
$filters = $paymentLog['filters'] ?? [];
$years = $paymentLog['options']['years'] ?? [date('Y')];
$months = invoice_months();
$selectedMonth = (string) ($filters['month'] ?? '');
$selectedYear = (string) ($filters['year'] ?? date('Y'));
$selectedStatus = (string) ($filters['status'] ?? 'unpaid');
$search = (string) ($filters['search'] ?? '');
$flash = $_SESSION['invoice_payment_log_flash'] ?? null;
unset($_SESSION['invoice_payment_log_flash']);
$returnTo = url('/invoice-payment-log') . (empty($_GET) ? '' : '?' . http_build_query($_GET));
$dateLabel = static function (?string $date): string {
    $normalized = date_input_value((string) ($date ?? ''));
    return $normalized !== '' ? date('d-m-Y', strtotime($normalized)) : '-';
};
?>

<section class="mx-auto max-w-[90rem] px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="<?= e(url('/invoices')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Kembali ke Invoice
        </a>
    </div>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Invoice</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Log Book Pembayaran</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Monitor invoice yang sudah dibayar, belum dibayar, tanggal pembayaran, dan umur piutang.
            </p>
        </div>
        <a href="<?= e(url('/invoice-create')) ?>" class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
            Buat Invoice
        </a>
    </div>

    <?php if (is_array($flash)): ?>
        <?php $flashOk = (bool) ($flash['ok'] ?? false); ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= $flashOk ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (! ($paymentLog['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Log book pembayaran belum bisa dibaca.</p>
            <p class="mt-2"><?= e($paymentLog['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
        </div>
    <?php else: ?>
        <form method="GET" action="<?= e(url('/invoice-payment-log')) ?>" class="mb-6 grid gap-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_1fr_1.2fr_2fr_auto] lg:items-end">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Bulan</span>
                <select name="month" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= e((string) $num) ?>" <?= $selectedMonth === (string) $num ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tahun</span>
                <select name="year" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= e((string) $year) ?>" <?= $selectedYear === (string) $year ? 'selected' : '' ?>><?= e((string) $year) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status</span>
                <select name="status" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="" <?= $selectedStatus === '' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="unpaid" <?= $selectedStatus === 'unpaid' ? 'selected' : '' ?>>Belum Dibayar</option>
                    <option value="paid" <?= $selectedStatus === 'paid' ? 'selected' : '' ?>>Sudah Dibayar</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Cari</span>
                <input name="search" value="<?= e($search) ?>" placeholder="Invoice, customer, laundry, sales" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Filter</button>
                <a href="<?= e(url('/invoice-payment-log')) ?>" class="rounded-lg border border-stone-300 px-4 py-2.5 text-center text-sm font-semibold text-ink transition hover:bg-stone-50">Reset</a>
            </div>
        </form>

        <div class="mb-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-stone-500">Invoice</p>
                <p class="mt-3 text-3xl font-bold text-ink"><?= e((string) ($summary['invoice_count'] ?? 0)) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Sudah Dibayar</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700"><?= e((string) ($summary['paid_count'] ?? 0)) ?></p>
                <p class="mt-1 text-sm font-semibold text-stone-600"><?= rupiah($summary['paid_total'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Belum Dibayar</p>
                <p class="mt-3 text-3xl font-bold text-rose-700"><?= e((string) ($summary['unpaid_count'] ?? 0)) ?></p>
                <p class="mt-1 text-sm font-semibold text-stone-600"><?= rupiah($summary['remaining_total'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-brand">Total Invoice</p>
                <p class="mt-3 text-2xl font-bold text-ink"><?= rupiah($summary['total_invoice'] ?? 0) ?></p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Sales</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Total</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Status</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tgl Bayar</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Sisa</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Umur</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-stone-500">Belum ada invoice sesuai filter.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($items as $invoice): ?>
                            <?php
                                $isPaid = (bool) ($invoice['is_paid'] ?? false);
                                $statusClass = $isPaid ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-rose-100 text-rose-800 ring-rose-200';
                                $customer = trim((string) ($invoice['nama_laundry_invoice'] ?? '')) ?: trim((string) ($invoice['nama_customer_invoice'] ?? '')) ?: trim((string) ($invoice['nama_customer_master'] ?? ''));
                                $sales = trim(implode(' / ', array_filter([
                                    $invoice['nama_sales_1'] ?? '',
                                    $invoice['nama_sales_2'] ?? '',
                                ]))) ?: '-';
                            ?>
                            <tr class="<?= $isPaid ? 'hover:bg-emerald-50/50' : 'bg-rose-50/40 hover:bg-rose-50' ?>">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                    <a href="<?= e(url('/invoice-create?code=' . ($invoice['kode_invoice'] ?? ''))) ?>" class="hover:underline"><?= e($invoice['nomor_invoice'] ?? '') ?></a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($dateLabel($invoice['tanggal_invoice'] ?? '')) ?></td>
                                <td class="min-w-56 px-4 py-3 text-ink"><?= e($customer) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($sales) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-ink"><?= rupiah($invoice['total_harga_jual'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= e($statusClass) ?>">
                                        <?= e($isPaid ? 'Lunas' : 'Belum Lunas') ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($dateLabel($invoice['tanggal_pembayaran'] ?? '')) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold <?= $isPaid ? 'text-stone-500' : 'text-rose-700' ?>"><?= rupiah($invoice['remaining_amount'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-stone-700"><?= ($invoice['age_days'] ?? null) !== null ? e((string) max(0, (int) $invoice['age_days'])) . ' hari' : '-' ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <form method="POST" action="<?= e(url('/invoice-payment-update')) ?>" class="flex min-w-[22rem] items-center gap-2">
                                        <input type="hidden" name="kode_invoice" value="<?= e($invoice['kode_invoice'] ?? '') ?>">
                                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                        <select name="status_pembayaran" class="w-32 rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink outline-none focus:border-brand">
                                            <option value="Belum Lunas" <?= ! $isPaid ? 'selected' : '' ?>>Belum Lunas</option>
                                            <option value="Lunas" <?= $isPaid ? 'selected' : '' ?>>Lunas</option>
                                        </select>
                                        <input type="date" name="tanggal_pembayaran" value="<?= e($invoice['payment_date_input'] ?? '') ?>" class="w-36 rounded-lg border border-stone-300 px-2 py-1.5 text-xs text-ink outline-none focus:border-brand">
                                        <button type="submit" class="rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-teal-800">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
