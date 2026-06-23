<?php
require dirname(__DIR__) . '/app/helpers.php';

header('Content-Type: text/plain');
echo "=== DEBUG DELETION ON PRODUCTION ===\n";

$pdo = db();
if ($pdo === null) {
    echo "ERROR: Database not connected.\n";
    exit;
}

$stmt = $pdo->query("SELECT kode_invoice, nomor_invoice, google_drive_file_id FROM invoices WHERE kode_invoice LIKE 'INV-TEST-%' OR nomor_invoice LIKE '%TEST%'");
$testInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($testInvoices) . " test invoices to delete:\n";
print_r($testInvoices);

$token = google_service_account_access_token();
if (!$token['ok']) {
    echo "WARNING: Google Service Account Token acquisition failed: " . ($token['error'] ?? 'Unknown') . "\n";
} else {
    echo "SUCCESS: Obtained Google OAuth token.\n";
}

foreach ($testInvoices as $inv) {
    $kode = $inv['kode_invoice'];
    $no = $inv['nomor_invoice'];
    echo "\n-----------------------------------\n";
    echo "Processing Deletion for: $no ($kode)\n";

    echo "1. Checking Google Drive file deletion:\n";
    $fileId = $inv['google_drive_file_id'];
    if (!$fileId) {
        $fileName = invoice_drive_filename(
            $inv['nomor_invoice'],
            $inv['nama_laundry_invoice'] ?: $inv['nama_customer_invoice'] ?: ''
        );
        echo "   Drive ID not in DB, searching by filename: '$fileName'\n";
        $fileId = google_drive_find_invoice_file($fileName);
    }
    
    if ($fileId) {
        echo "   Deleting file ID: $fileId\n";
        $driveOk = google_drive_delete_file($fileId);
        echo "   Drive delete result: " . ($driveOk ? 'SUCCESS' : 'FAILED') . "\n";
    } else {
        echo "   No file found in Drive.\n";
    }

    echo "2. Checking Google Sheets row deletion:\n";
    $rowIndex = sheets_find_invoice_row($inv['nomor_invoice']);
    if ($rowIndex !== null) {
        echo "   Found row index in Sheet: $rowIndex\n";
        $sheetOk = sheets_delete_invoice_row($inv['nomor_invoice']);
        echo "   Sheets delete result: " . ($sheetOk ? 'SUCCESS' : 'FAILED') . "\n";
    } else {
        echo "   Invoice row not found in Sheets.\n";
    }

    echo "3. Deleting from database:\n";
    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM invoice_items WHERE kode_invoice = ?')->execute([$kode]);
        $pdo->prepare('DELETE FROM invoices WHERE kode_invoice = ?')->execute([$kode]);
        $pdo->commit();
        echo "   SUCCESS: Deleted from DB.\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "   FAILED: DB deletion error: " . $e->getMessage() . "\n";
    }
}

echo "\nClean up orphaned items:\n";
$delItems = $pdo->exec("DELETE FROM invoice_items WHERE kode_invoice LIKE 'INV-TEST-%'");
echo "Orphaned items deleted count: $delItems\n";

echo "\n=== DEBUG COMPLETED ===\n";
