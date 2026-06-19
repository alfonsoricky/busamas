<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
        <div>
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand">Kontak</p>
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Form statis siap disambungkan ke proses backend.</h1>
            <p class="mt-4 leading-7 text-stone-600">
                Halaman ini bisa menjadi dasar untuk fitur kirim pesan, validasi input, atau penyimpanan data ke database.
            </p>
        </div>

        <form class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5">
                <label class="grid gap-2">
                    <span class="text-sm font-medium text-ink">Nama</span>
                    <input type="text" name="name" class="rounded-lg border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-teal-100" placeholder="Nama lengkap">
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-ink">Email</span>
                    <input type="email" name="email" class="rounded-lg border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-teal-100" placeholder="email@domain.com">
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-ink">Pesan</span>
                    <textarea name="message" rows="5" class="rounded-lg border border-stone-300 px-4 py-3 text-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-teal-100" placeholder="Tulis pesan"></textarea>
                </label>

                <button type="button" class="rounded-lg bg-brand px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                    Kirim Pesan
                </button>
            </div>
        </form>
    </div>
</section>
