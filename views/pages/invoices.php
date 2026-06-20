<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Transaksi</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Invoice Mapping</h1>
            <p class="mt-4 max-w-2xl leading-7 text-stone-600">
                Data invoice tahun 2025 sampai Juni 2026 yang sudah dipetakan ke master customer dan master barang.
            </p>
        </div>
    </div>

    <?php if (! ($invoiceMapping['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Invoice mapping belum bisa dibaca.</p>
            <p class="mt-2"><?= e($invoiceMapping['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
        </div>
    <?php else: ?>
        <form method="get" action="<?= e(url('/invoices')) ?>" class="mb-6 rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
            <div class="grid gap-4 md:grid-cols-[1fr_1fr_2fr_auto_auto] md:items-end">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Bulan</span>
                    <select name="month" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                        <option value="">Semua bulan</option>
                        <?php foreach (invoice_months() as $monthNumber => $monthName): ?>
                            <option value="<?= e((string) $monthNumber) ?>" <?= (string) ($invoiceMapping['filters']['month'] ?? '') === (string) $monthNumber ? 'selected' : '' ?>>
                                <?= e($monthName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Tahun</span>
                    <select name="year" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                        <option value="">Semua tahun</option>
                        <?php foreach ($invoiceMapping['options']['years'] as $year): ?>
                            <option value="<?= e($year) ?>" <?= (string) ($invoiceMapping['filters']['year'] ?? '') === (string) $year ? 'selected' : '' ?>>
                                <?= e($year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Laundry</span>
                    <input
                        name="laundry"
                        value="<?= e($invoiceMapping['filters']['laundry'] ?? '') ?>"
                        list="laundry-options"
                        placeholder="Cari nama laundry"
                        class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                    >
                    <datalist id="laundry-options">
                        <?php foreach ($invoiceMapping['options']['laundries'] as $laundry): ?>
                            <option value="<?= e($laundry) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>

                <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
                    Filter
                </button>

                <a href="<?= e(url('/invoices')) ?>" class="rounded-lg border border-stone-300 px-4 py-2 text-center text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Reset
                </a>
            </div>
        </form>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $invoiceMapping['summary']['total_invoice']) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Detail</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $invoiceMapping['summary']['total_detail']) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Subtotal</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= e(rupiah($invoiceMapping['summary']['subtotal'])) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Pembelian Barang</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= e(rupiah($invoiceMapping['summary']['total_pembelian_barang'] ?? 0)) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Utang Pembelian</p>
                <p class="mt-2 text-3xl font-bold text-red-700"><?= e(rupiah($invoiceMapping['summary']['total_utang_pembelian_barang'] ?? 0)) ?></p>
                <p class="mt-1 text-xs font-medium text-stone-500"><?= e((string) ($invoiceMapping['summary']['total_invoice_utang'] ?? 0)) ?> invoice</p>
            </div>
        </div>

        <?php if (! empty($invoiceMapping['customer_summary'])): ?>
            <div class="mb-6 grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-stone-200 bg-white shadow-sm">
                    <div class="border-b border-stone-200 px-4 py-3">
                        <h2 class="font-semibold text-ink">Pembelian Terbesar</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-left text-sm">
                            <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Laundry</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <?php foreach (array_slice($invoiceMapping['customer_summary'], 0, 5) as $customer): ?>
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-ink"><?= e($customer['nama_laundry'] ?: $customer['nama_customer']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e((string) $customer['jumlah_invoice']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e(rupiah($customer['subtotal'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-stone-200 bg-white shadow-sm">
                    <div class="border-b border-stone-200 px-4 py-3">
                        <h2 class="font-semibold text-ink">Pembelian Terkecil</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-left text-sm">
                            <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Laundry</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Invoice</th>
                                    <th class="whitespace-nowrap px-4 py-3 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <?php foreach (array_slice(array_reverse($invoiceMapping['customer_summary']), 0, 5) as $customer): ?>
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-ink"><?= e($customer['nama_laundry'] ?: $customer['nama_customer']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e((string) $customer['jumlah_invoice']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-coral"><?= e(rupiah($customer['subtotal'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
            $invoiceGroups = [];

            foreach ($invoiceMapping['items'] as $invoice) {
                $invoiceNumber = (string) ($invoice['nomor_invoice'] ?? '');
                $periodMonth = invoice_month_number($invoiceNumber);
                $periodYear = invoice_year($invoiceNumber);
                $periodKey = $periodYear . '-' . str_pad((string) $periodMonth, 2, '0', STR_PAD_LEFT);
                $periodLabel = trim((invoice_months()[$periodMonth] ?? 'Tanpa Bulan') . ' ' . ($periodYear ?: 'Tanpa Tahun'));

                if (! isset($invoiceGroups[$periodKey])) {
                    $invoiceGroups[$periodKey] = [
                        'label' => $periodLabel,
                        'items' => [],
                        'subtotal' => 0,
                        'total_pembelian_barang' => 0,
                        'total_utang_pembelian_barang' => 0,
                    ];
                }

                $invoiceGroups[$periodKey]['items'][] = $invoice;
                $invoiceGroups[$periodKey]['subtotal'] += (float) ($invoice['subtotal'] ?? 0);
                $invoiceGroups[$periodKey]['total_pembelian_barang'] += (float) ($invoice['total_pembelian_barang'] ?? 0);
                $invoiceGroups[$periodKey]['total_utang_pembelian_barang'] += (float) ($invoice['total_utang_pembelian_barang'] ?? 0);
            }
        ?>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nomor Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Laundry</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Subtotal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Pembelian Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Utang Pembelian Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Lihat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($invoiceGroups as $group): ?>
                            <tr class="bg-stone-200/70">
                                <td colspan="4" class="px-4 py-3 text-sm font-bold text-ink">
                                    <?= e($group['label']) ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-ink"><?= e(rupiah($group['subtotal'])) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-ink"><?= e(rupiah($group['total_pembelian_barang'])) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-red-700"><?= e(rupiah($group['total_utang_pembelian_barang'])) ?></td>
                                <td class="px-4 py-3"></td>
                            </tr>
                            <?php foreach ($group['items'] as $invoice): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($invoice['kode_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($invoice['nomor_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($invoice['tanggal_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($invoice['nama_laundry_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(rupiah($invoice['subtotal'] ?? 0)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(rupiah($invoice['total_pembelian_barang'] ?? 0)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <?php $debtTotal = (float) ($invoice['total_utang_pembelian_barang'] ?? 0); ?>
                                        <?php if ($debtTotal > 0): ?>
                                            <span class="font-semibold text-red-700"><?= e(rupiah($debtTotal)) ?></span>
                                        <?php else: ?>
                                            <span class="text-stone-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <a
                                            href="<?= e(url('/invoice-view') . '?' . http_build_query(['code' => $invoice['kode_invoice'] ?? ''])) ?>"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-stone-300 text-sm font-semibold text-brand transition hover:border-brand hover:bg-teal-50"
                                            title="Lihat invoice"
                                            aria-label="Lihat invoice"
                                        >&#128065;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
