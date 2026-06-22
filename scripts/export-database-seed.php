<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$outputPath = $baseDir . '/database/seed-data.sql';

$pdo = connect_database($config);
$tables = [
    'master_barang' => [
        'kode_barang',
        'nama_barang',
        'ukuran',
        'isi_default',
        'satuan_default',
        'harga_default',
        'jumlah_alias',
        'jumlah_transaksi',
        'jumlah_invoice',
        'alias',
    ],
    'master_customers' => [
        'kode_customer',
        'nama_customer',
        'nama_laundry',
        'no_telepon',
        'alamat_default',
        'jumlah_alias',
        'jumlah_invoice',
        'alias',
        'alamat_lain',
    ],
    'master_sales' => [
        'kode_sales',
        'nama_sales',
    ],
    'invoices' => [
        'kode_invoice',
        'nomor_invoice',
        'tanggal_invoice',
        'nomor_surat_jalan',
        'tanggal_surat_jalan',
        'po_number',
        'kode_sales_1',
        'nama_sales_1',
        'kode_sales_2',
        'nama_sales_2',
        'komisi_sales_1_persen',
        'komisi_sales_2_persen',
        'komisi_sales_terbayar',
        'komisi_sales_belum_terbayar',
        'status_pembayaran_komisi_sales',
        'tanggal_transfer_komisi_sales',
        'kode_customer',
        'nama_customer_master',
        'nama_customer_invoice',
        'nama_laundry_invoice',
        'no_telepon',
        'alamat',
        'total_item',
        'total_qty',
        'subtotal',
        'harga_normal_pricelist',
        'discount_persen',
        'discount_amount',
        'total_harga_jual',
        'status_pembayaran',
        'tanggal_pembayaran',
        'komisi_manager_terbayar',
        'komisi_manager_utang',
        'tanggal_transfer_komisi_manager',
        'tanggal_transfer_komisi_admin',
        'pph_final_terbayar',
        'pph_final_belum_terbayar',
        'komisi_admin_terbayar',
        'komisi_admin_belum_terbayar',
        'biaya_kirim',
        'biaya_admin_bank',
        'total_pembelian_barang',
        'total_utang_pembelian_barang',
        'status_pembelian_barang',
        'tanggal_transfer_pembelian_barang',
        'file_invoice',
    ],
    'invoice_items' => [
        'kode_invoice',
        'nomor_invoice',
        'tanggal_invoice',
        'kode_customer',
        'kode_barang',
        'nama_barang_master',
        'ukuran_master',
        'nama_barang_invoice',
        'isi_invoice',
        'jumlah',
        'satuan',
        'harga',
        'total',
        'file_invoice',
        'baris',
    ],
];

$sql = [];
$sql[] = '-- Busamas seed snapshot';
$sql[] = '-- Generated at ' . date('Y-m-d H:i:s');
$sql[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$sql[] = 'TRUNCATE TABLE `invoice_items`;';
$sql[] = 'TRUNCATE TABLE `invoices`;';
$sql[] = 'TRUNCATE TABLE `master_sales`;';
$sql[] = 'TRUNCATE TABLE `master_barang`;';
$sql[] = 'TRUNCATE TABLE `master_customers`;';
$sql[] = 'SET FOREIGN_KEY_CHECKS = 1;';

$counts = [];

foreach ($tables as $table => $columns) {
    $rows = fetch_table_rows($pdo, $table, $columns);
    $counts[$table] = count($rows);

    if ($rows === []) {
        continue;
    }

    $columnSql = implode(', ', array_map(static fn (string $column): string => quote_identifier($column), $columns));
    $sql[] = '';
    $sql[] = '-- ' . $table . ': ' . count($rows) . ' rows';

    foreach (array_chunk($rows, 100) as $chunk) {
        $values = [];

        foreach ($chunk as $row) {
            $values[] = '(' . implode(', ', array_map(
                static fn (string $column): string => quote_value($pdo, $row[$column] ?? null),
                $columns
            )) . ')';
        }

        $sql[] = 'INSERT INTO ' . quote_identifier($table) . ' (' . $columnSql . ') VALUES';
        $sql[] = implode(',' . PHP_EOL, $values) . ';';
    }
}

file_put_contents($outputPath, implode(PHP_EOL, $sql) . PHP_EOL);

echo 'Seed SQL dibuat: database/seed-data.sql' . PHP_EOL;
foreach ($counts as $table => $count) {
    echo $table . ': ' . $count . PHP_EOL;
}

function connect_database(array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function fetch_table_rows(PDO $pdo, string $table, array $columns): array
{
    $columnSql = implode(', ', array_map(static fn (string $column): string => quote_identifier($column), $columns));
    $orderColumn = match ($table) {
        'master_barang' => 'kode_barang',
        'master_customers' => 'kode_customer',
        'master_sales' => 'kode_sales',
        'invoices' => 'kode_invoice',
        'invoice_items' => 'kode_invoice, baris',
        default => 'id',
    };

    return $pdo->query('SELECT ' . $columnSql . ' FROM ' . quote_identifier($table) . ' ORDER BY ' . $orderColumn)->fetchAll();
}

function quote_identifier(string $value): string
{
    return '`' . str_replace('`', '``', $value) . '`';
}

function quote_value(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return $pdo->quote((string) $value);
}
