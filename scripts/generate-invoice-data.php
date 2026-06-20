<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$generatedDir = $baseDir . '/storage/generated';

$cacheFiles = array_filter(array_map(
    'trim',
    explode(',', getenv('INVOICE_CACHE_FILES') ?: 'invoice-extract-cache-BM-INV-2025-dedup.json,invoice-extract-cache-BM-INV-I-II-III-IV-V-VI-2026.json')
));

$invoiceOutput = $generatedDir . '/invoices-2025-jan-jun-2026.csv';
$itemOutput = $generatedDir . '/invoice-items-2025-jan-jun-2026.csv';
$failedOutput = $generatedDir . '/invoice-mapping-unmatched-2025-jan-jun-2026.csv';

$customerAliasRows = read_csv_assoc($generatedDir . '/master-customer-alias.csv');
$barangAliasRows = read_csv_assoc($generatedDir . '/master-barang-alias.csv');

$customerByInvoice = [];
$customerByName = [];
foreach ($customerAliasRows as $row) {
    $invoiceKey = invoice_key((string) ($row['nomor_invoice'] ?? ''));
    if ($invoiceKey === '') {
        continue;
    }

    $customerByInvoice[$invoiceKey] = $row;

    foreach ([$row['nama_master'] ?? '', $row['nama_customer'] ?? '', $row['nama_di_invoice'] ?? ''] as $name) {
        $nameKey = customer_name_key((string) $name);
        if ($nameKey !== '') {
            $customerByName[$nameKey] = $row;
        }
    }
}

$barangBySourceRow = [];
foreach ($barangAliasRows as $row) {
    $sourceKey = source_row_key((string) ($row['file_invoice'] ?? ''), (string) ($row['baris'] ?? ''));
    if ($sourceKey === '') {
        continue;
    }

    $barangBySourceRow[$sourceKey] = $row;
}

$invoices = [];
$items = [];
$unmatched = [];

foreach ($cacheFiles as $cacheFile) {
    $cachePath = $generatedDir . '/' . $cacheFile;
    $cache = json_decode((string) file_get_contents($cachePath), true);

    if (! is_array($cache)) {
        fwrite(STDERR, "Cache tidak valid: {$cacheFile}\n");
        exit(1);
    }

    $cache = apply_manual_invoice_corrections($cache);

    foreach (($cache['customers'] ?? []) as $customer) {
        $invoiceKey = invoice_key((string) ($customer['invoice_number'] ?? ''));

        if ($invoiceKey === '' || isset($invoices[$invoiceKey])) {
            continue;
        }

        $masterCustomer = $customerByInvoice[$invoiceKey] ?? null;
        if ($masterCustomer === null) {
            $unmatched[] = [
                'tipe' => 'customer',
                'nomor_invoice' => $customer['invoice_number'] ?? '',
                'file_invoice' => $customer['source_file'] ?? '',
                'baris' => '',
                'nilai' => $customer['raw_name'] ?? '',
                'catatan' => 'Invoice tidak ditemukan di master-customer-alias.csv',
            ];
        }

        $invoices[$invoiceKey] = [
            'kode_invoice' => '',
            'nomor_invoice' => $customer['invoice_number'] ?? '',
            'tanggal_invoice' => $customer['invoice_date'] ?? '',
            'kode_customer' => $masterCustomer['kode_customer'] ?? '',
            'nama_customer_master' => $masterCustomer['nama_master'] ?? '',
            'nama_customer_invoice' => $customer['raw_name'] ?? '',
            'nama_laundry_invoice' => $customer['laundry_name'] ?? '',
            'no_telepon' => $masterCustomer['no_telepon'] ?? ($customer['phone'] ?? ''),
            'alamat' => $masterCustomer['alamat'] ?? ($customer['address'] ?? ''),
            'total_item' => 0,
            'total_qty' => 0,
            'subtotal' => 0,
            'file_invoice' => $customer['source_file'] ?? '',
        ];
    }

    foreach (($cache['items'] ?? []) as $item) {
        $sourceFile = (string) ($item['source_file'] ?? '');
        $invoiceKey = invoice_key_from_source($sourceFile);

        if ($invoiceKey === '') {
            $unmatched[] = [
                'tipe' => 'invoice_item',
                'nomor_invoice' => '',
                'file_invoice' => $sourceFile,
                'baris' => (string) ($item['row'] ?? ''),
                'nilai' => $item['raw_name'] ?? '',
                'catatan' => 'Nomor invoice tidak bisa dibaca dari nama file',
            ];
            continue;
        }

        if (! isset($invoices[$invoiceKey])) {
            $fallbackName = customer_name_from_source($sourceFile);
            $fallbackCustomer = find_customer_by_name($fallbackName, $customerByName);

            if ($fallbackCustomer === null) {
                $unmatched[] = [
                    'tipe' => 'customer',
                    'nomor_invoice' => invoice_number_from_source($sourceFile),
                    'file_invoice' => $sourceFile,
                    'baris' => '',
                    'nilai' => $fallbackName,
                    'catatan' => 'Customer tidak ditemukan dari header maupun nama file',
                ];
            }

            $invoices[$invoiceKey] = [
                'kode_invoice' => '',
                'nomor_invoice' => invoice_number_from_source($sourceFile),
                'tanggal_invoice' => '',
                'kode_customer' => $fallbackCustomer['kode_customer'] ?? '',
                'nama_customer_master' => $fallbackCustomer['nama_master'] ?? '',
                'nama_customer_invoice' => $fallbackCustomer['nama_di_invoice'] ?? $fallbackName,
                'nama_laundry_invoice' => $fallbackName,
                'no_telepon' => $fallbackCustomer['no_telepon'] ?? '',
                'alamat' => $fallbackCustomer['alamat'] ?? '',
                'total_item' => 0,
                'total_qty' => 0,
                'subtotal' => 0,
                'file_invoice' => $sourceFile,
            ];
        }

        $sourceRowKey = source_row_key($sourceFile, (string) ($item['row'] ?? ''));
        $masterBarang = $barangBySourceRow[$sourceRowKey] ?? null;
        if ($masterBarang === null) {
            $unmatched[] = [
                'tipe' => 'barang',
                'nomor_invoice' => $invoices[$invoiceKey]['nomor_invoice'],
                'file_invoice' => $sourceFile,
                'baris' => (string) ($item['row'] ?? ''),
                'nilai' => $item['raw_name'] ?? '',
                'catatan' => 'Item tidak ditemukan di master-barang-alias.csv',
            ];
        }

        $qty = parse_number($item['jumlah'] ?? 0);
        $lineTotal = parse_number($item['total'] ?? 0);
        $unitPrice = parse_number($item['harga'] ?? 0);

        $items[] = [
            'kode_invoice' => '',
            'nomor_invoice' => $invoices[$invoiceKey]['nomor_invoice'],
            'tanggal_invoice' => $invoices[$invoiceKey]['tanggal_invoice'],
            'kode_customer' => $invoices[$invoiceKey]['kode_customer'],
            'kode_barang' => $masterBarang['kode_barang'] ?? '',
            'nama_barang_master' => $masterBarang['nama_master'] ?? '',
            'ukuran_master' => $masterBarang['ukuran_master'] ?? '',
            'nama_barang_invoice' => $item['raw_name'] ?? '',
            'isi_invoice' => $item['isi'] ?? '',
            'jumlah' => format_number($qty),
            'satuan' => $item['satuan'] ?? '',
            'harga' => format_number($unitPrice),
            'total' => format_number($lineTotal),
            'file_invoice' => $sourceFile,
            'baris' => (string) ($item['row'] ?? ''),
        ];

        $invoices[$invoiceKey]['total_item']++;
        $invoices[$invoiceKey]['total_qty'] += $qty;
        $invoices[$invoiceKey]['subtotal'] += $lineTotal;
    }
}

ksort($invoices, SORT_NATURAL);

$invoiceNumberToCode = [];
$invoiceRows = [];
$index = 1;
foreach ($invoices as $invoiceKey => $invoice) {
    $code = 'INV-' . str_pad((string) $index, 5, '0', STR_PAD_LEFT);
    $invoiceNumberToCode[$invoiceKey] = $code;
    $invoice['kode_invoice'] = $code;
    $invoice['total_qty'] = format_number((float) $invoice['total_qty']);
    $invoice['subtotal'] = format_number((float) $invoice['subtotal']);
    $invoiceRows[] = $invoice;
    $index++;
}

foreach ($items as &$item) {
    $item['kode_invoice'] = $invoiceNumberToCode[invoice_key((string) $item['nomor_invoice'])] ?? '';
}
unset($item);

usort($items, static function (array $a, array $b): int {
    $invoiceCompare = strnatcasecmp((string) ($a['kode_invoice'] ?? ''), (string) ($b['kode_invoice'] ?? ''));

    if ($invoiceCompare !== 0) {
        return $invoiceCompare;
    }

    return ((int) ($a['baris'] ?? 0)) <=> ((int) ($b['baris'] ?? 0));
});

write_csv_assoc($invoiceOutput, $invoiceRows, [
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
]);

write_csv_assoc($itemOutput, $items, [
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
]);

write_csv_assoc($failedOutput, $unmatched, [
    'tipe',
    'nomor_invoice',
    'file_invoice',
    'baris',
    'nilai',
    'catatan',
]);

echo 'Invoice unik: ' . count($invoiceRows) . PHP_EOL;
echo 'Detail invoice: ' . count($items) . PHP_EOL;
echo 'Tidak termapping: ' . count($unmatched) . PHP_EOL;
echo 'Invoice CSV: storage/generated/' . basename($invoiceOutput) . PHP_EOL;
echo 'Detail CSV: storage/generated/' . basename($itemOutput) . PHP_EOL;

function apply_manual_invoice_corrections(array $cache): array
{
    $invoiceNumber = '273/BM-INV/I/2026';
    $sourceFile = $invoiceNumber . ' CLEAN POINT.xlsx';
    $sourceFileId = 'manual-correction-clean-point-273-2026-01';
    $hasJanuary2026 = false;

    foreach (($cache['customers'] ?? []) as $customer) {
        if (str_contains((string) ($customer['invoice_number'] ?? ''), '/BM-INV/I/2026')) {
            $hasJanuary2026 = true;
            break;
        }
    }

    if (! $hasJanuary2026) {
        foreach (($cache['items'] ?? []) as $item) {
            if (str_contains((string) ($item['source_file'] ?? ''), '/BM-INV/I/2026')) {
                $hasJanuary2026 = true;
                break;
            }
        }
    }

    if (! $hasJanuary2026) {
        return $cache;
    }

    $hasCustomer = false;
    foreach (($cache['customers'] ?? []) as $customer) {
        if (($customer['invoice_number'] ?? '') === $invoiceNumber) {
            $hasCustomer = true;
            break;
        }
    }

    if (! $hasCustomer) {
        $cache['customers'][] = [
            'source_file' => $sourceFile,
            'source_file_id' => $sourceFileId,
            'raw_name' => 'CLEAN POINT LAUNDRY',
            'normalized_name' => customer_name_key('CLEAN POINT LAUNDRY'),
            'laundry_name' => 'CLEAN POINT LAUNDRY',
            'contact_name' => 'Bp. Gandung',
            'phone' => '081238500058',
            'address' => 'Jl. Wahyu Graha No. 55 - Buduk',
            'invoice_number' => $invoiceNumber,
            'invoice_date' => '20 Januari 2026',
        ];
    }

    $hasItem = false;
    foreach (($cache['items'] ?? []) as $item) {
        if (($item['source_file'] ?? '') === $sourceFile && strtoupper((string) ($item['raw_name'] ?? '')) === 'N-IRON') {
            $hasItem = true;
            break;
        }
    }

    if (! $hasItem) {
        $cache['items'][] = [
            'source_file' => $sourceFile,
            'source_file_id' => $sourceFileId,
            'row' => 23,
            'raw_name' => 'N-IRON',
            'normalized_name' => 'N IRON',
            'isi' => '5 KG',
            'jumlah' => '1',
            'satuan' => '5 kg',
            'variant_size' => '5 KG',
            'harga' => '687500',
            'total' => '687500',
        ];
    }

    return $cache;
}

function read_csv_assoc(string $path): array
{
    $handle = fopen($path, 'r');
    $headers = fgetcsv($handle) ?: [];
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $item = array_combine($headers, $row);
        if ($item !== false) {
            $rows[] = $item;
        }
    }

    fclose($handle);

    return $rows;
}

function write_csv_assoc(string $path, array $rows, array $headers): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, $headers);

    foreach ($rows as $row) {
        fputcsv($handle, array_map(
            static fn (string $header): mixed => $row[$header] ?? '',
            $headers
        ));
    }

    fclose($handle);
}

function invoice_key(string $value): string
{
    $value = normalize_spaces($value);
    if ($value === '') {
        return '';
    }

    return strtoupper(preg_replace('~\s+~', '', $value) ?? $value);
}

function invoice_key_from_source(string $source): string
{
    return invoice_key(invoice_number_from_source($source));
}

function invoice_number_from_source(string $source): string
{
    if (preg_match('~(\d+\s*/\s*BM-INV\s*/\s*[IVXLCDM]+\s*/\s*\d{4})~i', $source, $match)) {
        return preg_replace('~\s+~', '', $match[1]) ?? $match[1];
    }

    return '';
}

function customer_name_from_source(string $source): string
{
    $name = preg_replace('~.*?\d+\s*/\s*BM-INV\s*/\s*[IVXLCDM]+\s*/\s*\d{4}\s*~i', '', $source) ?? $source;
    $name = preg_replace('/\.xlsx$/i', '', $name) ?? $name;

    return normalize_spaces($name);
}

function customer_name_key(string $name): string
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

    $words = explode(' ', normalize_spaces($name));
    $normalizedWords = [];
    foreach ($words as $word) {
        $normalizedWords[] = $replacements[$word] ?? $word;
    }

    return normalize_spaces(implode(' ', $normalizedWords));
}

function find_customer_by_name(string $name, array $customerByName): ?array
{
    $key = customer_name_key($name);
    if ($key === '') {
        return null;
    }

    if (isset($customerByName[$key])) {
        return $customerByName[$key];
    }

    foreach ($customerByName as $candidateKey => $customer) {
        if (str_contains($candidateKey, $key) || str_contains($key, $candidateKey)) {
            return $customer;
        }
    }

    return null;
}

function source_row_key(string $sourceFile, string $row): string
{
    $sourceFile = normalize_spaces($sourceFile);
    $row = trim($row);

    if ($sourceFile === '' || $row === '') {
        return '';
    }

    return $sourceFile . '#' . $row;
}

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function parse_number(mixed $value): float
{
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $value = str_replace(',', '.', (string) $value);
    $value = preg_replace('/[^0-9.\-]/', '', $value) ?? '0';

    return (float) ($value === '' ? 0 : $value);
}

function format_number(float $value): string
{
    if (fmod($value, 1.0) === 0.0) {
        return (string) (int) $value;
    }

    return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
}
