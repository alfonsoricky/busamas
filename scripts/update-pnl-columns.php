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
    // Ensure PNL columns exist
    ensure_pnl_columns($pdo);

    // Seed PNL data
    seed_pnl_invoice_columns($pdo, $excelPath);

    echo 'PNL columns successfully created and updated from Excel.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Update PNL columns gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

function ensure_pnl_columns(PDO $pdo): void
{
    $columns = [
        'komisi_manager_terbayar' => 'ADD COLUMN komisi_manager_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_sales_2_persen',
        'komisi_manager_utang' => 'ADD COLUMN komisi_manager_utang DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_manager_terbayar',
        'pph_final_terbayar' => 'ADD COLUMN pph_final_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_harga_jual',
        'pph_final_belum_terbayar' => 'ADD COLUMN pph_final_belum_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER pph_final_terbayar',
        'komisi_admin_terbayar' => 'ADD COLUMN komisi_admin_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER pph_final_belum_terbayar',
        'komisi_admin_belum_terbayar' => 'ADD COLUMN komisi_admin_belum_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_admin_terbayar',
        'biaya_kirim' => 'ADD COLUMN biaya_kirim DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_admin_belum_terbayar',
        'biaya_admin_bank' => 'ADD COLUMN biaya_admin_bank DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER biaya_kirim',
    ];

    foreach ($columns as $columnName => $alterSql) {
        if (! invoice_column_exists_local($pdo, $columnName)) {
            $pdo->exec('ALTER TABLE invoices ' . $alterSql);
        }
    }
}

function invoice_column_exists_local(PDO $pdo, string $columnName): bool
{
    $statement = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'invoices'
            AND COLUMN_NAME = :column_name
    ");
    $statement->execute(['column_name' => $columnName]);

    return (int) $statement->fetchColumn() > 0;
}
