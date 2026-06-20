<?php
    $links = [
        '/' => 'Dashboard',
        '/master-barang' => 'Master Barang',
        '/master-customer' => 'Master Customer',
        '/master-sales' => 'Master Sales',
        '/invoices' => 'Invoice',
    ];
?>
<header class="border-b border-stone-200 bg-white/90 backdrop-blur">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="<?= e(url('/')) ?>" class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand text-sm font-bold text-white">B</span>
            <span class="text-lg font-semibold"><?= e(app_config('name')) ?></span>
        </a>

        <div class="flex items-center gap-1 rounded-lg border border-stone-200 bg-stone-100 p-1">
            <?php foreach ($links as $href => $label): ?>
                <a
                    href="<?= e(url($href)) ?>"
                    class="rounded-md px-3 py-2 text-sm font-medium transition <?= route_is($href) ? 'bg-white text-brand shadow-sm' : 'text-stone-600 hover:text-ink' ?>"
                >
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
