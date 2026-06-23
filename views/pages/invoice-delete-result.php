<section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-3 text-red-600">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h1 class="text-xl font-bold">Gagal Menghapus Invoice</h1>
        </div>
        
        <p class="mt-4 text-sm leading-6 text-stone-600">
            Terjadi kesalahan saat menghapus invoice dari database atau sinkronisasi Google Drive/Sheets.
        </p>

        <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-950">
            <strong>Pesan Error:</strong> <?= e($deleteResult['message'] ?? 'Terjadi kesalahan tidak diketahui.') ?>
        </div>

        <?php if (!empty($deleteResult['warnings'])): ?>
            <div class="mt-4 rounded-lg bg-orange-50 p-4 text-sm text-orange-950">
                <strong class="block mb-1">Peringatan Tambahan:</strong>
                <ul class="list-disc pl-5 space-y-1">
                    <?php foreach ($deleteResult['warnings'] as $warning): ?>
                        <li><?= e($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="mt-6 flex justify-end">
            <a href="<?= e(url('/invoices')) ?>" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
                Kembali ke Daftar Invoice
            </a>
        </div>
    </div>
</section>
