<?php
$tab = $tab ?? 'barang';
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Data Master</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Data Master Aplikasi</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Kelola data barang, customer/laundry, dan sales agent yang tersimpan dalam sistem Busamas.
            </p>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="<?= e(url('/master?tab=barang')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'barang' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Barang
            </a>
            <a href="<?= e(url('/master?tab=customer')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'customer' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Customer
            </a>
            <a href="<?= e(url('/master?tab=sales')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'sales' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Sales
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <?php if ($tab === 'barang'): ?>
        <?php if (! ($masterBarang['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master barang belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterBarang['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Barang</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterBarang['summary']['total_barang']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Transaksi</p>
                    <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $masterBarang['summary']['total_transaksi']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                    <p class="mt-2 text-3xl font-bold text-coral"><?= e((string) $masterBarang['summary']['total_invoice']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-master-datatable data-empty-label="barang">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Barang</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Ukuran</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Harga</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Transaksi</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterBarang['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['ukuran'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(rupiah($item['harga_default'] ?? 0)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_transaksi'] ?? '0') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_invoice'] ?? '0') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'customer'): ?>
        <?php if (! ($masterCustomer['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master customer belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterCustomer['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Customer</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterCustomer['summary']['total_customer']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                    <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $masterCustomer['summary']['total_invoice']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Dengan Telepon</p>
                    <p class="mt-2 text-3xl font-bold text-coral"><?= e((string) $masterCustomer['summary']['total_dengan_telepon']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-master-datatable data-empty-label="customer">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Customer</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Laundry</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Telepon</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Invoice</th>
                                <th class="px-4 py-3 font-semibold" data-sort-type="text">Alamat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterCustomer['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['nama_laundry'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['no_telepon'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_invoice'] ?? '0') ?></td>
                                    <td class="min-w-72 px-4 py-3 text-stone-600"><?= e($item['alamat_default'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'sales'): ?>
        <?php if (! ($masterSales['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master sales belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterSales['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Sales</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterSales['summary']['total_sales']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-master-datatable data-empty-label="sales">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterSales['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_sales'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_sales'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
    document.querySelectorAll('[data-master-datatable]').forEach((table) => {
        const tbody = table.tBodies[0];
        const headers = Array.from(table.tHead?.rows[0]?.cells || []);
        const originalRows = Array.from(tbody?.rows || []);
        const label = table.dataset.emptyLabel || 'data';
        const state = {
            search: '',
            perPage: 25,
            page: 1,
            sortIndex: -1,
            sortDir: 'asc',
        };

        const sortNeutralIcon = ' ⇅';
        const sortAscIcon = ' ▲';
        const sortDescIcon = ' ▼';
        const wrapper = document.createElement('div');
        wrapper.className = 'border-b border-stone-100 bg-white p-4';
        wrapper.innerHTML = `
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
                    <span>data</span>
                </div>
                <label class="flex w-full items-center gap-2 text-sm text-stone-600 lg:w-80">
                    <span>Cari</span>
                    <input type="search" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="Ketik untuk mencari..." data-dt-search>
                </label>
            </div>
        `;

        const footer = document.createElement('div');
        footer.className = 'flex flex-col gap-3 border-t border-stone-100 bg-white p-4 text-sm text-stone-600 sm:flex-row sm:items-center sm:justify-between';
        footer.innerHTML = `
            <p data-dt-info></p>
            <div class="flex flex-wrap items-center gap-2" data-dt-pagination></div>
        `;

        table.closest('.overflow-hidden')?.insertBefore(wrapper, table.closest('.overflow-x-auto'));
        table.closest('.overflow-hidden')?.appendChild(footer);

        const perPageInput = wrapper.querySelector('[data-dt-per-page]');
        const searchInput = wrapper.querySelector('[data-dt-search]');
        const info = footer.querySelector('[data-dt-info]');
        const pagination = footer.querySelector('[data-dt-pagination]');

        function cleanNumber(value) {
            const normalized = String(value || '').replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
            const number = Number.parseFloat(normalized);
            return Number.isFinite(number) ? number : 0;
        }

        function rowText(row) {
            return Array.from(row.cells).map((cell) => cell.textContent.trim()).join(' ').toLowerCase();
        }

        function filteredRows() {
            let rows = originalRows.filter((row) => rowText(row).includes(state.search));

            if (state.sortIndex >= 0) {
                const sortType = headers[state.sortIndex]?.dataset.sortType || 'text';
                rows = [...rows].sort((left, right) => {
                    const leftText = left.cells[state.sortIndex]?.textContent.trim() || '';
                    const rightText = right.cells[state.sortIndex]?.textContent.trim() || '';
                    const result = sortType === 'number'
                        ? cleanNumber(leftText) - cleanNumber(rightText)
                        : leftText.localeCompare(rightText, 'id', { numeric: true, sensitivity: 'base' });
                    return state.sortDir === 'asc' ? result : -result;
                });
            }

            return rows;
        }

        function pageRows(rows) {
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

        function render() {
            const rows = filteredRows();
            const totalPages = state.perPage === 'all' ? 1 : Math.max(1, Math.ceil(rows.length / state.perPage));
            state.page = Math.min(state.page, totalPages);

            tbody.replaceChildren(...pageRows(rows));
            headers.forEach((header, index) => {
                header.classList.add('cursor-pointer', 'select-none');
                const base = header.dataset.label || header.textContent.replace(/[▲▼]/g, '').trim();
                header.dataset.label = base;
                const icon = state.sortIndex === index ? (state.sortDir === 'asc' ? sortAscIcon : sortDescIcon) : sortNeutralIcon;
                header.textContent = base + icon;
            });

            if (rows.length === 0) {
                const emptyRow = tbody.insertRow();
                const cell = emptyRow.insertCell();
                cell.colSpan = headers.length;
                cell.className = 'px-4 py-8 text-center text-sm text-stone-500';
                cell.textContent = `Tidak ada ${label} yang cocok.`;
            }

            const start = rows.length === 0 ? 0 : (state.perPage === 'all' ? 1 : ((state.page - 1) * state.perPage) + 1);
            const end = state.perPage === 'all' ? rows.length : Math.min(state.page * state.perPage, rows.length);
            info.textContent = `Menampilkan ${start}-${end} dari ${rows.length} data`;

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
