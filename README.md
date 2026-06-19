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

## Membaca Google Sheet Private

Konfigurasi spreadsheet ada di `config/google-sheet.php`.

Project membaca Google Sheet private memakai Google Sheets API dan Service Account.

1. Buat Service Account di Google Cloud.
2. Aktifkan Google Sheets API.
3. Download credential JSON.
4. Simpan credential di `storage/google-service-account.json` atau set env `GOOGLE_SERVICE_ACCOUNT_JSON`.
5. Share spreadsheet ke email service account sebagai `Viewer`.
6. Atur range lewat env `GOOGLE_SHEET_RANGE` bila perlu, misalnya `Sheet1!A:Z`.

Buka:

```text
/sheet
```

## Membaca Google Drive Private

Konfigurasi folder Drive ada di `config/google-drive.php`.

1. Aktifkan Google Drive API.
2. Share folder Drive ke email service account sebagai `Viewer`.
3. Buka:

```text
/drive
```

## Master Barang

Halaman master barang membaca hasil generate dari `storage/generated/master-barang.csv`.

```text
/master-barang
```
