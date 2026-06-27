<?php
$activityLog = is_array($activityLog ?? null) ? $activityLog : ['ok' => false, 'items' => [], 'filters' => [], 'modules' => [], 'actions' => []];
$filters = $activityLog['filters'] ?? [];
$prettyJson = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }
    $decoded = json_decode($value, true);
    if (! is_array($decoded)) {
        return $value;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};
?>

<section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Settings</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Activity Log</h1>
            <p class="mt-2 max-w-2xl leading-7 text-stone-600">Riwayat aktivitas user untuk insert, update, delete, login, dan logout.</p>
        </div>
        <a href="<?= e(url('/settings')) ?>" class="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">
            Settings
        </a>
    </div>

    <?php if (! ($activityLog['ok'] ?? false)): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800"><?= e($activityLog['error'] ?? 'Activity log gagal dibaca.') ?></div>
    <?php else: ?>
        <form method="GET" action="<?= e(url('/settings/activity-log')) ?>" class="mb-6 grid gap-4 rounded-lg border border-stone-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_1fr_2fr_auto] lg:items-end">
            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-stone-500">Module</span>
                <select name="module" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand">
                    <option value="">Semua Module</option>
                    <?php foreach (($activityLog['modules'] ?? []) as $module): ?>
                        <option value="<?= e($module) ?>" <?= ($filters['module'] ?? '') === $module ? 'selected' : '' ?>><?= e($module) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-stone-500">Action</span>
                <select name="action" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand">
                    <option value="">Semua Action</option>
                    <?php foreach (($activityLog['actions'] ?? []) as $action): ?>
                        <option value="<?= e($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>><?= e($action) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-stone-500">Cari</span>
                <input name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="User, record, deskripsi" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand">
            </label>
            <div class="flex gap-2">
                <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">Filter</button>
                <a href="<?= e(url('/settings/activity-log')) ?>" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand">Reset</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-100 text-xs uppercase tracking-wide text-stone-600">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Waktu</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">User</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Action</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Module</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Record</th>
                            <th class="px-4 py-3 font-semibold">Deskripsi</th>
                            <th class="px-4 py-3 font-semibold">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (empty($activityLog['items'])): ?>
                            <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">Belum ada activity log.</td></tr>
                        <?php endif; ?>
                        <?php foreach (($activityLog['items'] ?? []) as $item): ?>
                            <tr class="align-top hover:bg-stone-50">
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['created_at'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink"><?= e($item['user_name'] ?? 'System') ?></td>
                                <td class="whitespace-nowrap px-4 py-3"><span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-brand"><?= e($item['action'] ?? '') ?></span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['module'] ?? '') ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-stone-700"><?= e($item['record_id'] ?? '-') ?></td>
                                <td class="min-w-72 px-4 py-3 text-stone-700"><?= e($item['description'] ?? '') ?></td>
                                <td class="min-w-96 px-4 py-3">
                                    <details>
                                        <summary class="cursor-pointer text-xs font-bold text-brand">Lihat perubahan</summary>
                                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                                            <pre class="max-h-72 overflow-auto rounded bg-stone-950 p-3 text-[11px] leading-5 text-stone-100"><?= e($prettyJson($item['old_values'] ?? null)) ?></pre>
                                            <pre class="max-h-72 overflow-auto rounded bg-stone-950 p-3 text-[11px] leading-5 text-stone-100"><?= e($prettyJson($item['new_values'] ?? null)) ?></pre>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
