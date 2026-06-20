<?php

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function google_sheet_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/google-sheet.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function google_drive_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/google-drive.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function database_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/database.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function db(): ?PDO
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo;
    }

    $config = database_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable) {
        $pdo = null;
    }

    return $pdo;
}

function db_all(string $sql, array $params = []): ?array
{
    $pdo = db();

    if ($pdo === null) {
        return null;
    }

    try {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    } catch (Throwable) {
        return null;
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $baseUrl = rtrim(base_url(), '/');
    $path = '/' . ltrim($path, '/');

    return $baseUrl . ($path === '/' ? '' : $path);
}

function base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $directory = rtrim(str_replace('/public', '', dirname($scriptName)), '/');

    return $directory === '/' ? '' : $directory;
}

function base_url(): string
{
    $configuredUrl = app_config('base_url');

    if ($configuredUrl) {
        return $configuredUrl;
    }

    $isHttps = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    return $scheme . '://' . $host . base_path();
}

function route_is(string $path): bool
{
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = base_path();

    if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
        $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
    }

    return rtrim($currentPath, '/') === rtrim($path, '/') || ($currentPath === '/' && $path === '/');
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);

    require dirname(__DIR__) . '/views/layouts/app.php';
}

function fetch_master_barang(): array
{
    $dbItems = db_all('SELECT kode_barang, nama_barang, ukuran, isi_default, satuan_default, harga_default, jumlah_alias, jumlah_transaksi, jumlah_invoice, alias FROM master_barang ORDER BY nama_barang, ukuran');

    if ($dbItems !== null) {
        return [
            'ok' => true,
            'items' => $dbItems,
            'summary' => [
                'total_barang' => count($dbItems),
                'total_transaksi' => array_sum(array_map(static fn (array $item): int => (int) ($item['jumlah_transaksi'] ?? 0), $dbItems)),
                'total_invoice' => array_sum(array_map(static fn (array $item): int => (int) ($item['jumlah_invoice'] ?? 0), $dbItems)),
            ],
            'error' => null,
        ];
    }

    $path = dirname(__DIR__) . '/storage/generated/master-barang.csv';

    if (! is_readable($path)) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [
                'total_barang' => 0,
                'total_transaksi' => 0,
                'total_invoice' => 0,
            ],
            'error' => 'File master barang belum tersedia. Jalankan scripts/generate-master-barang.php terlebih dahulu.',
        ];
    }

    $handle = fopen($path, 'r');
    $headers = fgetcsv($handle) ?: [];
    $items = [];

    while (($row = fgetcsv($handle)) !== false) {
        $item = array_combine($headers, $row);

        if ($item === false) {
            continue;
        }

        $items[] = $item;
    }

    fclose($handle);

    return [
        'ok' => true,
        'items' => $items,
        'summary' => [
            'total_barang' => count($items),
            'total_transaksi' => array_sum(array_map(static fn (array $item): int => (int) ($item['jumlah_transaksi'] ?? 0), $items)),
            'total_invoice' => array_sum(array_map(static fn (array $item): int => (int) ($item['jumlah_invoice'] ?? 0), $items)),
        ],
        'error' => null,
    ];
}

function fetch_master_customer(): array
{
    $dbItems = db_all('SELECT kode_customer, nama_customer, nama_laundry, no_telepon, alamat_default, jumlah_alias, jumlah_invoice, alias, alamat_lain FROM master_customers ORDER BY nama_laundry');

    if ($dbItems !== null) {
        return [
            'ok' => true,
            'items' => $dbItems,
            'summary' => [
                'total_customer' => count($dbItems),
                'total_invoice' => array_sum(array_map(static fn (array $item): int => (int) ($item['jumlah_invoice'] ?? 0), $dbItems)),
                'total_dengan_telepon' => count(array_filter($dbItems, static fn (array $item): bool => trim((string) ($item['no_telepon'] ?? '')) !== '')),
            ],
            'error' => null,
        ];
    }

    $path = dirname(__DIR__) . '/storage/generated/master-customer.csv';

    if (! is_readable($path)) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [
                'total_customer' => 0,
                'total_invoice' => 0,
                'total_dengan_telepon' => 0,
            ],
            'error' => 'File master customer belum tersedia. Jalankan scripts/generate-master-barang.php terlebih dahulu.',
        ];
    }

    $handle = fopen($path, 'r');
    $headers = fgetcsv($handle) ?: [];
    $items = [];

    while (($row = fgetcsv($handle)) !== false) {
        $item = array_combine($headers, $row);

        if ($item === false) {
            continue;
        }

        $items[] = $item;
    }

    fclose($handle);

    return [
        'ok' => true,
        'items' => $items,
        'summary' => [
            'total_customer' => count($items),
            'total_invoice' => array_sum(array_map(static fn (array $item): int => (int) ($item['jumlah_invoice'] ?? 0), $items)),
            'total_dengan_telepon' => count(array_filter($items, static fn (array $item): bool => trim((string) ($item['no_telepon'] ?? '')) !== '')),
        ],
        'error' => null,
    ];
}

function fetch_master_sales(): array
{
    $items = db_all('SELECT kode_sales, nama_sales FROM master_sales ORDER BY kode_sales');

    if ($items === null) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [
                'total_sales' => 0,
            ],
            'error' => 'Tabel master_sales belum tersedia. Jalankan scripts/seed-sales.php terlebih dahulu.',
        ];
    }

    return [
        'ok' => true,
        'items' => $items,
        'summary' => [
            'total_sales' => count($items),
        ],
        'error' => null,
    ];
}

function fetch_invoice_mapping(array $filters = []): array
{
    $invoicePath = dirname(__DIR__) . '/storage/generated/invoices-2025-jan-jun-2026.csv';
    $itemPath = dirname(__DIR__) . '/storage/generated/invoice-items-2025-jan-jun-2026.csv';
    $dbInvoices = db_all('SELECT kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, nama_customer_master, nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat, total_item, total_qty, subtotal, total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, file_invoice FROM invoices ORDER BY kode_invoice');
    $dbDetails = db_all('SELECT kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang, nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice, jumlah, satuan, harga, total, file_invoice, baris FROM invoice_items ORDER BY kode_invoice, baris');

    if ($dbInvoices !== null && $dbDetails !== null) {
        $invoices = $dbInvoices;
        $details = $dbDetails;
    } elseif (! is_readable($invoicePath) || ! is_readable($itemPath)) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [
                'total_invoice' => 0,
                'total_detail' => 0,
                'subtotal' => 0,
            ],
            'error' => 'File invoice mapping belum tersedia. Jalankan scripts/generate-invoice-data.php terlebih dahulu.',
        ];
    } else {
        $invoices = read_csv_rows($invoicePath);
        $details = read_csv_rows($itemPath);
    }

    $yearOptions = invoice_year_options($invoices);
    $laundryOptions = invoice_laundry_options($invoices);
    $month = trim((string) ($filters['month'] ?? ''));
    $year = trim((string) ($filters['year'] ?? ''));
    $laundry = trim((string) ($filters['laundry'] ?? ''));
    $sort = trim((string) ($filters['sort'] ?? ''));
    $direction = strtolower(trim((string) ($filters['direction'] ?? '')));
    $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

    $filteredInvoices = array_values(array_filter($invoices, static function (array $invoice) use ($month, $year, $laundry): bool {
        if ($month !== '' && invoice_month_number((string) ($invoice['nomor_invoice'] ?? '')) !== (int) $month) {
            return false;
        }

        if ($year !== '' && invoice_year((string) ($invoice['nomor_invoice'] ?? '')) !== $year) {
            return false;
        }

        if ($laundry !== '') {
            $haystack = strtoupper(implode(' ', [
                $invoice['nama_laundry_invoice'] ?? '',
                $invoice['nama_customer_master'] ?? '',
                $invoice['nama_customer_invoice'] ?? '',
            ]));

            if (! str_contains($haystack, strtoupper($laundry))) {
                return false;
            }
        }

        return true;
    }));

    if ($sort === 'subtotal') {
        usort($filteredInvoices, static function (array $a, array $b) use ($direction): int {
            $comparison = ((float) ($a['subtotal'] ?? 0)) <=> ((float) ($b['subtotal'] ?? 0));

            if ($comparison === 0) {
                $comparison = strnatcasecmp((string) ($a['nomor_invoice'] ?? ''), (string) ($b['nomor_invoice'] ?? ''));
            }

            return $direction === 'asc' ? $comparison : -$comparison;
        });
    } else {
        sort_invoices_newest_first($filteredInvoices);
    }

    $invoiceCodes = array_fill_keys(array_map(
        static fn (array $invoice): string => (string) ($invoice['kode_invoice'] ?? ''),
        $filteredInvoices
    ), true);

    $filteredDetails = array_values(array_filter(
        $details,
        static fn (array $detail): bool => isset($invoiceCodes[(string) ($detail['kode_invoice'] ?? '')])
    ));
    $customerSummary = invoice_customer_summary($filteredInvoices);

    return [
        'ok' => true,
        'items' => $filteredInvoices,
        'customer_summary' => $customerSummary,
        'filters' => [
            'month' => $month,
            'year' => $year,
            'laundry' => $laundry,
            'sort' => $sort,
            'direction' => $direction,
        ],
        'options' => [
            'years' => $yearOptions,
            'laundries' => $laundryOptions,
        ],
        'summary' => [
            'total_invoice' => count($filteredInvoices),
            'total_detail' => count($filteredDetails),
            'subtotal' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['subtotal'] ?? 0), $filteredInvoices)),
            'total_pembelian_barang' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['total_pembelian_barang'] ?? 0), $filteredInvoices)),
            'total_utang_pembelian_barang' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['total_utang_pembelian_barang'] ?? 0), $filteredInvoices)),
            'total_invoice_utang' => count(array_filter($filteredInvoices, static fn (array $invoice): bool => (float) ($invoice['total_utang_pembelian_barang'] ?? 0) > 0)),
        ],
        'error' => null,
    ];
}

function invoice_customer_summary(array $invoices): array
{
    $summary = [];

    foreach ($invoices as $invoice) {
        $customerCode = (string) ($invoice['kode_customer'] ?? '');
        $key = $customerCode !== '' ? $customerCode : (string) ($invoice['nama_customer_master'] ?? $invoice['nama_laundry_invoice'] ?? '');

        if ($key === '') {
            continue;
        }

        if (! isset($summary[$key])) {
            $summary[$key] = [
                'kode_customer' => $customerCode,
                'nama_customer' => $invoice['nama_customer_master'] ?? '',
                'nama_laundry' => $invoice['nama_laundry_invoice'] ?? '',
                'jumlah_invoice' => 0,
                'total_item' => 0,
                'total_qty' => 0,
                'subtotal' => 0,
            ];
        }

        $summary[$key]['jumlah_invoice']++;
        $summary[$key]['total_item'] += (int) ($invoice['total_item'] ?? 0);
        $summary[$key]['total_qty'] += (float) ($invoice['total_qty'] ?? 0);
        $summary[$key]['subtotal'] += (float) ($invoice['subtotal'] ?? 0);
    }

    $summary = array_values($summary);
    usort($summary, static function (array $a, array $b): int {
        $comparison = ((float) ($b['subtotal'] ?? 0)) <=> ((float) ($a['subtotal'] ?? 0));

        if ($comparison !== 0) {
            return $comparison;
        }

        return strcasecmp((string) ($a['nama_laundry'] ?? ''), (string) ($b['nama_laundry'] ?? ''));
    });

    return $summary;
}

function fetch_invoice_detail(string $code): array
{
    $invoicePath = dirname(__DIR__) . '/storage/generated/invoices-2025-jan-jun-2026.csv';
    $itemPath = dirname(__DIR__) . '/storage/generated/invoice-items-2025-jan-jun-2026.csv';
    $invoiceRows = db_all(
        'SELECT kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, nama_customer_master, nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat, total_item, total_qty, subtotal, total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, file_invoice FROM invoices WHERE kode_invoice = :kode_invoice OR nomor_invoice = :nomor_invoice LIMIT 1',
        [
            'kode_invoice' => $code,
            'nomor_invoice' => $code,
        ]
    );

    if ($invoiceRows !== null && $invoiceRows !== []) {
        $invoice = $invoiceRows[0];
        $items = db_all(
            'SELECT kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang, nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice, jumlah, satuan, harga, total, file_invoice, baris FROM invoice_items WHERE kode_invoice = :kode_invoice ORDER BY baris',
            ['kode_invoice' => $invoice['kode_invoice']]
        ) ?? [];
    } elseif (! is_readable($invoicePath) || ! is_readable($itemPath)) {
        return [
            'ok' => false,
            'invoice' => null,
            'items' => [],
            'summary' => [],
            'error' => 'File invoice mapping belum tersedia.',
        ];
    } else {
        $invoice = null;
        foreach (read_csv_rows($invoicePath) as $row) {
            if (($row['kode_invoice'] ?? '') === $code || ($row['nomor_invoice'] ?? '') === $code) {
                $invoice = $row;
                break;
            }
        }

        if ($invoice === null) {
            return [
                'ok' => false,
                'invoice' => null,
                'items' => [],
                'summary' => [],
                'error' => 'Invoice tidak ditemukan.',
            ];
        }

        $items = array_values(array_filter(
            read_csv_rows($itemPath),
            static fn (array $item): bool => ($item['kode_invoice'] ?? '') === ($invoice['kode_invoice'] ?? '')
        ));
    }

    if ($invoice === null) {
        return [
            'ok' => false,
            'invoice' => null,
            'items' => [],
            'summary' => [],
            'error' => 'Invoice tidak ditemukan.',
        ];
    }

    $subtotal = array_sum(array_map(static fn (array $item): float => (float) ($item['total'] ?? 0), $items));
    $discount = 0;
    $total = $subtotal - $discount;
    $sourceTotals = invoice_totals_from_local_file((string) ($invoice['file_invoice'] ?? ''));

    if ($sourceTotals !== null) {
        $subtotal = $sourceTotals['subtotal'] ?? $subtotal;
        $discount = $sourceTotals['discount'] ?? $discount;
        $total = $sourceTotals['total'] ?? $total;
    }

    return [
        'ok' => true,
        'invoice' => $invoice,
        'items' => $items,
        'summary' => [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'terbilang' => ucwords(normalize_spaces(number_to_indonesian_words((int) $total))) . ' Rupiah',
        ],
        'error' => null,
    ];
}

function invoice_totals_from_local_file(string $sourceFile): ?array
{
    $path = find_local_invoice_file($sourceFile);

    if ($path === null || ! class_exists('ZipArchive')) {
        return null;
    }

    try {
        $rows = read_xlsx_rows_for_invoice_view($path);
    } catch (Throwable) {
        return null;
    }

    $totals = [
        'subtotal' => null,
        'discount' => null,
        'total' => null,
    ];

    foreach ($rows as $row) {
        $marker = strtoupper(normalize_spaces(implode(' ', array_map('strval', $row))));
        $amount = last_numeric_value($row);

        if ($amount === null) {
            continue;
        }

        if (str_contains($marker, 'SUB TOTAL') || str_contains($marker, 'SUBTOTAL')) {
            $totals['subtotal'] = $amount;
            continue;
        }

        if (str_contains($marker, 'DISC')) {
            $totals['discount'] = $amount;
            continue;
        }

        if (preg_match('/\bTOTAL\b/', $marker) === 1 && ! str_contains($marker, 'TOTAL (')) {
            $totals['total'] = $amount;
        }
    }

    if ($totals['subtotal'] === null && $totals['discount'] === null && $totals['total'] === null) {
        return null;
    }

    return [
        'subtotal' => (float) ($totals['subtotal'] ?? 0),
        'discount' => (float) ($totals['discount'] ?? 0),
        'total' => (float) ($totals['total'] ?? (($totals['subtotal'] ?? 0) - ($totals['discount'] ?? 0))),
    ];
}

function find_local_invoice_file(string $sourceFile): ?string
{
    $root = dirname(__DIR__) . '/storage/drive';
    $target = str_replace('/', '_', $sourceFile);
    $target = normalize_spaces($target);

    if ($target === '' || ! is_dir($root)) {
        return null;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($iterator as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'xlsx') {
            continue;
        }

        if (normalize_spaces($file->getFilename()) === $target) {
            return $file->getPathname();
        }
    }

    return null;
}

function read_xlsx_rows_for_invoice_view(string $filePath): array
{
    $zip = new ZipArchive();

    if ($zip->open($filePath) !== true) {
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

function last_numeric_value(array $row): ?float
{
    $numbers = [];

    foreach ($row as $value) {
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '.', (string) $value)) ?? '';

        if ($clean !== '' && is_numeric($clean)) {
            $numbers[] = (float) $clean;
        }
    }

    return $numbers === [] ? null : end($numbers);
}

function number_to_indonesian_words(int $number): string
{
    $number = abs($number);
    $words = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan',
        'sepuluh',
        'sebelas',
    ];

    if ($number < 12) {
        return $words[$number];
    }

    if ($number < 20) {
        return number_to_indonesian_words($number - 10) . ' belas';
    }

    if ($number < 100) {
        return number_to_indonesian_words(intdiv($number, 10)) . ' puluh ' . number_to_indonesian_words($number % 10);
    }

    if ($number < 200) {
        return 'seratus ' . number_to_indonesian_words($number - 100);
    }

    if ($number < 1000) {
        return number_to_indonesian_words(intdiv($number, 100)) . ' ratus ' . number_to_indonesian_words($number % 100);
    }

    if ($number < 2000) {
        return 'seribu ' . number_to_indonesian_words($number - 1000);
    }

    if ($number < 1000000) {
        return number_to_indonesian_words(intdiv($number, 1000)) . ' ribu ' . number_to_indonesian_words($number % 1000);
    }

    if ($number < 1000000000) {
        return number_to_indonesian_words(intdiv($number, 1000000)) . ' juta ' . number_to_indonesian_words($number % 1000000);
    }

    return number_to_indonesian_words(intdiv($number, 1000000000)) . ' milyar ' . number_to_indonesian_words($number % 1000000000);
}

function invoice_months(): array
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
    ];
}

function invoice_month_number(string $invoiceNumber): int
{
    if (! preg_match('~/BM-INV/([IVXLCDM]+)/~i', $invoiceNumber, $match)) {
        return 0;
    }

    return roman_month_to_number(strtoupper($match[1]));
}

function invoice_year(string $invoiceNumber): string
{
    if (! preg_match('~/(\d{4})$~', $invoiceNumber, $match)) {
        return '';
    }

    return $match[1];
}

function invoice_sequence_number(string $invoiceNumber): int
{
    if (! preg_match('~^(\d+)~', $invoiceNumber, $match)) {
        return 0;
    }

    return (int) $match[1];
}

function sort_invoices_newest_first(array &$invoices): void
{
    usort($invoices, static function (array $a, array $b): int {
        $aNumber = (string) ($a['nomor_invoice'] ?? '');
        $bNumber = (string) ($b['nomor_invoice'] ?? '');
        $comparison = ((int) invoice_year($bNumber)) <=> ((int) invoice_year($aNumber));

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = invoice_month_number($bNumber) <=> invoice_month_number($aNumber);

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = invoice_sequence_number($bNumber) <=> invoice_sequence_number($aNumber);

        if ($comparison !== 0) {
            return $comparison;
        }

        return strnatcasecmp((string) ($b['kode_invoice'] ?? ''), (string) ($a['kode_invoice'] ?? ''));
    });
}

function invoice_year_options(array $invoices): array
{
    $years = [];
    foreach ($invoices as $invoice) {
        $year = invoice_year((string) ($invoice['nomor_invoice'] ?? ''));
        if ($year !== '') {
            $years[$year] = true;
        }
    }

    $years = array_keys($years);
    rsort($years);

    return $years;
}

function invoice_laundry_options(array $invoices): array
{
    $laundries = [];
    foreach ($invoices as $invoice) {
        $name = trim((string) ($invoice['nama_laundry_invoice'] ?? ''));
        if ($name !== '') {
            $laundries[$name] = true;
        }
    }

    $laundries = array_keys($laundries);
    natcasesort($laundries);

    return array_values($laundries);
}

function roman_month_to_number(string $roman): int
{
    return [
        'I' => 1,
        'II' => 2,
        'III' => 3,
        'IV' => 4,
        'V' => 5,
        'VI' => 6,
        'VII' => 7,
        'VIII' => 8,
        'IX' => 9,
        'X' => 10,
        'XI' => 11,
        'XII' => 12,
    ][$roman] ?? 0;
}

function read_csv_rows(string $path): array
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

function normalize_spaces(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function rupiah(mixed $value): string
{
    $number = (float) ($value ?: 0);

    return 'Rp' . number_format($number, 0, ',', '.');
}

function fetch_google_sheet_rows(?string $spreadsheetId = null, ?string $gid = null): array
{
    $spreadsheetId = $spreadsheetId ?: google_sheet_config('spreadsheet_id');
    $gid = $gid ?: google_sheet_config('gid', '0');
    $accessMode = google_sheet_config('access_mode', 'service_account');

    if ($accessMode === 'service_account') {
        return fetch_private_google_sheet_rows($spreadsheetId, google_sheet_config('range', 'A:Z'));
    }

    $csvUrl = 'https://docs.google.com/spreadsheets/d/' . rawurlencode($spreadsheetId)
        . '/export?format=csv&gid=' . rawurlencode($gid);

    $response = http_get($csvUrl);

    if (! $response['ok']) {
        return [
            'ok' => false,
            'headers' => [],
            'rows' => [],
            'error' => $response['error'],
            'source_url' => $csvUrl,
        ];
    }

    $rows = parse_csv_string($response['body']);
    $headers = $rows[0] ?? [];
    $dataRows = array_slice($rows, 1);

    return [
        'ok' => true,
        'headers' => $headers,
        'rows' => $dataRows,
        'error' => null,
        'source_url' => $csvUrl,
    ];
}

function fetch_private_google_sheet_rows(string $spreadsheetId, string $range): array
{
    $token = google_service_account_access_token();

    if (! $token['ok']) {
        return [
            'ok' => false,
            'headers' => [],
            'rows' => [],
            'error' => $token['error'],
            'source_url' => null,
        ];
    }

    $apiUrl = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId)
        . '/values/' . rawurlencode($range) . '?majorDimension=ROWS';
    $response = http_get($apiUrl, [
        'Authorization: Bearer ' . $token['access_token'],
        'Accept: application/json',
    ]);

    if (! $response['ok']) {
        return [
            'ok' => false,
            'headers' => [],
            'rows' => [],
            'error' => $response['error'],
            'source_url' => $apiUrl,
        ];
    }

    $payload = json_decode($response['body'], true);

    if (! is_array($payload)) {
        return [
            'ok' => false,
            'headers' => [],
            'rows' => [],
            'error' => 'Response Google Sheets API tidak valid.',
            'source_url' => $apiUrl,
        ];
    }

    $values = $payload['values'] ?? [];
    $headers = normalize_sheet_headers($values[0] ?? []);
    $rows = array_slice($values, 1);

    return [
        'ok' => true,
        'headers' => $headers,
        'rows' => $rows,
        'error' => null,
        'source_url' => $apiUrl,
    ];
}

function normalize_sheet_headers(array $headers): array
{
    $columnCount = count($headers);
    $normalizedHeaders = [];

    for ($index = 0; $index < $columnCount; $index++) {
        $header = trim((string) ($headers[$index] ?? ''));
        $normalizedHeaders[] = $header !== '' ? $header : 'Kolom ' . spreadsheet_column_name($index);
    }

    return $normalizedHeaders;
}

function spreadsheet_column_name(int $index): string
{
    $name = '';
    $index++;

    while ($index > 0) {
        $remainder = ($index - 1) % 26;
        $name = chr(65 + $remainder) . $name;
        $index = intdiv($index - 1, 26);
    }

    return $name;
}

function fetch_google_drive_files(?string $folderId = null): array
{
    $folderId = $folderId ?: google_drive_config('folder_id');
    $token = google_service_account_access_token();

    if (! $token['ok']) {
        return [
            'ok' => false,
            'files' => [],
            'error' => $token['error'],
            'source_url' => null,
        ];
    }

    $query = sprintf("'%s' in parents and trashed = false", str_replace("'", "\\'", $folderId));
    $params = http_build_query([
        'q' => $query,
        'fields' => 'files(id,name,mimeType,webViewLink,webContentLink,size,modifiedTime,iconLink)',
        'orderBy' => 'folder,name_natural',
        'pageSize' => 1000,
        'supportsAllDrives' => 'true',
        'includeItemsFromAllDrives' => 'true',
    ]);
    $apiUrl = 'https://www.googleapis.com/drive/v3/files?' . $params;
    $response = http_get($apiUrl, [
        'Authorization: Bearer ' . $token['access_token'],
        'Accept: application/json',
    ]);

    if (! $response['ok']) {
        return [
            'ok' => false,
            'files' => [],
            'error' => $response['error'],
            'source_url' => $apiUrl,
        ];
    }

    $payload = json_decode($response['body'], true);

    if (! is_array($payload)) {
        return [
            'ok' => false,
            'files' => [],
            'error' => 'Response Google Drive API tidak valid.',
            'source_url' => $apiUrl,
        ];
    }

    return [
        'ok' => true,
        'files' => $payload['files'] ?? [],
        'error' => null,
        'source_url' => $apiUrl,
    ];
}

function is_google_drive_folder(array $file): bool
{
    return ($file['mimeType'] ?? '') === 'application/vnd.google-apps.folder';
}

function human_file_size(mixed $bytes): string
{
    if ($bytes === null || $bytes === '') {
        return '-';
    }

    $bytes = (float) $bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;

    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }

    return rtrim(rtrim(number_format($bytes, 2), '0'), '.') . ' ' . $units[$unitIndex];
}

function google_service_account_access_token(): array
{
    static $cachedToken = null;

    if ($cachedToken !== null && $cachedToken['expires_at'] > time() + 60) {
        return $cachedToken;
    }

    $credentialPath = google_service_account_path();

    if (! is_string($credentialPath) || ! is_readable($credentialPath)) {
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => 'File service account JSON belum tersedia atau tidak bisa dibaca: ' . $credentialPath,
        ];
    }

    $credentials = json_decode((string) file_get_contents($credentialPath), true);

    if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => 'Format service account JSON tidak valid.',
        ];
    }

    $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
    $issuedAt = time();
    $expiresAt = $issuedAt + 3600;
    $header = base64_url_encode(json_encode([
        'alg' => 'RS256',
        'typ' => 'JWT',
    ]));
    $claim = base64_url_encode(json_encode([
        'iss' => $credentials['client_email'],
        'scope' => implode(' ', [
            'https://www.googleapis.com/auth/spreadsheets.readonly',
            'https://www.googleapis.com/auth/drive.readonly',
        ]),
        'aud' => $tokenUri,
        'iat' => $issuedAt,
        'exp' => $expiresAt,
    ]));
    $unsignedJwt = $header . '.' . $claim;
    $signed = openssl_sign($unsignedJwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

    if (! $signed) {
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => 'Gagal membuat signature JWT service account.',
        ];
    }

    $jwt = $unsignedJwt . '.' . base64_url_encode($signature);
    $response = http_post_form($tokenUri, [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);

    if (! $response['ok']) {
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => $response['error'],
        ];
    }

    $payload = json_decode($response['body'], true);

    if (! is_array($payload) || empty($payload['access_token'])) {
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => 'Response token Google tidak valid.',
        ];
    }

    $cachedToken = [
        'ok' => true,
        'access_token' => $payload['access_token'],
        'expires_at' => $issuedAt + (int) ($payload['expires_in'] ?? 3600),
        'error' => null,
    ];

    return $cachedToken;
}

function google_service_account_path(): string
{
    $configuredPath = google_sheet_config('service_account_path');

    if (is_string($configuredPath) && is_readable($configuredPath)) {
        return $configuredPath;
    }

    $jsonFiles = glob(dirname(__DIR__) . '/storage/*.json') ?: [];

    return $jsonFiles[0] ?? (string) $configuredPath;
}

function base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function http_get(string $url, array $headers = []): array
{
    if (function_exists('curl_init')) {
        $lastError = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $curl = curl_init($url);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_DNS_CACHE_TIMEOUT => 120,
                CURLOPT_USERAGENT => app_config('name') . '/1.0',
                CURLOPT_HTTPHEADER => $headers,
            ]);

            $body = curl_exec($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($body !== false && $statusCode < 400) {
                return [
                    'ok' => true,
                    'body' => $body,
                    'error' => null,
                ];
            }

            $lastError = $error ?: 'Request gagal dengan status HTTP ' . $statusCode . '.';

            if ($attempt < 3) {
                sleep($attempt);
            }
        }

        return [
            'ok' => false,
            'body' => '',
            'error' => $lastError ?: 'Request gagal.',
        ];
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header' => 'User-Agent: ' . app_config('name') . '/1.0',
        ],
    ]);
    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        return [
            'ok' => false,
            'body' => '',
            'error' => 'Google Sheet tidak bisa dibaca. Aktifkan allow_url_fopen atau extension cURL di hosting.',
        ];
    }

    return [
        'ok' => true,
        'body' => $body,
        'error' => null,
    ];
}

function parse_csv_string(string $csv): array
{
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $csv);
    rewind($handle);

    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        if ($row === [null] || $row === false) {
            continue;
        }

        $rows[] = $row;
    }

    fclose($handle);

    return $rows;
}

function http_post_form(string $url, array $data): array
{
    if (! function_exists('curl_init')) {
        return [
            'ok' => false,
            'body' => '',
            'error' => 'Extension cURL wajib aktif untuk autentikasi Google Service Account.',
        ];
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
    ]);

    $body = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);

    curl_close($curl);

    if ($body === false || $statusCode >= 400) {
        return [
            'ok' => false,
            'body' => is_string($body) ? $body : '',
            'error' => $error ?: 'Request token Google gagal dengan status HTTP ' . $statusCode . '.',
        ];
    }

    return [
        'ok' => true,
        'body' => $body,
        'error' => null,
    ];
}
