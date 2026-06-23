<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "=== START INVOICE SYNC FLOW TEST ===\n";

$pdo = db();
if ($pdo === null) {
    echo "ERROR: DB connection failed.\n";
    exit(1);
}

// Fetch a real customer and barang code
$kodeCustomer = $pdo->query("SELECT kode_customer FROM master_customers LIMIT 1")->fetchColumn();
$kodeBarang = $pdo->query("SELECT kode_barang FROM master_barang LIMIT 1")->fetchColumn();

if (!$kodeCustomer || !$kodeBarang) {
    echo "ERROR: master_customers or master_barang is empty.\n";
    exit(1);
}

echo "Using Customer Code: $kodeCustomer\n";
echo "Using Barang Code: $kodeBarang\n";

// 1. Create a mock invoice in the database
echo "1. Creating mock invoice...\n";
$kodeInvoice = 'INV-99999';
$nomorInvoice = '999/BM-INV/TEST/2026';
$namaLaundry = 'TEST LAUNDRY AGENT';

// Clean up first if exists
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
    $kodeInvoice, $nomorInvoice, '23 Juni 2026', 'SJ-99999', '23 Juni 2026', 'PO-99999',
    $kodeCustomer, 'Test Client', $namaLaundry, '08123456789', 'Jl. Test No. 99',
    1, 10, 1000000, 10, 100000, 900000,
    'Belum Lunas', null,
    10, 0, 0, 90000,
    'Belum TF', null,
    0, 0, null,
    0, 4500,
    0, 45000, null,
    15000, 2500,
    500000, 500000, 'Utang', null
]);

// Insert item
$stmt = $pdo->prepare('
    INSERT INTO invoice_items (
        kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang,
        nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice,
        jumlah, satuan, harga, total, baris
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');
$stmt->execute([
    $kodeInvoice, $nomorInvoice, '23 Juni 2026', $kodeCustomer, $kodeBarang,
    'Barang Test', '20 Liter', 'Barang Test', 'Isi Test',
    10, 'Pail', 100000, 1000000, 23
]);

echo "Mock invoice created in database successfully.\n";

// 2. Call sync_invoice_to_google (insert mode)
echo "2. Calling sync_invoice_to_google (isUpdate = false)...\n";
$syncResult = sync_invoice_to_google($kodeInvoice, false);
echo "Sync Result: " . json_encode($syncResult, JSON_PRETTY_PRINT) . "\n";

// 3. Verify file ID updated in database
$fileIdInDb = $pdo->query("SELECT google_drive_file_id FROM invoices WHERE kode_invoice = '$kodeInvoice'")->fetchColumn();
echo "File ID in DB: " . ($fileIdInDb ?: 'NULL') . "\n";

// 4. Update some invoice values and call sync again (update mode)
if ($fileIdInDb) {
    echo "3. Updating invoice in database...\n";
    $pdo->query("UPDATE invoices SET subtotal = 1200000, total_harga_jual = 1080000 WHERE kode_invoice = '$kodeInvoice'");
    echo "Calling sync_invoice_to_google (isUpdate = true)...\n";
    $syncResultUpdate = sync_invoice_to_google($kodeInvoice, true);
    echo "Sync Update Result: " . json_encode($syncResultUpdate, JSON_PRETTY_PRINT) . "\n";
}

// 5. Delete invoice
echo "4. Deleting invoice...\n";
$deleteResult = delete_invoice($kodeInvoice);
echo "Delete Result: " . json_encode($deleteResult, JSON_PRETTY_PRINT) . "\n";

echo "=== END INVOICE SYNC FLOW TEST ===\n";
