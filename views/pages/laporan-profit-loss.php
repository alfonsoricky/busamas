<?php
$pendapatan = $reportData['pendapatan'] ?? 0.0;
$hpp = $reportData['hpp'] ?? 0.0;
$laba_kotor = $reportData['laba_kotor'] ?? 0.0;
$komisi = $reportData['komisi'] ?? 0.0;
$laba_bersih = $reportData['laba_bersih'] ?? 0.0;
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <!-- Breadcrumbs -->
    <div class="mb-4">
        <a href="<?= e(url('/laporan')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Kembali ke Laporan Utama
        </a>
    </div>

    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Laporan Keuangan</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Laporan Profit & Loss</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">
            Ikhtisar laba rugi Busamas yang merinci pendapatan kotor, Harga Pokok Penjualan (HPP), komisi sales agent, dan laba bersih.
        </p>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- P&L Sheet Card -->
    <div class="mx-auto max-w-3xl overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        <div class="border-b border-stone-200 bg-stone-50 px-6 py-4 text-center">
            <h2 class="text-lg font-bold text-ink">LAPORAN LABA RUGI</h2>
            <?php
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $selMonth = $_GET['month'] ?? '';
            $selYear = $_GET['year'] ?? date('Y');
            $periodText = 'Periode: ';
            if ($selMonth !== '') {
                $periodText .= $monthNames[(int)$selMonth] . ' ';
            }
            $periodText .= $selYear !== '' ? $selYear : 'Semua Tahun';
            ?>
            <p class="text-xs uppercase tracking-wider text-stone-500"><?= e($periodText) ?></p>
        </div>

        <div class="px-8 py-6">
            <div class="space-y-6">
                <!-- PENDAPATAN -->
                <div>
                    <div class="flex justify-between border-b border-stone-200 pb-2 text-sm font-bold text-ink uppercase">
                        <span>Pendapatan</span>
                        <span></span>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Pendapatan Penjualan Barang</span>
                            <span><?= rupiah($pendapatan) ?></span>
                        </div>
                        <div class="flex justify-between border-t border-stone-100 pt-2 font-semibold text-ink pl-4">
                            <span>Total Pendapatan Bersih</span>
                            <span><?= rupiah($pendapatan) ?></span>
                        </div>
                    </div>
                </div>

                <!-- BEBAN POKOK PENJUALAN (HPP) -->
                <div>
                    <div class="flex justify-between border-b border-stone-200 pb-2 text-sm font-bold text-ink uppercase">
                        <span>Harga Pokok Penjualan (HPP)</span>
                        <span></span>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Pembelian Barang (HPP)</span>
                            <span><?= rupiah($hpp) ?></span>
                        </div>
                        <div class="flex justify-between border-t border-stone-100 pt-2 font-semibold text-ink pl-4">
                            <span>Total Harga Pokok Penjualan</span>
                            <span>(<?= rupiah($hpp) ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- LABA KOTOR -->
                <div class="flex justify-between rounded-lg bg-stone-50 p-4 text-base font-bold text-ink uppercase border border-stone-200">
                    <span>Laba Kotor (Gross Profit)</span>
                    <span class="text-brand"><?= rupiah($laba_kotor) ?></span>
                </div>

                <!-- BEBAN OPERASIONAL -->
                <div>
                    <div class="flex justify-between border-b border-stone-200 pb-2 text-sm font-bold text-ink uppercase">
                        <span>Beban Operasional</span>
                        <span></span>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Beban Komisi Sales Agent</span>
                            <span><?= rupiah($komisi) ?></span>
                        </div>
                        <div class="flex justify-between border-t border-stone-100 pt-2 font-semibold text-ink pl-4">
                            <span>Total Beban Operasional</span>
                            <span>(<?= rupiah($komisi) ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- LABA BERSIH -->
                <div class="flex justify-between rounded-lg bg-teal-50 p-4 text-lg font-extrabold text-brand uppercase border border-brand/20 shadow-inner">
                    <span>Laba Bersih (Net Profit)</span>
                    <span><?= rupiah($laba_bersih) ?></span>
                </div>
            </div>
        </div>

        <div class="border-t border-stone-200 bg-stone-50 px-6 py-4 text-center text-xs text-stone-500">
            Laporan ini dibuat secara otomatis berdasarkan data faktur invoice dan kalkulasi HPP supplier/vendor.
        </div>
    </div>
</section>
