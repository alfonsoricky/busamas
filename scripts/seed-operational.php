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
    $pdo->exec('TRUNCATE TABLE operational_expenses');
    $count = seed_operational_expenses_from_workbook($pdo, $excelPath);
    $bonusCount = seed_bonus_expenses($pdo);
    echo 'operational_expenses: ' . ($count + $bonusCount) . ' rows seeded successfully.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Seeder operational gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
