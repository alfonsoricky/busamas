<?php
$usersData = is_array($usersData ?? null) ? $usersData : ['ok' => false, 'items' => [], 'error' => 'Data user belum tersedia.'];
$editUser = is_array($editUser ?? null) ? $editUser : null;
$accountingSettings = is_array($accountingSettings ?? null) ? $accountingSettings : ['ok' => false, 'groups' => []];
$flash = $_SESSION['user_management_flash'] ?? null;
unset($_SESSION['user_management_flash']);
$accountBadge = static function (?array $account): string {
    if (! $account) {
        return '-';
    }

    return trim((string) ($account['code'] ?? '') . ' - ' . (string) ($account['name'] ?? ''));
};
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Admin</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Settings</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">
                Kelola user dan lihat mapping COA yang dipakai otomatis oleh form-form sistem.
            </p>
        </div>
        <a href="<?= e(url('/')) ?>" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
            Dashboard
        </a>
        <a href="<?= e(url('/settings/activity-log')) ?>" class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
            Activity Log
        </a>
    </div>

    <?php if (is_array($flash)): ?>
        <div class="mb-6 rounded-lg border p-4 text-sm <?= ($flash['ok'] ?? false) ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-[0.75fr_1.25fr]">
        <form method="POST" action="<?= e(url('/settings')) ?>" class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-ink">User Login</h2>
            <p class="mt-1 text-sm text-stone-600"><?= $editUser ? 'Update user yang dipilih.' : 'Tambah user baru untuk akses sistem.' ?></p>
            <input type="hidden" name="user_id" value="<?= e((string) ($editUser['id'] ?? 0)) ?>">

            <div class="mt-5 space-y-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Nama</span>
                    <input name="name" value="<?= e($editUser['name'] ?? '') ?>" required class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Email</span>
                    <input type="email" name="email" value="<?= e($editUser['email'] ?? '') ?>" required class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Password</span>
                    <input type="password" name="password" <?= $editUser ? '' : 'required' ?> class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                    <?php if ($editUser): ?>
                        <p class="mt-1 text-xs text-stone-500">Kosongkan jika password tidak ingin diganti.</p>
                    <?php endif; ?>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-stone-700">Role</span>
                    <select name="role" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
                        <option value="admin" <?= ($editUser['role'] ?? 'admin') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </label>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">
                    Simpan User
                </button>
                <?php if ($editUser): ?>
                    <a href="<?= e(url('/settings')) ?>" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
                        Batal
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="border-b border-stone-200 px-5 py-4">
                <h2 class="text-lg font-bold text-ink">Daftar User</h2>
            </div>

            <?php if (! ($usersData['ok'] ?? false)): ?>
                <div class="p-5 text-sm text-rose-700"><?= e((string) ($usersData['error'] ?? 'Data user gagal dibaca.')) ?></div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Email</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Role</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Update</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <?php foreach (($usersData['items'] ?? []) as $user): ?>
                                <tr class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= e($user['name'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($user['email'] ?? '') ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-brand ring-1 ring-teal-100"><?= e($user['role'] ?? '') ?></span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600"><?= e((string) ($user['updated_at'] ?? '-')) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <a href="<?= e(url('/settings?edit=' . (int) ($user['id'] ?? 0))) ?>" class="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-semibold text-brand transition hover:border-brand">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-8 rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 class="text-lg font-bold text-ink">COA Mapping Otomatis</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">Daftar akun COA yang saat ini dipakai saat form diposting ke jurnal akuntansi.</p>
        </div>

        <?php if (! ($accountingSettings['ok'] ?? false)): ?>
            <div class="p-5 text-sm text-rose-700">Mapping COA belum bisa dibaca karena database tidak terkoneksi.</div>
        <?php else: ?>
            <div class="divide-y divide-stone-100">
                <?php foreach (($accountingSettings['groups'] ?? []) as $group): ?>
                    <section class="p-5">
                        <h3 class="text-base font-bold text-ink"><?= e($group['title'] ?? '') ?></h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-stone-100 text-left text-sm">
                                <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                                    <tr>
                                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Field / Kondisi</th>
                                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Debit</th>
                                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-100">
                                    <?php foreach (($group['items'] ?? []) as $item): ?>
                                        <tr>
                                            <td class="min-w-72 px-4 py-3">
                                                <p class="font-semibold text-ink"><?= e($item['field'] ?? '') ?></p>
                                                <p class="mt-1 text-xs text-stone-500"><?= e($item['condition'] ?? '') ?></p>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                                <?php if (! empty($item['debit'])): ?>
                                                    <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-brand ring-1 ring-teal-100"><?= e($accountBadge($item['debit'])) ?></span>
                                                <?php else: ?>
                                                    <span class="text-stone-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                                <?php if (! empty($item['credit'])): ?>
                                                    <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-bold text-coral ring-1 ring-orange-100"><?= e($accountBadge($item['credit'])) ?></span>
                                                <?php else: ?>
                                                    <span class="text-stone-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
