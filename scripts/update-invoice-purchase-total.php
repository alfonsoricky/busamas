<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$sources = [
    [
        'path' => $baseDir . '/storage/PENJUALAN-2025.xlsx',
        'sheet' => 'Penjualan-2025',
    ],
    [
        'path' => $baseDir . '/storage/PENJUALAN-2026.xlsx',
        'sheet' => 'Penjualan',
    ],
];

foreach ($sources as $source) {
    if (! is_readable($source['path'])) {
        fwrite(STDERR, 'File tidak ditemukan: ' . relative_path($baseDir, $source['path']) . PHP_EOL);
        exit(1);
    }
}

$pdo = connect_database($config);
ensure_invoice_purchase_columns($pdo);

$purchaseRecords = read_purchase_records($sources);
$existingInvoices = fetch_existing_invoice_numbers($pdo);
$statement = $pdo->prepare('
    UPDATE invoices
    SET total_pembelian_barang = :total_pembelian_barang,
        total_utang_pembelian_barang = :total_utang_pembelian_barang,
        status_pembelian_barang = :status_pembelian_barang
    WHERE nomor_invoice = :nomor_invoice
');

$matched = 0;
$unmatched = [];

$pdo->beginTransaction();
$pdo->exec("
    UPDATE invoices
    SET total_pembelian_barang = 0,
        total_utang_pembelian_barang = 0,
        status_pembelian_barang = 'Lunas'
");

foreach ($purchaseRecords as $invoiceNumber => $record) {
    $invoiceKey = invoice_number_key($invoiceNumber);

    if (! isset($existingInvoices[$invoiceKey])) {
        $unmatched[] = $invoiceNumber;
        continue;
    }

    $debtTotal = (float) ($record['debt_total'] ?? 0);
    $statement->execute([
        'nomor_invoice' => $existingInvoices[$invoiceKey],
        'total_pembelian_barang' => $record['purchase_total'],
        'total_utang_pembelian_barang' => $debtTotal,
        'status_pembelian_barang' => $debtTotal > 0 ? 'Utang' : 'Lunas',
    ]);

    $matched++;
}

$pdo->commit();

echo 'Data pembelian barang dari Excel: ' . count($purchaseRecords) . PHP_EOL;
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

function ensure_invoice_purchase_columns(PDO $pdo): void
{
    if (! invoice_column_exists($pdo, 'total_pembelian_barang')) {
        $pdo->exec('
            ALTER TABLE invoices
            ADD COLUMN total_pembelian_barang DECIMAL(15,2) NOT NULL DEFAULT 0
            AFTER subtotal
        ');
    }

    if (! invoice_column_exists($pdo, 'total_utang_pembelian_barang')) {
        $pdo->exec('
            ALTER TABLE invoices
            ADD COLUMN total_utang_pembelian_barang DECIMAL(15,2) NOT NULL DEFAULT 0
            AFTER total_pembelian_barang
        ');
    }

    if (! invoice_column_exists($pdo, 'status_pembelian_barang')) {
        $pdo->exec("
            ALTER TABLE invoices
            ADD COLUMN status_pembelian_barang VARCHAR(20) NOT NULL DEFAULT 'Lunas'
            AFTER total_utang_pembelian_barang
        ");
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

function invoice_number_key(string $invoiceNumber): string
{
    return strtoupper(normalize_spaces($invoiceNumber));
}

function read_purchase_records(array $sources): array
{
    $records = [];

    foreach ($sources as $source) {
        $rows = read_xlsx_sheet_rows($source['path'], $source['sheet']);

        foreach ($rows as $row) {
            $invoiceNumber = normalize_spaces((string) ($row['A'] ?? ''));
            $purchaseTotal = parse_number($row['AG'] ?? '');
            $debtTotal = parse_number($row['AH'] ?? '');

            if (! is_invoice_number($invoiceNumber) || ($purchaseTotal === null && $debtTotal === null)) {
                continue;
            }

            $records[$invoiceNumber] = [
                'purchase_total' => $purchaseTotal ?? 0,
                'debt_total' => $debtTotal ?? 0,
            ];
        }
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

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function relative_path(string $baseDir, string $path): string
{
    return ltrim(str_replace($baseDir, '', $path), '/');
}
