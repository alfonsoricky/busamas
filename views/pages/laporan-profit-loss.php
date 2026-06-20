<?php
$pendapatan = $reportData['pendapatan'] ?? 0.0;
$komisi_sales = $reportData['komisi_sales'] ?? 0.0;
$komisi_manager = $reportData['komisi_manager'] ?? 0.0;
$komisi_admin = $reportData['komisi_admin'] ?? 0.0;
$pph = $reportData['pph'] ?? 0.0;
$pembelian_barang = $reportData['pembelian_barang'] ?? 0.0;
$biaya_admin_bank = $reportData['biaya_admin_bank'] ?? 0.0;
$biaya_kirim = $reportData['biaya_kirim'] ?? 0.0;
$operational = $reportData['operational'] ?? 0.0;
$bonus = $reportData['bonus'] ?? 0.0;
$discount = $reportData['discount'] ?? 0.0;
$total_pengeluaran = $reportData['total_pengeluaran'] ?? 0.0;
$laba_bersih = $reportData['laba_bersih'] ?? 0.0;

// Percentage helper function
$pct = function(float $val) use ($pendapatan) {
    if ($pendapatan <= 0) return '0.00%';
    return number_format(($val / $pendapatan) * 100, 2) . '%';
};
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
            Ikhtisar laporan laba rugi bulanan Busamas dengan rasio persentase beban terhadap total pendapatan penjualan kotor (subtotal).
        </p>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- P&L Sheet Card -->
    <div class="mx-auto max-w-3xl overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        <div class="border-b border-stone-200 bg-stone-50 px-6 py-4 text-center">
            <h2 class="text-lg font-bold text-ink">LAPORAN LABA RUGI (PNL)</h2>
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
                        <div class="flex gap-12">
                            <span class="w-28 text-right">Rasio (%)</span>
                            <span class="w-36 text-right">Jumlah (Rupiah)</span>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Pendapatan Penjualan (Pricelist)</span>
                            <div class="flex gap-12 font-medium">
                                <span class="w-28 text-right">100.00%</span>
                                <span class="w-36 text-right"><?= rupiah($pendapatan) ?></span>
                            </div>
                        </div>
                        <div class="flex justify-between border-t border-stone-100 pt-2 font-bold text-ink pl-4">
                            <span>TOTAL PENDAPATAN KOTOR</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right">100.00%</span>
                                <span class="w-36 text-right"><?= rupiah($pendapatan) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PENGELUARAN & BEBAN -->
                <div>
                    <div class="flex justify-between border-b border-stone-200 pb-2 text-sm font-bold text-ink uppercase">
                        <span>Pengeluaran & Beban</span>
                        <div class="flex gap-12">
                            <span class="w-28 text-right">Rasio (%)</span>
                            <span class="w-36 text-right">Jumlah (Rupiah)</span>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <!-- Komisi Sales -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Komisi Sales</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($komisi_sales) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($komisi_sales) ?>)</span>
                            </div>
                        </div>
                        <!-- Komisi Manager -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Komisi Manager</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($komisi_manager) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($komisi_manager) ?>)</span>
                            </div>
                        </div>
                        <!-- Komisi Admin -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Komisi Admin (5%)</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($komisi_admin) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($komisi_admin) ?>)</span>
                            </div>
                        </div>
                        <!-- PPH Final 0,5% -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>PPH Final 0,5%</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($pph) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($pph) ?>)</span>
                            </div>
                        </div>
                        <!-- Pembelian Barang (HPP) -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Pembelian Barang (HPP)</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($pembelian_barang) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($pembelian_barang) ?>)</span>
                            </div>
                        </div>
                        <!-- Biaya Admin BANK -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Biaya Admin BANK</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($biaya_admin_bank) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($biaya_admin_bank) ?>)</span>
                            </div>
                        </div>
                        <!-- Biaya Kirim -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Biaya Kirim</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($biaya_kirim) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($biaya_kirim) ?>)</span>
                            </div>
                        </div>
                        <!-- Operational -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Operational Expenses</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($operational) ?></span>
                                <span class="w-36 text-right">
                                    (<a href="<?= e(url('/operational')) ?>" class="text-brand hover:underline"><?= rupiah($operational) ?></a>)
                                </span>
                            </div>
                        </div>
                        <!-- Bonus -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Bonus</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($bonus) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($bonus) ?>)</span>
                            </div>
                        </div>
                        <!-- Discount -->
                        <div class="flex justify-between pl-4 text-stone-700">
                            <span>Discount Penjualan</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right text-stone-500"><?= $pct($discount) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($discount) ?>)</span>
                            </div>
                        </div>

                        <!-- TOTAL PENGELUARAN -->
                        <div class="flex justify-between border-t border-stone-100 pt-2 font-bold text-ink pl-4">
                            <span>TOTAL PENGELUARAN & BEBAN</span>
                            <div class="flex gap-12">
                                <span class="w-28 text-right"><?= $pct($total_pengeluaran) ?></span>
                                <span class="w-36 text-right">(<?= rupiah($total_pengeluaran) ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MONTHLY PNL / LABA BERSIH -->
                <div class="flex justify-between rounded-lg bg-teal-50 p-4 text-base font-extrabold uppercase border border-brand/20 shadow-inner">
                    <span class="text-brand">Monthly PNL (Laba Bersih)</span>
                    <div class="flex gap-12 text-brand">
                        <span class="w-28 text-right"><?= $pct($laba_bersih) ?></span>
                        <span class="w-36 text-right"><?= rupiah($laba_bersih) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-stone-200 bg-stone-50 px-6 py-4 text-center text-xs text-stone-500">
            Laporan ini dibuat berdasarkan database terintegrasi dari file Excel PENJUALAN-2026.xlsx.
        </div>
    </div>
</section>
