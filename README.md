# Busamas PHP Native

Starter project PHP native dengan struktur sederhana dan Tailwind CDN.

## Menjalankan Project

```bash
php -S localhost:8000 -t public
```

Buka `http://localhost:8000` di browser.

## Struktur Folder

```text
app/          Helper aplikasi
config/       Konfigurasi aplikasi
public/       Entry point dan document root
views/        Layout, partial, dan halaman
```

## Menambah Halaman

1. Tambahkan route baru di `public/index.php`.
2. Buat file view baru di `views/pages`.
3. Tambahkan link ke `views/partials/navbar.php` bila halaman perlu tampil di menu.
