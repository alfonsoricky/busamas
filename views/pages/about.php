<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Tentang project</p>
        <h1 class="text-3xl font-bold text-ink sm:text-4xl">Struktur sederhana untuk aplikasi PHP native.</h1>
        <p class="mt-4 leading-7 text-stone-600">
            Project ini sengaja dibuat tanpa framework besar agar mudah dipahami, tetapi tetap punya pemisahan file yang sehat untuk maintenance harian.
        </p>
    </div>

    <div class="mt-10 grid gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Folder utama</h2>
            <ul class="mt-4 space-y-3 text-sm text-stone-600">
                <li><strong class="text-ink">app/</strong> berisi helper global.</li>
                <li><strong class="text-ink">config/</strong> berisi konfigurasi aplikasi.</li>
                <li><strong class="text-ink">public/</strong> menjadi document root.</li>
                <li><strong class="text-ink">views/</strong> berisi layout, partial, dan halaman.</li>
            </ul>
        </div>

        <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Cara menambah halaman</h2>
            <ol class="mt-4 space-y-3 text-sm text-stone-600">
                <li>1. Tambahkan route di <strong class="text-ink">public/index.php</strong>.</li>
                <li>2. Buat file baru di <strong class="text-ink">views/pages</strong>.</li>
                <li>3. Tambahkan link di partial navbar bila diperlukan.</li>
            </ol>
        </div>
    </div>
</section>
