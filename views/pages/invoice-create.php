<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <?php if (! ($invoiceForm['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Form invoice belum bisa dibuka.</p>
        </div>
    <?php else: ?>
        <?php
            $edit = $invoiceForm['edit'] ?? ['mode' => 'create', 'invoice' => null, 'items' => []];
            $isUpdate = ($edit['mode'] ?? 'create') === 'update';
            $editInvoice = is_array($edit['invoice'] ?? null) ? $edit['invoice'] : [];
            $editItems = is_array($edit['items'] ?? null) ? $edit['items'] : [];
            $purchaseMode = (float) ($editInvoice['total_utang_pembelian_barang'] ?? 0) > 0 ? 'debt' : 'paid';
        ?>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Transaksi</p>
                <h1 class="text-3xl font-bold text-ink sm:text-4xl"><?= e($isUpdate ? 'Update Invoice' : 'Buat Invoice') ?></h1>
            </div>
            <a href="<?= e(url('/invoices')) ?>" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                Kembali
            </a>
        </div>

        <form method="post" action="<?= e(url('/invoice-create')) ?>" class="space-y-6" id="invoice-form">
            <?php if ($isUpdate): ?>
                <input type="hidden" name="kode_invoice" value="<?= e((string) ($editInvoice['kode_invoice'] ?? '')) ?>">
            <?php endif; ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Data Invoice</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nomor Invoice</span>
                        <input name="nomor_invoice" value="<?= e((string) ($editInvoice['nomor_invoice'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Invoice</span>
                        <input type="date" name="tanggal_invoice" value="<?= e((string) ($editInvoice['tanggal_invoice_input'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nomor Surat Jalan</span>
                        <input name="nomor_surat_jalan" value="<?= e((string) ($editInvoice['nomor_surat_jalan'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Surat Jalan</span>
                        <input type="date" name="tanggal_surat_jalan" value="<?= e((string) ($editInvoice['tanggal_surat_jalan_input'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">PO Number</span>
                        <input name="po_number" value="<?= e((string) ($editInvoice['po_number'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Master Customer & Sales</h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Laundry</span>
                        <select name="kode_customer" id="customer-select" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <option value="">Pilih customer</option>
                            <?php foreach ($invoiceForm['customers'] as $customer): ?>
                                <?php $selectedCustomer = (string) ($customer['kode_customer'] ?? '') === (string) ($editInvoice['kode_customer'] ?? ''); ?>
                                <option
                                    value="<?= e($customer['kode_customer'] ?? '') ?>"
                                    data-laundry="<?= e($customer['nama_laundry'] ?? '') ?>"
                                    data-customer="<?= e($customer['nama_customer'] ?? '') ?>"
                                    data-phone="<?= e($customer['no_telepon'] ?? '') ?>"
                                    data-address="<?= e($customer['alamat_default'] ?? '') ?>"
                                    <?= $selectedCustomer ? 'selected' : '' ?>
                                >
                                    <?= e(($customer['nama_laundry'] ?? '') . ' - ' . ($customer['kode_customer'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Customer</span>
                        <input name="nama_customer" id="customer-name" value="<?= e((string) ($editInvoice['nama_customer_invoice'] ?? $editInvoice['nama_customer_master'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">No. Telepon</span>
                        <input name="no_telepon" id="customer-phone" value="<?= e((string) ($editInvoice['no_telepon'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Alamat</span>
                        <input name="alamat" id="customer-address" value="<?= e((string) ($editInvoice['alamat'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Sales 1</span>
                        <select name="kode_sales_1" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <option value="">Pilih sales</option>
                            <?php foreach ($invoiceForm['sales'] as $sales): ?>
                                <option value="<?= e($sales['kode_sales'] ?? '') ?>" <?= (string) ($sales['kode_sales'] ?? '') === (string) ($editInvoice['kode_sales_1'] ?? '') ? 'selected' : '' ?>><?= e($sales['nama_sales'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Sales 2</span>
                        <select name="kode_sales_2" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <option value="">Pilih sales</option>
                            <?php foreach ($invoiceForm['sales'] as $sales): ?>
                                <option value="<?= e($sales['kode_sales'] ?? '') ?>" <?= (string) ($sales['kode_sales'] ?? '') === (string) ($editInvoice['kode_sales_2'] ?? '') ? 'selected' : '' ?>><?= e($sales['nama_sales'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-ink">Detail Barang</h2>
                    <button type="button" id="add-item" class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                        Tambah Barang
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="px-3 py-3 font-semibold">Barang</th>
                                <th class="px-3 py-3 font-semibold">Isi</th>
                                <th class="px-3 py-3 font-semibold">Jumlah</th>
                                <th class="px-3 py-3 font-semibold">Satuan</th>
                                <th class="px-3 py-3 font-semibold">Harga</th>
                                <th class="px-3 py-3 font-semibold">Total</th>
                                <th class="px-3 py-3 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody id="item-rows" class="divide-y divide-stone-100"></tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Status Pembayaran</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Status Pembayaran</span>
                        <select name="status_pembayaran" id="status-pembayaran" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <?php foreach ($invoiceForm['payment_statuses'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= (string) ($editInvoice['status_pembayaran'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block" data-payment-paid-field>
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Pembayaran</span>
                        <input type="date" name="tanggal_pembayaran" value="<?= e((string) ($editInvoice['tanggal_pembayaran'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block" data-payment-unpaid-field>
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Jumlah Terutang (Piutang)</span>
                        <input type="number" step="0.01" name="jumlah_terutang_piutang" id="jumlah-terutang-piutang" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                    </label>
                    <label class="block" data-payment-paid-field>
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Jumlah Terbayar (Pendapatan)</span>
                        <input type="number" step="0.01" name="jumlah_terbayar_pendapatan" id="jumlah-terbayar-pendapatan" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Discount</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Harga Normal Pricelist</span>
                        <input type="number" step="0.01" name="harga_normal_pricelist" id="harga-normal" value="<?= e((string) ($editInvoice['harga_normal_pricelist'] ?? '')) ?>" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Discount (%)</span>
                        <input type="number" step="0.01" name="discount" id="discount-percent" value="<?= e(clean_decimal($editInvoice['discount_persen'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Discount Amount</span>
                        <input type="number" step="0.01" name="discount_amount" id="discount-amount" value="<?= e((string) ($editInvoice['discount_amount'] ?? '')) ?>" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Total Harga Jual</span>
                        <input type="number" step="0.01" name="total_harga_jual" id="total-harga-jual" value="<?= e((string) ($editInvoice['total_harga_jual'] ?? '')) ?>" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm font-bold text-stone-600 outline-none">
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Pembayaran & Komisi</h2>
                <div class="space-y-6">

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Komisi Sales</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Sales 1 (%)</span>
                                <input type="number" step="0.01" name="komisi_sales_1_persen" id="komisi-sales-1-percent" value="<?= e(clean_decimal($editInvoice['komisi_sales_1_persen'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Sales 2 (%)</span>
                                <input type="number" step="0.01" name="komisi_sales_2_persen" id="komisi-sales-2-percent" value="<?= e(clean_decimal($editInvoice['komisi_sales_2_persen'] ?? '')) ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Total Komisi (%)</span>
                                <input type="number" step="0.01" name="total_komisi_persen" id="total-komisi-percent" readonly class="w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                            <label class="block" data-sales-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Sales Terbayar</span>
                                <input type="number" step="0.01" name="komisi_sales_terbayar" id="komisi-sales-paid" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Status Pembayaran Sales</span>
                                <select name="status_pembayaran_sales" id="status-pembayaran-sales" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <?php foreach ($invoiceForm['commission_statuses'] as $status): ?>
                                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block" data-sales-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Transfer Komisi Sales</span>
                                <input type="date" name="tanggal_transfer_komisi_sales" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block" data-sales-unpaid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Sales Belum Terbayar</span>
                                <input type="number" step="0.01" name="komisi_sales_belum_terbayar" id="komisi-sales-unpaid" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Komisi Manager</h3>
                        <div class="mb-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                                <input type="radio" name="mode_komisi_manager" value="paid" class="h-4 w-4 accent-brand">
                                Terbayar
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                                <input type="radio" name="mode_komisi_manager" value="debt" class="h-4 w-4 accent-brand" checked>
                                Komisi Manager Utang
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block" data-manager-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Manager Terbayar</span>
                                <input type="number" step="0.01" name="komisi_manager_terbayar" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block" data-manager-debt-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Manager Utang (Rp)</span>
                                <input type="number" step="0.01" name="komisi_manager_utang" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block" data-manager-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Transfer Manager</span>
                                <input type="date" name="tanggal_transfer_manager" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Pajak</h3>
                        <div class="mb-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                                <input type="radio" name="mode_pajak" value="paid" class="h-4 w-4 accent-brand">
                                Pajak Terbayar
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                                <input type="radio" name="mode_pajak" value="debt" class="h-4 w-4 accent-brand" checked>
                                Pajak Belum Terbayar
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block" data-tax-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">PPH Final Terbayar</span>
                                <input type="number" step="0.01" name="pph_final_terbayar" id="pph-final-paid" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                            <label class="block" data-tax-debt-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">PPH Final Belum Terbayar</span>
                                <input type="number" step="0.01" name="pph_final_belum_terbayar" id="pph-final-unpaid" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Komisi Admin</h3>
                        <div class="mb-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                                <input type="radio" name="mode_komisi_admin" value="paid" class="h-4 w-4 accent-brand">
                                Komisi Admin Terbayar
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                                <input type="radio" name="mode_komisi_admin" value="debt" class="h-4 w-4 accent-brand" checked>
                                Komisi Admin Belum Terbayar
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block" data-admin-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Admin Terbayar</span>
                                <input type="number" step="0.01" name="komisi_admin_terbayar" id="komisi-admin-paid" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                            <label class="block" data-admin-debt-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Admin Belum Terbayar</span>
                                <input type="number" step="0.01" name="komisi_admin_belum_terbayar" id="komisi-admin-unpaid" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                            <label class="block" data-admin-paid-field>
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Transfer Komisi Admin</span>
                                <input type="date" name="tanggal_transfer_komisi_admin" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Biaya-Biaya</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Biaya Kirim</span>
                                <input type="number" step="0.01" name="biaya_kirim" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Biaya Admin Bank</span>
                                <input type="number" step="0.01" name="biaya_admin_bank" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                        </div>
                    </section>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-ink">Pembelian Barang</h2>
                    </div>
                    <button type="button" id="toggle-purchase-panel" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-3 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                        Input Harga Pembelian Barang
                    </button>
                </div>

                <div class="mb-4 grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                        <input type="radio" name="mode_pembelian_barang" value="paid" class="h-4 w-4 accent-brand" <?= $purchaseMode === 'paid' ? 'checked' : '' ?>>
                        Bayar Pembelian Barang
                    </label>
                    <label class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 text-sm font-semibold text-ink">
                        <input type="radio" name="mode_pembelian_barang" value="debt" class="h-4 w-4 accent-brand" <?= $purchaseMode === 'debt' ? 'checked' : '' ?>>
                        Utang Pembelian Barang
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <label class="block" data-purchase-paid-field>
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Pembelian Barang</span>
                        <input type="number" step="0.01" name="pembelian_barang" id="purchase-paid-total" value="<?= e((string) ($editInvoice['total_pembelian_barang'] ?? '')) ?>" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block" data-purchase-debt-field>
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Jumlah Utang Pembelian Barang</span>
                        <input type="number" step="0.01" name="jumlah_utang_pembelian_barang" id="purchase-debt-total" value="<?= e((string) ($editInvoice['total_utang_pembelian_barang'] ?? '')) ?>" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block" data-purchase-paid-field>
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Transfer Pembelian Barang</span>
                        <input type="date" name="tanggal_transfer_pembelian_barang" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                </div>

                <div id="purchase-price-panel" class="mt-5 hidden rounded-lg border border-stone-200">
                    <div class="border-b border-stone-200 px-4 py-3">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-stone-500">Harga Pembelian Per Barang</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                            <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                                <tr>
                                    <th class="px-3 py-3 font-semibold">Barang</th>
                                    <th class="px-3 py-3 font-semibold">Jumlah</th>
                                    <th class="px-3 py-3 font-semibold">Harga Pembelian</th>
                                    <th class="px-3 py-3 font-semibold">Total Pembelian</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-price-rows" class="divide-y divide-stone-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="<?= e(url('/invoices')) ?>" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Batal
                </a>
                <button type="button" class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
                    Simpan Invoice
                </button>
            </div>
        </form>

        <script>
            const barangOptions = <?= json_encode($invoiceForm['barang'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const initialInvoiceItems = <?= json_encode($editItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const itemRows = document.querySelector('#item-rows');
            const addItemButton = document.querySelector('#add-item');
            const customerSelect = document.querySelector('#customer-select');
            const customerName = document.querySelector('#customer-name');
            const customerPhone = document.querySelector('#customer-phone');
            const customerAddress = document.querySelector('#customer-address');
            const hargaNormal = document.querySelector('#harga-normal');
            const discountPercent = document.querySelector('#discount-percent');
            const discountAmount = document.querySelector('#discount-amount');
            const totalHargaJual = document.querySelector('#total-harga-jual');
            const statusPembayaran = document.querySelector('#status-pembayaran');
            const paymentPaidFields = document.querySelectorAll('[data-payment-paid-field]');
            const paymentUnpaidFields = document.querySelectorAll('[data-payment-unpaid-field]');
            const jumlahTerutangPiutang = document.querySelector('#jumlah-terutang-piutang');
            const jumlahTerbayarPendapatan = document.querySelector('#jumlah-terbayar-pendapatan');
            const komisiSales1Percent = document.querySelector('#komisi-sales-1-percent');
            const komisiSales2Percent = document.querySelector('#komisi-sales-2-percent');
            const totalKomisiPercent = document.querySelector('#total-komisi-percent');
            const komisiSalesPaid = document.querySelector('#komisi-sales-paid');
            const komisiSalesUnpaid = document.querySelector('#komisi-sales-unpaid');
            const statusPembayaranSales = document.querySelector('#status-pembayaran-sales');
            const salesPaidFields = document.querySelectorAll('[data-sales-paid-field]');
            const salesUnpaidFields = document.querySelectorAll('[data-sales-unpaid-field]');
            const managerModeInputs = document.querySelectorAll('input[name="mode_komisi_manager"]');
            const managerPaidFields = document.querySelectorAll('[data-manager-paid-field]');
            const managerDebtFields = document.querySelectorAll('[data-manager-debt-field]');
            const taxModeInputs = document.querySelectorAll('input[name="mode_pajak"]');
            const taxPaidFields = document.querySelectorAll('[data-tax-paid-field]');
            const taxDebtFields = document.querySelectorAll('[data-tax-debt-field]');
            const pphFinalPaid = document.querySelector('#pph-final-paid');
            const pphFinalUnpaid = document.querySelector('#pph-final-unpaid');
            const adminModeInputs = document.querySelectorAll('input[name="mode_komisi_admin"]');
            const adminPaidFields = document.querySelectorAll('[data-admin-paid-field]');
            const adminDebtFields = document.querySelectorAll('[data-admin-debt-field]');
            const komisiAdminPaid = document.querySelector('#komisi-admin-paid');
            const komisiAdminUnpaid = document.querySelector('#komisi-admin-unpaid');
            const purchaseModeInputs = document.querySelectorAll('input[name="mode_pembelian_barang"]');
            const purchasePaidFields = document.querySelectorAll('[data-purchase-paid-field]');
            const purchaseDebtFields = document.querySelectorAll('[data-purchase-debt-field]');
            const purchasePaidTotal = document.querySelector('#purchase-paid-total');
            const purchaseDebtTotal = document.querySelector('#purchase-debt-total');
            const purchasePanel = document.querySelector('#purchase-price-panel');
            const purchaseRows = document.querySelector('#purchase-price-rows');
            const togglePurchasePanelButton = document.querySelector('#toggle-purchase-panel');

            function moneyValue(input) {
                if (!input) {
                    return 0;
                }

                return currencyToNumber(input.value);
            }

            function currencyToNumber(value) {
                value = String(value || '').trim();

                if (value === '') {
                    return 0;
                }

                value = value.replace(/[^\d,.-]/g, '');

                if (value.includes(',')) {
                    value = value.replace(/\./g, '').replace(',', '.');
                } else {
                    const dotParts = value.split('.');

                    if (dotParts.length > 1 && dotParts.slice(1).every((part) => part.length === 3)) {
                        value = dotParts.join('');
                    }

                    value = value.replace(/,/g, '');
                }

                return Number.parseFloat(value || '0') || 0;
            }

            function formatRupiah(value) {
                const number = Number.parseFloat(value || '0') || 0;

                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(number);
            }

            function setMoneyValue(input, value) {
                if (!input) {
                    return;
                }

                input.value = formatRupiah(value);
            }

            function cleanNumber(value, precision = 2) {
                const number = Number.parseFloat(value || '0') || 0;

                if (Math.abs(number) < 0.0000001) {
                    return '0';
                }

                return number.toFixed(precision).replace(/\.?0+$/, '');
            }

            function prepareMoneyFields() {
                document.querySelectorAll('.money-field, [data-item-harga], [data-item-total], [data-purchase-price], [data-purchase-total]').forEach((input) => {
                    input.type = 'text';
                    input.inputMode = 'decimal';

                    if (input.value !== '') {
                        setMoneyValue(input, currencyToNumber(input.value));
                    }
                });
            }

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function recalculateSummary() {
                const subtotal = Array.from(document.querySelectorAll('[data-item-total]')).reduce((sum, input) => sum + moneyValue(input), 0);
                const discountFromPercent = subtotal * (moneyValue(discountPercent) / 100);

                setMoneyValue(hargaNormal, subtotal);

                if (discountPercent.value !== '') {
                    setMoneyValue(discountAmount, discountFromPercent);
                }

                setMoneyValue(totalHargaJual, Math.max(subtotal - moneyValue(discountAmount), 0));
                recalculateReceivable();
                recalculateCommission();
                recalculateTax();
                recalculateAdminCommission();
            }

            function recalculateReceivable() {
                setMoneyValue(jumlahTerutangPiutang, Math.max(moneyValue(totalHargaJual) - moneyValue(jumlahTerbayarPendapatan), 0));
            }

            function togglePaymentFields() {
                const isPaid = statusPembayaran.value === 'Lunas';

                paymentPaidFields.forEach((field) => field.classList.toggle('hidden', !isPaid));
                paymentUnpaidFields.forEach((field) => field.classList.toggle('hidden', isPaid));

                if (isPaid && jumlahTerbayarPendapatan.value === '') {
                    setMoneyValue(jumlahTerbayarPendapatan, moneyValue(totalHargaJual));
                }

                recalculateReceivable();
            }

            function recalculateCommission() {
                const totalCommission = moneyValue(komisiSales1Percent) + moneyValue(komisiSales2Percent);
                totalKomisiPercent.value = totalCommission ? cleanNumber(totalCommission) : '0';
                const commissionAmount = moneyValue(totalHargaJual) * (totalCommission / 100);
                setMoneyValue(komisiSalesUnpaid, Math.max(commissionAmount - moneyValue(komisiSalesPaid), 0));
            }

            function toggleSalesPaymentFields() {
                const isPaid = statusPembayaranSales.value === 'Dibayar';

                salesPaidFields.forEach((field) => field.classList.toggle('hidden', !isPaid));
                salesUnpaidFields.forEach((field) => field.classList.toggle('hidden', isPaid));
            }

            function selectedManagerMode() {
                return document.querySelector('input[name="mode_komisi_manager"]:checked')?.value || 'debt';
            }

            function toggleManagerFields() {
                const isPaid = selectedManagerMode() === 'paid';

                managerPaidFields.forEach((field) => {
                    field.classList.toggle('hidden', !isPaid);
                    field.querySelectorAll('input').forEach((input) => input.disabled = !isPaid);
                });
                managerDebtFields.forEach((field) => {
                    field.classList.toggle('hidden', isPaid);
                    field.querySelectorAll('input').forEach((input) => input.disabled = isPaid);
                });
            }

            function selectedTaxMode() {
                return document.querySelector('input[name="mode_pajak"]:checked')?.value || 'debt';
            }

            function toggleTaxFields() {
                const isPaid = selectedTaxMode() === 'paid';

                taxPaidFields.forEach((field) => field.classList.toggle('hidden', !isPaid));
                taxDebtFields.forEach((field) => field.classList.toggle('hidden', isPaid));
                recalculateTax();
            }

            function recalculateTax() {
                const taxAmount = moneyValue(totalHargaJual) * 0.005;
                const formattedTax = taxAmount || 0;

                if (selectedTaxMode() === 'paid') {
                    setMoneyValue(pphFinalPaid, formattedTax);
                    setMoneyValue(pphFinalUnpaid, 0);
                } else {
                    setMoneyValue(pphFinalPaid, 0);
                    setMoneyValue(pphFinalUnpaid, formattedTax);
                }
            }

            function selectedAdminMode() {
                return document.querySelector('input[name="mode_komisi_admin"]:checked')?.value || 'debt';
            }

            function toggleAdminFields() {
                const isPaid = selectedAdminMode() === 'paid';

                adminPaidFields.forEach((field) => field.classList.toggle('hidden', !isPaid));
                adminDebtFields.forEach((field) => field.classList.toggle('hidden', isPaid));
                recalculateAdminCommission();
            }

            function recalculateAdminCommission() {
                const adminAmount = moneyValue(totalHargaJual) * 0.05;
                const formattedAdminAmount = adminAmount || 0;

                if (selectedAdminMode() === 'paid') {
                    setMoneyValue(komisiAdminPaid, formattedAdminAmount);
                    setMoneyValue(komisiAdminUnpaid, 0);
                } else {
                    setMoneyValue(komisiAdminPaid, 0);
                    setMoneyValue(komisiAdminUnpaid, formattedAdminAmount);
                }
            }

            function selectedPurchaseMode() {
                return document.querySelector('input[name="mode_pembelian_barang"]:checked')?.value || 'debt';
            }

            function togglePurchaseFields() {
                const isDebt = selectedPurchaseMode() === 'debt';

                purchasePaidFields.forEach((field) => {
                    field.classList.toggle('hidden', isDebt);
                    field.querySelectorAll('input').forEach((input) => input.disabled = isDebt);
                });
                purchaseDebtFields.forEach((field) => {
                    field.classList.toggle('hidden', !isDebt);
                    field.querySelectorAll('input').forEach((input) => input.disabled = !isDebt);
                });
            }

            function invoiceItemSnapshots() {
                return Array.from(itemRows.querySelectorAll('tr')).map((row) => {
                    const itemSelect = row.querySelector('[data-item-barang]');
                    return {
                        label: itemSelect?.selectedOptions[0]?.textContent.trim() || 'Barang belum dipilih',
                        quantity: moneyValue(row.querySelector('[data-item-jumlah]')),
                    };
                });
            }

            function rebuildPurchaseRows(updateTotals = true) {
                const snapshots = invoiceItemSnapshots();

                purchaseRows.innerHTML = snapshots.map((item, index) => `
                    <tr>
                        <td class="px-3 py-3 font-medium text-ink">
                            ${escapeHtml(item.label)}
                            <input type="hidden" name="purchase_items[${index}][nama_barang]" value="${escapeHtml(item.label)}">
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-stone-700">
                            ${item.quantity.toFixed(2)}
                            <input type="hidden" name="purchase_items[${index}][jumlah]" value="${item.quantity.toFixed(2)}">
                        </td>
                        <td class="px-3 py-3">
                            <input type="text" inputmode="decimal" name="purchase_items[${index}][harga_pembelian]" class="w-40 rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-purchase-price>
                        </td>
                        <td class="px-3 py-3">
                            <input type="text" inputmode="decimal" name="purchase_items[${index}][total_pembelian]" class="w-40 rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm font-semibold text-stone-600 outline-none" readonly data-purchase-total>
                        </td>
                    </tr>
                `).join('');

                if (updateTotals) {
                    recalculatePurchaseTotals();
                }
            }

            function recalculatePurchaseTotals() {
                let purchaseTotal = 0;

                purchaseRows.querySelectorAll('tr').forEach((row) => {
                    const quantity = Number.parseFloat(row.querySelector('input[name$="[jumlah]"]')?.value || '0') || 0;
                    const price = moneyValue(row.querySelector('[data-purchase-price]'));
                    const total = quantity * price;
                    setMoneyValue(row.querySelector('[data-purchase-total]'), total);
                    purchaseTotal += total;
                });

                if (selectedPurchaseMode() === 'debt') {
                    setMoneyValue(purchaseDebtTotal, purchaseTotal);
                } else {
                    setMoneyValue(purchasePaidTotal, purchaseTotal);
                }
            }

            function barangOptionMarkup() {
                return [
                    '<option value="">Pilih barang</option>',
                    ...barangOptions.map((item) => {
                        const label = `${item.nama_barang || ''} ${item.ukuran || ''}`.trim();
                        return `<option value="${item.kode_barang || ''}" data-isi="${item.isi_default || ''}" data-satuan="${item.satuan_default || ''}" data-harga="${item.harga_default || 0}">${label}</option>`;
                    }),
                ].join('');
            }

            function addItemRow(item = {}) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-3">
                        <select name="items[][kode_barang]" class="w-56 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-barang>
                            ${barangOptionMarkup()}
                        </select>
                    </td>
                    <td class="px-3 py-3">
                        <input name="items[][isi]" class="w-24 rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-isi>
                    </td>
                    <td class="px-3 py-3">
                        <input type="number" step="0.01" name="items[][jumlah]" class="w-24 rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-jumlah>
                    </td>
                    <td class="px-3 py-3">
                        <input name="items[][satuan]" class="w-24 rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-satuan>
                    </td>
                    <td class="px-3 py-3">
                        <input type="text" inputmode="decimal" name="items[][harga]" class="w-32 rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-harga>
                    </td>
                    <td class="px-3 py-3">
                        <input type="text" inputmode="decimal" name="items[][total]" class="w-36 rounded-lg border border-stone-300 px-3 py-2 text-sm font-semibold outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-total>
                    </td>
                    <td class="px-3 py-3">
                        <button type="button" class="rounded-md border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:border-red-400 hover:text-red-700" data-remove-item>Hapus</button>
                    </td>
                `;

                itemRows.appendChild(row);

                if (Object.keys(item).length > 0) {
                    row.querySelector('[data-item-barang]').value = item.kode_barang || '';
                    row.querySelector('[data-item-isi]').value = item.isi || '';
                    row.querySelector('[data-item-jumlah]').value = item.jumlah || '';
                    row.querySelector('[data-item-satuan]').value = item.satuan || '';
                    setMoneyValue(row.querySelector('[data-item-harga]'), item.harga || 0);
                    setMoneyValue(row.querySelector('[data-item-total]'), item.total || 0);
                }
            }

            itemRows.addEventListener('change', (event) => {
                if (!event.target.matches('[data-item-barang]')) {
                    return;
                }

                const selected = event.target.selectedOptions[0];
                const row = event.target.closest('tr');
                row.querySelector('[data-item-isi]').value = selected.dataset.isi || '';
                row.querySelector('[data-item-satuan]').value = selected.dataset.satuan || '';
                setMoneyValue(row.querySelector('[data-item-harga]'), selected.dataset.harga || 0);
                row.querySelector('[data-item-jumlah]').value = row.querySelector('[data-item-jumlah]').value || '1';
                setMoneyValue(row.querySelector('[data-item-total]'), moneyValue(row.querySelector('[data-item-jumlah]')) * moneyValue(row.querySelector('[data-item-harga]')));
                recalculateSummary();
                rebuildPurchaseRows();
            });

            itemRows.addEventListener('input', (event) => {
                if (!event.target.matches('[data-item-jumlah], [data-item-harga], [data-item-total]')) {
                    return;
                }

                const row = event.target.closest('tr');

                if (!event.target.matches('[data-item-total]')) {
                    setMoneyValue(row.querySelector('[data-item-total]'), moneyValue(row.querySelector('[data-item-jumlah]')) * moneyValue(row.querySelector('[data-item-harga]')));
                }

                recalculateSummary();
                rebuildPurchaseRows();
            });

            itemRows.addEventListener('click', (event) => {
                if (!event.target.matches('[data-remove-item]')) {
                    return;
                }

                event.target.closest('tr').remove();
                recalculateSummary();
                rebuildPurchaseRows();
            });

            purchaseRows.addEventListener('input', (event) => {
                if (!event.target.matches('[data-purchase-price]')) {
                    return;
                }

                recalculatePurchaseTotals();
            });

            document.addEventListener('focusin', (event) => {
                if (!event.target.matches('.money-field, [data-item-harga], [data-item-total], [data-purchase-price]')) {
                    return;
                }

                event.target.value = moneyValue(event.target) || '';
            });

            document.addEventListener('focusout', (event) => {
                if (!event.target.matches('.money-field, [data-item-harga], [data-item-total], [data-purchase-price], [data-purchase-total]')) {
                    return;
                }

                setMoneyValue(event.target, moneyValue(event.target));
            });

            customerSelect.addEventListener('change', () => {
                const selected = customerSelect.selectedOptions[0];
                customerName.value = selected.dataset.customer || '';
                customerPhone.value = selected.dataset.phone || '';
                customerAddress.value = selected.dataset.address || '';
            });

            addItemButton.addEventListener('click', addItemRow);
            discountPercent.addEventListener('input', recalculateSummary);
            statusPembayaran.addEventListener('change', togglePaymentFields);
            jumlahTerbayarPendapatan.addEventListener('input', recalculateReceivable);
            komisiSales1Percent.addEventListener('input', recalculateCommission);
            komisiSales2Percent.addEventListener('input', recalculateCommission);
            komisiSalesPaid.addEventListener('input', recalculateCommission);
            statusPembayaranSales.addEventListener('change', toggleSalesPaymentFields);
            managerModeInputs.forEach((input) => input.addEventListener('change', toggleManagerFields));
            taxModeInputs.forEach((input) => input.addEventListener('change', toggleTaxFields));
            adminModeInputs.forEach((input) => input.addEventListener('change', toggleAdminFields));
            purchaseModeInputs.forEach((input) => input.addEventListener('change', togglePurchaseFields));
            togglePurchasePanelButton.addEventListener('click', () => {
                purchasePanel.classList.toggle('hidden');
                rebuildPurchaseRows();
            });

            prepareMoneyFields();

            if (initialInvoiceItems.length > 0) {
                initialInvoiceItems.forEach((item) => addItemRow(item));
            } else {
                addItemRow();
            }
            recalculateSummary();
            togglePaymentFields();
            toggleSalesPaymentFields();
            toggleManagerFields();
            toggleTaxFields();
            toggleAdminFields();
            togglePurchaseFields();
            rebuildPurchaseRows(false);
        </script>
    <?php endif; ?>
</section>
