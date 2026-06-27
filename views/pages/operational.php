<?php
$selectedYear = $_GET['year'] ?? date('Y');
$selectedMonth = $_GET['month'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$flash = $_SESSION['operational_flash'] ?? null;
unset($_SESSION['operational_flash']);
$editItem = is_array($operationalEdit['item'] ?? null) ? $operationalEdit['item'] : null;
$isEdit = $editItem !== null;
$formTanggal = $isEdit ? date_input_value((string) ($editItem['tanggal'] ?? '')) : date('Y-m-d');
$formBulanPnl = (int) ($editItem['bulan_pnl'] ?? date('n'));
$formTahunPnl = (string) ($editItem['tahun_pnl'] ?? date('Y'));
$formStatus = (string) ($editItem['status_pembayaran'] ?? 'Lunas');
$formTanggalBayar = $isEdit ? date_input_value((string) ($editItem['tanggal_pembayaran'] ?? '')) : date('Y-m-d');

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Keuangan</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Pengeluaran Operasional</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Pantau seluruh pengeluaran operasional perusahaan yang ditarik secara otomatis dari sheet <b>operational</b> pada file Excel.
            </p>
        </div>
        <a href="<?= e(url('/operational/bonus-sales')) ?>" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
            Bonus / Insentif Tim Sales
        </a>
    </div>

    <?php if (is_array($flash)): ?>
        <?php $flashOk = (bool) ($flash['ok'] ?? false); ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= $flashOk ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/operational-create')) ?>" class="mb-6 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-ink"><?= $isEdit ? 'Update Pengeluaran Manual' : 'Input Pengeluaran Manual' ?></h2>
            <?php if ($isEdit): ?>
                <a href="<?= e(url('/operational') . '?' . http_build_query(['month' => $selectedMonth, 'year' => $selectedYear, 'status' => $selectedStatus, 'search' => $searchQuery])) ?>" class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-ink transition hover:bg-stone-50">
                    Batal Edit
                </a>
            <?php endif; ?>
        </div>
        <input type="hidden" name="operational_id" value="<?= e((string) ($editItem['id'] ?? '')) ?>">
        <input type="hidden" name="filter_month" value="<?= e((string) $selectedMonth) ?>">
        <input type="hidden" name="filter_year" value="<?= e((string) $selectedYear) ?>">
        <input type="hidden" name="filter_status" value="<?= e((string) $selectedStatus) ?>">
        <input type="hidden" name="filter_search" value="<?= e((string) $searchQuery) ?>">

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tanggal</span>
                <input type="date" name="tanggal" value="<?= e($formTanggal) ?>" required class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Bulan PNL</span>
                <select name="bulan_pnl" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= e((string) $num) ?>" <?= $formBulanPnl === (int) $num ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tahun PNL</span>
                <input type="number" name="tahun_pnl" min="2020" max="2100" value="<?= e($formTahunPnl) ?>" required class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status</span>
                <select name="status_pembayaran" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                    <option value="Lunas" <?= strcasecmp($formStatus, 'Lunas') === 0 ? 'selected' : '' ?>>Lunas</option>
                    <option value="Hutang" <?= strcasecmp($formStatus, 'Lunas') !== 0 ? 'selected' : '' ?>>Hutang</option>
                </select>
            </label>

            <label class="block lg:col-span-2">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Nama Pengeluaran</span>
                <input name="nama_pengeluaran" value="<?= e((string) ($editItem['nama_pengeluaran'] ?? '')) ?>" required placeholder="Contoh: Bensin operasional" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Jumlah</span>
                <input type="number" step="0.01" min="0" name="jumlah" value="<?= e(clean_decimal($editItem['jumlah'] ?? '')) ?>" required placeholder="0" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tanggal Bayar</span>
                <input type="date" name="tanggal_pembayaran" value="<?= e($formTanggalBayar) ?>" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block lg:col-span-3">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Keterangan</span>
                <input name="keterangan" value="<?= e((string) ($editItem['keterangan'] ?? '')) ?>" placeholder="Opsional" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                    <?= $isEdit ? 'Update Pengeluaran' : 'Simpan Pengeluaran' ?>
                </button>
            </div>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Pengeluaran</p>
            <p class="mt-2 text-2xl font-bold text-ink"><?= rupiah($summary['total_pengeluaran'] ?? 0) ?></p>
            <p class="text-xs text-stone-400 mt-1">Seluruh pengeluaran di periode terpilih</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Dibayar (Lunas)</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700"><?= rupiah($summary['total_lunas'] ?? 0) ?></p>
            <p class="text-xs text-emerald-600/80 mt-1">Pengeluaran yang sudah diselesaikan</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Belum Dibayar (Hutang)</p>
            <p class="mt-2 text-2xl font-bold text-coral"><?= rupiah($summary['total_hutang'] ?? 0) ?></p>
            <p class="text-xs text-coral/80 mt-1">Kewajiban biaya operasional tertunda</p>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Cari Pengeluaran</label>
            <input type="text" name="search" value="<?= e($searchQuery) ?>" placeholder="Nama pengeluaran / ket..." class="w-full sm:w-48 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
        </div>

        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Bulan</label>
            <select name="month" class="w-full sm:w-40 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Bulan</option>
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= (string)$selectedMonth === (string)$num ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Tahun</label>
            <select name="year" class="w-full sm:w-36 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Tahun</option>
                <option value="2026" <?= (string)$selectedYear === '2026' ? 'selected' : '' ?>>2026 (Tahun Ini)</option>
                <option value="2025" <?= (string)$selectedYear === '2025' ? 'selected' : '' ?>>2025</option>
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Status</label>
            <select name="status" class="w-full sm:w-36 rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
                <option value="">Semua Status</option>
                <option value="Lunas" <?= $selectedStatus === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="Hutang" <?= $selectedStatus === 'Hutang' ? 'selected' : '' ?>>Hutang</option>
            </select>
        </div>

        <div class="w-full sm:w-auto flex gap-2">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                Filter
            </button>
            <?php if ($selectedMonth !== '' || $selectedYear !== '2026' || $selectedStatus !== '' || $searchQuery !== ''): ?>
                <a href="<?= e(url('/operational')) ?>" class="rounded-lg bg-stone-100 hover:bg-stone-200 px-4 py-2 text-sm font-semibold text-stone-700 transition">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Expenses Table -->
    <?php if (! ($expenses['ok'] ?? false)): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-semibold">Gagal memuat data operasional.</p>
            <p class="mt-1"><?= e($expenses['error'] ?? 'Terjadi kesalahan sistem.') ?></p>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">Nama Pengeluaran</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold text-right">Jumlah (Rp)</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold text-center">Status</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Tanggal Bayar</th>
                            <th class="px-4 py-3.5 font-semibold">Keterangan</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($expenses['items'])): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-stone-400">
                                    Tidak menemukan data pengeluaran operasional yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses['items'] as $item): ?>
                                <tr class="hover:bg-stone-50 transition-colors">
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600 font-medium">
                                        <?= e($item['tanggal'] ? date('d-m-Y', strtotime($item['tanggal'])) : '-') ?>
                                    </td>
                                    <td class="px-4 py-3 text-ink font-semibold">
                                        <?= e($item['nama_pengeluaran'] ?? '') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-ink">
                                        <?= rupiah($item['jumlah'] ?? 0) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center">
                                        <?php if (strtolower(trim($item['status_pembayaran'] ?? '')) === 'lunas'): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                Lunas
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                Hutang
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                        <?= e($item['tanggal_pembayaran'] ? date('d-m-Y', strtotime($item['tanggal_pembayaran'])) : '-') ?>
                                    </td>
                                    <td class="px-4 py-3 text-stone-500 max-w-xs truncate" title="<?= e($item['keterangan'] ?? '') ?>">
                                        <?= e($item['keterangan'] ?? '-') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="<?= e(url('/operational') . '?' . http_build_query(['month' => $selectedMonth, 'year' => $selectedYear, 'status' => $selectedStatus, 'search' => $searchQuery, 'edit_id' => $item['id'] ?? ''])) ?>" class="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-semibold text-brand transition hover:border-brand hover:bg-teal-50">
                                                Edit
                                            </a>
                                            <form method="POST" action="<?= e(url('/operational-delete')) ?>" data-confirm-message="Hapus pengeluaran operasional ini beserta jurnalnya?">
                                                <input type="hidden" name="operational_id" value="<?= e((string) ($item['id'] ?? '')) ?>">
                                                <input type="hidden" name="filter_month" value="<?= e((string) $selectedMonth) ?>">
                                                <input type="hidden" name="filter_year" value="<?= e((string) $selectedYear) ?>">
                                                <input type="hidden" name="filter_status" value="<?= e((string) $selectedStatus) ?>">
                                                <input type="hidden" name="filter_search" value="<?= e((string) $searchQuery) ?>">
                                                <button type="submit" class="rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
