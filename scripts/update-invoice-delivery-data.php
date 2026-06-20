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
ensure_delivery_columns($pdo);

$records = read_delivery_records($excelPath);
$existingInvoices = fetch_existing_invoice_numbers($pdo);
$statement = $pdo->prepare('
    UPDATE invoices
    SET nomor_surat_jalan = :nomor_surat_jalan,
        tanggal_surat_jalan = :tanggal_surat_jalan
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
        'nomor_surat_jalan' => $record['nomor_surat_jalan'],
        'tanggal_surat_jalan' => $record['tanggal_surat_jalan'],
    ]);

    $matched++;
}

$pdo->commit();

echo 'Data surat jalan dari Excel: ' . count($records) . PHP_EOL;
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

function ensure_delivery_columns(PDO $pdo): void
{
    if (! invoice_column_exists($pdo, 'nomor_surat_jalan')) {
        $pdo->exec('
            ALTER TABLE invoices
            ADD COLUMN nomor_surat_jalan VARCHAR(50) NULL
            AFTER tanggal_invoice
        ');
    }

    if (! invoice_column_exists($pdo, 'tanggal_surat_jalan')) {
        $pdo->exec('
            ALTER TABLE invoices
            ADD COLUMN tanggal_surat_jalan VARCHAR(50) NULL
            AFTER nomor_surat_jalan
        ');
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

function read_delivery_records(string $path): array
{
    $rows = read_xlsx_sheet_rows($path, 'Penjualan');
    $records = [];

    foreach ($rows as $row) {
        $invoiceNumber = normalize_spaces((string) ($row['A'] ?? ''));
        $deliveryNumber = normalize_spaces((string) ($row['C'] ?? ''));
        $deliveryDate = parse_excel_date($row['D'] ?? '');

        if (! is_invoice_number($invoiceNumber) || $deliveryNumber === '') {
            continue;
        }

        $records[$invoiceNumber] = [
            'nomor_surat_jalan' => $deliveryNumber,
            'tanggal_surat_jalan' => $deliveryDate,
        ];
    }

    return $records;
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

function parse_excel_date(mixed $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (is_numeric($value)) {
        $timestamp = ((int) floor((float) $value) - 25569) * 86400;
        return gmdate('j ', $timestamp) . month_name((int) gmdate('n', $timestamp)) . gmdate(' Y', $timestamp);
    }

    return normalize_spaces($value);
}

function month_name(int $month): string
{
    return [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ][$month] ?? '';
}

function is_invoice_number(string $value): bool
{
    return preg_match('~/BM-INV/[IVXLCDM]+/\d{4}$~i', $value) === 1;
}

function invoice_number_key(string $invoiceNumber): string
{
    return strtoupper(normalize_spaces($invoiceNumber));
}

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}
