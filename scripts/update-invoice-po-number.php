<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$config = require $baseDir . '/config/database.php';
$driveDir = $baseDir . '/storage/drive';

if (! is_dir($driveDir)) {
    fwrite(STDERR, 'Folder tidak ditemukan: storage/drive' . PHP_EOL);
    exit(1);
}

$pdo = connect_database($config);
ensure_po_number_column($pdo);
$pdo->exec("UPDATE invoices SET po_number = NULL WHERE po_number = 'Number'");

$files = find_invoice_files_2026($driveDir);
$statement = $pdo->prepare('
    UPDATE invoices
    SET po_number = :po_number
    WHERE nomor_invoice = :nomor_invoice
');

$found = [];
$unmatched = [];

foreach ($files as $file) {
    $invoiceNumber = invoice_number_from_filename(basename($file));

    if ($invoiceNumber === '') {
        continue;
    }

    $poNumber = read_po_number_from_xlsx($file);

    if ($poNumber === '') {
        continue;
    }

    $statement->execute([
        'nomor_invoice' => $invoiceNumber,
        'po_number' => $poNumber,
    ]);

    if ($statement->rowCount() > 0) {
        $found[$invoiceNumber] = $poNumber;
    } else {
        $unmatched[$invoiceNumber] = $poNumber;
    }
}

echo 'File invoice 2026 dicek: ' . count($files) . PHP_EOL;
echo 'PO Number ditemukan dan update DB: ' . count($found) . PHP_EOL;
echo 'PO Number ditemukan tapi invoice tidak cocok: ' . count($unmatched) . PHP_EOL;

if ($found !== []) {
    $examples = [];
    foreach (array_slice($found, 0, 10, true) as $invoice => $poNumber) {
        $examples[] = $invoice . ' = ' . $poNumber;
    }
    echo 'Contoh update: ' . implode(', ', $examples) . PHP_EOL;
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

function ensure_po_number_column(PDO $pdo): void
{
    $statement = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'invoices'
            AND COLUMN_NAME = 'po_number'
    ");
    $statement->execute();

    if ((int) $statement->fetchColumn() === 0) {
        $pdo->exec('
            ALTER TABLE invoices
            ADD COLUMN po_number VARCHAR(50) NULL
            AFTER tanggal_surat_jalan
        ');
    }
}

function find_invoice_files_2026(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'xlsx') {
            continue;
        }

        if (preg_match('/BM-INV_[IVXLCDM]+_2026/i', $file->getFilename()) !== 1) {
            continue;
        }

        $files[] = $file->getPathname();
    }

    sort($files);

    return $files;
}

function invoice_number_from_filename(string $filename): string
{
    if (preg_match('/^0*(\d+)_BM-INV_([IVXLCDM]+)_2026/i', $filename, $match) !== 1) {
        return '';
    }

    return ((int) $match[1]) . '/BM-INV/' . strtoupper($match[2]) . '/2026';
}

function read_po_number_from_xlsx(string $path): string
{
    $rows = read_xlsx_first_sheet_rows($path);

    foreach ($rows as $row) {
        $columns = array_keys($row);
        usort($columns, static fn (string $a, string $b): int => column_number($a) <=> column_number($b));

        foreach ($columns as $index => $column) {
            $value = normalize_spaces((string) ($row[$column] ?? ''));

            if (! is_po_label($value)) {
                continue;
            }

            $inlineValue = value_after_label($value);

            if ($inlineValue !== '') {
                return $inlineValue;
            }

            for ($offset = 1; $offset <= 4; $offset++) {
                $candidateColumn = $columns[$index + $offset] ?? '';
                $candidate = normalize_spaces((string) ($row[$candidateColumn] ?? ''));

                if ($candidate !== '' && ! is_po_label($candidate) && $candidate !== ':') {
                    return trim($candidate, " \t\n\r\0\x0B:");
                }
            }
        }
    }

    return '';
}

function read_xlsx_first_sheet_rows(string $path): array
{
    $zip = new ZipArchive();

    if ($zip->open($path) !== true) {
        return [];
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

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        return [];
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

function is_po_label(string $value): bool
{
    return preg_match('/\bP\.?\s*O\.?\s*(NUMBER|NO|#)?\b/i', $value) === 1
        || preg_match('/\bPO\s*(NUMBER|NO|#)?\b/i', $value) === 1;
}

function value_after_label(string $value): string
{
    if (preg_match('/^\s*(?:P\.?\s*O\.?|PO)\s*(?:NUMBER|NO|#)?\s*$/i', $value) === 1) {
        return '';
    }

    if (preg_match('/(?:P\.?\s*O\.?|PO)\s*(?:NUMBER|NO|#)?\s*:\s*(.+)$/i', $value, $match) !== 1) {
        return '';
    }

    $candidate = normalize_spaces($match[1]);

    if ($candidate === '' || $candidate === ':' || is_po_label($candidate)) {
        return '';
    }

    return trim($candidate, " \t\n\r\0\x0B:");
}

function column_number(string $column): int
{
    $number = 0;

    foreach (str_split(strtoupper($column)) as $char) {
        $number = ($number * 26) + (ord($char) - 64);
    }

    return $number;
}

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}
