<?php
$items = $reportData['items'] ?? [];
$summary = $reportData['summary'] ?? [];
$salesOptions = $reportData['sales_options'] ?? [];
$statusOptions = $reportData['status_options'] ?? [];
$selectedSales = $reportData['selected_sales'] ?? ($_GET['sales'] ?? '');
$selectedStatus = $reportData['selected_status'] ?? ($_GET['status'] ?? '');
$asOfDate = $reportData['as_of_date'] ?? ($_GET['as_of'] ?? date('Y-m-d'));
$autoExportPdf = ($_GET['export'] ?? '') === 'pdf';
$exportUrl = url('/laporan/aktivitas-customer') . '?' . http_build_query(array_filter([
    'sales' => $selectedSales,
    'status' => $selectedStatus,
    'as_of' => $asOfDate,
    'export' => 'pdf',
], static fn ($value): bool => (string) $value !== ''));

$formatDate = static function (?string $date): string {
    if (! $date) {
        return '-';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y', $timestamp) : $date;
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'aktif' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'follow_up' => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
        'dingin' => 'bg-orange-50 text-orange-700 ring-orange-200',
        default => 'bg-red-50 text-red-700 ring-red-200',
    };
};
?>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 print:max-w-none print:px-0 print:py-0">
    <div class="mb-4 print:hidden">
        <a href="<?= e(url('/laporan')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Kembali ke Laporan Utama
        </a>
    </div>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between print:mb-4">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand print:text-xs print:text-stone-600">Laporan Sales</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl print:text-2xl">Aktivitas Customer</h1>
            <p class="mt-2 max-w-3xl leading-7 text-stone-600 print:text-sm">
                Melihat customer terakhir transaksi kapan, siapa sales yang memegang, nilai transaksi, dan customer yang perlu difollow up.
            </p>
            <p class="mt-2 hidden text-xs text-stone-500 print:block">
                Filter: Sales <?= e($selectedSales !== '' ? $selectedSales : 'Semua') ?> | Status <?= e($selectedStatus !== '' ? ($statusOptions[$selectedStatus] ?? $selectedStatus) : 'Semua') ?> | Per tanggal <?= e($formatDate($asOfDate)) ?>
            </p>
        </div>
        <a href="<?= e($exportUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 print:hidden">
            Download PDF
        </a>
    </div>

    <form method="GET" action="<?= e(url('/laporan/aktivitas-customer')) ?>" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm print:hidden">
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Sales</label>
            <select name="sales" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-56">
                <option value="">Semua Sales</option>
                <?php foreach ($salesOptions as $salesName): ?>
                    <option value="<?= e($salesName) ?>" <?= (string) $selectedSales === (string) $salesName ? 'selected' : '' ?>><?= e($salesName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status Aktivitas</label>
            <select name="status" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-56">
                <option value="">Semua Status</option>
                <?php foreach ($statusOptions as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= (string) $selectedStatus === (string) $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Per Tanggal</label>
            <input type="date" name="as_of" value="<?= e($asOfDate) ?>" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-44">
        </div>
        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                Terapkan Filter
            </button>
            <a href="<?= e(url('/laporan/aktivitas-customer')) ?>" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:bg-stone-50">
                Reset
            </a>
        </div>
    </form>

    <?php if (($reportData['ok'] ?? true) === false): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800"><?= e($reportData['error'] ?? 'Gagal memuat laporan aktivitas customer.') ?></div>
    <?php else: ?>
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm lg:col-span-2">
                <p class="text-sm font-medium text-stone-500">Customer Terfilter</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= number_format((int) ($summary['total_customer'] ?? 0), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Aktif</p>
                <p class="mt-2 text-2xl font-bold text-brand"><?= number_format((int) ($summary['aktif'] ?? 0), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Follow Up</p>
                <p class="mt-2 text-2xl font-bold text-yellow-600"><?= number_format((int) ($summary['follow_up'] ?? 0), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Dingin</p>
                <p class="mt-2 text-2xl font-bold text-orange-600"><?= number_format((int) ($summary['dingin'] ?? 0), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Dormant</p>
                <p class="mt-2 text-2xl font-bold text-red-600"><?= number_format((int) ($summary['dormant'] ?? 0), 0, ',', '.') ?></p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Omzet Customer Terfilter</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= rupiah($summary['total_penjualan'] ?? 0) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Sisa Piutang Customer Terfilter</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah($summary['total_piutang'] ?? 0) ?></p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm print:rounded-none print:border-stone-400 print:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm print:text-[10px]" <?= $autoExportPdf ? '' : 'data-simple-datatable data-dt-unit="customer" data-dt-empty="Tidak ada customer yang cocok."' ?>>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer / Laundry</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Sales</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice Terakhir</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="date">Tanggal Terakhir</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Hari Tidak Transaksi</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Status Aktivitas</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Jumlah Invoice</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Total Omzet</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Sisa Piutang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">No. WA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $lastInvoice = $item['last_invoice'] ?? [];
                            $phone = preg_replace('/[^0-9]/', '', (string) ($lastInvoice['no_telepon'] ?? ''));
                            ?>
                            <tr class="hover:bg-stone-50" data-dt-row>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">
                                    <?= e($item['nama_customer'] ?? '') ?>
                                    <?php if (! empty($item['kode_customer'])): ?>
                                        <span class="ml-2 text-xs font-medium text-stone-400"><?= e($item['kode_customer']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['last_sales_display'] ?: ($item['sales_display'] ?? '')) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                    <?php if (! empty($lastInvoice['kode_invoice'])): ?>
                                        <a href="<?= e(url('/invoice-view?code=' . ($lastInvoice['kode_invoice'] ?? ''))) ?>" class="hover:underline"><?= e($lastInvoice['nomor_invoice'] ?? '') ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($formatDate($lastInvoice['tanggal_invoice'] ?? null)) ?></td>
                                <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink">
                                    <?= $item['days_since_last_transaction'] === null ? '-' : number_format((int) $item['days_since_last_transaction'], 0, ',', '.') ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= e($statusClass($item['activity_status'] ?? 'dormant')) ?>">
                                        <?= e($item['activity_label'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((int) ($item['jumlah_invoice'] ?? 0), 0, ',', '.') ?></td>
                                <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-coral"><?= rupiah($item['total_piutang'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                    <?php if ($phone !== ''): ?>
                                        <a href="https://wa.me/<?= e($phone) ?>" target="_blank" class="font-semibold text-brand hover:underline print:text-stone-900 print:no-underline"><?= e($phone) ?></a>
                                    <?php else: ?>
                                        <span class="text-xs text-stone-400">No HP kosong</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (! $autoExportPdf): ?>
            <?php require dirname(__DIR__) . '/partials/simple-datatable.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($autoExportPdf): ?>
        <script>
            window.addEventListener('load', () => {
                document.title = <?= json_encode('Follow Up Customer ' . ($selectedSales !== '' ? $selectedSales . ' ' : '') . $formatDate($asOfDate), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                setTimeout(() => window.print(), 250);
            });
        </script>
    <?php endif; ?>
</section>

<style>
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            background: #fff !important;
        }

        header,
        footer {
            display: none !important;
        }

        main,
        section {
            margin: 0 !important;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
</style>
