<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Google Drive</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">File di folder Drive.</h1>
            <p class="mt-4 max-w-2xl leading-7 text-stone-600">
                Halaman ini membaca daftar file private dari Google Drive menggunakan Drive API dan Service Account.
            </p>
        </div>

        <a
            href="https://drive.google.com/drive/folders/<?= e(google_drive_config('folder_id')) ?>"
            target="_blank"
            rel="noreferrer"
            class="inline-flex rounded-lg border border-stone-300 px-4 py-3 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand"
        >
            Buka Folder
        </a>
    </div>

    <?php if (! ($drive['ok'] ?? false)): ?>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
            <p class="font-semibold">Google Drive belum bisa dibaca.</p>
            <p class="mt-2"><?= e($drive['error'] ?? 'Terjadi kesalahan saat mengambil data.') ?></p>
            <p class="mt-2">Pastikan Google Drive API aktif dan folder sudah di-share ke email service account sebagai Viewer.</p>
        </div>
    <?php elseif (empty($drive['files'])): ?>
        <div class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600 shadow-sm">
            Folder kosong, atau service account belum diberi akses ke file di folder ini.
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Nama</th>
                            <th class="px-4 py-3 font-semibold">Tipe</th>
                            <th class="px-4 py-3 font-semibold">Ukuran</th>
                            <th class="px-4 py-3 font-semibold">Diubah</th>
                            <th class="px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($drive['files'] as $file): ?>
                            <tr class="hover:bg-stone-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <?php if (! empty($file['iconLink'])): ?>
                                            <img src="<?= e($file['iconLink']) ?>" alt="" class="h-5 w-5">
                                        <?php endif; ?>
                                        <span class="font-medium text-ink"><?= e($file['name'] ?? '-') ?></span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                    <?= is_google_drive_folder($file) ? 'Folder' : e($file['mimeType'] ?? '-') ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                    <?= e(human_file_size($file['size'] ?? null)) ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-600">
                                    <?= e(! empty($file['modifiedTime']) ? date('d M Y H:i', strtotime($file['modifiedTime'])) : '-') ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <?php if (! empty($file['webViewLink'])): ?>
                                        <a
                                            href="<?= e($file['webViewLink']) ?>"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="font-semibold text-brand hover:text-teal-800"
                                        >
                                            Buka
                                        </a>
                                    <?php else: ?>
                                        <span class="text-stone-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
