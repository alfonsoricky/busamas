# Busamas ERP

Project PHP native untuk invoice, operasional, komisi, prive, dan laporan akuntansi Busamas.

## Requirement Lokal

- MAMP aktif
- PHP MAMP: `/Applications/MAMP/bin/php/php8.0.0/bin/php`
- MySQL MAMP aktif di port `3306`
- Database lokal: `busamas`

## Setup Database Lokal

1. Buka phpMyAdmin MAMP.
2. Buat database:

```sql
CREATE DATABASE busamas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Import file SQL terbaru jika ingin mengganti database lokal, misalnya:

```text
storage/u928360788_busamas (2).sql
```

Alternatif via terminal:

```bash
/Applications/MAMP/Library/bin/mysql -h 127.0.0.1 -P 3306 -u root busamas < "storage/u928360788_busamas (2).sql"
```

## File `.env` Lokal

Buat atau sesuaikan file `.env` di root project:

```text
APP_URL=http://127.0.0.1:8000/
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=busamas
DB_USERNAME=root
DB_PASSWORD=
```

Catatan: di MAMP lokal, gunakan `127.0.0.1`, bukan `localhost`, agar koneksi tidak salah socket.

## Menjalankan Project Lokal

Dari root project:

```bash
env APP_URL=http://127.0.0.1:8000/ DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=busamas DB_USERNAME=root DB_PASSWORD= /Applications/MAMP/bin/php/php8.0.0/bin/php -S 127.0.0.1:8000 -t public
```

Buka:

```text
http://127.0.0.1:8000/login
```

## Login

Gunakan user admin yang sudah ada di database lokal. Jika database baru kosong, jalankan migration/seed dari menu Database setelah login pertama tersedia dari dump/seed.

## Maintenance Database

Menu maintenance:

```text
/db-maintenance
```

Fungsi penting:

- `Update Hari Ini`: menjalankan seeder update terakhir yang berisi koreksi invoice, payment, prive, biaya kirim, dan jurnal terkait.
- `Migrate & Seed`: membuat/mengisi ulang tabel dari `database/schema.sql` dan `database/seed-data.sql`.

Jalankan tombol seeder di hosting hanya setelah kode terbaru sudah dipull/deploy.

## Struktur Folder

```text
app/          Helper dan logic aplikasi
config/       Konfigurasi database/env/google
database/     Schema dan seed SQL
public/       Entry point aplikasi
scripts/      Script import/generate data
storage/      File Excel, dump SQL, generated files
views/        Layout, partial, dan halaman
```

## Hosting

Contoh `.env` hosting:

```text
APP_URL=https://busamas.com/erp
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u928360788_busamas
DB_USERNAME=u928360788_busamas
DB_PASSWORD=isi_password_hosting
```

Setelah deploy:

1. Pull kode terbaru di hosting.
2. Pastikan `.env` hosting benar.
3. Buka `/db-maintenance`.
4. Jalankan tombol seeder yang diperlukan, misalnya `Update Hari Ini`.

## Catatan Akuntansi

Posting jurnal memakai data sumber transaksi:

- invoice mencatat piutang, pendapatan, diskon, pajak, komisi, pembelian, dan biaya terkait;
- kas masuk customer dicatat dari tanggal pembayaran aktual atau tabel `invoice_payments`;
- kas keluar pembelian, komisi, biaya kirim, admin bank, operasional, bonus, dan prive memakai tanggal bayar/transfer aktual;
- jika status masih hutang atau tanggal bayar kosong, transaksi tetap menjadi hutang dan tidak mengurangi kas.

## Troubleshooting

Jika muncul `Database belum bisa dibaca`:

1. Pastikan MAMP MySQL aktif.
2. Pastikan database `busamas` ada.
3. Pastikan `.env` lokal memakai `DB_HOST=127.0.0.1`.
4. Tes koneksi:

```bash
env DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=busamas DB_USERNAME=root DB_PASSWORD= /Applications/MAMP/bin/php/php8.0.0/bin/php -r 'require "app/helpers.php"; var_dump(db() instanceof PDO);'
```

Jika muncul error PHP `Cannot unpack array with string keys`, pastikan kode sudah dipull sampai commit yang memakai `array_merge` untuk kompatibilitas PHP 8.0.
