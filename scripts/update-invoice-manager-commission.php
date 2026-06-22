<?php

declare(strict_types=1);

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
    $count = update_invoice_manager_commission($pdo, $excelPath);
    echo 'invoices updated (komisi manager): ' . $count . ' rows updated successfully.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Update komisi manager gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

function update_invoice_manager_commission(PDO $pdo, string $excelPath): int
{
    // Pastikan kolom tanggal_transfer_komisi_manager sudah ada
    $cols = $pdo->query('DESCRIBE invoices')->fetchAll(PDO::FETCH_COLUMN);
    $existingCols = array_flip($cols);
    if (!isset($existingCols['tanggal_transfer_komisi_manager'])) {
        $pdo->exec('ALTER TABLE invoices ADD COLUMN tanggal_transfer_komisi_manager DATE NULL AFTER komisi_manager_utang');
        echo 'Kolom tanggal_transfer_komisi_manager ditambahkan.' . PHP_EOL;
    }

    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');

    $statement = $pdo->prepare('
        UPDATE invoices
        SET komisi_manager_terbayar = :komisi_manager_terbayar,
            komisi_manager_utang = :komisi_manager_utang,
            tanggal_transfer_komisi_manager = :tanggal_transfer
        WHERE nomor_invoice = :nomor_invoice
    ');

    $pdo->beginTransaction();
    $count = 0;

    foreach ($rows as $row) {
        $invoiceNumber = trim((string) ($row['A'] ?? ''));
        if ($invoiceNumber === '' || strcasecmp($invoiceNumber, 'nomor invoice') === 0) {
            continue;
        }

        $terbayar  = parse_excel_number_internal($row['W'] ?? '');
        $utang     = parse_excel_number_internal($row['X'] ?? '');
        $tglSerial = trim((string) ($row['Y'] ?? ''));
        $tanggal   = excel_serial_to_date($tglSerial);

        // Skip baris yang semua nilai 0 dan tanggal kosong (baris total/header)
        if ($terbayar === 0.0 && $utang === 0.0 && $tanggal === null) {
            continue;
        }

        $statement->execute([
            'komisi_manager_terbayar' => $terbayar,
            'komisi_manager_utang'    => $utang,
            'tanggal_transfer'        => $tanggal,
            'nomor_invoice'           => $invoiceNumber,
        ]);

        if ($statement->rowCount() > 0) {
            $count++;
        }
    }

    $pdo->commit();
    return $count;
}

/**
 * Konversi serial date Excel ke format YYYY-MM-DD.
 * Excel menyimpan tanggal sebagai angka integer (jumlah hari sejak 1900-01-00).
 */
function excel_serial_to_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '' || !is_numeric($value)) {
        return null;
    }

    $serial = (int) round((float) $value);
    if ($serial <= 0) {
        return null;
    }

    // Excel bug: menganggap 1900 adalah tahun kabisat, jadi kita kompensasi
    if ($serial >= 60) {
        $serial--;
    }

    $unix = ($serial - 1) * 86400 + mktime(0, 0, 0, 1, 1, 1900);
    $date = date('Y-m-d', $unix);

    // Validasi range tahun yang wajar
    $year = (int) date('Y', $unix);
    if ($year < 2000 || $year > 2100) {
        return null;
    }

    return $date;
}

/**
 * Parse angka dari Excel (bisa berupa string kosong, desimal, dll).
 */
function parse_excel_number_internal(mixed $value): float
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0.0;
    }
    $value = str_replace(',', '.', $value);
    $value = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';
    return is_numeric($value) ? (float) $value : 0.0;
}
