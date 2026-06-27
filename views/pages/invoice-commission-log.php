<?php
$items = $commissionLog['items'] ?? [];
$summary = $commissionLog['summary'] ?? [];
$filters = $commissionLog['filters'] ?? [];
$config = $commissionLog['config'] ?? [];
$years = $commissionLog['options']['years'] ?? [date('Y')];
$months = invoice_months();
$selectedMonth = (string) ($filters['month'] ?? '');
$selectedYear = (string) ($filters['year'] ?? date('Y'));
$selectedStatus = (string) ($filters['status'] ?? 'unpaid');
$selectedCustomerStatus = (string) ($filters['customer_status'] ?? '');
$search = (string) ($filters['search'] ?? '');
$type = (string) ($config['type'] ?? 'sales');
$route = (string) ($config['route'] ?? '/invoices');
$updateRoute = (string) ($config['update_route'] ?? '/invoices');
$label = (string) ($config['label'] ?? 'Komisi');
$title = (string) ($config['title'] ?? 'Log Book Komisi');
$flashKey = 'invoice_commission_' . $type . '_flash';
$flash = $_SESSION[$flashKey] ?? null;
unset($_SESSION[$flashKey]);
$returnTo = url($route) . (empty($_GET) ? '' : '?' . http_build_query($_GET));
$dateLabel = static function (?string $date): string {
    $normalized = date_input_value((string) ($date ?? ''));
    return $normalized !== '' ? date('d-m-Y', strtotime($normalized)) : '-';
};
$otherLinks = [
    'sales' => ['label' => 'Komisi Sales', 'route' => '/invoice-commission-sales-log'],
    'manager' => ['label' => 'Komisi Manager', 'route' => '/invoice-commission-manager-log'],
    'admin' => ['label' => 'Komisi Admin', 'route' => '/invoice-commission-admin-log'],
];
?>

<section class="mx-auto max-w-[90rem] px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="<?= e(url('/invoices')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            Kembali ke Invoice
        </a>
    </div>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Invoice</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl"><?= e($title) ?></h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Monitor status pembayaran <?= e(strtolower($label)) ?> per invoice dan posting ulang jurnal akuntansi saat status disimpan.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($otherLinks as $linkType => $link): ?>
                <a href="<?= e(url($link['route'])) ?>" class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-semibold transition <?= $type === $linkType ? 'border-brand bg-teal-50 text-brand' : 'border-stone-300 text-ink hover:border-brand hover:text-brand' ?>">
                    <?= e($link['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <?php $flashOk = (bool) ($flash['ok'] ?? false); ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= $flashOk ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (! ($commissionLog['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Log book komisi belum bisa dibaca.</p>
            <p class="mt-2"><?= e($commissionLog['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
        </div>
    <?php else: ?>
        <form method="GET" action="<?= e(url($route)) ?>" class="mb-6 grid gap-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm xl:grid-cols-[1fr_1fr_1.1fr_1.1fr_2fr_auto] xl:items-end">
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
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status Komisi</span>
                <select name="status" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="" <?= $selectedStatus === '' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="unpaid" <?= $selectedStatus === 'unpaid' ? 'selected' : '' ?>>Belum Dibayar</option>
                    <option value="paid" <?= $selectedStatus === 'paid' ? 'selected' : '' ?>>Terbayar</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status Customer</span>
                <select name="customer_status" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="" <?= $selectedCustomerStatus === '' ? 'selected' : '' ?>>Semua Customer</option>
                    <option value="paid" <?= $selectedCustomerStatus === 'paid' ? 'selected' : '' ?>>Customer Lunas</option>
                    <option value="unpaid" <?= $selectedCustomerStatus === 'unpaid' ? 'selected' : '' ?>>Customer Belum Lunas</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Cari</span>
                <input name="search" value="<?= e($search) ?>" placeholder="Invoice, customer, laundry, sales" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Filter</button>
                <a href="<?= e(url($route)) ?>" class="rounded-lg border border-stone-300 px-4 py-2.5 text-center text-sm font-semibold text-ink transition hover:bg-stone-50">Reset</a>
            </div>
        </form>

        <div class="mb-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-stone-500">Invoice</p>
                <p class="mt-3 text-3xl font-bold text-ink"><?= e((string) ($summary['invoice_count'] ?? 0)) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-brand">Total <?= e($label) ?></p>
                <p class="mt-3 text-2xl font-bold text-ink"><?= rupiah($summary['commission_total'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Terbayar</p>
                <p class="mt-3 text-2xl font-bold text-emerald-700"><?= rupiah($summary['paid_total'] ?? 0) ?></p>
                <p class="mt-1 text-sm font-semibold text-stone-600"><?= e((string) ($summary['paid_count'] ?? 0)) ?> invoice</p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Belum Dibayar</p>
                <p class="mt-3 text-2xl font-bold text-rose-700"><?= rupiah($summary['debt_total'] ?? 0) ?></p>
                <p class="mt-1 text-sm font-semibold text-stone-600"><?= e((string) ($summary['debt_count'] ?? 0)) ?> invoice</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-simple-datatable data-dt-unit="invoice" data-dt-empty="Tidak ada data komisi yang cocok.">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="date">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Sales</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold" data-sort-type="number">Total Komisi</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold" data-sort-type="number">Terbayar</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold" data-sort-type="number">Belum Dibayar</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Komisi</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="date">Tgl Transfer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="none">Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-stone-500">Belum ada data komisi sesuai filter.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($items as $invoice): ?>
                            <?php
                                $isPaid = (bool) ($invoice['is_paid'] ?? false);
                                $customerPaid = (bool) ($invoice['customer_paid'] ?? false);
                                $rowClass = $isPaid ? 'hover:bg-emerald-50/50' : 'bg-rose-50/40 hover:bg-rose-50';
                                $commissionClass = $isPaid ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-rose-100 text-rose-800 ring-rose-200';
                                $customerClass = $customerPaid ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-amber-100 text-amber-800 ring-amber-200';
                                $customer = trim((string) ($invoice['nama_laundry_invoice'] ?? '')) ?: trim((string) ($invoice['nama_customer_invoice'] ?? '')) ?: trim((string) ($invoice['nama_customer_master'] ?? ''));
                                $sales = trim(implode(' / ', array_filter([
                                    $invoice['nama_sales_1'] ?? '',
                                    $invoice['nama_sales_2'] ?? '',
                                ]))) ?: '-';
                            ?>
                            <tr class="<?= e($rowClass) ?>" data-dt-row>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                    <a href="<?= e(url('/invoice-create?code=' . ($invoice['kode_invoice'] ?? ''))) ?>" class="hover:underline"><?= e($invoice['nomor_invoice'] ?? '') ?></a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($dateLabel($invoice['tanggal_invoice'] ?? '')) ?></td>
                                <td class="min-w-56 px-4 py-3 text-ink"><?= e($customer) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($sales) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-ink"><?= rupiah($invoice['commission_total'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-emerald-700"><?= rupiah($invoice['commission_paid'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold <?= $isPaid ? 'text-stone-500' : 'text-rose-700' ?>"><?= rupiah($invoice['commission_debt'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= e($customerClass) ?>">
                                        <?= e($customerPaid ? 'Lunas' : 'Belum Lunas') ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= e($commissionClass) ?>">
                                        <?= e($isPaid ? 'Terbayar' : 'Belum Dibayar') ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($dateLabel($invoice['transfer_date_input'] ?? '')) ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <form method="POST" action="<?= e(url($updateRoute)) ?>" class="flex min-w-[26rem] items-center gap-2">
                                        <input type="hidden" name="kode_invoice" value="<?= e($invoice['kode_invoice'] ?? '') ?>">
                                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                        <input type="hidden" name="commission_total" value="<?= e(clean_decimal($invoice['commission_total'] ?? 0)) ?>">
                                        <select name="status_komisi" class="w-36 rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink outline-none focus:border-brand">
                                            <option value="unpaid" <?= ! $isPaid ? 'selected' : '' ?>>Belum Dibayar</option>
                                            <option value="paid" <?= $isPaid ? 'selected' : '' ?>>Terbayar</option>
                                        </select>
                                        <input type="date" name="tanggal_transfer" value="<?= e($invoice['transfer_date_input'] ?? '') ?>" class="w-36 rounded-lg border border-stone-300 px-2 py-1.5 text-xs text-ink outline-none focus:border-brand">
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

<?php require dirname(__DIR__) . '/partials/simple-datatable.php'; ?>
