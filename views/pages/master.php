<?php
$tab = $tab ?? 'barang';
$flash = $flash ?? null;
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <!-- Flash Messages -->
    <?php if (is_array($flash)): ?>
        <?php $flashOk = (bool) ($flash['ok'] ?? false); ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= $flashOk ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? $flash['error'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Data Master</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Data Master Aplikasi</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Kelola data barang, customer/laundry, dan sales agent yang tersimpan dalam sistem Busamas.
            </p>
        </div>
        <div>
            <button onclick="openAddModal()" class="rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand/90 transition shadow-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah <?= $tab === 'barang' ? 'Barang' : ($tab === 'customer' ? 'Customer' : 'Sales') ?>
            </button>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-stone-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="<?= e(url('/master?tab=barang')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'barang' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Barang
            </a>
            <a href="<?= e(url('/master?tab=customer')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'customer' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Customer
            </a>
            <a href="<?= e(url('/master?tab=sales')) ?>" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold <?= $tab === 'sales' ? 'border-brand text-brand' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' ?>">
                Master Sales
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <?php if ($tab === 'barang'): ?>
        <?php if (! ($masterBarang['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master barang belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterBarang['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Barang</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterBarang['summary']['total_barang']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Transaksi</p>
                    <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $masterBarang['summary']['total_transaksi']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                    <p class="mt-2 text-3xl font-bold text-coral"><?= e((string) $masterBarang['summary']['total_invoice']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-master-datatable data-empty-label="barang">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Barang</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Ukuran</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Harga</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Transaksi</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Invoice</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Status</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterBarang['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_barang'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['ukuran'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e(rupiah($item['harga_default'] ?? 0)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_transaksi'] ?? '0') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_invoice'] ?? '0') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <?php if ($item['is_active'] ?? 1): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Aktif</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-stone-50 px-2.5 py-0.5 text-xs font-semibold text-stone-600 ring-1 ring-inset ring-stone-500/20">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 flex items-center gap-2">
                                        <button type="button" onclick='openEditModal(<?= json_encode($item) ?>)' class="text-brand hover:text-brand-dark p-1" title="Ubah">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        
                                        <form method="POST" action="<?= url('/master?tab=barang') ?>" class="inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="table" value="master_barang">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-stone-500 hover:text-brand p-1" title="<?= ($item['is_active'] ?? 1) ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <?php if ($item['is_active'] ?? 1): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="<?= url('/master?tab=barang') ?>" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="table" value="master_barang">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 p-1" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'customer'): ?>
        <?php if (! ($masterCustomer['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master customer belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterCustomer['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Customer</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterCustomer['summary']['total_customer']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Invoice</p>
                    <p class="mt-2 text-3xl font-bold text-brand"><?= e((string) $masterCustomer['summary']['total_invoice']) ?></p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Dengan Telepon</p>
                    <p class="mt-2 text-3xl font-bold text-coral"><?= e((string) $masterCustomer['summary']['total_dengan_telepon']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-master-datatable data-empty-label="customer">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Customer</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Laundry</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Telepon</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="number">Invoice</th>
                                <th class="px-4 py-3 font-semibold" data-sort-type="text">Alamat</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Status</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterCustomer['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_customer'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['nama_laundry'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['no_telepon'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['jumlah_invoice'] ?? '0') ?></td>
                                    <td class="min-w-72 px-4 py-3 text-stone-600"><?= e($item['alamat_default'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <?php if ($item['is_active'] ?? 1): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Aktif</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-stone-50 px-2.5 py-0.5 text-xs font-semibold text-stone-600 ring-1 ring-inset ring-stone-500/20">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 flex items-center gap-2">
                                        <button type="button" onclick='openEditModal(<?= json_encode($item) ?>)' class="text-brand hover:text-brand-dark p-1" title="Ubah">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        
                                        <form method="POST" action="<?= url('/master?tab=customer') ?>" class="inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="table" value="master_customers">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-stone-500 hover:text-brand p-1" title="<?= ($item['is_active'] ?? 1) ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <?php if ($item['is_active'] ?? 1): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="<?= url('/master?tab=customer') ?>" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="table" value="master_customers">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 p-1" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'sales'): ?>
        <?php if (! ($masterSales['ok'] ?? false)): ?>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                <p class="font-semibold">Master sales belum bisa dibaca.</p>
                <p class="mt-2"><?= e($masterSales['error'] ?? 'Terjadi kesalahan saat membaca data.') ?></p>
            </div>
        <?php else: ?>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-stone-500">Total Sales</p>
                    <p class="mt-2 text-3xl font-bold text-ink"><?= e((string) $masterSales['summary']['total_sales']) ?></p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm" data-master-datatable data-empty-label="sales">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Nama Sales</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold" data-sort-type="text">Status</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach ($masterSales['items'] as $item): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_sales'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_sales'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <?php if ($item['is_active'] ?? 1): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Aktif</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-stone-50 px-2.5 py-0.5 text-xs font-semibold text-stone-600 ring-1 ring-inset ring-stone-500/20">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 flex items-center gap-2">
                                        <button type="button" onclick='openEditModal(<?= json_encode($item) ?>)' class="text-brand hover:text-brand-dark p-1" title="Ubah">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        
                                        <form method="POST" action="<?= url('/master?tab=sales') ?>" class="inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="table" value="master_sales">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-stone-500 hover:text-brand p-1" title="<?= ($item['is_active'] ?? 1) ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <?php if ($item['is_active'] ?? 1): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="<?= url('/master?tab=sales') ?>" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="table" value="master_sales">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 p-1" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- Modal CRUD Container -->
<div id="master-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4 transition-all duration-300">
    <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl transition-all scale-95 duration-300">
        <div class="mb-5 flex items-center justify-between border-b border-stone-100 pb-3">
            <h3 class="text-xl font-bold text-ink" id="modal-title">Form Data Master</h3>
            <button type="button" class="text-stone-400 hover:text-stone-700 transition text-2xl font-semibold" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="modal-form" method="POST" action="<?= url('/master?tab=' . $tab) ?>" class="space-y-4">
            <input type="hidden" name="action" id="form-action" value="">
            <input type="hidden" name="id" id="form-id" value="">
            
            <?php if ($tab === 'barang'): ?>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Nama Barang</label>
                    <input type="text" name="nama_barang" id="input-nama_barang" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Ukuran</label>
                    <input type="text" name="ukuran" id="input-ukuran" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="Contoh: 20 L, 5 L, 20 KG">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Harga Standar (Rp)</label>
                    <input type="number" name="harga_default" id="input-harga_default" required min="0" step="0.01" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </div>
                
            <?php elseif ($tab === 'customer'): ?>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Nama Laundry</label>
                    <input type="text" name="nama_laundry" id="input-nama_laundry" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Nama Owner / Customer</label>
                    <input type="text" name="nama_customer" id="input-nama_customer" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">No. Telepon / HP</label>
                    <input type="text" name="no_telepon" id="input-no_telepon" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Alamat Utama</label>
                    <textarea name="alamat_default" id="input-alamat_default" rows="3" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20"></textarea>
                </div>
                
            <?php elseif ($tab === 'sales'): ?>
                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1">Nama Sales Agent</label>
                    <input type="text" name="nama_sales" id="input-nama_sales" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-semibold text-stone-700 mb-1">Status Keaktifan</label>
                <select name="is_active" id="input-is_active" class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    <option value="1">Aktif</option>
                    <option value="0">Non-aktif</option>
                </select>
            </div>
            
            <div class="mt-6 flex justify-end gap-3 border-t border-stone-100 pt-4">
                <button type="button" onclick="closeModal()" class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-ink hover:bg-stone-50 transition">Batal</button>
                <button type="submit" class="rounded-md bg-brand px-5 py-2 text-sm font-semibold text-white hover:bg-brand/90 transition shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('master-modal');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const formId = document.getElementById('form-id');

    function openAddModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modalTitle.textContent = 'Tambah <?= $tab === 'barang' ? 'Barang' : ($tab === 'customer' ? 'Customer' : 'Sales') ?>';
        formAction.value = 'create_<?= $tab ?>';
        formId.value = '';
        
        // Reset all inputs
        const inputs = modal.querySelectorAll('input:not([type="hidden"]), textarea, select');
        inputs.forEach(i => {
            if (i.tagName === 'SELECT') {
                i.value = '1';
            } else {
                i.value = '';
            }
        });
    }

    function openEditModal(data) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modalTitle.textContent = 'Ubah <?= $tab === 'barang' ? 'Barang' : ($tab === 'customer' ? 'Customer' : 'Sales') ?>';
        formAction.value = 'update_<?= $tab ?>';
        formId.value = data.id;
        
        // Populate inputs
        for (const key in data) {
            const input = document.getElementById('input-' + key);
            if (input) {
                input.value = data[key] !== null ? data[key] : '';
            }
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close on click outside modal content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.querySelectorAll('[data-master-datatable]').forEach((table) => {
        const tbody = table.tBodies[0];
        const headers = Array.from(table.tHead?.rows[0]?.cells || []);
        const originalRows = Array.from(tbody?.rows || []);
        const label = table.dataset.emptyLabel || 'data';
        const state = {
            search: '',
            perPage: 25,
            page: 1,
            sortIndex: -1,
            sortDir: 'asc',
        };

        const sortNeutralIcon = ' ⇅';
        const sortAscIcon = ' ▲';
        const sortDescIcon = ' ▼';
        const wrapper = document.createElement('div');
        wrapper.className = 'border-b border-stone-100 bg-white p-4';
        wrapper.innerHTML = `
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                    <span>Tampilkan</span>
                    <select class="rounded-md border border-stone-300 bg-white px-2 py-1.5 text-sm outline-none focus:border-brand" data-dt-per-page>
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">Semua</option>
                    </select>
                    <span>data</span>
                </div>
                <label class="flex w-full items-center gap-2 text-sm text-stone-600 lg:w-80">
                    <span>Cari</span>
                    <input type="search" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="Ketik untuk mencari..." data-dt-search>
                </label>
            </div>
        `;

        const footer = document.createElement('div');
        footer.className = 'flex flex-col gap-3 border-t border-stone-100 bg-white p-4 text-sm text-stone-600 sm:flex-row sm:items-center sm:justify-between';
        footer.innerHTML = `
            <p data-dt-info></p>
            <div class="flex flex-wrap items-center gap-2" data-dt-pagination></div>
        `;

        table.closest('.overflow-hidden')?.insertBefore(wrapper, table.closest('.overflow-x-auto'));
        table.closest('.overflow-hidden')?.appendChild(footer);

        const perPageInput = wrapper.querySelector('[data-dt-per-page]');
        const searchInput = wrapper.querySelector('[data-dt-search]');
        const info = footer.querySelector('[data-dt-info]');
        const pagination = footer.querySelector('[data-dt-pagination]');

        function cleanNumber(value) {
            const normalized = String(value || '').replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
            const number = Number.parseFloat(normalized);
            return Number.isFinite(number) ? number : 0;
        }

        function rowText(row) {
            // Exclude status and actions column from search text
            const cells = Array.from(row.cells).slice(0, -2);
            return cells.map((cell) => cell.textContent.trim()).join(' ').toLowerCase();
        }

        function filteredRows() {
            let rows = originalRows.filter((row) => rowText(row).includes(state.search));

            if (state.sortIndex >= 0) {
                const sortType = headers[state.sortIndex]?.dataset.sortType || 'text';
                rows = [...rows].sort((left, right) => {
                    const leftText = left.cells[state.sortIndex]?.textContent.trim() || '';
                    const rightText = right.cells[state.sortIndex]?.textContent.trim() || '';
                    const result = sortType === 'number'
                        ? cleanNumber(leftText) - cleanNumber(rightText)
                        : leftText.localeCompare(rightText, 'id', { numeric: true, sensitivity: 'base' });
                    return state.sortDir === 'asc' ? result : -result;
                });
            }

            return rows;
        }

        function pageRows(rows) {
            if (state.perPage === 'all') {
                return rows;
            }
            const start = (state.page - 1) * state.perPage;
            return rows.slice(start, start + state.perPage);
        }

        function button(labelText, disabled, active, onClick) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = labelText;
            btn.disabled = disabled;
            btn.className = [
                'min-w-9 rounded-md border px-3 py-1.5 text-sm font-semibold transition',
                active ? 'border-brand bg-brand text-white' : 'border-stone-300 bg-white text-ink hover:border-brand hover:text-brand',
                disabled ? 'cursor-not-allowed opacity-50 hover:border-stone-300 hover:text-ink' : '',
            ].join(' ');
            btn.addEventListener('click', onClick);
            return btn;
        }

        function render() {
            const rows = filteredRows();
            const totalPages = state.perPage === 'all' ? 1 : Math.max(1, Math.ceil(rows.length / state.perPage));
            state.page = Math.min(state.page, totalPages);

            tbody.replaceChildren(...pageRows(rows));
            headers.forEach((header, index) => {
                // Don't make actions column sortable
                if (index === headers.length - 1) return;
                
                header.classList.add('cursor-pointer', 'select-none');
                const base = header.dataset.label || header.textContent.replace(/[▲▼]/g, '').trim();
                header.dataset.label = base;
                const icon = state.sortIndex === index ? (state.sortDir === 'asc' ? sortAscIcon : sortDescIcon) : sortNeutralIcon;
                header.textContent = base + icon;
            });

            if (rows.length === 0) {
                const emptyRow = tbody.insertRow();
                const cell = emptyRow.insertCell();
                cell.colSpan = headers.length;
                cell.className = 'px-4 py-8 text-center text-sm text-stone-500';
                cell.textContent = `Tidak ada ${label} yang cocok.`;
            }

            const start = rows.length === 0 ? 0 : (state.perPage === 'all' ? 1 : ((state.page - 1) * state.perPage) + 1);
            const end = state.perPage === 'all' ? rows.length : Math.min(state.page * state.perPage, rows.length);
            info.textContent = `Menampilkan ${start}-${end} dari ${rows.length} data`;

            pagination.replaceChildren();
            pagination.appendChild(button('Prev', state.page <= 1, false, () => {
                state.page -= 1;
                render();
            }));

            const totalButtons = state.perPage === 'all' ? 1 : totalPages;
            for (let page = 1; page <= totalButtons; page += 1) {
                if (totalButtons > 7 && page !== 1 && page !== totalButtons && Math.abs(page - state.page) > 1) {
                    if (page === 2 || page === totalButtons - 1) {
                        const dots = document.createElement('span');
                        dots.className = 'px-1 text-stone-400';
                        dots.textContent = '...';
                        pagination.appendChild(dots);
                    }
                    continue;
                }
                pagination.appendChild(button(String(page), false, page === state.page, () => {
                    state.page = page;
                    render();
                }));
            }

            pagination.appendChild(button('Next', state.page >= totalPages, false, () => {
                state.page += 1;
                render();
            }));
        }

        perPageInput.addEventListener('change', () => {
            state.perPage = perPageInput.value === 'all' ? 'all' : Number.parseInt(perPageInput.value, 10);
            state.page = 1;
            render();
        });

        searchInput.addEventListener('input', () => {
            state.search = searchInput.value.trim().toLowerCase();
            state.page = 1;
            render();
        });

        headers.forEach((header, index) => {
            if (index === headers.length - 1) return;
            header.addEventListener('click', () => {
                if (state.sortIndex === index) {
                    state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortIndex = index;
                    state.sortDir = 'asc';
                }
                render();
            });
        });

        render();
    });
</script>
