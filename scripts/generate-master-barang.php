<?php

$nameContains = getenv('INVOICE_NAME_CONTAINS') ?: '';
$nameRegex = getenv('INVOICE_NAME_REGEX') ?: '';
$localDriveDir = getenv('LOCAL_DRIVE_DIR') ?: '';
$mergeCacheFiles = getenv('MERGE_CACHE_FILES') ?: '';
$outputSuffix = getenv('OUTPUT_SUFFIX') ?: '';

require dirname(__DIR__) . '/app/helpers.php';

$outputDir = dirname(__DIR__) . '/storage/generated';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

if ($mergeCacheFiles !== '') {
    $cacheFiles = array_filter(array_map('trim', explode(',', $mergeCacheFiles)));
    $items = [];
    $customers = [];
    $failedFiles = [];

    foreach ($cacheFiles as $cacheFile) {
        $cachePath = str_starts_with($cacheFile, '/')
            ? $cacheFile
            : $outputDir . '/' . $cacheFile;
        $cache = read_extract_cache($cachePath);
        $items = array_merge($items, $cache['items']);
        $customers = array_merge($customers, $cache['customers']);
        $failedFiles = array_merge($failedFiles, $cache['failed']);
    }

    write_outputs($outputDir, $outputSuffix, $items, $customers, $failedFiles, 0);

    exit;
}

if ($localDriveDir !== '') {
    $xlsxFiles = local_xlsx_files($localDriveDir);
} else {
    $drive = fetch_google_drive_files();

    if (! $drive['ok']) {
        fwrite(STDERR, 'Gagal membaca Google Drive: ' . $drive['error'] . PHP_EOL);
        exit(1);
    }

    $xlsxFiles = $drive['files'];
}

$xlsxFiles = array_values(array_filter($xlsxFiles, static function (array $file) use ($nameContains, $nameRegex): bool {
    if ($nameContains !== '' && ! str_contains(strtoupper($file['name'] ?? ''), strtoupper($nameContains))) {
        return false;
    }

    if ($nameRegex !== '' && preg_match('~' . str_replace('~', '\~', $nameRegex) . '~i', $file['name'] ?? '') !== 1) {
        return false;
    }

    return ($file['mimeType'] ?? '') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
}));

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
$token = $localDriveDir !== ''
    ? ['ok' => true, 'access_token' => null]
    : google_service_account_access_token();

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

    if (($file['local_path'] ?? '') !== '') {
        $response = [
            'ok' => true,
            'body' => file_get_contents($file['local_path']),
            'error' => null,
        ];
    } else {
        $downloadUrl = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($file['id']) . '?alt=media';
        $response = http_get($downloadUrl, [
            'Authorization: Bearer ' . $token['access_token'],
        ]);
    }

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

write_outputs($outputDir, $outputSuffix, $items, $customers, array_merge($cache['failed'], $failedFiles), count($xlsxFiles));

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

function local_xlsx_files(string $directory): array
{
    $root = realpath($directory);

    if ($root === false || ! is_dir($root)) {
        fwrite(STDERR, 'Folder lokal tidak ditemukan: ' . $directory . PHP_EOL);
        exit(1);
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($iterator as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'xlsx') {
            continue;
        }

        $path = $file->getPathname();
        $name = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

        $files[] = [
            'id' => md5($path),
            'name' => str_replace('_', '/', $name),
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'local_path' => $path,
        ];
    }

    usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

    return $files;
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

function write_outputs(string $outputDir, string $suffix, array $items, array $customers, array $failedFiles, int $invoiceFileCount): void
{
    $suffix = $suffix !== '' ? '-' . trim($suffix, '-') : '';
    [$items, $customers] = apply_manual_invoice_corrections($items, $customers);
    $groups = group_similar_items($items);
    $customerGroups = group_similar_customers($customers);

    usort($groups, static function (array $a, array $b): int {
        return strcasecmp($a['canonical_name'], $b['canonical_name']);
    });
    usort($customerGroups, static function (array $a, array $b): int {
        return strcasecmp($a['canonical_name'], $b['canonical_name']);
    });

    write_master_barang_csv($outputDir . '/master-barang' . $suffix . '.csv', $groups);
    write_alias_csv($outputDir . '/master-barang-alias' . $suffix . '.csv', $groups);
    write_master_customer_csv($outputDir . '/master-customer' . $suffix . '.csv', $customerGroups);
    write_customer_alias_csv($outputDir . '/master-customer-alias' . $suffix . '.csv', $customerGroups);
    write_failed_csv($outputDir . '/master-barang-gagal' . $suffix . '.csv', $failedFiles);

    echo PHP_EOL;
    echo 'File invoice XLSX: ' . $invoiceFileCount . PHP_EOL;
    echo 'Baris item terbaca: ' . count($items) . PHP_EOL;
    echo 'Master barang unik: ' . count($groups) . PHP_EOL;
    echo 'Customer invoice terbaca: ' . count($customers) . PHP_EOL;
    echo 'Master customer unik: ' . count($customerGroups) . PHP_EOL;
    echo 'File gagal: ' . count($failedFiles) . PHP_EOL;
    echo 'Output: storage/generated/master-barang' . $suffix . '.csv' . PHP_EOL;
    echo 'Alias: storage/generated/master-barang-alias' . $suffix . '.csv' . PHP_EOL;
    echo 'Customer: storage/generated/master-customer' . $suffix . '.csv' . PHP_EOL;
}

function apply_manual_invoice_corrections(array $items, array $customers): array
{
    $invoiceNumber = '273/BM-INV/I/2026';
    $sourceFile = $invoiceNumber . ' CLEAN POINT.xlsx';
    $sourceFileId = 'manual-correction-clean-point-273-2026-01';
    $hasJanuary2026 = false;

    foreach ($customers as $customer) {
        if (str_contains((string) ($customer['invoice_number'] ?? ''), '/BM-INV/I/2026')) {
            $hasJanuary2026 = true;
            break;
        }
    }

    if (! $hasJanuary2026) {
        foreach ($items as $item) {
            if (str_contains((string) ($item['source_file'] ?? ''), '/BM-INV/I/2026')) {
                $hasJanuary2026 = true;
                break;
            }
        }
    }

    if (! $hasJanuary2026) {
        return [$items, $customers];
    }

    $hasCustomer = false;
    foreach ($customers as $customer) {
        if (($customer['invoice_number'] ?? '') === $invoiceNumber) {
            $hasCustomer = true;
            break;
        }
    }

    if (! $hasCustomer) {
        $customers[] = [
            'source_file' => $sourceFile,
            'source_file_id' => $sourceFileId,
            'raw_name' => 'CLEAN POINT LAUNDRY',
            'normalized_name' => normalize_customer_name('CLEAN POINT LAUNDRY'),
            'laundry_name' => 'CLEAN POINT LAUNDRY',
            'contact_name' => 'Bp. Gandung',
            'phone' => '081238500058',
            'address' => 'Jl. Wahyu Graha No. 55 - Buduk',
            'invoice_number' => $invoiceNumber,
            'invoice_date' => '20 Januari 2026',
        ];
    }

    $hasItem = false;
    foreach ($items as $item) {
        if (($item['source_file'] ?? '') === $sourceFile && normalize_item_name((string) ($item['raw_name'] ?? '')) === 'N IRON') {
            $hasItem = true;
            break;
        }
    }

    if (! $hasItem) {
        $items[] = [
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

    return [$items, $customers];
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
    $laundryName = '';
    $contactName = '';
    $address = '';
    $phone = '';
    $invoiceNumber = '';
    $invoiceDate = '';

    foreach ($rows as $row) {
        $labelA = strtoupper(normalize_spaces($row['A'] ?? ''));
        $labelE = strtoupper(normalize_spaces($row['E'] ?? ''));

        if ($labelA === 'KEPADA') {
            $laundryName = clean_label_value($row['B'] ?? '');
        }

        if ($labelA === 'ALAMAT') {
            $address = clean_label_value($row['B'] ?? '');
        }

        if ($labelA === 'UP.' || $labelA === 'UP') {
            $contactName = clean_label_value($row['B'] ?? '');
            $phone = $phone ?: extract_phone_number($contactName);
            $contactName = strip_phone_from_name($contactName);
        }

        if ($labelE === 'NO. INVOICE' || $labelE === 'NO INVOICE') {
            $invoiceNumber = clean_label_value($row['F'] ?? '');
        }

        if ($labelE === 'TANGGAL') {
            $invoiceDate = clean_label_value($row['F'] ?? '');
        }

        foreach ($row as $value) {
            if (preg_match('/\b(hp|wa|telp|telepon|phone)\b/i', (string) $value)) {
                $phone = $phone ?: extract_phone_number((string) $value);
            }
        }
    }

    if ($laundryName === '') {
        return null;
    }

    return [
        'source_file' => $fileName,
        'source_file_id' => $fileId,
        'raw_name' => $laundryName,
        'normalized_name' => normalize_customer_name($laundryName),
        'laundry_name' => $laundryName,
        'contact_name' => $contactName,
        'phone' => $phone,
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
        $normalizedName = normalize_customer_name($customer['raw_name']);

        foreach ($groups as $index => $group) {
            $score = item_similarity_score($normalizedName, $group['normalized_key']);

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
            'normalized_key' => $normalizedName,
            'canonical_name' => canonical_customer_name($customer['raw_name']),
            'customers' => [$customer],
            'aliases' => [$customer['raw_name'] => true],
            'addresses' => $customer['address'] !== '' ? [$customer['address'] => true] : [],
        ];
    }

    foreach ($groups as &$group) {
        $nameCounts = [];
        $addressCounts = [];
        $contactCounts = [];
        $phoneCounts = [];

        foreach ($group['customers'] as $customer) {
            $name = canonical_customer_name($customer['raw_name']);
            $address = normalize_spaces($customer['address']);
            $contact = normalize_spaces($customer['contact_name'] ?? '');
            $phone = normalize_spaces($customer['phone'] ?? '');
            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;

            if ($address !== '') {
                $addressCounts[$address] = ($addressCounts[$address] ?? 0) + 1;
            }

            if ($contact !== '') {
                $contactCounts[$contact] = ($contactCounts[$contact] ?? 0) + 1;
            }

            if ($phone !== '') {
                $phoneCounts[$phone] = ($phoneCounts[$phone] ?? 0) + 1;
            }
        }

        arsort($nameCounts);
        arsort($addressCounts);
        arsort($contactCounts);
        arsort($phoneCounts);

        $group['canonical_name'] = array_key_first($nameCounts) ?: $group['canonical_name'];
        $group['default_address'] = array_key_first($addressCounts) ?: '';
        $group['default_contact'] = array_key_first($contactCounts) ?: '';
        $group['default_phone'] = array_key_first($phoneCounts) ?: '';
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

        if (in_array($group['normalized_key'], ['OXO', 'OXO BLEACH'], true)) {
            $group['canonical_name'] = 'Oxo Bleach';
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
        'PAIN' => 'PINE',
        'PARFUME' => 'PARFUM',
        'PERFUME' => 'PARFUM',
        'LITER' => 'L',
        'LTR' => 'L',
    ];

    $words = explode(' ', normalize_spaces($name));
    $normalizedWords = [];

    foreach ($words as $word) {
        if ($word === 'COKRO') {
            continue;
        }

        $normalizedWords[] = $replacements[$word] ?? $word;
    }

    $normalizedName = normalize_spaces(implode(' ', $normalizedWords));
    $normalizedName = preg_replace('/\bMC BLEACH LIQUID\b/', 'MC BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bRUST GONE KARAT\b/', 'ANTI KARAT', $normalizedName);
    $normalizedName = preg_replace('/\bRUST GONE\b/', 'ANTI KARAT', $normalizedName);
    $normalizedName = preg_replace('/\bOMAXX OXO\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bOXO BLEACH OMAXX\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bOXO OMAXX\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\b0X0\b/', 'OXO BLEACH', $normalizedName);
    $normalizedName = preg_replace('/\bOXO\b(?!\s+BLEACH)/', 'OXO BLEACH', $normalizedName);

    return normalize_spaces($normalizedName);
}

function item_variant_size(string $isi, string $satuan, string $normalizedName = ''): string
{
    $isi = normalize_size($isi);
    $satuan = normalize_size($satuan);

    if ($normalizedName === 'N IRON') {
        $isi = str_replace(' L', ' KG', $isi);
        $satuan = str_replace(' L', ' KG', $satuan);

        if ($isi === '1 PAIL' || $satuan === '1 PAIL') {
            return '20 KG';
        }
    }

    if ($normalizedName === 'MC BLEACH' && ($isi === '1 PAIL' || $satuan === '1 PAIL')) {
        return '20 L';
    }

    if ($normalizedName === 'E 951' && ($isi === '1 PAIL' || $satuan === '1 PAIL')) {
        return '20 L';
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
    $value = preg_replace('/^(\d+)\.0$/', '$1 L', $value);
    $value = preg_replace('/^(\d+)$/', '$1 L', $value);

    return trim($value);
}

function normalize_customer_name(string $name): string
{
    $name = strtoupper($name);
    $name = preg_replace('/\bBE\s+TO\s+BE\b/i', 'B2B', $name);
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
        if ($word === 'COKRO') {
            continue;
        }

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

function extract_phone_number(string $value): string
{
    if (! preg_match('/(?:hp|wa|telp|telepon|phone)?\\s*:?\\s*(\\+?\\d[\\d\\s\\-().]{7,}\\d)/i', $value, $matches)) {
        return '';
    }

    $digits = preg_replace('/[^0-9+]/', '', $matches[1]);

    if ($digits === null || strlen(preg_replace('/\\D/', '', $digits)) < 8) {
        return '';
    }

    return $digits;
}

function strip_phone_from_name(string $value): string
{
    $value = preg_replace('/\\s*[-–—]?\\s*(?:hp|wa|telp|telepon|phone)?\\s*:?\\s*\\+?\\d[\\d\\s\\-().]{7,}\\d/i', '', $value);

    return normalize_spaces((string) $value);
}

function token_sort(string $value): string
{
    $tokens = array_filter(explode(' ', normalize_spaces($value)));
    sort($tokens, SORT_STRING);

    return implode(' ', $tokens);
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
        'nama_laundry',
        'no_telepon',
        'alamat_default',
        'jumlah_alias',
        'jumlah_invoice',
        'alias',
        'alamat_lain',
    ]);

    foreach ($groups as $index => $group) {
        fputcsv($handle, [
            'CST-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            $group['default_contact'],
            $group['canonical_name'],
            $group['default_phone'],
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
        'nama_customer',
        'nama_di_invoice',
        'no_telepon',
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
                $customer['contact_name'] ?? '',
                $customer['raw_name'],
                $customer['phone'] ?? '',
                $customer['address'],
                $customer['invoice_number'],
                $customer['invoice_date'],
                $customer['source_file'],
            ]);
        }
    }

    fclose($handle);
}
