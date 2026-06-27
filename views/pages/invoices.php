<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Transaksi</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Invoice Mapping</h1>
            <p class="mt-4 max-w-2xl leading-7 text-stone-600">
                Data invoice tahun 2025 sampai Juni 2026 yang sudah dipetakan ke master customer dan master barang.
            </p>
        </div>
        <a href="<?= e(url('/invoice-create')) ?>" class="inline-flex items-center justify-center self-start rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800 sm:self-end">
            Buat Invoice
        </a>
    </div>

    <div class="mb-8 rounded-lg border border-stone-200 bg-white p-3 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Log Book Invoice</p>
            <div class="flex flex-wrap gap-2">
                <a href="<?= e(url('/invoice-payment-log')) ?>" class="inline-flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Pembayaran
                </a>
                <a href="<?= e(url('/invoice-purchase-log')) ?>" class="inline-flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Pembelian
                </a>
                <a href="<?= e(url('/invoice-commission-sales-log')) ?>" class="inline-flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Komisi Sales
                </a>
                <a href="<?= e(url('/invoice-commission-manager-log')) ?>" class="inline-flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Komisi Manager
                </a>
                <a href="<?= e(url('/invoice-commission-admin-log')) ?>" class="inline-flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Komisi Admin
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($_SESSION['google_sync_warnings'])): ?>
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-5 text-sm leading-6 text-rose-950 shadow-sm">
            <p class="font-bold text-base flex items-center gap-2 text-rose-900">
                <span>⚠️ Gagal Sinkronisasi Google Drive / Google Sheets</span>
            </p>
            <ul class="list-disc pl-5 mt-2 space-y-1">
                <?php foreach ($_SESSION['google_sync_warnings'] as $warning): ?>
                    <li><?= e($warning) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3 text-xs text-rose-700 italic">Catatan: Data invoice Anda tetap berhasil disimpan di database lokal, namun unggahan file XLSX ke Google Drive atau baris data di Google Sheets gagal/tertunda. Silakan periksa koneksi internet atau kredensial Google API.</p>
        </div>
        <?php unset($_SESSION['google_sync_warnings']); ?>
    <?php endif; ?>

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

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
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
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-invoice-datatable>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nomor Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="date">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Laundry</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Subtotal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Status</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="none">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($invoiceGroups as $group): ?>
                            <tr class="bg-stone-200/70" data-invoice-group="<?= e($group['label']) ?>">
                                <td colspan="3" class="px-4 py-3 text-sm font-bold text-ink">
                                    <?= e($group['label']) ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-ink"><?= e(rupiah($group['subtotal'])) ?></td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
                            </tr>
                            <?php foreach ($group['items'] as $invoice): ?>
                                <?php
                                    $paymentStatus = (string) ($invoice['status_pembayaran'] ?? 'Belum Lunas');
                                    $isUnpaid = strcasecmp($paymentStatus, 'Lunas') !== 0;
                                    $rowClass = $isUnpaid ? 'bg-red-50 hover:bg-red-100/70' : 'bg-teal-50/70 hover:bg-teal-100/70';
                                    $statusClass = $isUnpaid ? 'bg-red-100 text-red-800 ring-red-200' : 'bg-teal-100 text-teal-800 ring-teal-200';
                                    $statusLabel = $isUnpaid ? 'Belum Lunas' : 'Lunas';
                                ?>
                                <tr class="<?= e($rowClass) ?>" data-invoice-row data-group-label="<?= e($group['label']) ?>">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($invoice['nomor_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($invoice['tanggal_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($invoice['nama_laundry_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(rupiah($invoice['subtotal'] ?? 0)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= e($statusClass) ?>">
                                            <?= e($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 flex items-center gap-2">
                                        <a
                                            href="<?= e(url('/invoice-view') . '?' . http_build_query(['code' => $invoice['kode_invoice'] ?? ''])) ?>"
                                            class="inline-flex items-center rounded-md border border-stone-300 px-3 py-1.5 text-xs font-semibold text-brand transition hover:border-brand hover:bg-teal-50"
                                            title="Lihat invoice"
                                            aria-label="Lihat invoice"
                                        >Lihat</a>
                                        <a
                                            href="<?= e(url('/invoice-create') . '?' . http_build_query(['code' => $invoice['kode_invoice'] ?? ''])) ?>"
                                            class="inline-flex items-center rounded-md bg-brand px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-teal-800"
                                            title="Update invoice"
                                            aria-label="Update invoice"
                                        >Update</a>
                                        <form action="<?= e(url('/invoice-delete')) ?>" method="POST" class="inline" data-confirm-message="Apakah Anda yakin ingin menghapus invoice ini?">
                                            <input type="hidden" name="kode_invoice" value="<?= e($invoice['kode_invoice'] ?? '') ?>">
                                            <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-800" title="Hapus invoice" aria-label="Hapus invoice">Hapus</button>
                                        </form>
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

<script>
    document.querySelectorAll('[data-invoice-datatable]').forEach((table) => {
        const tbody = table.tBodies[0];
        const headers = Array.from(table.tHead?.rows[0]?.cells || []);
        const groupRows = new Map(Array.from(tbody.querySelectorAll('[data-invoice-group]')).map((row) => [row.dataset.invoiceGroup, row.cloneNode(true)]));
        const invoiceRows = Array.from(tbody.querySelectorAll('[data-invoice-row]'));
        const sortNeutralIcon = ' ⇅';
        const sortAscIcon = ' ▲';
        const sortDescIcon = ' ▼';
        const state = {
            search: '',
            perPage: 25,
            page: 1,
            sortIndex: -1,
            sortDir: 'asc',
        };

        const toolbar = document.createElement('div');
        toolbar.className = 'border-b border-stone-100 bg-white p-4';
        toolbar.innerHTML = `
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                    <span>Tampilkan</span>
                    <select class="rounded-md border border-stone-300 bg-white px-2 py-1.5 text-sm outline-none focus:border-brand" data-dt-per-page>
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">Semua</option>
                    </select>
                    <span>invoice</span>
                </div>
                <label class="flex w-full items-center gap-2 text-sm text-stone-600 lg:w-96">
                    <span>Cari</span>
                    <input type="search" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="Nomor, laundry, status..." data-dt-search>
                </label>
            </div>
        `;

        const footer = document.createElement('div');
        footer.className = 'flex flex-col gap-3 border-t border-stone-100 bg-white p-4 text-sm text-stone-600 sm:flex-row sm:items-center sm:justify-between';
        footer.innerHTML = `
            <p data-dt-info></p>
            <div class="flex flex-wrap items-center gap-2" data-dt-pagination></div>
        `;

        table.closest('.overflow-hidden')?.insertBefore(toolbar, table.closest('.overflow-x-auto'));
        table.closest('.overflow-hidden')?.appendChild(footer);

        const perPageInput = toolbar.querySelector('[data-dt-per-page]');
        const searchInput = toolbar.querySelector('[data-dt-search]');
        const info = footer.querySelector('[data-dt-info]');
        const pagination = footer.querySelector('[data-dt-pagination]');

        function cleanNumber(value) {
            const normalized = String(value || '').replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
            const number = Number.parseFloat(normalized);
            return Number.isFinite(number) ? number : 0;
        }

        function cleanDate(value) {
            const timestamp = Date.parse(String(value || '').trim());
            return Number.isFinite(timestamp) ? timestamp : 0;
        }

        function rowText(row) {
            return Array.from(row.cells).map((cell) => cell.textContent.trim()).join(' ').toLowerCase();
        }

        function filteredRows() {
            let rows = invoiceRows.filter((row) => rowText(row).includes(state.search));

            if (state.sortIndex >= 0) {
                const sortType = headers[state.sortIndex]?.dataset.sortType || 'text';
                if (sortType !== 'none') {
                    rows = [...rows].sort((left, right) => {
                        const leftText = left.cells[state.sortIndex]?.textContent.trim() || '';
                        const rightText = right.cells[state.sortIndex]?.textContent.trim() || '';
                        let result;
                        if (sortType === 'number') {
                            result = cleanNumber(leftText) - cleanNumber(rightText);
                        } else if (sortType === 'date') {
                            result = cleanDate(leftText) - cleanDate(rightText);
                        } else {
                            result = leftText.localeCompare(rightText, 'id', { numeric: true, sensitivity: 'base' });
                        }
                        return state.sortDir === 'asc' ? result : -result;
                    });
                }
            }

            return rows;
        }

        function visibleRows(rows) {
            if (state.perPage === 'all') {
                return rows;
            }
            const start = (state.page - 1) * state.perPage;
            return rows.slice(start, start + state.perPage);
        }

        function button(labelText, disabled, active, onClick) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = labelText;
            btn.disabled = disabled;
            btn.className = [
                'min-w-9 rounded-md border px-3 py-1.5 text-sm font-semibold transition',
                active ? 'border-brand bg-brand text-white' : 'border-stone-300 bg-white text-ink hover:border-brand hover:text-brand',
                disabled ? 'cursor-not-allowed opacity-50 hover:border-stone-300 hover:text-ink' : '',
            ].join(' ');
            btn.addEventListener('click', onClick);
            return btn;
        }

        function renderHeaders() {
            headers.forEach((header, index) => {
                const sortType = header.dataset.sortType || 'text';
                const base = header.dataset.label || header.textContent.replace(/[▲▼⇅]/g, '').trim();
                header.dataset.label = base;
                if (sortType === 'none') {
                    header.textContent = base;
                    header.classList.remove('cursor-pointer', 'select-none');
                    return;
                }
                header.classList.add('cursor-pointer', 'select-none');
                const icon = state.sortIndex === index ? (state.sortDir === 'asc' ? sortAscIcon : sortDescIcon) : sortNeutralIcon;
                header.textContent = base + icon;
            });
        }

        function render() {
            const rows = filteredRows();
            const totalPages = state.perPage === 'all' ? 1 : Math.max(1, Math.ceil(rows.length / state.perPage));
            state.page = Math.min(state.page, totalPages);

            const pageRows = visibleRows(rows);
            const fragment = document.createDocumentFragment();
            let lastGroup = '';

            pageRows.forEach((row) => {
                const groupLabel = row.dataset.groupLabel || '';
                if (groupLabel && groupLabel !== lastGroup) {
                    fragment.appendChild((groupRows.get(groupLabel) || document.createElement('tr')).cloneNode(true));
                    lastGroup = groupLabel;
                }
                fragment.appendChild(row);
            });

            tbody.replaceChildren(fragment);

            if (rows.length === 0) {
                const emptyRow = tbody.insertRow();
                const cell = emptyRow.insertCell();
                cell.colSpan = headers.length;
                cell.className = 'px-4 py-8 text-center text-sm text-stone-500';
                cell.textContent = 'Tidak ada invoice yang cocok.';
            }

            renderHeaders();

            const start = rows.length === 0 ? 0 : (state.perPage === 'all' ? 1 : ((state.page - 1) * state.perPage) + 1);
            const end = state.perPage === 'all' ? rows.length : Math.min(state.page * state.perPage, rows.length);
            info.textContent = `Menampilkan ${start}-${end} dari ${rows.length} invoice`;

            pagination.replaceChildren();
            pagination.appendChild(button('Prev', state.page <= 1, false, () => {
                state.page -= 1;
                render();
            }));

            const totalButtons = state.perPage === 'all' ? 1 : totalPages;
            for (let page = 1; page <= totalButtons; page += 1) {
                if (totalButtons > 7 && page !== 1 && page !== totalButtons && Math.abs(page - state.page) > 1) {
                    if (page === 2 || page === totalButtons - 1) {
                        const dots = document.createElement('span');
                        dots.className = 'px-1 text-stone-400';
                        dots.textContent = '...';
                        pagination.appendChild(dots);
                    }
                    continue;
                }
                pagination.appendChild(button(String(page), false, page === state.page, () => {
                    state.page = page;
                    render();
                }));
            }

            pagination.appendChild(button('Next', state.page >= totalPages, false, () => {
                state.page += 1;
                render();
            }));
        }

        perPageInput.addEventListener('change', () => {
            state.perPage = perPageInput.value === 'all' ? 'all' : Number.parseInt(perPageInput.value, 10);
            state.page = 1;
            render();
        });

        searchInput.addEventListener('input', () => {
            state.search = searchInput.value.trim().toLowerCase();
            state.page = 1;
            render();
        });

        headers.forEach((header, index) => {
            if ((header.dataset.sortType || 'text') === 'none') {
                return;
            }
            header.addEventListener('click', () => {
                if (state.sortIndex === index) {
                    state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortIndex = index;
                    state.sortDir = 'asc';
                }
                render();
            });
        });

        render();
    });
</script>
