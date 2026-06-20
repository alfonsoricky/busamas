<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <?php if (! ($invoiceForm['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Form invoice belum bisa dibuka.</p>
        </div>
    <?php else: ?>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Transaksi</p>
                <h1 class="text-3xl font-bold text-ink sm:text-4xl">Buat Invoice</h1>
            </div>
            <a href="<?= e(url('/invoices')) ?>" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                Kembali
            </a>
        </div>

        <form method="post" action="<?= e(url('/invoice-create')) ?>" class="space-y-6" id="invoice-form">
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Data Invoice</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nomor Invoice</span>
                        <input name="nomor_invoice" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Invoice</span>
                        <input type="date" name="tanggal_invoice" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nomor Surat Jalan</span>
                        <input name="nomor_surat_jalan" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Surat Jalan</span>
                        <input type="date" name="tanggal_surat_jalan" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
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
                                <option
                                    value="<?= e($customer['kode_customer'] ?? '') ?>"
                                    data-laundry="<?= e($customer['nama_laundry'] ?? '') ?>"
                                    data-customer="<?= e($customer['nama_customer'] ?? '') ?>"
                                    data-phone="<?= e($customer['no_telepon'] ?? '') ?>"
                                    data-address="<?= e($customer['alamat_default'] ?? '') ?>"
                                >
                                    <?= e(($customer['nama_laundry'] ?? '') . ' - ' . ($customer['kode_customer'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Customer</span>
                        <input name="nama_customer" id="customer-name" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">No. Telepon</span>
                        <input name="no_telepon" id="customer-phone" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Alamat</span>
                        <input name="alamat" id="customer-address" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Sales 1</span>
                        <select name="kode_sales_1" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <option value="">Pilih sales</option>
                            <?php foreach ($invoiceForm['sales'] as $sales): ?>
                                <option value="<?= e($sales['kode_sales'] ?? '') ?>"><?= e($sales['nama_sales'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Nama Sales 2</span>
                        <select name="kode_sales_2" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <option value="">Pilih sales</option>
                            <?php foreach ($invoiceForm['sales'] as $sales): ?>
                                <option value="<?= e($sales['kode_sales'] ?? '') ?>"><?= e($sales['nama_sales'] ?? '') ?></option>
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
                        <select name="status_pembayaran" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <?php foreach ($invoiceForm['payment_statuses'] as $status): ?>
                                <option value="<?= e($status) ?>"><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Pembayaran</span>
                        <input type="date" name="tanggal_pembayaran" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Jumlah Terutang (Piutang)</span>
                        <input type="number" step="0.01" name="jumlah_terutang_piutang" id="jumlah-terutang-piutang" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-stone-700">Jumlah Terbayar (Pendapatan)</span>
                        <input type="number" step="0.01" name="jumlah_terbayar_pendapatan" id="jumlah-terbayar-pendapatan" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-ink">Pembayaran & Komisi</h2>
                <div class="space-y-6">
                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Discount</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Harga Normal Pricelist</span>
                                <input type="number" step="0.01" name="harga_normal_pricelist" id="harga-normal" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Discount (%)</span>
                                <input type="number" step="0.01" name="discount" id="discount-percent" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Discount Amount</span>
                                <input type="number" step="0.01" name="discount_amount" id="discount-amount" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm text-stone-600 outline-none">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Total Harga Jual</span>
                                <input type="number" step="0.01" name="total_harga_jual" id="total-harga-jual" readonly class="money-field w-full rounded-lg border border-stone-200 bg-stone-100 px-3 py-2 text-sm font-bold text-stone-600 outline-none">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Komisi Sales</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Sales 1 (%)</span>
                                <input type="number" step="0.01" name="komisi_sales_1_persen" id="komisi-sales-1-percent" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Sales 2 (%)</span>
                                <input type="number" step="0.01" name="komisi_sales_2_persen" id="komisi-sales-2-percent" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
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
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Manager Terbayar</span>
                                <input type="number" step="0.01" name="komisi_manager_terbayar" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Manager Utang (Rp)</span>
                                <input type="number" step="0.01" name="komisi_manager_utang" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Transfer Manager</span>
                                <input type="date" name="tanggal_transfer_manager" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Pajak</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">PPH Final Terbayar</span>
                                <input type="number" step="0.01" name="pph_final_terbayar" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">PPH Final Belum Terbayar</span>
                                <input type="number" step="0.01" name="pph_final_belum_terbayar" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-stone-200 pt-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-stone-500">Komisi Admin</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Admin Terbayar</span>
                                <input type="number" step="0.01" name="komisi_admin_terbayar" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Komisi Admin Belum Terbayar</span>
                                <input type="number" step="0.01" name="komisi_admin_belum_terbayar" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
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
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Pembelian Barang</span>
                                <input type="number" step="0.01" name="pembelian_barang" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Jumlah Utang Pembelian Barang</span>
                                <input type="number" step="0.01" name="jumlah_utang_pembelian_barang" class="money-field w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-stone-700">Tanggal Transfer Pembelian Barang</span>
                                <input type="date" name="tanggal_transfer_pembelian_barang" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                            </label>
                        </div>
                    </section>
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

            function moneyValue(input) {
                return Number.parseFloat(input.value || '0') || 0;
            }

            function recalculateSummary() {
                const subtotal = Array.from(document.querySelectorAll('[data-item-total]')).reduce((sum, input) => sum + moneyValue(input), 0);
                const discountFromPercent = subtotal * (moneyValue(discountPercent) / 100);

                hargaNormal.value = subtotal ? subtotal.toFixed(2) : '';

                if (discountPercent.value !== '') {
                    discountAmount.value = discountFromPercent.toFixed(2);
                }

                totalHargaJual.value = Math.max(subtotal - moneyValue(discountAmount), 0).toFixed(2);
                recalculateReceivable();
                recalculateCommission();
            }

            function recalculateReceivable() {
                jumlahTerutangPiutang.value = Math.max(moneyValue(totalHargaJual) - moneyValue(jumlahTerbayarPendapatan), 0).toFixed(2);
            }

            function recalculateCommission() {
                const totalCommission = moneyValue(komisiSales1Percent) + moneyValue(komisiSales2Percent);
                totalKomisiPercent.value = totalCommission ? totalCommission.toFixed(2) : '';
                const commissionAmount = moneyValue(totalHargaJual) * (totalCommission / 100);
                komisiSalesUnpaid.value = Math.max(commissionAmount - moneyValue(komisiSalesPaid), 0).toFixed(2);
            }

            function toggleSalesPaymentFields() {
                const isPaid = statusPembayaranSales.value === 'Dibayar';

                salesPaidFields.forEach((field) => field.classList.toggle('hidden', !isPaid));
                salesUnpaidFields.forEach((field) => field.classList.toggle('hidden', isPaid));
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

            function addItemRow() {
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
                        <input type="number" step="0.01" name="items[][harga]" class="w-32 rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-harga>
                    </td>
                    <td class="px-3 py-3">
                        <input type="number" step="0.01" name="items[][total]" class="w-36 rounded-lg border border-stone-300 px-3 py-2 text-sm font-semibold outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" data-item-total>
                    </td>
                    <td class="px-3 py-3">
                        <button type="button" class="rounded-md border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:border-red-400 hover:text-red-700" data-remove-item>Hapus</button>
                    </td>
                `;

                itemRows.appendChild(row);
            }

            itemRows.addEventListener('change', (event) => {
                if (!event.target.matches('[data-item-barang]')) {
                    return;
                }

                const selected = event.target.selectedOptions[0];
                const row = event.target.closest('tr');
                row.querySelector('[data-item-isi]').value = selected.dataset.isi || '';
                row.querySelector('[data-item-satuan]').value = selected.dataset.satuan || '';
                row.querySelector('[data-item-harga]').value = selected.dataset.harga || '';
                row.querySelector('[data-item-jumlah]').value = row.querySelector('[data-item-jumlah]').value || '1';
                row.querySelector('[data-item-total]').value = (moneyValue(row.querySelector('[data-item-jumlah]')) * moneyValue(row.querySelector('[data-item-harga]'))).toFixed(2);
                recalculateSummary();
            });

            itemRows.addEventListener('input', (event) => {
                if (!event.target.matches('[data-item-jumlah], [data-item-harga], [data-item-total]')) {
                    return;
                }

                const row = event.target.closest('tr');

                if (!event.target.matches('[data-item-total]')) {
                    row.querySelector('[data-item-total]').value = (moneyValue(row.querySelector('[data-item-jumlah]')) * moneyValue(row.querySelector('[data-item-harga]'))).toFixed(2);
                }

                recalculateSummary();
            });

            itemRows.addEventListener('click', (event) => {
                if (!event.target.matches('[data-remove-item]')) {
                    return;
                }

                event.target.closest('tr').remove();
                recalculateSummary();
            });

            customerSelect.addEventListener('change', () => {
                const selected = customerSelect.selectedOptions[0];
                customerName.value = selected.dataset.customer || '';
                customerPhone.value = selected.dataset.phone || '';
                customerAddress.value = selected.dataset.address || '';
            });

            addItemButton.addEventListener('click', addItemRow);
            discountPercent.addEventListener('input', recalculateSummary);
            jumlahTerbayarPendapatan.addEventListener('input', recalculateReceivable);
            komisiSales1Percent.addEventListener('input', recalculateCommission);
            komisiSales2Percent.addEventListener('input', recalculateCommission);
            komisiSalesPaid.addEventListener('input', recalculateCommission);
            statusPembayaranSales.addEventListener('change', toggleSalesPaymentFields);

            addItemRow();
            toggleSalesPaymentFields();
        </script>
    <?php endif; ?>
</section>
