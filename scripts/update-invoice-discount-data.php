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
ensure_invoice_discount_columns($pdo);

$records = read_discount_records($excelPath, 'Penjualan');
$existingInvoices = fetch_existing_invoice_numbers($pdo);
$statement = $pdo->prepare('
    UPDATE invoices
    SET harga_normal_pricelist = :harga_normal_pricelist,
        discount_persen = :discount_persen,
        discount_amount = :discount_amount,
        total_harga_jual = :total_harga_jual
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

    $statement->execute([
        'nomor_invoice' => $existingInvoices[$invoiceKey],
        'harga_normal_pricelist' => $record['harga_normal_pricelist'],
        'discount_persen' => $record['discount_persen'],
        'discount_amount' => $record['discount_amount'],
        'total_harga_jual' => $record['total_harga_jual'],
    ]);

    $matched++;
}

$pdo->commit();

echo 'Data discount dari Excel 2026: ' . count($records) . PHP_EOL;
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

function ensure_invoice_discount_columns(PDO $pdo): void
{
    $columns = [
        'harga_normal_pricelist' => 'ADD COLUMN harga_normal_pricelist DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER subtotal',
        'discount_persen' => 'ADD COLUMN discount_persen DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER harga_normal_pricelist',
        'discount_amount' => 'ADD COLUMN discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER discount_persen',
        'total_harga_jual' => 'ADD COLUMN total_harga_jual DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER discount_amount',
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

function read_discount_records(string $path, string $sheetName): array
{
    $rows = read_xlsx_sheet_rows($path, $sheetName);
    $records = [];

    foreach ($rows as $row) {
        $invoiceNumber = normalize_spaces((string) ($row['A'] ?? ''));

        if (! is_invoice_number($invoiceNumber)) {
            continue;
        }

        $hargaNormal = parse_number($row['H'] ?? '') ?? 0;
        $discountPercent = parse_percent($row['I'] ?? '');
        $discountAmount = parse_number($row['J'] ?? '') ?? 0;
        $totalHargaJual = parse_number($row['K'] ?? '') ?? max($hargaNormal - $discountAmount, 0);

        $records[$invoiceNumber] = [
            'harga_normal_pricelist' => $hargaNormal,
            'discount_persen' => $discountPercent,
            'discount_amount' => $discountAmount,
            'total_harga_jual' => $totalHargaJual,
        ];
    }

    return $records;
}

function is_invoice_number(string $value): bool
{
    return preg_match('~/BM-INV/[IVXLCDM]+/\d{4}$~i', $value) === 1;
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

function parse_number(mixed $value): ?float
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $value = str_replace(',', '.', $value);
    $value = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';

    if ($value === '' || ! is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

function parse_percent(mixed $value): float
{
    $number = parse_number($value);

    if ($number === null) {
        return 0;
    }

    return abs($number) <= 1 ? $number * 100 : $number;
}

function invoice_number_key(string $invoiceNumber): string
{
    return strtoupper(normalize_spaces($invoiceNumber));
}

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}
