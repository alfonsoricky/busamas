<?php
require 'app/helpers.php';
$pdo = db();
$excelPath = 'storage/PENJUALAN-2026.xlsx';

echo "=== DATABASE VALUE FOR 267/BM-INV/I/2026 ===\n";
$stmt = $pdo->prepare("
    SELECT nomor_invoice, komisi_sales_terbayar, komisi_sales_belum_terbayar, tanggal_transfer_komisi_sales, status_pembayaran_komisi_sales
    FROM invoices
    WHERE nomor_invoice = :no
");
$stmt->execute(['no' => '267/BM-INV/I/2026']);
$dbVal = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($dbVal);

echo "\n=== EXCEL VALUE FOR 267/BM-INV/I/2026 ===\n";
$rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');
foreach ($rows as $index => $row) {
    $invoiceNo = trim((string) ($row['A'] ?? ''));
    if ($invoiceNo === '267/BM-INV/I/2026') {
        echo "Row Index: $index\n";
        foreach ($row as $col => $val) {
            if ($col === 'S' || $col === 'T' || $col === 'U' || $col === 'V') {
                echo "  Col $col: $val\n";
            }
        }
    }
}
