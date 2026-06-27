<section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <?php if (! ($invoiceDetail['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Invoice belum bisa dibuka.</p>
            <p class="mt-2"><?= e($invoiceDetail['error'] ?? 'Terjadi kesalahan saat membaca invoice.') ?></p>
        </div>
    <?php else: ?>
        <?php
            $invoice = $invoiceDetail['invoice'];
            $items = $invoiceDetail['items'];
            $summary = $invoiceDetail['summary'];
            $printDate = $invoice['tanggal_invoice'] ?: '';
            $exportUrl = url('/invoice-view') . '?' . http_build_query([
                'code' => $invoice['kode_invoice'] ?? '',
                'export' => 'pdf',
            ]);
            $autoExportPdf = ($_GET['export'] ?? '') === 'pdf';
        ?>

        <div class="mb-5 flex items-center justify-between print:hidden">
            <a href="<?= e(url('/invoices')) ?>" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                Kembali
            </a>
            <div class="flex flex-wrap gap-2">
                <a href="<?= e($exportUrl) ?>" target="_blank" rel="noopener" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
                    Export PDF
                </a>
                <button onclick="window.print()" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                    Print
                </button>
            </div>
        </div>

        <div class="mx-auto bg-white p-8 text-[11px] text-black shadow-sm ring-1 ring-stone-200 print:p-0 print:shadow-none print:ring-0" style="width: 210mm; min-height: 297mm;">
            <div class="pt-20">
                <h1 class="border-b-2 border-black pb-1 text-center text-base font-bold">INVOICE</h1>

                <div class="mt-4 grid grid-cols-[1.2fr_0.8fr] gap-10">
                    <div class="grid grid-cols-[70px_1fr] gap-y-2">
                        <div>Kepada</div>
                        <div>: <strong><?= e($invoice['nama_laundry_invoice'] ?? '') ?></strong></div>
                        <div>Alamat</div>
                        <div>: <strong><?= e($invoice['alamat'] ?? '') ?></strong></div>
                        <div>Up.</div>
                        <div>: <?= e($invoice['nama_customer_invoice'] ?? '') ?></div>
                    </div>

                    <div class="grid grid-cols-[90px_1fr] gap-y-2">
                        <div>Tanggal</div>
                        <div>: <strong><?= e($invoice['tanggal_invoice'] ?? '') ?></strong></div>
                        <div>No. Invoice</div>
                        <div>: <strong><?= e($invoice['nomor_invoice'] ?? '') ?></strong></div>
                        <div>PO Number</div>
                        <div>:</div>
                    </div>
                </div>

                <table class="mt-10 w-full border-collapse text-[11px]">
                    <thead>
                        <tr>
                            <th class="border border-black px-2 py-1 font-semibold">No.</th>
                            <th class="border border-black px-2 py-1 font-semibold">Nama Barang</th>
                            <th class="border border-black px-2 py-1 font-semibold">Isi</th>
                            <th class="border border-black px-2 py-1 font-semibold" colspan="2">Jumlah</th>
                            <th class="border border-black px-2 py-1 font-semibold">Harga</th>
                            <th class="border border-black px-2 py-1 font-semibold">Total ( Rp. )</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <td class="border border-black px-2 py-1 text-center"><?= e((string) ($index + 1)) ?></td>
                                <td class="border border-black px-2 py-1 font-semibold"><?= e($item['nama_barang_invoice'] ?? '') ?></td>
                                <td class="border border-black px-2 py-1 text-center"><?= e($item['isi_invoice'] ?? '') ?></td>
                                <td class="border border-black px-2 py-1 text-center"><?= e($item['jumlah'] ?? '') ?></td>
                                <td class="border border-black px-2 py-1 text-center"><?= e($item['satuan'] ?? '') ?></td>
                                <td class="border border-black px-2 py-1 text-right"><?= e(number_format((float) ($item['harga'] ?? 0), 0, ',', '.')) ?></td>
                                <td class="border border-black px-2 py-1 text-right"><?= e(number_format((float) ($item['total'] ?? 0), 0, ',', '.')) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php for ($row = count($items); $row < 7; $row++): ?>
                            <tr>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                                <td class="border border-black px-2 py-2">&nbsp;</td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <div class="mt-6 grid grid-cols-[1fr_220px] gap-8">
                    <div></div>
                    <div class="grid grid-cols-[1fr_1fr] gap-y-2">
                        <div>Sub total</div>
                        <div class="text-right font-bold"><?= e(number_format((float) ($summary['subtotal'] ?? 0), 0, ',', '.')) ?></div>
                        <div>Pembelian barang</div>
                        <div class="text-right font-bold"><?= e(number_format((float) ($invoice['total_pembelian_barang'] ?? 0), 0, ',', '.')) ?></div>
                        <div>Status pembelian</div>
                        <div class="text-right font-bold">
                            <?php if ((float) ($invoice['total_utang_pembelian_barang'] ?? 0) > 0): ?>
                                <span class="text-red-700">Utang</span>
                            <?php else: ?>
                                <span class="text-emerald-700">Lunas</span>
                            <?php endif; ?>
                        </div>
                        <div>Utang pembelian</div>
                        <div class="text-right font-bold"><?= e(number_format((float) ($invoice['total_utang_pembelian_barang'] ?? 0), 0, ',', '.')) ?></div>
                        <div>Disc.</div>
                        <div class="text-right font-bold"><?= e(number_format((float) ($summary['discount'] ?? 0), 0, ',', '.')) ?></div>
                        <div class="pt-3 font-bold">TOTAL</div>
                        <div class="pt-3 text-right font-bold"><?= e(number_format((float) ($summary['total'] ?? 0), 0, ',', '.')) ?></div>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-[1fr_220px] gap-8">
                    <p class="font-semibold italic">( <?= e($summary['terbilang'] ?? '') ?> )</p>
                    <p>Denpasar, <?= e($printDate) ?></p>
                </div>

                <div class="mt-8 grid grid-cols-[280px_1fr_160px] gap-8">
                    <div class="border border-black p-2 leading-5">
                        <p><strong>BCA - KCP Gatot Subroto Barat</strong></p>
                        <p>No. Rek : 6115506904</p>
                        <p>a/n : <strong>FRANS GATU HURINT</strong></p>
                    </div>
                    <div></div>
                    <div class="pt-20 text-center font-semibold">( Frans Hurint )</div>
                </div>
            </div>
        </div>

        <?php if ($autoExportPdf): ?>
            <script>
                window.addEventListener('load', () => {
                    document.title = <?= json_encode('Invoice ' . ($invoice['nomor_invoice'] ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                    setTimeout(() => window.print(), 250);
                });
            </script>
        <?php endif; ?>
    <?php endif; ?>
</section>

<style>
    @media print {
        @page {
            size: A4;
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
            padding: 0 !important;
        }
    }
</style>
