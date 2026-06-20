<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/dedupe-cache-by-invoice.php input-cache.json output-cache.json\n");
    exit(1);
}

$input = $argv[1];
$output = $argv[2];

$cache = json_decode((string) file_get_contents($input), true);
if (! is_array($cache)) {
    fwrite(STDERR, "Cache tidak valid: {$input}\n");
    exit(1);
}

$inputCustomers = count($cache['customers'] ?? []);
$inputItems = count($cache['items'] ?? []);

$invoiceKey = static function (string $value): string {
    if (preg_match('~(\d+\s*/\s*BM-INV\s*/\s*[IVXLCDM]+\s*/\s*\d{4})~i', $value, $match)) {
        return strtoupper(preg_replace('~\s+~', '', $match[1]));
    }

    return strtoupper(trim($value));
};

$keptInvoices = [];
$keptSources = [];
$customers = [];
foreach (($cache['customers'] ?? []) as $customer) {
    $key = $invoiceKey((string) ($customer['invoice_number'] ?? $customer['source_file'] ?? ''));
    if ($key === '' || isset($keptInvoices[$key])) {
        continue;
    }

    $keptInvoices[$key] = true;
    $sourceKey = (string) ($customer['source_file_id'] ?? $customer['source_file'] ?? '');
    if ($sourceKey !== '') {
        $keptSources[$sourceKey] = true;
    }

    $customers[] = $customer;
}

$items = [];
foreach (($cache['items'] ?? []) as $item) {
    $sourceKey = (string) ($item['source_file_id'] ?? $item['source_file'] ?? '');
    if ($sourceKey !== '' && isset($keptSources[$sourceKey])) {
        $items[] = $item;
    }
}

$cache['customers'] = $customers;
$cache['items'] = $items;
$cache['processed'] = array_values(array_unique(array_map(
    static fn (array $customer): string => (string) ($customer['source_file_id'] ?? $customer['source_file'] ?? ''),
    $customers
)));

file_put_contents($output, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Input customers: {$inputCustomers}" . PHP_EOL;
echo "Input items: {$inputItems}" . PHP_EOL;
echo "Unique invoices: " . count($keptInvoices) . PHP_EOL;
echo "Output customers: " . count($customers) . PHP_EOL;
echo "Output items: " . count($items) . PHP_EOL;
echo "Output: {$output}" . PHP_EOL;
