<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$generatedDir = $baseDir . '/storage/generated';

if (!is_dir($generatedDir)) {
    mkdir($generatedDir, 0775, true);
    echo "Created directory storage/generated\n";
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Reconstructing master CSVs...\n";

// 1. Reconstruct master-barang.csv
$barangRows = $pdo->query("SELECT kode_barang, nama_barang, ukuran, isi_default, satuan_default, harga_default, jumlah_alias, jumlah_transaksi, jumlah_invoice, alias FROM master_barang ORDER BY kode_barang")->fetchAll();
$handle = fopen($generatedDir . '/master-barang.csv', 'w');
fputcsv($handle, ['kode_barang', 'nama_barang', 'ukuran', 'isi_default', 'satuan_default', 'harga_default', 'jumlah_alias', 'jumlah_transaksi', 'jumlah_invoice', 'alias']);
foreach ($barangRows as $r) {
    fputcsv($handle, $r);
}
fclose($handle);
echo " - Reconstructed master-barang.csv: " . count($barangRows) . " rows\n";

// 2. Reconstruct master-customer.csv
$customerRows = $pdo->query("SELECT kode_customer, nama_customer, nama_laundry, no_telepon, alamat_default, jumlah_alias, jumlah_invoice, alias, alamat_lain FROM master_customers ORDER BY kode_customer")->fetchAll();
$handle = fopen($generatedDir . '/master-customer.csv', 'w');
fputcsv($handle, ['kode_customer', 'nama_customer', 'nama_laundry', 'no_telepon', 'alamat_default', 'jumlah_alias', 'jumlah_invoice', 'alias', 'alamat_lain']);
foreach ($customerRows as $r) {
    fputcsv($handle, $r);
}
fclose($handle);
echo " - Reconstructed master-customer.csv: " . count($customerRows) . " rows\n";

// 3. Reconstruct master-customer-alias.csv & master-barang-alias.csv & cache files from existing invoices/items
echo "Querying invoices and items from DB...\n";
$invoices = $pdo->query("SELECT * FROM invoices ORDER BY id ASC")->fetchAll();
$invoiceItems = $pdo->query("SELECT * FROM invoice_items ORDER BY id ASC")->fetchAll();

$itemsByInvoice = [];
foreach ($invoiceItems as $item) {
    $itemsByInvoice[$item['nomor_invoice']][] = $item;
}

$custAliasHandle = fopen($generatedDir . '/master-customer-alias.csv', 'w');
fputcsv($custAliasHandle, ['kode_customer', 'nama_master', 'nama_customer', 'nama_di_invoice', 'no_telepon', 'alamat', 'nomor_invoice', 'tanggal_invoice', 'file_invoice']);

$brgAliasHandle = fopen($generatedDir . '/master-barang-alias.csv', 'w');
fputcsv($brgAliasHandle, ['kode_barang', 'nama_master', 'ukuran_master', 'nama_di_invoice', 'isi', 'satuan', 'harga', 'file_invoice', 'baris']);

$cache2025 = [
    'processed' => [],
    'items' => [],
    'customers' => [],
    'failed' => [],
];

$cache2026 = [
    'processed' => [],
    'items' => [],
    'customers' => [],
    'failed' => [],
];

foreach ($invoices as $inv) {
    $fileInvoice = (string) $inv['file_invoice'];
    if ($fileInvoice === '') {
        continue;
    }

    $sourceFileId = md5($baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . str_replace('/', '_', $fileInvoice));
    
    // Choose cache based on invoice year
    $is2025 = str_contains((string) $inv['nomor_invoice'], '/2025');
    if ($is2025) {
        $cache = &$cache2025;
    } else {
        $cache = &$cache2026;
    }

    // Add to processed
    $cache['processed'][$sourceFileId] = true;

    // Write customer alias
    fputcsv($custAliasHandle, [
        $inv['kode_customer'],
        $inv['nama_customer_master'],
        $inv['nama_customer_invoice'], // contact or raw customer name
        $inv['nama_customer_invoice'], // raw name on invoice
        $inv['no_telepon'],
        $inv['alamat'],
        $inv['nomor_invoice'],
        $inv['tanggal_invoice'],
        $fileInvoice
    ]);

    // Add to cache customers list
    $cache['customers'][] = [
        'source_file' => $fileInvoice,
        'source_file_id' => $sourceFileId,
        'raw_name' => $inv['nama_customer_invoice'],
        'normalized_name' => customer_name_key_reconstruct($inv['nama_customer_invoice']),
        'laundry_name' => $inv['nama_laundry_invoice'],
        'contact_name' => $inv['nama_customer_invoice'],
        'phone' => $inv['no_telepon'],
        'address' => $inv['alamat'],
        'invoice_number' => $inv['nomor_invoice'],
        'invoice_date' => $inv['tanggal_invoice'],
    ];

    // Add items for this invoice
    $items = $itemsByInvoice[$inv['nomor_invoice']] ?? [];
    foreach ($items as $item) {
        // Write barang alias
        fputcsv($brgAliasHandle, [
            $item['kode_barang'],
            $item['nama_barang_master'],
            $item['ukuran_master'],
            $item['nama_barang_invoice'],
            $item['isi_invoice'],
            $item['satuan'],
            (float)$item['harga'],
            $fileInvoice,
            (int)$item['baris']
        ]);

        // Add to cache items list
        $cache['items'][] = [
            'source_file' => $fileInvoice,
            'source_file_id' => $sourceFileId,
            'row' => (int)$item['baris'],
            'raw_name' => $item['nama_barang_invoice'],
            'normalized_name' => item_name_key_reconstruct($item['nama_barang_invoice']),
            'isi' => $item['isi_invoice'],
            'jumlah' => (float)$item['jumlah'],
            'satuan' => $item['satuan'],
            'variant_size' => $item['ukuran_master'],
            'harga' => (float)$item['harga'],
            'total' => (float)$item['total'],
        ];
    }
}

fclose($custAliasHandle);
fclose($brgAliasHandle);
echo " - Reconstructed master-customer-alias.csv\n";
echo " - Reconstructed master-barang-alias.csv\n";

// Write JSON caches
file_put_contents($generatedDir . '/invoice-extract-cache-BM-INV-2025-dedup.json', json_encode($cache2025, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
file_put_contents($generatedDir . '/invoice-extract-cache-BM-INV-I-II-III-IV-V-VI-2026.json', json_encode($cache2026, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo " - Reconstructed invoice-extract-cache-BM-INV-2025-dedup.json: " . count($cache2025['customers']) . " invoices\n";
echo " - Reconstructed invoice-extract-cache-BM-INV-I-II-III-IV-V-VI-2026.json: " . count($cache2026['customers']) . " invoices\n";

// Write canonical invoices and items CSVs (for seeder)
$invHandle = fopen($generatedDir . '/invoices-2025-jan-jun-2026.csv', 'w');
fputcsv($invHandle, ['kode_invoice', 'nomor_invoice', 'tanggal_invoice', 'kode_customer', 'nama_customer_master', 'nama_customer_invoice', 'nama_laundry_invoice', 'no_telepon', 'alamat', 'total_item', 'total_qty', 'subtotal', 'file_invoice']);
foreach ($invoices as $inv) {
    fputcsv($invHandle, [
        $inv['kode_invoice'],
        $inv['nomor_invoice'],
        $inv['tanggal_invoice'],
        $inv['kode_customer'],
        $inv['nama_customer_master'],
        $inv['nama_customer_invoice'],
        $inv['nama_laundry_invoice'],
        $inv['no_telepon'],
        $inv['alamat'],
        (int)$inv['total_item'],
        (float)$inv['total_qty'],
        (float)$inv['subtotal'],
        $inv['file_invoice'],
    ]);
}
fclose($invHandle);

$itemHandle = fopen($generatedDir . '/invoice-items-2025-jan-jun-2026.csv', 'w');
fputcsv($itemHandle, ['kode_invoice', 'nomor_invoice', 'tanggal_invoice', 'kode_customer', 'kode_barang', 'nama_barang_master', 'ukuran_master', 'nama_barang_invoice', 'isi_invoice', 'jumlah', 'satuan', 'harga', 'total', 'file_invoice', 'baris']);
foreach ($invoiceItems as $item) {
    fputcsv($itemHandle, [
        $item['kode_invoice'],
        $item['nomor_invoice'],
        $item['tanggal_invoice'],
        $item['kode_customer'],
        $item['kode_barang'],
        $item['nama_barang_master'],
        $item['ukuran_master'],
        $item['nama_barang_invoice'],
        $item['isi_invoice'],
        (float)$item['jumlah'],
        $item['satuan'],
        (float)$item['harga'],
        (float)$item['total'],
        $item['file_invoice'],
        (int)$item['baris'],
    ]);
}
fclose($itemHandle);
echo " - Reconstructed invoices-2025-jan-jun-2026.csv\n";
echo " - Reconstructed invoice-items-2025-jan-jun-2026.csv\n";

echo "\nGenerated folder reconstructed successfully!\n";

function customer_name_key_reconstruct(string $name): string
{
    $name = strtoupper($name);
    $name = preg_replace('/\b(BAPAK|BPK|PAK|IBU|BU|MR|MRS|MS)\b\.?/i', ' ', $name) ?? $name;
    $name = preg_replace('/[^A-Z0-9]+/', ' ', $name) ?? $name;

    $replacements = [
        'LONDRY' => 'LAUNDRY',
        'LAUNDRI' => 'LAUNDRY',
        'LUNDRY' => 'LAUNDRY',
        'LDRY' => 'LAUNDRY',
        'VILLA' => 'VILLAS',
        'HOTELS' => 'HOTEL',
        'CLEAN' => 'CLEANING',
    ];

    $words = explode(' ', trim(preg_replace('/\s+/', ' ', $name)));
    $normalizedWords = [];
    foreach ($words as $word) {
        $normalizedWords[] = $replacements[$word] ?? $word;
    }

    return trim(preg_replace('/\s+/', ' ', implode(' ', $normalizedWords)));
}

function item_name_key_reconstruct(string $name): string
{
    $name = strtoupper($name);
    $name = str_replace(['&', '+'], ' DAN ', $name);
    $name = preg_replace('/[^A-Z0-9]+/', ' ', $name) ?? $name;

    $replacements = [
        'ALKALINE' => 'ALKALIN',
        'DETERJEN' => 'DETERGENT',
        'DETTERGENT' => 'DETERGENT',
        'SOFTERGENT' => 'SOFT DETERGENT',
        'LIQUIDE' => 'LIQUID',
        'LQUID' => 'LIQUID',
        'LIQ' => 'LIQUID',
        'SOFTENER' => 'SOFTNER',
        'SOFTNER' => 'SOFTNER',
        'BLEACHING' => 'BLEACH',
        'BLACH' => 'BLEACH',
        'OXY' => 'OXO',
        'MCB' => 'MC BLEACH',
        'PAIN' => 'PINE',
        'PARFUME' => 'PARFUM',
        'PERFUME' => 'PARFUM',
        'LITER' => 'L',
        'LTR' => 'L',
    ];

    $words = explode(' ', trim(preg_replace('/\s+/', ' ', $name)));
    $normalizedWords = [];

    foreach ($words as $word) {
        if ($word === 'COKRO') {
            continue;
        }

        $normalizedWords[] = $replacements[$word] ?? $word;
    }

    $normalizedName = trim(preg_replace('/\s+/', ' ', implode(' ', $normalizedWords)));
    $normalizedName = preg_replace('/\bMC BLEACH LIQUID\b/', 'MC BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bRUST GONE KARAT\b/', 'ANTI KARAT', $normalizedName);
    $normalizedName = preg_replace('/\bRUST GONE\b/', 'ANTI KARAT', $normalizedName);
    $normalizedName = preg_replace('/\bOMAXX OXO\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bOXO BLEACH OMAXX\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bOXO OMAXX\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\b0X0\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bOXO\b(?!\s+BLEACH)/', 'OXO BLEACH', $normalizedName);

    return trim(preg_replace('/\s+/', ' ', $normalizedName));
}
