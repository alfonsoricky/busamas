# Aturan Sinkronisasi Data Busamas ERP

Untuk menghindari ketidakcocokan data keuangan (laporan P&L dan Jurnal Akuntansi) antara database lokal ERP dan file Excel rekapitulasi penjualan (`PENJUALAN-2026.xlsx`):

## 1. Harga Barang & Subtotal
*   **Konsistensi Harga**: Jika pelanggan mendapatkan harga khusus/diskon per unit barang pada invoice fisiknya (misalnya `E-951` dijual Rp1.540.000 dari harga standar Rp1.980.000), maka di rekap Excel dan database harga satuan barang tersebut harus ditulis sebesar harga khusus tersebut (Rp1.540.000).
*   **Subtotal Harus Cocok**: Nilai Subtotal (Kolom H di Excel) harus selalu sama dengan jumlah perkalian kuantitas dengan harga unit aktual yang ditagihkan. Jangan menggunakan harga pricelist standar pada rekap Excel jika harga di invoice fisiknya berbeda.

## 2. Sumber Data Invoice Baru
*   **Source of Truth**: Nomor invoice, tanggal invoice, nomor surat jalan (SJ), dan tanggal surat jalan (SJ) untuk invoice baru harus diambil secara langsung dari kolom rekap Excel utama (`PENJUALAN-2026.xlsx` Kolom A, B, C, D) sebagai satu-satunya sumber kebenaran, bukan diekstrak secara otomatis dari dokumen invoice individual (.xlsx/.docx).

## 3. Rumus Diskon & Total Harga Jual
*   **Konsistensi Matematis**: Nilai total harga jual (Kolom K) harus selalu memenuhi rumus:
    `Subtotal (Kolom H) - Diskon Amount (Kolom J) = Total Harga Jual (Kolom K)`
