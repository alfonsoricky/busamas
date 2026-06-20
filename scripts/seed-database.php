<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$generatedDir = $baseDir . '/storage/generated';

$pdo = connect_server($config);
$pdo->exec((string) file_get_contents($baseDir . '/database/schema.sql'));
$pdo = connect_database($config);

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE invoice_items');
    $pdo->exec('TRUNCATE TABLE invoices');
    $pdo->exec('TRUNCATE TABLE master_barang');
    $pdo->exec('TRUNCATE TABLE master_customers');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $pdo->beginTransaction();
    $counts = [
        'master_barang' => seed_csv($pdo, $generatedDir . '/master-barang.csv', 'master_barang', [
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
        ]),
        'master_customers' => seed_csv($pdo, $generatedDir . '/master-customer.csv', 'master_customers', [
            'kode_customer',
            'nama_customer',
            'nama_laundry',
            'no_telepon',
            'alamat_default',
            'jumlah_alias',
            'jumlah_invoice',
            'alias',
            'alamat_lain',
        ]),
        'invoices' => seed_csv($pdo, $generatedDir . '/invoices-2025-jan-jun-2026.csv', 'invoices', [
            'kode_invoice',
            'nomor_invoice',
            'tanggal_invoice',
            'kode_customer',
            'nama_customer_master',
            'nama_customer_invoice',
            'nama_laundry_invoice',
            'no_telepon',
            'alamat',
            'total_item',
            'total_qty',
            'subtotal',
            'file_invoice',
        ]),
        'invoice_items' => seed_csv($pdo, $generatedDir . '/invoice-items-2025-jan-jun-2026.csv', 'invoice_items', [
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
        ]),
    ];

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Seeder gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Database: ' . $config['database'] . PHP_EOL;
foreach ($counts as $table => $count) {
    echo $table . ': ' . $count . PHP_EOL;
}

function connect_server(array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['charset']
    );

    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
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

function seed_csv(PDO $pdo, string $path, string $table, array $columns): int
{
    if (! is_readable($path)) {
        throw new RuntimeException('CSV tidak ditemukan: ' . $path);
    }

    $headers = [];
    $handle = fopen($path, 'r');

    if ($handle === false) {
        throw new RuntimeException('CSV tidak bisa dibuka: ' . $path);
    }

    $headers = fgetcsv($handle) ?: [];
    $placeholders = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));
    $columnSql = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns));
    $statement = $pdo->prepare("INSERT INTO `{$table}` ({$columnSql}) VALUES ({$placeholders})");
    $count = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $data = array_combine($headers, $row);

        if ($data === false) {
            continue;
        }

        $params = [];
        foreach ($columns as $column) {
            $params[$column] = normalize_value($data[$column] ?? null, $column);
        }

        $statement->execute($params);
        $count++;
    }

    fclose($handle);

    return $count;
}

function normalize_value(mixed $value, string $column): mixed
{
    $numericColumns = [
        'harga_default',
        'jumlah_alias',
        'jumlah_transaksi',
        'jumlah_invoice',
        'total_item',
        'total_qty',
        'subtotal',
        'jumlah',
        'harga',
        'total',
        'baris',
    ];

    if ($value === null) {
        return in_array($column, $numericColumns, true) ? 0 : null;
    }

    $value = trim((string) $value);

    if ($value === '') {
        return in_array($column, $numericColumns, true) ? 0 : null;
    }

    return $value;
}
