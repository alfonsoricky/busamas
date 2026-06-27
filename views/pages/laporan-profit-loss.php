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

    <!-- Grafik Tren Profit & Loss -->
    <div class="mx-auto max-w-3xl mb-6 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-bold text-ink">Tren Laba Rugi Bulanan</h2>
                <p class="text-[10px] text-stone-500">Perbandingan Omset, Beban, dan Laba Bersih di tahun <?= e($_GET['year'] ?? date('Y')) ?></p>
            </div>
            <div class="flex items-center gap-3 text-[10px] font-semibold">
                <span class="flex items-center gap-1 text-brand">
                    <span class="h-2 w-2 rounded-full bg-brand"></span>
                    Omset
                </span>
                <span class="flex items-center gap-1 text-rose-600">
                    <span class="h-2 w-2 rounded-full bg-rose-600"></span>
                    Beban
                </span>
                <span class="flex items-center gap-1 text-coral">
                    <span class="h-2 w-2 rounded-full bg-coral"></span>
                    Laba Bersih
                </span>
            </div>
        </div>
        <div class="relative h-[250px] w-full">
            <canvas id="pnlTrendChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('pnlTrendChart').getContext('2d');
        <?php $selYear = $_GET['year'] ?? date('Y'); $trends = fetch_dashboard_trends($selYear); ?>
        const labels = <?= json_encode($trends['labels'] ?? []) ?>;
        const revenueData = <?= json_encode($trends['revenue'] ?? []) ?>;
        const profitData = <?= json_encode($trends['profit'] ?? []) ?>;
        const expensesData = revenueData.map((rev, i) => rev - profitData[i]);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Omset',
                        data: revenueData,
                        borderColor: '#0f766e', // brand
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0f766e',
                        pointRadius: 3.5,
                        tension: 0.3
                    },
                    {
                        label: 'Beban',
                        data: expensesData,
                        borderColor: '#e11d48', // rose-600
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#e11d48',
                        pointRadius: 3.5,
                        tension: 0.3
                    },
                    {
                        label: 'Laba Bersih',
                        data: profitData,
                        borderColor: '#f97316', // coral
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#f97316',
                        pointRadius: 3.5,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#17202a',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.raw !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.raw);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 10
                            },
                            color: '#78716c',
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact', compactDisplay: 'short' }).format(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 10
                            },
                            color: '#78716c'
                        }
                    }
                }
            }
        });
    });
    </script>

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
