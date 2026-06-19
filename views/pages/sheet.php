<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Google Sheet</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Data dari spreadsheet.</h1>
            <p class="mt-4 max-w-2xl leading-7 text-stone-600">
                Halaman ini membaca data private dari Google Sheet menggunakan Google Sheets API dan Service Account.
            </p>
        </div>

        <?php if (! empty($sheet['source_url'])): ?>
            <a
                href="<?= e($sheet['source_url']) ?>"
                target="_blank"
                rel="noreferrer"
                class="inline-flex rounded-lg border border-stone-300 px-4 py-3 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand"
            >
                Endpoint API
            </a>
        <?php endif; ?>
    </div>

    <?php if (! ($sheet['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Google Sheet belum bisa dibaca.</p>
            <p class="mt-2"><?= e($sheet['error'] ?? 'Terjadi kesalahan saat mengambil data.') ?></p>
            <p class="mt-2">Pastikan file credential service account sudah terpasang dan spreadsheet sudah di-share ke email service account sebagai Viewer.</p>
        </div>
    <?php elseif (empty($sheet['rows'])): ?>
        <div class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600 shadow-sm">
            Data sheet kosong atau hanya berisi header.
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <?php foreach ($sheet['headers'] as $header): ?>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold"><?= e($header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($sheet['rows'] as $row): ?>
                            <tr class="hover:bg-stone-50">
                                <?php foreach ($sheet['headers'] as $index => $header): ?>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                        <?= e($row[$index] ?? '') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
