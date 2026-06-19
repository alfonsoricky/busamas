<?php

require dirname(__DIR__) . '/app/helpers.php';

$drive = fetch_google_drive_files();

if (! $drive['ok']) {
    fwrite(STDERR, 'Gagal membaca Google Drive: ' . $drive['error'] . PHP_EOL);
    exit(1);
}

$nameContains = getenv('INVOICE_NAME_CONTAINS') ?: '';
$nameRegex = getenv('INVOICE_NAME_REGEX') ?: '';
$xlsxFiles = array_values(array_filter($drive['files'], static function (array $file) use ($nameContains, $nameRegex): bool {
    if ($nameContains !== '' && ! str_contains(strtoupper($file['name'] ?? ''), strtoupper($nameContains))) {
        return false;
    }

    if ($nameRegex !== '' && preg_match('/' . $nameRegex . '/i', $file['name'] ?? '') !== 1) {
        return false;
    }

    return ($file['mimeType'] ?? '') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
}));

$outputDir = dirname(__DIR__) . '/storage/generated';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

$cacheLabel = $nameRegex !== '' ? $nameRegex : $nameContains;
$cacheSuffix = $cacheLabel !== '' ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($cacheLabel, '/')) : '';
$cachePath = $outputDir . '/invoice-extract-cache' . $cacheSuffix . '.json';
$cache = getenv('RESET_CACHE') === '1'
    ? [
        'processed' => [],
        'items' => [],
        'customers' => [],
        'failed' => [],
    ]
    : read_extract_cache($cachePath);
$items = $cache['items'];
$customers = $cache['customers'];
$failedFiles = [];
$token = google_service_account_access_token();

if (! $token['ok']) {
    fwrite(STDERR, 'Gagal membuat access token: ' . $token['error'] . PHP_EOL);
    exit(1);
}

foreach ($xlsxFiles as $index => $file) {
    $fileName = $file['name'] ?? ('file-' . ($index + 1));
    $fileId = $file['id'] ?? '';

    if ($fileId !== '' && isset($cache['processed'][$fileId])) {
        echo '[' . ($index + 1) . '/' . count($xlsxFiles) . '] SKIP ' . $fileName . PHP_EOL;
        continue;
    }

    echo '[' . ($index + 1) . '/' . count($xlsxFiles) . '] READ ' . $fileName . PHP_EOL;

    $downloadUrl = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($file['id']) . '?alt=media';
    $response = http_get($downloadUrl, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);

    if (! $response['ok']) {
        $failedFiles[] = [
            'file' => $fileName,
            'error' => $response['error'],
        ];
        $cache['processed'][$fileId] = true;
        $cache['failed'][] = end($failedFiles);
        write_extract_cache($cachePath, $cache, $items, $customers);
        continue;
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'invoice-');
    file_put_contents($tmpFile, $response['body']);

    try {
        $rows = read_xlsx_rows($tmpFile);
        $items = array_merge($items, extract_invoice_items($rows, $fileName, $file['id']));
        $customer = extract_invoice_customer($rows, $fileName, $file['id']);

        if ($customer !== null) {
            $customers[] = $customer;
        }

        $cache['processed'][$fileId] = true;
        write_extract_cache($cachePath, $cache, $items, $customers);
    } catch (Throwable $exception) {
        $failedFiles[] = [
            'file' => $fileName,
            'error' => $exception->getMessage(),
        ];
        $cache['processed'][$fileId] = true;
        $cache['failed'][] = end($failedFiles);
        write_extract_cache($cachePath, $cache, $items, $customers);
    } finally {
        @unlink($tmpFile);
    }
}

$groups = group_similar_items($items);
$customerGroups = group_similar_customers($customers);

usort($groups, static function (array $a, array $b): int {
    return strcasecmp($a['canonical_name'], $b['canonical_name']);
});
usort($customerGroups, static function (array $a, array $b): int {
    return strcasecmp($a['canonical_name'], $b['canonical_name']);
});

write_master_barang_csv($outputDir . '/master-barang.csv', $groups);
write_alias_csv($outputDir . '/master-barang-alias.csv', $groups);
write_master_customer_csv($outputDir . '/master-customer.csv', $customerGroups);
write_customer_alias_csv($outputDir . '/master-customer-alias.csv', $customerGroups);
write_failed_csv($outputDir . '/master-barang-gagal.csv', array_merge($cache['failed'], $failedFiles));

echo PHP_EOL;
echo 'File invoice XLSX: ' . count($xlsxFiles) . PHP_EOL;
echo 'Baris item terbaca: ' . count($items) . PHP_EOL;
echo 'Master barang unik: ' . count($groups) . PHP_EOL;
echo 'Customer invoice terbaca: ' . count($customers) . PHP_EOL;
echo 'Master customer unik: ' . count($customerGroups) . PHP_EOL;
echo 'File gagal: ' . count($failedFiles) . PHP_EOL;
echo 'Output: storage/generated/master-barang.csv' . PHP_EOL;
echo 'Alias: storage/generated/master-barang-alias.csv' . PHP_EOL;
echo 'Customer: storage/generated/master-customer.csv' . PHP_EOL;

function read_xlsx_rows(string $filePath): array
{
    $zip = new ZipArchive();

    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('File XLSX tidak bisa dibuka.');
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
        throw new RuntimeException('sheet1.xml tidak ditemukan.');
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];

    foreach ($sheet->sheetData->row as $row) {
        $rowIndex = (int) $row['r'];
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

        $rows[$rowIndex] = $values;
    }

    return $rows;
}

function read_extract_cache(string $path): array
{
    if (! is_readable($path)) {
        return [
            'processed' => [],
            'items' => [],
            'customers' => [],
            'failed' => [],
        ];
    }

    $data = json_decode((string) file_get_contents($path), true);

    if (! is_array($data)) {
        return [
            'processed' => [],
            'items' => [],
            'customers' => [],
            'failed' => [],
        ];
    }

    return [
        'processed' => $data['processed'] ?? [],
        'items' => $data['items'] ?? [],
        'customers' => $data['customers'] ?? [],
        'failed' => $data['failed'] ?? [],
    ];
}

function write_extract_cache(string $path, array $cache, array $items, array $customers): void
{
    file_put_contents($path, json_encode([
        'processed' => $cache['processed'],
        'items' => $items,
        'customers' => $customers,
        'failed' => $cache['failed'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function extract_invoice_items(array $rows, string $fileName, string $fileId): array
{
    $headerRow = null;

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $value) {
            if (strtoupper(trim((string) $value)) === 'NAMA BARANG') {
                $headerRow = $rowIndex;
                break 2;
            }
        }
    }

    $startRow = $headerRow ?? 1;

    $items = [];

    foreach ($rows as $rowIndex => $row) {
        if ($rowIndex <= $startRow) {
            continue;
        }

        $name = normalize_spaces($row['B'] ?? '');
        $marker = strtoupper(normalize_spaces(($row['A'] ?? '') . ' ' . ($row['F'] ?? '') . ' ' . ($row['G'] ?? '')));

        if ($name === '') {
            continue;
        }

        if (str_contains($marker, 'SUB TOTAL') || str_contains($marker, 'SUBTOTAL') || str_contains($marker, 'TOTAL')) {
            break;
        }

        if (! preg_match('/^\d+(\.0+)?$/', trim((string) ($row['A'] ?? '')))) {
            continue;
        }

        $normalizedName = normalize_item_name($name);

        $items[] = [
            'source_file' => $fileName,
            'source_file_id' => $fileId,
            'row' => $rowIndex,
            'raw_name' => $name,
            'normalized_name' => $normalizedName,
            'isi' => normalize_spaces($row['C'] ?? ''),
            'jumlah' => normalize_spaces($row['D'] ?? ''),
            'satuan' => normalize_spaces($row['E'] ?? ''),
            'variant_size' => item_variant_size($row['C'] ?? '', $row['E'] ?? '', $normalizedName),
            'harga' => normalize_number($row['F'] ?? ''),
            'total' => normalize_number($row['G'] ?? ''),
        ];
    }

    return $items;
}

function extract_invoice_customer(array $rows, string $fileName, string $fileId): ?array
{
    $customerName = '';
    $address = '';
    $invoiceNumber = '';
    $invoiceDate = '';

    foreach ($rows as $row) {
        $labelA = strtoupper(normalize_spaces($row['A'] ?? ''));
        $labelE = strtoupper(normalize_spaces($row['E'] ?? ''));

        if ($labelA === 'KEPADA') {
            $customerName = clean_label_value($row['B'] ?? '');
        }

        if ($labelA === 'ALAMAT') {
            $address = clean_label_value($row['B'] ?? '');
        }

        if ($labelE === 'NO. INVOICE' || $labelE === 'NO INVOICE') {
            $invoiceNumber = clean_label_value($row['F'] ?? '');
        }

        if ($labelE === 'TANGGAL') {
            $invoiceDate = clean_label_value($row['F'] ?? '');
        }
    }

    if ($customerName === '') {
        return null;
    }

    return [
        'source_file' => $fileName,
        'source_file_id' => $fileId,
        'raw_name' => $customerName,
        'normalized_name' => normalize_customer_name($customerName),
        'address' => $address,
        'invoice_number' => $invoiceNumber,
        'invoice_date' => $invoiceDate,
    ];
}

function group_similar_customers(array $customers): array
{
    $groups = [];

    foreach ($customers as $customer) {
        $bestIndex = null;
        $bestScore = 0;

        foreach ($groups as $index => $group) {
            $score = item_similarity_score($customer['normalized_name'], $group['normalized_key']);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        if ($bestIndex !== null && $bestScore >= 92) {
            $groups[$bestIndex]['customers'][] = $customer;
            $groups[$bestIndex]['aliases'][$customer['raw_name']] = true;

            if ($customer['address'] !== '') {
                $groups[$bestIndex]['addresses'][$customer['address']] = true;
            }

            continue;
        }

        $groups[] = [
            'normalized_key' => $customer['normalized_name'],
            'canonical_name' => canonical_customer_name($customer['raw_name']),
            'customers' => [$customer],
            'aliases' => [$customer['raw_name'] => true],
            'addresses' => $customer['address'] !== '' ? [$customer['address'] => true] : [],
        ];
    }

    foreach ($groups as &$group) {
        $nameCounts = [];
        $addressCounts = [];

        foreach ($group['customers'] as $customer) {
            $name = canonical_customer_name($customer['raw_name']);
            $address = normalize_spaces($customer['address']);
            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;

            if ($address !== '') {
                $addressCounts[$address] = ($addressCounts[$address] ?? 0) + 1;
            }
        }

        arsort($nameCounts);
        arsort($addressCounts);

        $group['canonical_name'] = array_key_first($nameCounts) ?: $group['canonical_name'];
        $group['default_address'] = array_key_first($addressCounts) ?: '';
        $group['invoice_count'] = count(array_unique(array_column($group['customers'], 'source_file')));
    }

    return $groups;
}

function group_similar_items(array $items): array
{
    $groups = [];

    foreach ($items as $item) {
        $bestIndex = null;
        $bestScore = 0;

        foreach ($groups as $index => $group) {
            if ($item['variant_size'] !== $group['variant_size']) {
                continue;
            }

            $score = item_similarity_score($item['normalized_name'], $group['normalized_key']);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        if ($bestIndex !== null && $bestScore >= 94) {
            $groups[$bestIndex]['items'][] = $item;
            $groups[$bestIndex]['aliases'][$item['raw_name']] = true;
            $groups[$bestIndex]['normalized_aliases'][$item['normalized_name']] = true;
            continue;
        }

        $groups[] = [
            'normalized_key' => $item['normalized_name'],
            'canonical_name' => canonical_item_name($item['raw_name']),
            'variant_size' => $item['variant_size'],
            'items' => [$item],
            'aliases' => [$item['raw_name'] => true],
            'normalized_aliases' => [$item['normalized_name'] => true],
        ];
    }

    foreach ($groups as &$group) {
        $nameCounts = [];
        $priceCounts = [];
        $packCounts = [];
        $unitCounts = [];

        foreach ($group['items'] as $item) {
            $name = canonical_item_name($item['raw_name']);
            $pack = $item['isi'];
            $unit = $item['satuan'];
            $price = $item['harga'];

            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;

            if ($pack !== '') {
                $packCounts[$pack] = ($packCounts[$pack] ?? 0) + 1;
            }

            if ($unit !== '') {
                $unitCounts[$unit] = ($unitCounts[$unit] ?? 0) + 1;
            }

            if ($price !== '') {
                $priceCounts[$price] = ($priceCounts[$price] ?? 0) + 1;
            }
        }

        arsort($nameCounts);
        arsort($packCounts);
        arsort($unitCounts);
        arsort($priceCounts);

        $group['canonical_name'] = array_key_first($nameCounts) ?: $group['canonical_name'];

        if ($group['normalized_key'] === 'ANTI KARAT') {
            $group['canonical_name'] = 'Anti Karat';
        }

        $group['default_isi'] = array_key_first($packCounts) ?: '';
        $group['default_satuan'] = array_key_first($unitCounts) ?: '';
        $group['default_harga'] = array_key_first($priceCounts) ?: '';
        $group['invoice_count'] = count(array_unique(array_column($group['items'], 'source_file')));
        $group['transaction_count'] = count($group['items']);
    }

    return $groups;
}

function item_similarity_score(string $left, string $right): float
{
    if ($left === $right) {
        return 100;
    }

    similar_text($left, $right, $percent);

    $leftTokens = token_sort($left);
    $rightTokens = token_sort($right);
    similar_text($leftTokens, $rightTokens, $tokenPercent);

    return max($percent, $tokenPercent);
}

function normalize_item_name(string $name): string
{
    $name = strtoupper($name);
    $name = str_replace(['&', '+'], ' DAN ', $name);
    $name = preg_replace('/[^A-Z0-9]+/', ' ', $name);

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
        'PARFUME' => 'PARFUM',
        'PERFUME' => 'PARFUM',
        'LITER' => 'L',
        'LTR' => 'L',
    ];

    $words = explode(' ', normalize_spaces($name));
    $normalizedWords = [];

    foreach ($words as $word) {
        $normalizedWords[] = $replacements[$word] ?? $word;
    }

    $normalizedName = normalize_spaces(implode(' ', $normalizedWords));
    $normalizedName = preg_replace('/\bMC BLEACH LIQUID\b/', 'MC BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bRUST GONE KARAT\b/', 'ANTI KARAT', $normalizedName);

    return normalize_spaces($normalizedName);
}

function item_variant_size(string $isi, string $satuan, string $normalizedName = ''): string
{
    $isi = normalize_size($isi);
    $satuan = normalize_size($satuan);

    if ($normalizedName === 'N IRON') {
        $isi = str_replace(' L', ' KG', $isi);
        $satuan = str_replace(' L', ' KG', $satuan);
    }

    if (preg_match('/^\d+(?:\.\d+)?\s+(L|KG)$/', $satuan)) {
        return $satuan;
    }

    return $isi;
}

function normalize_size(string $value): string
{
    $value = strtoupper(normalize_spaces($value));
    $value = str_replace(',', '.', $value);
    $value = preg_replace('/\bLITER\b/', 'L', $value);
    $value = preg_replace('/\bLTR\b/', 'L', $value);
    $value = preg_replace('/\bKILO\b/', 'KG', $value);
    $value = preg_replace('/\bKILOGRAM\b/', 'KG', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
}

function normalize_customer_name(string $name): string
{
    $name = strtoupper($name);
    $name = preg_replace('/\b(BAPAK|BPK|PAK|IBU|BU|MR|MRS|MS)\b\.?/i', ' ', $name);
    $name = str_replace(['&', '+'], ' DAN ', $name);
    $name = preg_replace('/[^A-Z0-9]+/', ' ', $name);

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

function canonical_item_name(string $name): string
{
    return ucwords(strtolower(normalize_spaces($name)));
}

function canonical_customer_name(string $name): string
{
    return ucwords(strtolower(normalize_spaces($name)));
}

function clean_label_value(string $value): string
{
    return normalize_spaces(ltrim($value, ":\t\n\r\0\x0B "));
}

function token_sort(string $value): string
{
    $tokens = array_filter(explode(' ', normalize_spaces($value)));
    sort($tokens, SORT_STRING);

    return implode(' ', $tokens);
}

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function normalize_number(string $value): string
{
    $value = preg_replace('/[^0-9.-]/', '', $value);

    return $value === null ? '' : $value;
}

function write_master_barang_csv(string $path, array $groups): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, [
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
    ]);

    foreach ($groups as $index => $group) {
        fputcsv($handle, [
            'BRG-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            $group['canonical_name'],
            $group['variant_size'],
            $group['default_isi'],
            $group['default_satuan'],
            $group['default_harga'],
            count($group['aliases']),
            $group['transaction_count'],
            $group['invoice_count'],
            implode(' | ', array_keys($group['aliases'])),
        ]);
    }

    fclose($handle);
}

function write_alias_csv(string $path, array $groups): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, [
        'kode_barang',
        'nama_master',
        'ukuran_master',
        'nama_di_invoice',
        'isi',
        'satuan',
        'harga',
        'file_invoice',
        'baris',
    ]);

    foreach ($groups as $index => $group) {
        $code = 'BRG-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

        foreach ($group['items'] as $item) {
            fputcsv($handle, [
                $code,
                $group['canonical_name'],
                $group['variant_size'],
                $item['raw_name'],
                $item['isi'],
                $item['satuan'],
                $item['harga'],
                $item['source_file'],
                $item['row'],
            ]);
        }
    }

    fclose($handle);
}

function write_failed_csv(string $path, array $failedFiles): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, ['file', 'error']);

    foreach ($failedFiles as $failedFile) {
        fputcsv($handle, [$failedFile['file'], $failedFile['error']]);
    }

    fclose($handle);
}

function write_master_customer_csv(string $path, array $groups): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, [
        'kode_customer',
        'nama_customer',
        'alamat_default',
        'jumlah_alias',
        'jumlah_invoice',
        'alias',
        'alamat_lain',
    ]);

    foreach ($groups as $index => $group) {
        fputcsv($handle, [
            'CST-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            $group['canonical_name'],
            $group['default_address'],
            count($group['aliases']),
            $group['invoice_count'],
            implode(' | ', array_keys($group['aliases'])),
            implode(' | ', array_keys($group['addresses'])),
        ]);
    }

    fclose($handle);
}

function write_customer_alias_csv(string $path, array $groups): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, [
        'kode_customer',
        'nama_master',
        'nama_di_invoice',
        'alamat',
        'nomor_invoice',
        'tanggal_invoice',
        'file_invoice',
    ]);

    foreach ($groups as $index => $group) {
        $code = 'CST-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

        foreach ($group['customers'] as $customer) {
            fputcsv($handle, [
                $code,
                $group['canonical_name'],
                $customer['raw_name'],
                $customer['address'],
                $customer['invoice_number'],
                $customer['invoice_date'],
                $customer['source_file'],
            ]);
        }
    }

    fclose($handle);
}
