<?php

declare(strict_types=1);

/**
 * Update data komisi sales, manager, dan admin dari sheet Penjualan di PENJUALAN-2026.xlsx
 *
 * Kolom yang diupdate:
 *   S  -> komisi_sales_terbayar
 *   T  -> komisi_sales_belum_terbayar
 *   U  -> status_pembayaran_komisi_sales
 *   V  -> tanggal_transfer_komisi_sales
 *   W  -> komisi_manager_terbayar
 *   X  -> komisi_manager_utang
 *   Y  -> tanggal_transfer_komisi_manager
 *   AD -> tanggal_transfer_komisi_admin
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/helpers.php';

$excelPath = $baseDir . '/storage/PENJUALAN-2026.xlsx';

if (! is_readable($excelPath)) {
    fwrite(STDERR, 'File tidak ditemukan: storage/PENJUALAN-2026.xlsx' . PHP_EOL);
    exit(1);
}

$pdo = db();
if ($pdo === null) {
    fwrite(STDERR, 'Koneksi database gagal.' . PHP_EOL);
    exit(1);
}

try {
    $count = update_invoice_commission_data($pdo, $excelPath);
    echo 'invoices updated (komisi sales + manager + admin): ' . $count . ' rows updated successfully.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Update komisi gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

function update_invoice_commission_data(PDO $pdo, string $excelPath): int
{
    // Pastikan semua kolom baru sudah ada
    $cols         = $pdo->query('DESCRIBE invoices')->fetchAll(PDO::FETCH_COLUMN);
    $existingCols = array_flip($cols);

    $newCols = [
        'komisi_sales_terbayar'          => "ADD COLUMN komisi_sales_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_sales_2_persen",
        'komisi_sales_belum_terbayar'    => "ADD COLUMN komisi_sales_belum_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_sales_terbayar",
        'status_pembayaran_komisi_sales' => "ADD COLUMN status_pembayaran_komisi_sales VARCHAR(50) NULL AFTER komisi_sales_belum_terbayar",
        'tanggal_transfer_komisi_sales'  => "ADD COLUMN tanggal_transfer_komisi_sales DATE NULL AFTER status_pembayaran_komisi_sales",
        'tanggal_transfer_komisi_manager'=> "ADD COLUMN tanggal_transfer_komisi_manager DATE NULL AFTER komisi_manager_utang",
        'tanggal_transfer_komisi_admin'  => "ADD COLUMN tanggal_transfer_komisi_admin DATE NULL AFTER komisi_admin_belum_terbayar",
    ];

    foreach ($newCols as $colName => $alterSql) {
        if (! isset($existingCols[$colName])) {
            $pdo->exec('ALTER TABLE invoices ' . $alterSql);
            echo "Kolom $colName ditambahkan." . PHP_EOL;
        }
    }

    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');

    $statement = $pdo->prepare('
        UPDATE invoices
        SET komisi_sales_terbayar           = :sales_terbayar,
            komisi_sales_belum_terbayar     = :sales_belum_terbayar,
            status_pembayaran_komisi_sales  = :status_sales,
            tanggal_transfer_komisi_sales   = :tgl_sales,
            komisi_manager_terbayar         = :manager_terbayar,
            komisi_manager_utang            = :manager_utang,
            tanggal_transfer_komisi_manager = :tgl_manager,
            tanggal_transfer_komisi_admin   = :tgl_admin
        WHERE nomor_invoice = :nomor_invoice
    ');

    $pdo->beginTransaction();
    $count = 0;

    foreach ($rows as $row) {
        $invoiceNo = trim((string) ($row['A'] ?? ''));
        if ($invoiceNo === '' || stripos($invoiceNo, 'invoice') !== false) {
            continue;
        }

        $salesTerbayar      = komisi_parse_number($row['S'] ?? '');
        $salesBelumTerbayar = komisi_parse_number($row['T'] ?? '');
        $statusSales        = trim((string) ($row['U'] ?? ''));
        $tglSales           = komisi_excel_date(trim((string) ($row['V'] ?? '')));
        $managerTerbayar    = komisi_parse_number($row['W'] ?? '');
        $managerUtang       = komisi_parse_number($row['X'] ?? '');
        $tglManager         = komisi_excel_date(trim((string) ($row['Y'] ?? '')));
        $tglAdmin           = komisi_excel_date(trim((string) ($row['AD'] ?? '')));

        // Skip baris yang semua nilainya 0 (baris total/subtotal)
        if ($salesTerbayar === 0.0 && $salesBelumTerbayar === 0.0
            && $managerTerbayar === 0.0 && $managerUtang === 0.0
            && $statusSales === '' && $tglSales === null) {
            continue;
        }

        $statement->execute([
            'sales_terbayar'      => $salesTerbayar,
            'sales_belum_terbayar'=> $salesBelumTerbayar,
            'status_sales'        => $statusSales !== '' ? $statusSales : null,
            'tgl_sales'           => $tglSales,
            'manager_terbayar'    => $managerTerbayar,
            'manager_utang'       => $managerUtang,
            'tgl_manager'         => $tglManager,
            'tgl_admin'           => $tglAdmin,
            'nomor_invoice'       => $invoiceNo,
        ]);

        if ($statement->rowCount() > 0) {
            $count++;
        }
    }

    $pdo->commit();
    return $count;
}

function komisi_parse_number(mixed $value): float
{
    $value = trim((string) $value);
    if ($value === '') return 0.0;
    $value = str_replace(',', '.', $value);
    $clean = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';
    return is_numeric($clean) ? (float) $clean : 0.0;
}

function komisi_excel_date(string $value): ?string
{
    if ($value === '' || ! is_numeric($value)) return null;
    $serial = (int) round((float) $value);
    if ($serial <= 0) return null;
    if ($serial >= 60) $serial--;
    $unix = ($serial - 1) * 86400 + mktime(0, 0, 0, 1, 1, 1900);
    $year = (int) date('Y', $unix);
    if ($year < 2000 || $year > 2100) return null;
    return date('Y-m-d', $unix);
}
