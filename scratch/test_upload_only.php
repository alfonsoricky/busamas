<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "=== START INVOICE UPLOAD ONLY TEST ===\n";

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

$kodeInvoice = 'INV-TEST-004';
$nomorInvoice = '999/BM-INV/TEST-STYLE/2026';
$namaLaundry = 'ZCLEAN LAUNDRY DYNAMIC ENV';

// Clean up first if exists in DB
$pdo->prepare("DELETE FROM invoice_items WHERE kode_invoice = ?")->execute([$kodeInvoice]);
$pdo->prepare("DELETE FROM invoices WHERE kode_invoice = ?")->execute([$kodeInvoice]);

echo "Creating invoice $nomorInvoice in DB...\n";
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
    $kodeInvoice, $nomorInvoice, '23 Juni 2026', 'SJ-77777', '23 Juni 2026', 'PO-77777',
    $kodeCustomer, 'Test Client', $namaLaundry, '08987654321', 'Jl. Sukses Selalu No. 77',
    1, 5, 500000, 0, 0, 500000,
    'Belum Lunas', null,
    10, 0, 0, 50000,
    'Belum TF', null,
    0, 0, null,
    0, 2500,
    0, 25000, null,
    10000, 0,
    250000, 250000, 'Utang', null
]);

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
    5, 'Pail', 100000, 500000, 23
]);

echo "DB record created. Now calling sync_invoice_to_google...\n";
$syncResult = sync_invoice_to_google($kodeInvoice, false);

echo "Sync Result:\n" . json_encode($syncResult, JSON_PRETTY_PRINT) . "\n";
echo "=== END INVOICE UPLOAD ONLY TEST ===\n";
