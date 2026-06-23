<?php
require dirname(__DIR__) . '/app/helpers.php';

$pdo = db();
if ($pdo === null) {
    echo "ERROR: Database not connected.\n";
    exit(1);
}

// 1. Fetch real master data
$customer = $pdo->query("SELECT kode_customer, nama_customer, nama_laundry, no_telepon, alamat_default FROM master_customers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$barangList = $pdo->query("SELECT kode_barang, nama_barang, ukuran, harga_default, satuan_default FROM master_barang LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
$sales = $pdo->query("SELECT kode_sales, nama_sales FROM master_sales LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$customer || count($barangList) < 2 || !$sales) {
    echo "ERROR: Insufficient master data. Please run seeder first.\n";
    exit(1);
}

// Generate unique code sequence
$maxTestCode = $pdo->query("SELECT MAX(kode_invoice) FROM invoices WHERE kode_invoice LIKE 'INV-TEST-%'")->fetchColumn();
if ($maxTestCode && preg_match('/^INV-TEST-(\d+)$/', $maxTestCode, $matches)) {
    $nextNum = (int)$matches[1] + 1;
} else {
    $nextNum = 1;
}

$kodeInvoice = 'INV-TEST-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
$nomorInvoice = '999/BM-INV/TEST-' . $nextNum . '/2026';
$todayStr = date('d') . ' Juni ' . date('Y');

// Calculate totals using actual price from master
$price1 = (float)($barangList[0]['harga_default'] ?: 100000);
$price2 = (float)($barangList[1]['harga_default'] ?: 120000);
$qty1 = 3.0;
$qty2 = 4.0;
$total1 = $price1 * $qty1;
$total2 = $price2 * $qty2;
$subtotal = $total1 + $total2;

echo "=== CREATING PERSISTENT TEST INVOICE ===\n";
echo "Invoice Number: $nomorInvoice\n";
echo "Customer: " . $customer['nama_laundry'] . "\n";
echo "Sales Agent: " . $sales['nama_sales'] . "\n";
echo "Item 1: " . $barangList[0]['nama_barang'] . " x $qty1 = " . $total1 . "\n";
echo "Item 2: " . $barangList[1]['nama_barang'] . " x $qty2 = " . $total2 . "\n";
echo "Subtotal: $subtotal\n";

try {
    $pdo->beginTransaction();

    // Clean up first if exists (just in case)
    $pdo->prepare("DELETE FROM invoice_items WHERE kode_invoice = ?")->execute([$kodeInvoice]);
    $pdo->prepare("DELETE FROM invoices WHERE kode_invoice = ?")->execute([$kodeInvoice]);

    // Insert invoice
    $stmt = $pdo->prepare('
        INSERT INTO invoices (
            kode_invoice, nomor_invoice, tanggal_invoice, nomor_surat_jalan, tanggal_surat_jalan, po_number,
            kode_customer, nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat,
            total_item, total_qty, subtotal, discount_persen, discount_amount, total_harga_jual,
            status_pembayaran, tanggal_pembayaran,
            komisi_sales_1_persen, komisi_sales_2_persen, komisi_sales_terbayar, komisi_sales_belum_terbayar,
            status_pembayaran_komisi_sales, tanggal_transfer_komisi_sales,
            komisi_manager_terbayar, komisi_manager_utang, tanggal_transfer_komisi_manager,
            pph_final_terbayar, pph_final_belum_terbayar,
            komisi_admin_terbayar, komisi_admin_belum_terbayar, tanggal_transfer_komisi_admin,
            biaya_kirim, biaya_admin_bank,
            total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, tanggal_transfer_pembelian_barang
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?
        )
    ');

    $stmt->execute([
        $kodeInvoice, $nomorInvoice, $todayStr, 'SJ-TEST-' . $nextNum, $todayStr, 'PO-TEST-' . $nextNum,
        $customer['kode_customer'], $customer['nama_customer'], $customer['nama_laundry'], $customer['no_telepon'], $customer['alamat_default'],
        2, ($qty1 + $qty2), $subtotal, 0, 0, $subtotal,
        'Belum Lunas', null,
        10, 0, 0, $subtotal * 0.1,
        'Belum TF', null,
        0, 0, null,
        0, $subtotal * 0.005,
        0, $subtotal * 0.05, null,
        15000, 0,
        $subtotal * 0.5, $subtotal * 0.5, 'Utang', null
    ]);

    // Insert item 1
    $stmt = $pdo->prepare('
        INSERT INTO invoice_items (
            kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang,
            nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice,
            jumlah, satuan, harga, total, baris
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $kodeInvoice, $nomorInvoice, $todayStr, $customer['kode_customer'], $barangList[0]['kode_barang'],
        $barangList[0]['nama_barang'], $barangList[0]['ukuran'], $barangList[0]['nama_barang'], 'Ukuran ' . $barangList[0]['ukuran'],
        $qty1, $barangList[0]['satuan_default'] ?: 'Pail', $price1, $total1, 23
    ]);

    // Insert item 2
    $stmt->execute([
        $kodeInvoice, $nomorInvoice, $todayStr, $customer['kode_customer'], $barangList[1]['kode_barang'],
        $barangList[1]['nama_barang'], $barangList[1]['ukuran'], $barangList[1]['nama_barang'], 'Ukuran ' . $barangList[1]['ukuran'],
        $qty2, $barangList[1]['satuan_default'] ?: 'Pail', $price2, $total2, 25
    ]);

    $pdo->commit();
    echo "SUCCESS: Saved to database local.\n";

    // Call sync to Google Drive & Sheets
    echo "Syncing to Google Drive & Sheets...\n";
    $syncResult = sync_invoice_to_google($kodeInvoice, false);
    print_r($syncResult);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
