<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$excelPath = $baseDir . '/storage/PENJUALAN-2026.xlsx';

if (! is_readable($excelPath)) {
    fwrite(STDERR, 'File tidak ditemukan: storage/PENJUALAN-2026.xlsx' . PHP_EOL);
    exit(1);
}

$pdo = connect_server($config);
$pdo->exec((string) file_get_contents($baseDir . '/database/schema.sql'));
$pdo = connect_database($config);
$sales = read_sales_from_workbook($excelPath);

try {
    $pdo->exec('TRUNCATE TABLE master_sales');

    $pdo->beginTransaction();
    $statement = $pdo->prepare('
        INSERT INTO master_sales (kode_sales, nama_sales)
        VALUES (:kode_sales, :nama_sales)
    ');

    foreach ($sales as $index => $name) {
        $statement->execute([
            'kode_sales' => 'SLS-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            'nama_sales' => $name,
        ]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Seeder sales gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo 'master_sales: ' . count($sales) . PHP_EOL;

function read_sales_from_workbook(string $path): array
{
    $rows = read_xlsx_sheet_rows($path, 'sales');
    $sales = [];

    foreach ($rows as $index => $row) {
        if ($index === 0) {
            continue;
        }

        $name = normalize_sales_name((string) ($row['A'] ?? ''));

        if ($name === '') {
            continue;
        }

        $sales[strtoupper($name)] = $name;
    }

    return array_values($sales);
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

        $rows[] = $values;
    }

    return $rows;
}

function normalize_sales_name(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);

    if ($name === '') {
        return '';
    }

    $lowerName = strtolower($name);
    $words = explode(' ', $lowerName);
    $normalized = [];

    foreach ($words as $word) {
        $normalized[] = match ($word) {
            'pt' => 'PT',
            'pbk' => 'PBK',
            default => ucfirst($word),
        };
    }

    return implode(' ', $normalized);
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
