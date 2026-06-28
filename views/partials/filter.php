<?php
$selectedYear = $_GET['year'] ?? (str_contains($_SERVER['REQUEST_URI'], '/laporan/hutang') ? '' : date('Y'));
$selectedMonth = $_GET['month'] ?? '';
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<form method="GET" action="" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
    <!-- Preserves other parameters like 'group' or 'tab' if they exist -->
    <?php foreach ($_GET as $key => $val): ?>
        <?php if ($key !== 'month' && $key !== 'year' && $key !== 'cust_pay' && $key !== 'comm_pay'): ?>
            <input type="hidden" name="<?= e($key) ?>" value="<?= e($val) ?>">
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="w-full sm:w-auto">
        <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Bulan</label>
        <select name="month" class="w-full sm:w-48 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            <option value="">Semua Bulan</option>
            <?php foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>" <?= (string)$selectedMonth === (string)$num ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="w-full sm:w-auto">
        <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Tahun</label>
        <select name="year" class="w-full sm:w-40 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            <option value="">Semua Tahun</option>
            <option value="2026" <?= (string)$selectedYear === '2026' ? 'selected' : '' ?>>2026 (Tahun Berjalan)</option>
            <option value="2025" <?= (string)$selectedYear === '2025' ? 'selected' : '' ?>>2025</option>
        </select>
    </div>

    <?php if (($_GET['tab'] ?? '') === 'sales_commission'): ?>
        <?php
        $selectedCustPay = $_GET['cust_pay'] ?? '';
        $selectedCommPay = $_GET['comm_pay'] ?? '';
        ?>
        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Bayar Customer</label>
            <select name="cust_pay" class="w-full sm:w-48 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Status</option>
                <option value="Lunas" <?= $selectedCustPay === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="Belum Lunas" <?= $selectedCustPay === 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Transfer Komisi</label>
            <select name="comm_pay" class="w-full sm:w-48 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Status</option>
                <option value="Belum TF" <?= $selectedCommPay === 'Belum TF' ? 'selected' : '' ?>>Belum TF</option>
                <option value="Transfer" <?= $selectedCommPay === 'Transfer' ? 'selected' : '' ?>>Transfer</option>
            </select>
        </div>
    <?php endif; ?>

    <div class="w-full sm:w-auto">
        <button type="submit" class="w-full sm:w-auto rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
            Terapkan Filter
        </button>
    </div>
</form>
