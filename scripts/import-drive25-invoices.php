<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/helpers.php';

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    run_drive25_invoice_import_cli($baseDir);
}

function run_drive25_invoice_import_cli(string $baseDir): void
{
    $driveDir = $baseDir . '/storage/drive25';
    $excelPath = $baseDir . '/storage/PENJUALAN-2026.xlsx';

    $pdo = db();
    if ($pdo === null) {
        fwrite(STDERR, 'Koneksi database gagal.' . PHP_EOL);
        exit(1);
    }

    if (! is_dir($driveDir)) {
        fwrite(STDERR, 'Folder tidak ditemukan: storage/drive25' . PHP_EOL);
        exit(1);
    }

    if (! is_readable($excelPath)) {
        fwrite(STDERR, 'File tidak ditemukan: storage/PENJUALAN-2026.xlsx' . PHP_EOL);
        exit(1);
    }

    try {
        $result = import_drive25_invoices($pdo, $driveDir, $excelPath);
        $pdo->beginTransaction();
        $journal = regenerate_all_accounting_journals($pdo);
        $pdo->commit();

        echo 'Invoice diproses: ' . $result['processed'] . PHP_EOL;
        echo 'Invoice dibuat: ' . $result['created'] . PHP_EOL;
        echo 'Invoice diupdate: ' . $result['updated'] . PHP_EOL;
        echo 'Item dibuat ulang: ' . $result['items'] . PHP_EOL;
        echo 'Jurnal akuntansi: ' . $journal['entries'] . ' jurnal, ' . $journal['lines'] . ' baris' . PHP_EOL;

        if ($result['warnings'] !== []) {
            echo 'Peringatan:' . PHP_EOL;
            foreach ($result['warnings'] as $warning) {
                echo '- ' . $warning . PHP_EOL;
            }
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, 'Import gagal: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}

function import_drive25_invoices(PDO $pdo, string $driveDir, string $excelPath, int $minInvoiceNumber = 453, int $maxInvoiceNumber = 462): array
{
    $excelRows = sales_rows_by_invoice($excelPath);
    $files = glob($driveDir . '/*.xlsx') ?: [];
    usort($files, static fn (string $a, string $b): int => invoice_number_from_drive_file($a) <=> invoice_number_from_drive_file($b));

    $customerMap = customer_map_for_import($pdo);
    $barangRows = $pdo->query('SELECT kode_barang, nama_barang, ukuran, isi_default, satuan_default FROM master_barang')->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $invoiceInsert = $pdo->prepare(invoice_insert_sql());
    $invoiceUpdate = $pdo->prepare(invoice_update_sql());
    $itemInsert = $pdo->prepare('
        INSERT INTO invoice_items (
            kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang,
            nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice,
            jumlah, satuan, harga, total, file_invoice, baris
        ) VALUES (
            :kode_invoice, :nomor_invoice, :tanggal_invoice, :kode_customer, :kode_barang,
            :nama_barang_master, :ukuran_master, :nama_barang_invoice, :isi_invoice,
            :jumlah, :satuan, :harga, :total, :file_invoice, :baris
        )
    ');

    $created = 0;
    $updated = 0;
    $itemCount = 0;
    $processed = 0;
    $warnings = [];

    $startedTransaction = ! $pdo->inTransaction();
    if ($startedTransaction) {
        $pdo->beginTransaction();
    }

    foreach ($files as $file) {
        $number = invoice_number_from_drive_file($file);
        if ($number < $minInvoiceNumber || $number > $maxInvoiceNumber) {
            continue;
        }

        cleanup_blank_invoice_for_file($pdo, basename($file));

        $invoice = parse_invoice_file_for_import($file);
        $nomorInvoice = $invoice['nomor_invoice'];
        $excel = $excelRows[invoice_number_key_2025($nomorInvoice)] ?? [];
        if ($excel === []) {
            $warnings[] = 'Tidak ada baris Excel untuk ' . $nomorInvoice;
        }

        $customer = resolve_customer_for_import($customerMap, (string) ($excel['E'] ?? $invoice['laundry']));
        if ($customer === null) {
            $warnings[] = 'Customer tidak ditemukan: ' . ($excel['E'] ?? $invoice['laundry']) . ' (' . $nomorInvoice . ')';
        }

        $existing = fetch_invoice_by_number($pdo, $nomorInvoice);
        $kodeInvoice = $existing['kode_invoice'] ?? next_invoice_code_for_import($pdo);
        $tanggalInvoice = $invoice['tanggal_invoice'] !== '' ? $invoice['tanggal_invoice'] : indonesian_date_from_excel($excel['B'] ?? '');

        $purchaseDebt = (float) parse_number_internal($excel['AH'] ?? 0);
        $purchaseTransferDate = excel_date_2025($excel['AI'] ?? '');
        $totalItem = count($invoice['items']);
        $totalQty = array_sum(array_map(static fn (array $item): float => (float) $item['jumlah'], $invoice['items']));
        $subtotal = (float) parse_number_internal($excel['H'] ?? $invoice['subtotal']);
        $totalHargaJual = (float) parse_number_internal($excel['K'] ?? $subtotal);
        $salesMap = $GLOBALS['sales_map_import'] ??= fetch_sales_map_2025($pdo);
        $sales1 = resolve_sales_2025($pdo, $salesMap, (string) ($excel['F'] ?? ''));
        $sales2 = resolve_sales_2025($pdo, $salesMap, (string) ($excel['G'] ?? ''));
        $GLOBALS['sales_map_import'] = $salesMap;

        $payload = [
            'kode_invoice' => $kodeInvoice,
            'nomor_invoice' => $nomorInvoice,
            'tanggal_invoice' => $tanggalInvoice,
            'nomor_surat_jalan' => '',
            'tanggal_surat_jalan' => '',
            'po_number' => $invoice['po_number'],
            'kode_sales_1' => $sales1['kode_sales'],
            'nama_sales_1' => $sales1['nama_sales'],
            'kode_sales_2' => $sales2['kode_sales'],
            'nama_sales_2' => $sales2['nama_sales'],
            'komisi_sales_1_persen' => parse_percent_2025($excel['P'] ?? 0),
            'komisi_sales_2_persen' => parse_percent_2025($excel['Q'] ?? 0),
            'komisi_sales_terbayar' => (float) parse_number_internal($excel['S'] ?? 0),
            'komisi_sales_belum_terbayar' => (float) parse_number_internal($excel['T'] ?? 0),
            'status_pembayaran_komisi_sales' => normalize_spaces((string) ($excel['U'] ?? '')) ?: null,
            'tanggal_transfer_komisi_sales' => excel_date_2025($excel['V'] ?? ''),
            'komisi_manager_terbayar' => (float) parse_number_internal($excel['W'] ?? 0),
            'komisi_manager_utang' => (float) parse_number_internal($excel['X'] ?? 0),
            'tanggal_transfer_komisi_manager' => excel_date_2025($excel['Y'] ?? ''),
            'tanggal_transfer_komisi_admin' => excel_date_2025($excel['AD'] ?? ''),
            'kode_customer' => $customer['kode_customer'] ?? null,
            'nama_customer_master' => $customer['nama_customer'] ?? '',
            'nama_customer_invoice' => $invoice['customer_name'],
            'nama_laundry_invoice' => $customer['nama_laundry'] ?? ($excel['E'] ?? $invoice['laundry']),
            'no_telepon' => $customer['no_telepon'] ?? $invoice['phone'],
            'alamat' => $customer['alamat_default'] ?? $invoice['address'],
            'total_item' => $totalItem,
            'total_qty' => $totalQty,
            'subtotal' => $subtotal,
            'harga_normal_pricelist' => (float) parse_number_internal($excel['H'] ?? $subtotal),
            'discount_persen' => parse_percent_2025($excel['I'] ?? 0),
            'discount_amount' => (float) parse_number_internal($excel['J'] ?? 0),
            'total_harga_jual' => $totalHargaJual,
            'status_pembayaran' => normalize_payment_status_2025((string) ($excel['L'] ?? '')),
            'tanggal_pembayaran' => excel_date_2025($excel['M'] ?? ''),
            'pph_final_terbayar' => (float) parse_number_internal($excel['Z'] ?? 0),
            'pph_final_belum_terbayar' => (float) parse_number_internal($excel['AA'] ?? 0),
            'komisi_admin_terbayar' => (float) parse_number_internal($excel['AB'] ?? 0),
            'komisi_admin_belum_terbayar' => (float) parse_number_internal($excel['AC'] ?? 0),
            'biaya_kirim' => (float) parse_number_internal($excel['AE'] ?? 0),
            'biaya_admin_bank' => (float) parse_number_internal($excel['AF'] ?? 0),
            'total_pembelian_barang' => (float) parse_number_internal($excel['AG'] ?? 0),
            'total_utang_pembelian_barang' => $purchaseDebt,
            'status_pembelian_barang' => $purchaseTransferDate !== null ? 'Lunas' : ($purchaseDebt > 0 ? 'Utang' : 'Lunas'),
            'tanggal_transfer_pembelian_barang' => $purchaseTransferDate,
            'file_invoice' => basename($file),
        ];

        if ($existing === null) {
            $invoiceInsert->execute($payload);
            $created++;
        } else {
            $updatePayload = $payload;
            unset($updatePayload['kode_invoice']);
            $invoiceUpdate->execute($updatePayload);
            $updated++;
        }

        $pdo->prepare('DELETE FROM invoice_items WHERE kode_invoice = ?')->execute([$kodeInvoice]);

        foreach ($invoice['items'] as $item) {
            $barang = resolve_barang_for_import($barangRows, $item['nama_barang'], $item['isi']);
            if ($barang === null) {
                $warnings[] = 'Barang tidak ditemukan: ' . $item['nama_barang'] . ' ' . $item['isi'] . ' (' . $nomorInvoice . ')';
            }

            $itemInsert->execute([
                'kode_invoice' => $kodeInvoice,
                'nomor_invoice' => $nomorInvoice,
                'tanggal_invoice' => $tanggalInvoice,
                'kode_customer' => $payload['kode_customer'],
                'kode_barang' => $barang['kode_barang'] ?? null,
                'nama_barang_master' => $barang['nama_barang'] ?? '',
                'ukuran_master' => $barang['ukuran'] ?? '',
                'nama_barang_invoice' => $item['nama_barang'],
                'isi_invoice' => $item['isi'],
                'jumlah' => $item['jumlah'],
                'satuan' => $item['satuan'],
                'harga' => $item['harga'],
                'total' => $item['total'],
                'file_invoice' => basename($file),
                'baris' => $item['baris'],
            ]);
            $itemCount++;
        }

        $processed++;
    }

    if ($startedTransaction) {
        $pdo->commit();
    }

    return [
        'processed' => $processed,
        'created' => $created,
        'updated' => $updated,
        'items' => $itemCount,
        'warnings' => $warnings,
    ];
}

function sales_rows_by_invoice(string $excelPath): array
{
    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');
    $map = [];

    foreach ($rows as $row) {
        $invoice = normalize_spaces((string) ($row['A'] ?? ''));
        if ($invoice !== '') {
            $map[invoice_number_key_2025($invoice)] = $row;
        }
    }

    return $map;
}

function parse_invoice_file_for_import(string $file): array
{
    $rows = read_xlsx_rows_for_invoice_view($file);
    $customerName = invoice_row_value_after_label($rows, 'Kepada');
    $address = invoice_row_value_after_label($rows, 'Alamat');
    $phone = '';
    foreach ($rows as $row) {
        foreach ($row as $value) {
            $text = normalize_spaces((string) $value);
            if (preg_match('/^hp\s*:/i', $text)) {
                $phone = preg_replace('/^hp\s*:\s*/i', '', $text) ?? '';
                break 2;
            }
        }
    }

    $items = [];
    $subtotal = 0.0;
    foreach ($rows as $index => $row) {
        $name = normalize_spaces((string) ($row['B'] ?? ''));
        $total = parse_number_internal($row['G'] ?? '');
        if ($name === '' || strcasecmp($name, 'Nama Barang') === 0 || $total <= 0) {
            continue;
        }

        $items[] = [
            'baris' => $index + 1,
            'nama_barang' => $name,
            'isi' => normalize_spaces((string) ($row['C'] ?? '')),
            'jumlah' => (float) parse_number_internal($row['D'] ?? 0),
            'satuan' => normalize_spaces((string) ($row['E'] ?? '')),
            'harga' => (float) parse_number_internal($row['F'] ?? 0),
            'total' => $total,
        ];
        $subtotal += $total;
    }

    return [
        'nomor_invoice' => invoice_row_value_after_label($rows, 'No. Invoice') ?: invoice_number_from_drive_file_name(basename($file)),
        'tanggal_invoice' => invoice_row_value_after_label($rows, 'Tanggal'),
        'po_number' => invoice_row_value_after_label($rows, 'PO Number'),
        'customer_name' => $customerName,
        'laundry' => $customerName,
        'address' => $address,
        'phone' => $phone,
        'items' => $items,
        'subtotal' => $subtotal,
    ];
}

function invoice_row_value_after_label(array $rows, string $label): string
{
    $columns = range('A', 'H');
    $labelKey = invoice_label_key_for_import($label);

    foreach ($rows as $row) {
        foreach ($columns as $index => $column) {
            if (invoice_label_key_for_import((string) ($row[$column] ?? '')) !== $labelKey) {
                continue;
            }

            for ($next = $index + 1; $next < count($columns); $next++) {
                $value = clean_label_value_for_import((string) ($row[$columns[$next]] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }
    }

    return '';
}

function invoice_label_key_for_import(string $value): string
{
    return preg_replace('/[^A-Z0-9]+/', '', strtoupper(normalize_spaces($value))) ?? '';
}

function customer_map_for_import(PDO $pdo): array
{
    $rows = $pdo->query('SELECT kode_customer, nama_customer, nama_laundry, no_telepon, alamat_default FROM master_customers')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $map = [];

    foreach ($rows as $row) {
        $map[customer_key_for_import((string) $row['nama_laundry'])] = $row;
        $map[customer_key_for_import((string) $row['nama_customer'])] = $row;
    }

    return $map;
}

function resolve_customer_for_import(array $map, string $name): ?array
{
    $key = customer_key_for_import($name);
    $aliases = [
        'AHSLAUNDRY' => 'ASIAHOSPITALITYSERVICE',
        'AHSPESANGGARAN' => 'ASIAHOSPITALITYSERVICE',
        'GOLAUNDRY' => 'GOLAUNDRYBALI',
        'JDMCLEANINGSBY' => 'JDMCLEANINGSBY',
        'JDMCLEANING-SBY' => 'JDMCLEANINGSBY',
        'ERIKLAUNDRY' => 'ERIKLAUNDRY',
        'INDOLAUNDRY' => 'INDOLAUNDRY',
        'ZCLEANLAUNDRY' => 'ZCLEANLAUNDRY',
        'YANTOLAUNDRY' => 'YANTOLAUNDRY',
    ];

    return $map[$key] ?? $map[$aliases[$key] ?? ''] ?? null;
}

function resolve_barang_for_import(array $barangRows, string $name, string $size): ?array
{
    $nameKey = barang_name_key_for_import($name);
    $sizeKey = strtoupper(str_replace(' ', '', $size));
    $aliases = [
        'OXO' => 'OXOBLEACH',
        'MSOUR' => 'MSOUR',
        'M-SOUR' => 'MSOUR',
        'MSOFT' => 'MSOFT',
        'M-SOFT' => 'MSOFT',
    ];
    $nameKey = $aliases[$nameKey] ?? $nameKey;
    $sizeAliases = [
        'MCBLEACH' => [
            '15L' => '5L',
        ],
    ];
    $sizeKey = $sizeAliases[$nameKey][$sizeKey] ?? $sizeKey;

    foreach ($barangRows as $row) {
        $rowName = $aliases[barang_name_key_for_import((string) $row['nama_barang'])] ?? barang_name_key_for_import((string) $row['nama_barang']);
        $rowSize = strtoupper(str_replace(' ', '', (string) $row['ukuran']));
        if ($rowName === $nameKey && $rowSize === $sizeKey) {
            return $row;
        }
    }

    return null;
}

function invoice_insert_sql(): string
{
    return '
        INSERT INTO invoices (
            kode_invoice, nomor_invoice, tanggal_invoice, nomor_surat_jalan, tanggal_surat_jalan, po_number,
            kode_sales_1, nama_sales_1, kode_sales_2, nama_sales_2,
            komisi_sales_1_persen, komisi_sales_2_persen, komisi_sales_terbayar, komisi_sales_belum_terbayar,
            status_pembayaran_komisi_sales, tanggal_transfer_komisi_sales,
            komisi_manager_terbayar, komisi_manager_utang, tanggal_transfer_komisi_manager,
            tanggal_transfer_komisi_admin, kode_customer, nama_customer_master,
            nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat,
            total_item, total_qty, subtotal, harga_normal_pricelist, discount_persen, discount_amount, total_harga_jual,
            status_pembayaran, tanggal_pembayaran,
            pph_final_terbayar, pph_final_belum_terbayar,
            komisi_admin_terbayar, komisi_admin_belum_terbayar,
            biaya_kirim, biaya_admin_bank,
            total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, tanggal_transfer_pembelian_barang, file_invoice
        ) VALUES (
            :kode_invoice, :nomor_invoice, :tanggal_invoice, :nomor_surat_jalan, :tanggal_surat_jalan, :po_number,
            :kode_sales_1, :nama_sales_1, :kode_sales_2, :nama_sales_2,
            :komisi_sales_1_persen, :komisi_sales_2_persen, :komisi_sales_terbayar, :komisi_sales_belum_terbayar,
            :status_pembayaran_komisi_sales, :tanggal_transfer_komisi_sales,
            :komisi_manager_terbayar, :komisi_manager_utang, :tanggal_transfer_komisi_manager,
            :tanggal_transfer_komisi_admin, :kode_customer, :nama_customer_master,
            :nama_customer_invoice, :nama_laundry_invoice, :no_telepon, :alamat,
            :total_item, :total_qty, :subtotal, :harga_normal_pricelist, :discount_persen, :discount_amount, :total_harga_jual,
            :status_pembayaran, :tanggal_pembayaran,
            :pph_final_terbayar, :pph_final_belum_terbayar,
            :komisi_admin_terbayar, :komisi_admin_belum_terbayar,
            :biaya_kirim, :biaya_admin_bank,
            :total_pembelian_barang, :total_utang_pembelian_barang, :status_pembelian_barang, :tanggal_transfer_pembelian_barang, :file_invoice
        )
    ';
}

function invoice_update_sql(): string
{
    return '
        UPDATE invoices SET
            tanggal_invoice = :tanggal_invoice,
            nomor_surat_jalan = :nomor_surat_jalan,
            tanggal_surat_jalan = :tanggal_surat_jalan,
            po_number = :po_number,
            kode_sales_1 = :kode_sales_1,
            nama_sales_1 = :nama_sales_1,
            kode_sales_2 = :kode_sales_2,
            nama_sales_2 = :nama_sales_2,
            komisi_sales_1_persen = :komisi_sales_1_persen,
            komisi_sales_2_persen = :komisi_sales_2_persen,
            komisi_sales_terbayar = :komisi_sales_terbayar,
            komisi_sales_belum_terbayar = :komisi_sales_belum_terbayar,
            status_pembayaran_komisi_sales = :status_pembayaran_komisi_sales,
            tanggal_transfer_komisi_sales = :tanggal_transfer_komisi_sales,
            komisi_manager_terbayar = :komisi_manager_terbayar,
            komisi_manager_utang = :komisi_manager_utang,
            tanggal_transfer_komisi_manager = :tanggal_transfer_komisi_manager,
            tanggal_transfer_komisi_admin = :tanggal_transfer_komisi_admin,
            kode_customer = :kode_customer,
            nama_customer_master = :nama_customer_master,
            nama_customer_invoice = :nama_customer_invoice,
            nama_laundry_invoice = :nama_laundry_invoice,
            no_telepon = :no_telepon,
            alamat = :alamat,
            total_item = :total_item,
            total_qty = :total_qty,
            subtotal = :subtotal,
            harga_normal_pricelist = :harga_normal_pricelist,
            discount_persen = :discount_persen,
            discount_amount = :discount_amount,
            total_harga_jual = :total_harga_jual,
            status_pembayaran = :status_pembayaran,
            tanggal_pembayaran = :tanggal_pembayaran,
            pph_final_terbayar = :pph_final_terbayar,
            pph_final_belum_terbayar = :pph_final_belum_terbayar,
            komisi_admin_terbayar = :komisi_admin_terbayar,
            komisi_admin_belum_terbayar = :komisi_admin_belum_terbayar,
            biaya_kirim = :biaya_kirim,
            biaya_admin_bank = :biaya_admin_bank,
            total_pembelian_barang = :total_pembelian_barang,
            total_utang_pembelian_barang = :total_utang_pembelian_barang,
            status_pembelian_barang = :status_pembelian_barang,
            tanggal_transfer_pembelian_barang = :tanggal_transfer_pembelian_barang,
            file_invoice = :file_invoice
        WHERE nomor_invoice = :nomor_invoice
    ';
}

function fetch_invoice_by_number(PDO $pdo, string $nomorInvoice): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE nomor_invoice = ? LIMIT 1');
    $stmt->execute([$nomorInvoice]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function cleanup_blank_invoice_for_file(PDO $pdo, string $fileName): void
{
    $stmt = $pdo->prepare('SELECT kode_invoice FROM invoices WHERE (nomor_invoice IS NULL OR nomor_invoice = \'\') AND file_invoice = ?');
    $stmt->execute([$fileName]);
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($codes as $code) {
        $pdo->prepare('DELETE FROM invoice_items WHERE kode_invoice = ?')->execute([$code]);
        $pdo->prepare('DELETE FROM invoices WHERE kode_invoice = ?')->execute([$code]);
    }
}

function next_invoice_code_for_import(PDO $pdo): string
{
    $codes = $pdo->query("SELECT kode_invoice FROM invoices WHERE kode_invoice LIKE 'INV-%'")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $max = 0;
    foreach ($codes as $code) {
        if (preg_match('/^INV-(\d+)$/', (string) $code, $match)) {
            $max = max($max, (int) $match[1]);
        }
    }

    return 'INV-' . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
}

function invoice_number_from_drive_file(string $file): int
{
    preg_match('/^(\d+)_BM-INV/i', basename($file), $match);

    return (int) ($match[1] ?? 0);
}

function invoice_number_from_drive_file_name(string $file): string
{
    if (preg_match('/^(\d+)_BM-INV_([IVXLCDM]+)_(\d{4})/i', $file, $match)) {
        return (int) $match[1] . '/BM-INV/' . strtoupper($match[2]) . '/' . $match[3];
    }

    return '';
}

function clean_label_value_for_import(string $value): string
{
    return normalize_spaces(preg_replace('/^\s*:\s*/', '', $value) ?? $value);
}

function indonesian_date_from_excel(mixed $value): string
{
    $date = excel_date_2025($value);
    if ($date === null) {
        return '';
    }

    $months = invoice_months();
    $timestamp = strtotime($date);

    return (int) date('j', $timestamp) . ' ' . ($months[(int) date('n', $timestamp)] ?? '') . ' ' . date('Y', $timestamp);
}

function customer_key_for_import(string $name): string
{
    return preg_replace('/[^A-Z0-9]+/', '', strtoupper($name)) ?? '';
}

function barang_name_key_for_import(string $name): string
{
    return preg_replace('/[^A-Z0-9]+/', '', strtoupper($name)) ?? '';
}
