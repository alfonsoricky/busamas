<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Data Master</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Master Sales</h1>
        <p class="mt-4 max-w-2xl leading-7 text-stone-600">
            Data sales diambil dari sheet sales pada file PENJUALAN-2026.xlsx.
        </p>
    </div>

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
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($masterSales['items'] as $item): ?>
                            <tr class="hover:bg-stone-50">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-brand"><?= e($item['kode_sales'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-ink"><?= e($item['nama_sales'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
