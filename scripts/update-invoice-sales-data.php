<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$excelPath = $baseDir . '/storage/PENJUALAN-2026.xlsx';

if (! is_readable($excelPath)) {
    fwrite(STDERR, 'File tidak ditemukan: storage/PENJUALAN-2026.xlsx' . PHP_EOL);
    exit(1);
}

$pdo = connect_database($config);
ensure_invoice_sales_columns($pdo);

$records = read_sales_records($excelPath);
$salesMap = fetch_sales_map($pdo);
$existingInvoices = fetch_existing_invoice_numbers($pdo);
$statement = $pdo->prepare('
    UPDATE invoices
    SET kode_sales_1 = :kode_sales_1,
        nama_sales_1 = :nama_sales_1,
        kode_sales_2 = :kode_sales_2,
        nama_sales_2 = :nama_sales_2,
        komisi_sales_1_persen = :komisi_sales_1_persen,
        komisi_sales_2_persen = :komisi_sales_2_persen
    WHERE nomor_invoice = :nomor_invoice
');

$matched = 0;
$unmatched = [];

$pdo->beginTransaction();

foreach ($records as $invoiceNumber => $record) {
    $invoiceKey = invoice_number_key($invoiceNumber);

    if (! isset($existingInvoices[$invoiceKey])) {
        $unmatched[] = $invoiceNumber;
        continue;
    }

    $sales1 = resolve_sales($pdo, $salesMap, $record['nama_sales_1']);
    $sales2 = resolve_sales($pdo, $salesMap, $record['nama_sales_2']);

    $statement->execute([
        'nomor_invoice' => $existingInvoices[$invoiceKey],
        'kode_sales_1' => $sales1['kode_sales'],
        'nama_sales_1' => $sales1['nama_sales'],
        'kode_sales_2' => $sales2['kode_sales'],
        'nama_sales_2' => $sales2['nama_sales'],
        'komisi_sales_1_persen' => $record['komisi_sales_1_persen'],
        'komisi_sales_2_persen' => $record['komisi_sales_2_persen'],
    ]);

    $matched++;
}

$pdo->commit();

echo 'Data sales dari Excel: ' . count($records) . PHP_EOL;
echo 'Invoice ditemukan dan disinkronkan: ' . $matched . PHP_EOL;
echo 'Invoice tidak ditemukan: ' . count($unmatched) . PHP_EOL;

if ($unmatched !== []) {
    echo 'Contoh tidak ditemukan: ' . implode(', ', array_slice($unmatched, 0, 10)) . PHP_EOL;
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

function ensure_invoice_sales_columns(PDO $pdo): void
{
    $columns = [
        'kode_sales_1' => "ADD COLUMN kode_sales_1 VARCHAR(20) NULL AFTER tanggal_surat_jalan",
        'nama_sales_1' => "ADD COLUMN nama_sales_1 VARCHAR(150) NULL AFTER kode_sales_1",
        'kode_sales_2' => "ADD COLUMN kode_sales_2 VARCHAR(20) NULL AFTER nama_sales_1",
        'nama_sales_2' => "ADD COLUMN nama_sales_2 VARCHAR(150) NULL AFTER kode_sales_2",
        'komisi_sales_1_persen' => "ADD COLUMN komisi_sales_1_persen DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER nama_sales_2",
        'komisi_sales_2_persen' => "ADD COLUMN komisi_sales_2_persen DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER komisi_sales_1_persen",
    ];

    foreach ($columns as $columnName => $alterSql) {
        if (! invoice_column_exists($pdo, $columnName)) {
            $pdo->exec('ALTER TABLE invoices ' . $alterSql);
        }
    }
}

function invoice_column_exists(PDO $pdo, string $columnName): bool
{
    $statement = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'invoices'
            AND COLUMN_NAME = :column_name
    ");
    $statement->execute(['column_name' => $columnName]);

    return (int) $statement->fetchColumn() > 0;
}

function fetch_existing_invoice_numbers(PDO $pdo): array
{
    $rows = $pdo->query('SELECT nomor_invoice FROM invoices')->fetchAll(PDO::FETCH_COLUMN);
    $numbers = [];

    foreach ($rows as $row) {
        $numbers[invoice_number_key((string) $row)] = (string) $row;
    }

    return $numbers;
}

function fetch_sales_map(PDO $pdo): array
{
    $rows = $pdo->query('SELECT kode_sales, nama_sales FROM master_sales ORDER BY kode_sales')->fetchAll();
    $map = [];

    foreach ($rows as $row) {
        $map[sales_key((string) $row['nama_sales'])] = [
            'kode_sales' => (string) $row['kode_sales'],
            'nama_sales' => (string) $row['nama_sales'],
        ];
    }

    return $map;
}

function resolve_sales(PDO $pdo, array &$salesMap, string $name): array
{
    $name = normalize_sales_name($name);

    if ($name === '') {
        return [
            'kode_sales' => null,
            'nama_sales' => null,
        ];
    }

    $key = sales_key($name);

    if (isset($salesMap[$key])) {
        return $salesMap[$key];
    }

    $code = next_sales_code($pdo);
    $statement = $pdo->prepare('INSERT INTO master_sales (kode_sales, nama_sales) VALUES (:kode_sales, :nama_sales)');
    $statement->execute([
        'kode_sales' => $code,
        'nama_sales' => $name,
    ]);

    $salesMap[$key] = [
        'kode_sales' => $code,
        'nama_sales' => $name,
    ];

    return $salesMap[$key];
}

function next_sales_code(PDO $pdo): string
{
    $lastCode = (string) $pdo->query('SELECT kode_sales FROM master_sales ORDER BY kode_sales DESC LIMIT 1')->fetchColumn();

    if (preg_match('/SLS-(\d+)/', $lastCode, $match) !== 1) {
        return 'SLS-0001';
    }

    return sprintf('SLS-%04d', ((int) $match[1]) + 1);
}

function read_sales_records(string $path): array
{
    $rows = read_xlsx_sheet_rows($path, 'Penjualan');
    $records = [];

    foreach ($rows as $row) {
        $invoiceNumber = normalize_spaces((string) ($row['A'] ?? ''));

        if (! is_invoice_number($invoiceNumber)) {
            continue;
        }

        $records[$invoiceNumber] = [
            'nama_sales_1' => normalize_sales_name((string) ($row['F'] ?? '')),
            'nama_sales_2' => normalize_sales_name((string) ($row['G'] ?? '')),
            'komisi_sales_1_persen' => parse_percent($row['P'] ?? ''),
            'komisi_sales_2_persen' => parse_percent($row['Q'] ?? ''),
        ];
    }

    return $records;
}

function normalize_sales_name(string $name): string
{
    $name = normalize_spaces($name);

    return [
        'pak adi' => 'Pak Adi',
        'Tim Denis' => 'Denis Team',
    ][$name] ?? $name;
}

function parse_percent(mixed $value): float
{
    $value = trim((string) $value);

    if ($value === '') {
        return 0;
    }

    $value = str_replace(',', '.', $value);
    $value = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';

    if ($value === '' || ! is_numeric($value)) {
        return 0;
    }

    $number = (float) $value;

    return abs($number) <= 1 ? $number * 100 : $number;
}

function read_xlsx_sheet_rows(string $path, string $sheetName): array
{
    $zip = new ZipArchive();

    if ($zip->open($path) !== true) {
        throw new RuntimeException('Workbook tidak bisa dibuka.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);

        foreach ($xml->si as $si) {
            $text = '';

            if (isset($si->t)) {
                $text = (string) $si->t;
            } else {
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }

            $sharedStrings[] = $text;
        }
    }

    $relationships = [];
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if ($relsXml !== false) {
        $xml = simplexml_load_string($relsXml);

        foreach ($xml->Relationship as $relationship) {
            $relationships[(string) $relationship['Id']] = (string) $relationship['Target'];
        }
    }

    $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
    $sheetPath = '';

    foreach ($workbook->sheets->sheet as $sheet) {
        if ((string) $sheet['name'] !== $sheetName) {
            continue;
        }

        $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $target = $relationships[(string) $attributes['id']] ?? '';
        $sheetPath = 'xl/' . ltrim($target, '/');
        break;
    }

    if ($sheetPath === '') {
        throw new RuntimeException('Sheet tidak ditemukan: ' . $sheetName);
    }

    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('XML sheet tidak ditemukan: ' . $sheetName);
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];

    foreach ($sheet->sheetData->row as $row) {
        $rowNumber = (int) $row['r'];
        $values = [];

        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $column = preg_replace('/\d+/', '', $ref);
            $type = (string) $cell['t'];
            $value = (string) $cell->v;

            if ($type === 's') {
                $value = $sharedStrings[(int) $value] ?? $value;
            } elseif ($type === 'inlineStr') {
                $value = (string) $cell->is->t;
            }

            $values[$column] = trim($value);
        }

        $rows[$rowNumber] = $values;
    }

    return $rows;
}

function is_invoice_number(string $value): bool
{
    return preg_match('~/BM-INV/[IVXLCDM]+/\d{4}$~i', $value) === 1;
}

function invoice_number_key(string $invoiceNumber): string
{
    return strtoupper(normalize_spaces($invoiceNumber));
}

function sales_key(string $name): string
{
    return strtoupper(normalize_spaces($name));
}

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}
