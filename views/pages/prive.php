<?php
$priveData = is_array($priveData ?? null) ? $priveData : ['ok' => false, 'items' => [], 'summary' => [], 'partners' => [], 'filters' => []];
$filters = is_array($priveData['filters'] ?? null) ? $priveData['filters'] : [];
$selectedMonth = (string) ($filters['month'] ?? ($_GET['month'] ?? ''));
$selectedYear = (string) ($filters['year'] ?? ($_GET['year'] ?? date('Y')));
$selectedPartner = (string) ($filters['partner'] ?? ($_GET['partner'] ?? ''));
$selectedStatus = (string) ($filters['status'] ?? ($_GET['status'] ?? ''));
$summary = is_array($priveData['summary'] ?? null) ? $priveData['summary'] : [];
$partners = is_array($priveData['partners'] ?? null) ? $priveData['partners'] : [];
$flash = $_SESSION['prive_flash'] ?? null;
unset($_SESSION['prive_flash']);

$editItem = is_array($priveEdit['item'] ?? null) ? $priveEdit['item'] : null;
$isEdit = $editItem !== null;
$formTanggal = $isEdit ? date_input_value((string) ($editItem['tanggal'] ?? '')) : date('Y-m-d');
$formBulanPnl = (int) ($editItem['bulan_pnl'] ?? date('n'));
$formTahunPnl = (string) ($editItem['tahun_pnl'] ?? date('Y'));
$formStatus = (string) ($editItem['status_pembayaran'] ?? 'Hutang');
$formTanggalTransfer = $isEdit ? date_input_value((string) ($editItem['tanggal_transfer'] ?? '')) : '';

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Keuangan</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Prive Partner</h1>
        <p class="mt-2 max-w-2xl leading-7 text-stone-600">
            Catat pembagian prive partner, pantau yang sudah dibayar dan yang masih menjadi hutang, serta posting otomatis ke akuntansi.
        </p>
    </div>

    <?php if (is_array($flash)): ?>
        <?php $flashOk = (bool) ($flash['ok'] ?? false); ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= $flashOk ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (! empty($priveEdit['error'])): ?>
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <?= e((string) $priveEdit['error']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/prive-save')) ?>" class="mb-6 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-ink"><?= $isEdit ? 'Update Prive Partner' : 'Input Prive Partner' ?></h2>
            <?php if ($isEdit): ?>
                <a href="<?= e(url('/prive') . '?' . http_build_query(['month' => $selectedMonth, 'year' => $selectedYear, 'partner' => $selectedPartner, 'status' => $selectedStatus])) ?>" class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-ink transition hover:bg-stone-50">
                    Batal Edit
                </a>
            <?php endif; ?>
        </div>

        <input type="hidden" name="prive_id" value="<?= e((string) ($editItem['id'] ?? '')) ?>">
        <input type="hidden" name="filter_month" value="<?= e($selectedMonth) ?>">
        <input type="hidden" name="filter_year" value="<?= e($selectedYear) ?>">
        <input type="hidden" name="filter_partner" value="<?= e($selectedPartner) ?>">
        <input type="hidden" name="filter_status" value="<?= e($selectedStatus) ?>">

        <datalist id="partner-list">
            <?php foreach ($partners as $partnerName): ?>
                <option value="<?= e((string) $partnerName) ?>"></option>
            <?php endforeach; ?>
        </datalist>

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
                    <option value="Hutang" <?= strcasecmp($formStatus, 'Lunas') !== 0 ? 'selected' : '' ?>>Hutang</option>
                    <option value="Lunas" <?= strcasecmp($formStatus, 'Lunas') === 0 ? 'selected' : '' ?>>Lunas</option>
                </select>
            </label>

            <label class="block lg:col-span-2">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Partner</span>
                <input name="partner" list="partner-list" value="<?= e((string) ($editItem['partner'] ?? '')) ?>" required placeholder="Contoh: Partner A" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Jumlah Prive</span>
                <input type="number" step="0.01" min="0" name="jumlah" value="<?= e(clean_decimal($editItem['jumlah'] ?? '')) ?>" required placeholder="0" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tanggal Transfer</span>
                <input type="date" name="tanggal_transfer" value="<?= e($formTanggalTransfer) ?>" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <label class="block lg:col-span-3">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Keterangan</span>
                <input name="keterangan" value="<?= e((string) ($editItem['keterangan'] ?? '')) ?>" placeholder="Opsional" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white">
            </label>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                    <?= $isEdit ? 'Update Prive' : 'Simpan Prive' ?>
                </button>
            </div>
        </div>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Total Prive</p>
            <p class="mt-2 text-2xl font-bold text-ink"><?= rupiah($summary['total'] ?? 0) ?></p>
            <p class="mt-1 text-xs text-stone-400"><?= e((string) ($summary['count'] ?? 0)) ?> transaksi sesuai filter</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Sudah Dibayar</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700"><?= rupiah($summary['lunas'] ?? 0) ?></p>
            <p class="mt-1 text-xs text-emerald-600/80">Status Lunas</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-stone-500">Belum Dibayar</p>
            <p class="mt-2 text-2xl font-bold text-coral"><?= rupiah($summary['hutang'] ?? 0) ?></p>
            <p class="mt-1 text-xs text-coral/80">Masih menjadi hutang prive</p>
        </div>
    </div>

    <form method="GET" action="<?= e(url('/prive')) ?>" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Bulan</label>
            <select name="month" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-40">
                <option value="">Semua Bulan</option>
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= e((string) $num) ?>" <?= $selectedMonth === (string) $num ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Tahun</label>
            <input type="number" name="year" min="2020" max="2100" value="<?= e($selectedYear) ?>" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-32">
        </div>

        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Partner</label>
            <input name="partner" value="<?= e($selectedPartner) ?>" placeholder="Semua partner" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-48">
        </div>

        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500">Status</label>
            <select name="status" class="w-full rounded-lg border border-stone-300 bg-stone-50 px-3 py-2 text-sm text-ink outline-none transition focus:border-brand focus:bg-white sm:w-36">
                <option value="">Semua Status</option>
                <option value="Lunas" <?= $selectedStatus === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="Hutang" <?= $selectedStatus === 'Hutang' ? 'selected' : '' ?>>Hutang</option>
            </select>
        </div>

        <div class="flex w-full gap-2 sm:w-auto">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                Filter
            </button>
            <?php if ($selectedMonth !== '' || $selectedYear !== (string) date('Y') || $selectedPartner !== '' || $selectedStatus !== ''): ?>
                <a href="<?= e(url('/prive')) ?>" class="rounded-lg bg-stone-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-200">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (! ($priveData['ok'] ?? false)): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-semibold">Gagal memuat data prive.</p>
            <p class="mt-1"><?= e((string) ($priveData['error'] ?? 'Terjadi kesalahan sistem.')) ?></p>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Periode PNL</th>
                            <th class="px-4 py-3.5 font-semibold">Partner</th>
                            <th class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Jumlah</th>
                            <th class="whitespace-nowrap px-4 py-3.5 text-center font-semibold">Status</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Tanggal Transfer</th>
                            <th class="px-4 py-3.5 font-semibold">Keterangan</th>
                            <th class="whitespace-nowrap px-4 py-3.5 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($priveData['items'])): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-stone-400">
                                    Belum ada data prive yang sesuai dengan filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($priveData['items'] as $item): ?>
                                <?php $isLunas = strcasecmp((string) ($item['status_pembayaran'] ?? ''), 'Lunas') === 0; ?>
                                <tr class="transition-colors hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-stone-600">
                                        <?= e(! empty($item['tanggal']) ? date('d-m-Y', strtotime((string) $item['tanggal'])) : '-') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                        <?= e(($months[(int) ($item['bulan_pnl'] ?? 0)] ?? '-') . ' ' . (string) ($item['tahun_pnl'] ?? '')) ?>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-ink">
                                        <?= e((string) ($item['partner'] ?? '')) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-ink">
                                        <?= rupiah($item['jumlah'] ?? 0) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center">
                                        <?php if ($isLunas): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Lunas</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Hutang</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                        <?= e(! empty($item['tanggal_transfer']) ? date('d-m-Y', strtotime((string) $item['tanggal_transfer'])) : '-') ?>
                                    </td>
                                    <td class="max-w-xs truncate px-4 py-3 text-stone-500" title="<?= e((string) ($item['keterangan'] ?? '')) ?>">
                                        <?= e((string) ($item['keterangan'] ?? '-')) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="<?= e(url('/prive') . '?' . http_build_query(['month' => $selectedMonth, 'year' => $selectedYear, 'partner' => $selectedPartner, 'status' => $selectedStatus, 'edit_id' => $item['id'] ?? ''])) ?>" class="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-semibold text-brand transition hover:border-brand hover:bg-teal-50">
                                                Edit
                                            </a>
                                            <form method="POST" action="<?= e(url('/prive-delete')) ?>" onsubmit="return confirm('Hapus data prive ini beserta jurnalnya?')">
                                                <input type="hidden" name="prive_id" value="<?= e((string) ($item['id'] ?? '')) ?>">
                                                <input type="hidden" name="filter_month" value="<?= e($selectedMonth) ?>">
                                                <input type="hidden" name="filter_year" value="<?= e($selectedYear) ?>">
                                                <input type="hidden" name="filter_partner" value="<?= e($selectedPartner) ?>">
                                                <input type="hidden" name="filter_status" value="<?= e($selectedStatus) ?>">
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
