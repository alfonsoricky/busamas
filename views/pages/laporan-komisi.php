<?php
$tab = $_GET['tab'] ?? 'semua';
$status = $_GET['status'] ?? '';
$selectedMonth = $_GET['month'] ?? '';
$selectedYear = $_GET['year'] ?? date('Y');

$items = $reportData['items'] ?? [];
$summary = $reportData['summary'] ?? [];
$years = $reportData['options']['years'] ?? [date('Y')];

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
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
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Laporan Komisi</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Komisi Sales, Manager & Admin</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Pantau komisi terbayar dan kewajiban utang komisi untuk sales agent, manager, dan tim admin.
            </p>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" class="mb-6 grid gap-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_1.2fr_auto] sm:items-end">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">

        <div>
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Bulan</label>
            <select name="month" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Bulan</option>
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= (string)$selectedMonth === (string)$num ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Tahun</label>
            <select name="year" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Tahun</option>
                <?php foreach ($years as $yr): ?>
                    <option value="<?= e($yr) ?>" <?= (string)$selectedYear === (string)$yr ? 'selected' : '' ?>><?= e($yr) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Status Pembayaran</label>
            <select name="status" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>Semua Status</option>
                <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Ada Komisi Terbayar</option>
                <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Ada Komisi Belum Terbayar (Utang)</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                Filter
            </button>
            <a href="<?= e(url('/laporan/komisi?tab=' . $tab)) ?>" class="rounded-lg border border-stone-300 px-4 py-2.5 text-center text-sm font-semibold text-ink transition hover:bg-stone-50">
                Reset
            </a>
        </div>
    </form>

    <!-- KPI Summary Cards -->
    <div class="mb-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Sales Commission Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-teal-600">Komisi Sales</p>
                <span class="rounded-full bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-700">Total: <?= rupiah($summary['sales_total'] ?? 0) ?></span>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-stone-500">Terbayar:</span>
                    <span class="font-bold text-emerald-600"><?= rupiah($summary['sales_paid'] ?? 0) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-stone-500">Utang/Sisa:</span>
                    <span class="font-bold text-red-600"><?= rupiah($summary['sales_unpaid'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Manager Commission Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-violet-600">Komisi Manager</p>
                <span class="rounded-full bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700">Total: <?= rupiah($summary['manager_total'] ?? 0) ?></span>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-stone-500">Terbayar:</span>
                    <span class="font-bold text-emerald-600"><?= rupiah($summary['manager_paid'] ?? 0) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-stone-500">Utang/Sisa:</span>
                    <span class="font-bold text-red-600"><?= rupiah($summary['manager_unpaid'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Admin Commission Card -->
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Komisi Admin</p>
                <span class="rounded-full bg-sky-50 px-2 py-1 text-[10px] font-bold text-sky-700">Total: <?= rupiah($summary['admin_total'] ?? 0) ?></span>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-stone-500">Terbayar:</span>
                    <span class="font-bold text-emerald-600"><?= rupiah($summary['admin_paid'] ?? 0) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-stone-500">Utang/Sisa:</span>
                    <span class="font-bold text-red-600"><?= rupiah($summary['admin_unpaid'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Grand Total Card -->
        <div class="rounded-xl border border-brand bg-teal-950 p-5 shadow-sm text-white transition hover:shadow-md">
            <div class="flex items-center justify-between border-b border-teal-800 pb-2">
                <p class="text-xs font-bold uppercase tracking-wider text-teal-400">Total Pengeluaran Komisi</p>
                <span class="rounded-full bg-teal-900 px-2.5 py-0.5 text-[10px] font-bold text-teal-300">Semua Staff</span>
            </div>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-teal-300">Terbayar:</span>
                    <span class="font-bold text-emerald-400"><?= rupiah($summary['total_paid'] ?? 0) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-teal-300">Total Utang:</span>
                    <span class="font-bold text-coral"><?= rupiah($summary['total_unpaid'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-6 overflow-x-auto pb-1" aria-label="Tabs">
            <?php
            $buildUrl = fn($t) => url('/laporan/komisi') . '?' . http_build_query(array_merge($_GET, ['tab' => $t]));
            ?>
            <a href="<?= e($buildUrl('semua')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'semua' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Ringkasan Semua Komisi
            </a>
            <a href="<?= e($buildUrl('sales')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'sales' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Komisi Sales
            </a>
            <a href="<?= e($buildUrl('manager')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'manager' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Komisi Manager
            </a>
            <a href="<?= e($buildUrl('admin')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'admin' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Komisi Admin
            </a>
        </nav>
    </div>

    <!-- Tab Contents -->
    <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <?php if (empty($items)): ?>
                <div class="p-8 text-center text-stone-500">
                    <p class="text-lg font-semibold">Tidak Ada Data</p>
                    <p class="mt-2 text-sm text-stone-400">Tidak ada data invoice yang sesuai dengan filter saat ini.</p>
                </div>
            <?php else: ?>
                <?php if ($tab === 'semua'): ?>
                    <!-- RINGKASAN SEMUA KOMISI TABLE -->
                    <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                        <thead class="bg-stone-100 text-stone-700 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Invoice</th>
                                <th class="px-4 py-3 whitespace-nowrap">Customer</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Subtotal</th>
                                <th class="px-4 py-3 text-right bg-teal-50 text-teal-900 whitespace-nowrap">Sales Paid</th>
                                <th class="px-4 py-3 text-right bg-teal-50 text-teal-900 whitespace-nowrap">Sales Debt</th>
                                <th class="px-4 py-3 text-right bg-violet-50 text-violet-900 whitespace-nowrap">Mgr Paid</th>
                                <th class="px-4 py-3 text-right bg-violet-50 text-violet-900 whitespace-nowrap">Mgr Debt</th>
                                <th class="px-4 py-3 text-right bg-sky-50 text-sky-900 whitespace-nowrap">Adm Paid</th>
                                <th class="px-4 py-3 text-right bg-sky-50 text-sky-900 whitespace-nowrap">Adm Debt</th>
                                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">Total Utang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 font-medium text-stone-800">
                            <?php foreach ($items as $inv): ?>
                                <?php
                                $sPaid = (float)($inv['komisi_sales_terbayar'] ?? 0);
                                $sDebt = (float)($inv['komisi_sales_belum_terbayar'] ?? 0);
                                $mPaid = (float)($inv['komisi_manager_terbayar'] ?? 0);
                                $mDebt = (float)($inv['komisi_manager_utang'] ?? 0);
                                $aPaid = (float)($inv['komisi_admin_terbayar'] ?? 0);
                                $aDebt = (float)($inv['komisi_admin_belum_terbayar'] ?? 0);
                                $tDebt = $sDebt + $mDebt + $aDebt;
                                ?>
                                <tr class="hover:bg-stone-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-brand hover:underline">
                                        <a href="<?= e(url('/invoice-create?code=' . ($inv['nomor_invoice'] ?? ''))) ?>"><?= e($inv['nomor_invoice'] ?? '') ?></a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap max-w-xs truncate" title="<?= e($inv['nama_customer'] ?? '') ?>"><?= e($inv['nama_customer'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-stone-600"><?= rupiah($inv['total_harga_jual'] ?? 0) ?></td>
                                    
                                    <td class="px-4 py-3 text-right bg-teal-50/50 text-emerald-700 whitespace-nowrap"><?= $sPaid > 0 ? rupiah($sPaid) : '-' ?></td>
                                    <td class="px-4 py-3 text-right bg-teal-50/50 text-red-600 whitespace-nowrap"><?= $sDebt > 0 ? rupiah($sDebt) : '-' ?></td>
                                    
                                    <td class="px-4 py-3 text-right bg-violet-50/50 text-emerald-700 whitespace-nowrap"><?= $mPaid > 0 ? rupiah($mPaid) : '-' ?></td>
                                    <td class="px-4 py-3 text-right bg-violet-50/50 text-red-600 whitespace-nowrap"><?= $mDebt > 0 ? rupiah($mDebt) : '-' ?></td>
                                    
                                    <td class="px-4 py-3 text-right bg-sky-50/50 text-emerald-700 whitespace-nowrap"><?= $aPaid > 0 ? rupiah($aPaid) : '-' ?></td>
                                    <td class="px-4 py-3 text-right bg-sky-50/50 text-red-600 whitespace-nowrap"><?= $aDebt > 0 ? rupiah($aDebt) : '-' ?></td>
                                    
                                    <td class="px-4 py-3 text-right font-bold text-stone-900 whitespace-nowrap <?= $tDebt > 0 ? 'text-red-700' : 'text-emerald-700' ?>">
                                        <?= $tDebt > 0 ? rupiah($tDebt) : 'Lunas' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($tab === 'sales'): ?>
                    <!-- KOMISI SALES TABLE -->
                    <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                        <thead class="bg-teal-50 text-teal-900 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Invoice</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal</th>
                                <th class="px-4 py-3 whitespace-nowrap">Sales Agent</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Subtotal</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Rate Komisi</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Terbayar</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Utang (Belum TF)</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal Transfer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 font-medium text-stone-800">
                            <?php foreach ($items as $inv): ?>
                                <?php
                                $sPaid = (float)($inv['komisi_sales_terbayar'] ?? 0);
                                $sDebt = (float)($inv['komisi_sales_belum_terbayar'] ?? 0);
                                $sStatus = trim((string)($inv['status_pembayaran_komisi_sales'] ?? ''));
                                if ($sStatus === '') {
                                    $sStatus = $sDebt > 0 ? 'Belum Dibayar' : ($sPaid > 0 ? 'Dibayar' : '-');
                                }
                                
                                // Agent and rates formatting
                                $agents = [];
                                $rates = [];
                                if (trim((string)($inv['nama_sales_1'] ?? '')) !== '') {
                                    $agents[] = trim($inv['nama_sales_1']);
                                    $rates[] = clean_decimal($inv['komisi_sales_1_persen'] ?? 0) . '%';
                                }
                                if (trim((string)($inv['nama_sales_2'] ?? '')) !== '') {
                                    $agents[] = trim($inv['nama_sales_2']);
                                    $rates[] = clean_decimal($inv['komisi_sales_2_persen'] ?? 0) . '%';
                                }
                                $agentText = empty($agents) ? '-' : implode(' & ', $agents);
                                $rateText = empty($rates) ? '-' : implode(' + ', $rates);
                                ?>
                                <tr class="hover:bg-stone-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-brand hover:underline">
                                        <a href="<?= e(url('/invoice-create?code=' . ($inv['nomor_invoice'] ?? ''))) ?>"><?= e($inv['nomor_invoice'] ?? '') ?></a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-600"><?= e($inv['tanggal_invoice'] ?? '') ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-900"><?= e($agentText) ?></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-stone-600"><?= rupiah($inv['total_harga_jual'] ?? 0) ?></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-stone-600 font-semibold"><?= e($rateText) ?></td>
                                    <td class="px-4 py-3 text-right text-emerald-700 whitespace-nowrap"><?= $sPaid > 0 ? rupiah($sPaid) : '-' ?></td>
                                    <td class="px-4 py-3 text-right text-red-600 whitespace-nowrap"><?= $sDebt > 0 ? rupiah($sDebt) : '-' ?></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <?php if ($sStatus === 'Dibayar'): ?>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Lunas</span>
                                        <?php elseif ($sStatus === 'Belum Dibayar' || $sDebt > 0): ?>
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Utang</span>
                                        <?php else: ?>
                                            <span class="text-stone-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-500 font-normal"><?= e($inv['tanggal_transfer_komisi_sales'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($tab === 'manager'): ?>
                    <!-- KOMISI MANAGER TABLE -->
                    <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                        <thead class="bg-violet-50 text-violet-900 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Invoice</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal</th>
                                <th class="px-4 py-3 whitespace-nowrap">Customer / Laundry</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Subtotal</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Terbayar</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Utang Manager</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal Transfer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 font-medium text-stone-800">
                            <?php foreach ($items as $inv): ?>
                                <?php
                                $mPaid = (float)($inv['komisi_manager_terbayar'] ?? 0);
                                $mDebt = (float)($inv['komisi_manager_utang'] ?? 0);
                                $isLunas = $mPaid > 0 && $mDebt <= 0;
                                ?>
                                <tr class="hover:bg-stone-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-brand hover:underline">
                                        <a href="<?= e(url('/invoice-create?code=' . ($inv['nomor_invoice'] ?? ''))) ?>"><?= e($inv['nomor_invoice'] ?? '') ?></a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-600"><?= e($inv['tanggal_invoice'] ?? '') ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-900"><?= e($inv['nama_customer'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-stone-600"><?= rupiah($inv['total_harga_jual'] ?? 0) ?></td>
                                    <td class="px-4 py-3 text-right text-emerald-700 whitespace-nowrap"><?= $mPaid > 0 ? rupiah($mPaid) : '-' ?></td>
                                    <td class="px-4 py-3 text-right text-red-600 whitespace-nowrap"><?= $mDebt > 0 ? rupiah($mDebt) : '-' ?></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <?php if ($isLunas): ?>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Lunas</span>
                                        <?php elseif ($mDebt > 0): ?>
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Utang</span>
                                        <?php else: ?>
                                            <span class="text-stone-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-500 font-normal"><?= e($inv['tanggal_transfer_komisi_manager'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($tab === 'admin'): ?>
                    <!-- KOMISI ADMIN TABLE -->
                    <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                        <thead class="bg-sky-50 text-sky-900 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">Invoice</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal</th>
                                <th class="px-4 py-3 whitespace-nowrap">Customer / Laundry</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Subtotal</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Terbayar</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Utang Admin</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tanggal Transfer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 font-medium text-stone-800">
                            <?php foreach ($items as $inv): ?>
                                <?php
                                $aPaid = (float)($inv['komisi_admin_terbayar'] ?? 0);
                                $aDebt = (float)($inv['komisi_admin_belum_terbayar'] ?? 0);
                                $isLunas = $aPaid > 0 && $aDebt <= 0;
                                ?>
                                <tr class="hover:bg-stone-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-brand hover:underline">
                                        <a href="<?= e(url('/invoice-create?code=' . ($inv['nomor_invoice'] ?? ''))) ?>"><?= e($inv['nomor_invoice'] ?? '') ?></a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-600"><?= e($inv['tanggal_invoice'] ?? '') ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-900"><?= e($inv['nama_customer'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-stone-600"><?= rupiah($inv['total_harga_jual'] ?? 0) ?></td>
                                    <td class="px-4 py-3 text-right text-emerald-700 whitespace-nowrap"><?= $aPaid > 0 ? rupiah($aPaid) : '-' ?></td>
                                    <td class="px-4 py-3 text-right text-red-600 whitespace-nowrap"><?= $aDebt > 0 ? rupiah($aDebt) : '-' ?></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <?php if ($isLunas): ?>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Lunas</span>
                                        <?php elseif ($aDebt > 0): ?>
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Utang</span>
                                        <?php else: ?>
                                            <span class="text-stone-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-stone-500 font-normal"><?= e($inv['tanggal_transfer_komisi_admin'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
