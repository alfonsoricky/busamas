<?php
$group = $reportData['type'] ?? 'invoice';
$items = $reportData['items'] ?? [];
?>

<?php
$salesCode = trim($_GET['sales_code'] ?? '');
if ($salesCode !== ''):
    $year = $_GET['year'] ?? date('Y');
    $detail = fetch_laporan_penjualan_sales_detail($salesCode, $year);
    if ($detail['ok']):
        $sum = $detail['summary'] ?? [];
?>
<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <!-- Breadcrumbs/Back -->
    <div class="mb-4">
        <a href="<?= e(url('/laporan/penjualan?group=sales')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Kembali ke Laporan Per Sales
        </a>
    </div>

    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Detail Performa Sales Agent</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl"><?= e($detail['nama_sales']) ?> (<?= e($detail['kode_sales']) ?>)</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Laporan performa individu, tren bulanan penjualan & komisi, serta daftar seluruh invoice yang ditangani di tahun <?= e($year) ?>.
            </p>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" action="" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <input type="hidden" name="group" value="sales">
        <input type="hidden" name="sales_code" value="<?= e($salesCode) ?>">
        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Tahun</label>
            <select name="year" class="w-full sm:w-40 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="2026" <?= (string)$year === '2026' ? 'selected' : '' ?>>2026 (Tahun Berjalan)</option>
                <option value="2025" <?= (string)$year === '2025' ? 'selected' : '' ?>>2025</option>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <button type="submit" class="w-full sm:w-auto rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                Terapkan Filter
            </button>
        </div>
    </form>

    <!-- Summary KPI Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Jumlah Invoice</p>
            <p class="mt-2 text-3xl font-bold text-ink"><?= number_format((float)($sum['jumlah_invoice'] ?? 0), 0, ',', '.') ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Qty Terjual</p>
            <p class="mt-2 text-3xl font-bold text-brand"><?= number_format((float)($sum['total_qty'] ?? 0), 0, ',', '.') ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Omset Kontribusi</p>
            <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah($sum['total_penjualan'] ?? 0.0) ?></p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Estimasi Komisi</p>
            <p class="mt-2 text-3xl font-bold text-teal-700"><?= rupiah($sum['total_komisi'] ?? 0.0) ?></p>
        </div>
    </div>

    <!-- Chart & Top Customers Grid -->
    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <!-- Monthly Trend Line Chart -->
        <div class="lg:col-span-2 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-ink">Tren Penjualan & Komisi Bulanan</h2>
                    <p class="text-[10px] text-stone-500">Fluktuasi omset dan perolehan komisi bulanan sales agent</p>
                </div>
                <div class="flex items-center gap-3 text-[10px] font-semibold">
                    <span class="flex items-center gap-1 text-brand">
                        <span class="h-2 w-2 rounded-full bg-brand"></span>
                        Penjualan
                    </span>
                    <span class="flex items-center gap-1 text-coral">
                        <span class="h-2 w-2 rounded-full bg-coral"></span>
                        Komisi
                    </span>
                </div>
            </div>
            <div class="relative h-[250px] w-full">
                <canvas id="salesAgentTrendChart"></canvas>
            </div>
        </div>

        <!-- Top Customers for this Sales -->
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-ink mb-4">Top 5 Customer Terbesar</h2>
            <div class="space-y-3">
                <?php if (empty($detail['top_customers'])): ?>
                    <p class="text-xs text-stone-400 py-3 text-center">Belum ada transaksi customer.</p>
                <?php else: ?>
                    <?php
                    $maxCustVal = count($detail['top_customers']) > 0 ? (float)($detail['top_customers'][0]['total_penjualan'] ?? 1.0) : 1.0;
                    foreach ($detail['top_customers'] as $cust):
                        $val = (float)($cust['total_penjualan'] ?? 0);
                        $pct = $maxCustVal > 0 ? ($val / $maxCustVal) * 100 : 0;
                    ?>
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-ink truncate max-w-[150px]"><?= e($cust['nama_customer'] ?? 'Customer') ?></span>
                                <span class="font-semibold text-stone-700"><?= rupiah($val) ?></span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-stone-100">
                                <div style="width: <?= $pct ?>%" class="h-1.5 rounded-full bg-brand"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detailed Invoices Table -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm mb-10">
        <div class="px-5 py-4 border-b border-stone-100">
            <h2 class="text-sm font-bold text-ink">Daftar Invoice Terkait</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">No. Invoice</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer</th>
                        <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Qty</th>
                        <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Nilai</th>
                        <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Komisi (%)</th>
                        <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Nilai Komisi</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Pembayaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-xs">
                    <?php if (empty($detail['invoices'])): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-stone-500">Tidak ada data invoice.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail['invoices'] as $inv): ?>
                            <tr class="hover:bg-stone-50">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                    <a href="<?= e(url('/invoice-view?code=' . ($inv['nomor_invoice'] ?? ''))) ?>" class="hover:underline">
                                        <?= e($inv['nomor_invoice'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($inv['tanggal_invoice'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-ink truncate max-w-[150px]"><?= e($inv['nama_customer'] ?? '') ?></td>
                                <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($inv['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                <td class="text-right whitespace-nowrap px-4 py-3 font-bold text-ink"><?= rupiah($inv['total_harga_jual'] ?? 0) ?></td>
                                <td class="text-right whitespace-nowrap px-4 py-3 text-stone-600"><?= number_format((float)($inv['komisi_persen'] ?? 0), 1, ',', '.') ?>%</td>
                                <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= rupiah($inv['komisi_nilai'] ?? 0) ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <?php
                                    $isPaid = trim(strtolower($inv['status_pembayaran'] ?? '')) === 'lunas';
                                    ?>
                                    <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold <?= $isPaid ? 'bg-teal-50 text-brand' : 'bg-orange-50 text-coral' ?>">
                                        <?= e($inv['status_pembayaran'] ?? 'Belum Lunas') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesAgentTrendChart').getContext('2d');
    const labels = <?= json_encode($detail['monthly_trends']['labels'] ?? []) ?>;
    const salesData = <?= json_encode($detail['monthly_trends']['sales'] ?? []) ?>;
    const commissionData = <?= json_encode($detail['monthly_trends']['commission'] ?? []) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Penjualan',
                    data: salesData,
                    borderColor: '#0f766e', // brand
                    backgroundColor: 'rgba(15, 118, 110, 0.02)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#0f766e',
                    pointRadius: 3,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Komisi',
                    data: commissionData,
                    borderColor: '#f97316', // coral
                    backgroundColor: 'rgba(249, 115, 22, 0.02)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#f97316',
                    pointRadius: 3,
                    tension: 0.3,
                    fill: true
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
<?php
        return; // stop execution here
    else:
?>
<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-900 mb-6">
        <p class="font-semibold">Error</p>
        <p class="mt-1"><?= e($detail['error'] ?? 'Sales agent tidak ditemukan.') ?></p>
        <a href="<?= e(url('/laporan/penjualan?group=sales')) ?>" class="mt-3 inline-block font-semibold text-brand hover:underline">&larr; Kembali ke Laporan Per Sales</a>
    </div>
</section>
<?php
        return; // stop execution here
    endif;
endif;
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

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Laporan Penjualan</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Penjualan Busamas</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Laporan penjualan dari data invoice, dikelompokkan berdasarkan tipe laporan yang Anda pilih di bawah ini.
            </p>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/filter.php'; ?>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <?php
            $tabs = [
                'invoice' => 'Per Invoice',
                'customer' => 'Per Customer',
                'produk' => 'Per Produk',
                'sales' => 'Per Sales',
            ];
            foreach ($tabs as $key => $label):
                $isActive = $group === $key;
                $url = url('/laporan/penjualan?group=' . $key);
            ?>
                <a href="<?= e($url) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $isActive ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <?php if ($group === 'invoice'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Qty Terjual</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= number_format(array_sum(array_column($items, 'total_qty')), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Penjualan Bersih</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_harga_jual'))) ?></p>
            </div>
        <?php elseif ($group === 'customer'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Customer</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Qty Dibeli</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= number_format(array_sum(array_column($items, 'total_qty')), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Nilai Pembelian</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_penjualan'))) ?></p>
            </div>
        <?php elseif ($group === 'produk'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Produk Terjual</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Volume Terjual</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= number_format(array_sum(array_column($items, 'total_qty')), 0, ',', '.') ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Omset Produk</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_penjualan'))) ?></p>
            </div>
        <?php elseif ($group === 'sales'): ?>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Sales Agent</p>
                <p class="mt-2 text-3xl font-bold text-ink"><?= count($items) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Penjualan Sales</p>
                <p class="mt-2 text-3xl font-bold text-brand"><?= rupiah(array_sum(array_column($items, 'total_penjualan'))) ?></p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-stone-500">Total Estimasi Komisi</p>
                <p class="mt-2 text-3xl font-bold text-coral"><?= rupiah(array_sum(array_column($items, 'total_komisi'))) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Grafik Tren Penjualan -->
    <div class="mb-6 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-bold text-ink">Tren Penjualan Bulanan</h2>
                <p class="text-[10px] text-stone-500">Omset penjualan bulanan di tahun <?= e($_GET['year'] ?? date('Y')) ?></p>
            </div>
        </div>
        <div class="relative h-[250px] w-full">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        <?php $trends = fetch_dashboard_trends($_GET['year'] ?? date('Y')); ?>
        const labels = <?= json_encode($trends['labels'] ?? []) ?>;
        const revenueData = <?= json_encode($trends['revenue'] ?? []) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Penjualan',
                        data: revenueData,
                        borderColor: '#0f766e', // brand
                        backgroundColor: 'rgba(15, 118, 110, 0.03)',
                        borderWidth: 3,
                        pointBackgroundColor: '#0f766e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true
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
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Penjualan: ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.raw);
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

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <?php if ($group === 'invoice'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">No. Invoice</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Sales</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Qty</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Subtotal</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Diskon</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-stone-500">Tidak ada data penjualan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                        <a href="<?= e(url('/invoice-view?code=' . ($item['nomor_invoice'] ?? ''))) ?>" class="hover:underline">
                                            <?= e($item['nomor_invoice'] ?? '') ?>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['tanggal_invoice'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($item['nama_sales_1'] ?? '-') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($item['subtotal'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= rupiah($item['discount_amount'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_harga_jual'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'customer'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Customer / Laundry</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Jumlah Invoice</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Qty Dibeli</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Nilai Pembelian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada data customer.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_customer'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? 'Unknown Customer') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['jumlah_invoice'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'produk'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Barang</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Ukuran</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Qty Terjual</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Penjualan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada data produk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_barang'] ?? '-') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_barang_master'] ?? 'Unknown Product') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e($item['ukuran_master'] ?? '') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['total_qty'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php elseif ($group === 'sales'): ?>
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Sales</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Sales Agent</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Jumlah Invoice</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Total Penjualan (Rp)</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 font-semibold">Estimasi Komisi (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada data sales.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand">
                                        <a href="<?= e(url('/laporan/penjualan?group=sales&sales_code=' . urlencode($item['kode_sales'] ?? ''))) ?>" class="hover:underline">
                                            <?= e($item['kode_sales'] ?? '-') ?>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink">
                                        <a href="<?= e(url('/laporan/penjualan?group=sales&sales_code=' . urlencode($item['kode_sales'] ?? ''))) ?>" class="hover:underline">
                                            <?= e($item['nama_sales'] ?? 'Unknown Sales') ?>
                                        </a>
                                    </td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 text-stone-700"><?= number_format((float)($item['jumlah_invoice'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= rupiah($item['total_penjualan'] ?? 0) ?></td>
                                    <td class="text-right whitespace-nowrap px-4 py-3 font-semibold text-coral"><?= rupiah($item['total_komisi'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</section>
