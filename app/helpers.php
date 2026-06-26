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

    $currentPath = rtrim($currentPath, '/') ?: '/';
    $targetPath = rtrim($path, '/') ?: '/';

    if ($currentPath === $targetPath) {
        return true;
    }

    return $targetPath !== '/' && str_starts_with($currentPath . '/', $targetPath . '/');
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);

    require dirname(__DIR__) . '/views/layouts/app.php';
}

function fetch_database_maintenance(?string $action = null): array
{
    $result = null;
    $counts = database_table_counts();

    if ($action === 'update-hosting') {
        $result = run_hosting_update();
        $counts = database_table_counts();
    } elseif ($action === 'restore-local-snapshot') {
        $result = run_database_snapshot_restore();
        $counts = database_table_counts();
    } elseif ($action === 'update-hosting-latest') {
        $result = run_hosting_latest_data_update();
        $counts = database_table_counts();
    } elseif ($action === 'update-latest') {
        $result = run_latest_update();
        $counts = database_table_counts();
    } elseif ($action === 'update-2025-latest') {
        $result = run_2025_latest_update();
        $counts = database_table_counts();
    } elseif ($action === 'update-2026-operational-latest') {
        $result = run_2026_operational_latest_update();
        $counts = database_table_counts();
    } elseif ($action === 'update-pnl-sales-commission') {
        $result = run_pnl_sales_commission_update();
        $counts = database_table_counts();
    } elseif ($action === 'seed-krisna-april-bonus') {
        $result = run_seed_krisna_april_bonus_status();
        $counts = database_table_counts();
    } elseif ($action === 'migrate-seed') {
        $result = run_database_migration_seed();
        $counts = database_table_counts();
    } elseif ($action === 'seed-operational') {
        $result = run_database_operational_seed();
        $counts = database_table_counts();
    } elseif ($action === 'update-manager-commission') {
        $result = run_update_manager_commission();
        $counts = database_table_counts();
    } elseif ($action === 'update-commission') {
        $result = run_update_all_commission();
        $counts = database_table_counts();
    } elseif ($action === 'generate-invoice-data') {
        $result = run_invoice_data_generation();
        $counts = database_table_counts();
    } elseif ($action === 'generate-accounting-journals') {
        $result = run_accounting_journal_generation();
        $counts = database_table_counts();
    } elseif ($action === 'create-test-data') {
        $result = run_create_test_data();
        $counts = database_table_counts();
    } elseif ($action === 'delete-test-data') {
        $result = run_delete_test_data();
        $counts = database_table_counts();
    }

    return [
        'ok' => true,
        'seed_file' => database_seed_file_info(),
        'table_counts' => $counts,
        'database_connected' => db() !== null,
        'result' => $result,
    ];
}

function run_update_manager_commission(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok'         => false,
            'message'    => 'File Excel storage/PENJUALAN-2026.xlsx tidak ditemukan.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        $cliPhp = 'C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe';
        if (file_exists($cliPhp)) {
            $scriptPath = dirname(__DIR__) . '/scripts/update-invoice-manager-commission.php';
            $cmd        = '"' . $cliPhp . '" -d extension=zip "' . $scriptPath . '" 2>&1';
            $output     = shell_exec($cmd);
            if (strpos((string) $output, 'updated successfully') !== false) {
                preg_match('/invoices updated.*?:\s*(\d+)/', (string) $output, $m);
                $count = isset($m[1]) ? (int) $m[1] : 0;
                return [
                    'ok'         => true,
                    'message'    => 'Update komisi manager berhasil (via CLI PHP).',
                    'statements' => $count,
                    'counts'     => database_table_counts(),
                ];
            } else {
                return [
                    'ok'         => false,
                    'message'    => 'Update komisi manager gagal (via CLI PHP): ' . $output,
                    'statements' => 0,
                    'counts'     => database_table_counts(),
                ];
            }
        }

        return [
            'ok'         => false,
            'message'    => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif. Aktifkan extension=zip pada php.ini.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $count = update_invoice_manager_commission_from_excel($pdo, $excelPath);
        $journalResult = accounting_tables_ready($pdo)
            ? regenerate_all_accounting_journals($pdo)
            : ['lines' => 0];
        return [
            'ok'         => true,
            'message'    => 'Update komisi manager berhasil. ' . $count . ' invoice diperbarui.',
            'statements' => $count + $journalResult['lines'],
            'counts'     => database_table_counts(),
        ];
    } catch (Throwable $exception) {
        return [
            'ok'         => false,
            'message'    => 'Update komisi manager gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function update_invoice_manager_commission_from_excel(PDO $pdo, string $excelPath): int
{
    // Pastikan kolom tanggal_transfer_komisi_manager ada
    $cols         = $pdo->query('DESCRIBE invoices')->fetchAll(PDO::FETCH_COLUMN);
    $existingCols = array_flip($cols);
    if (! isset($existingCols['tanggal_transfer_komisi_manager'])) {
        $pdo->exec('ALTER TABLE invoices ADD COLUMN tanggal_transfer_komisi_manager DATE NULL AFTER komisi_manager_utang');
    }

    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');

    $statement = $pdo->prepare('
        UPDATE invoices
        SET komisi_manager_terbayar = :terbayar,
            komisi_manager_utang    = :utang,
            tanggal_transfer_komisi_manager = :tanggal
        WHERE nomor_invoice = :nomor_invoice
    ');

    $pdo->beginTransaction();
    $count = 0;
    foreach ($rows as $row) {
        $invoiceNo = trim((string) ($row['A'] ?? ''));
        if ($invoiceNo === '' || strcasecmp($invoiceNo, 'nomor invoice') === 0) {
            continue;
        }

        $terbayar  = manager_commission_parse_number($row['W'] ?? '');
        $utang     = manager_commission_parse_number($row['X'] ?? '');
        $tanggal   = manager_commission_excel_date(trim((string) ($row['Y'] ?? '')));

        if ($terbayar === 0.0 && $utang === 0.0 && $tanggal === null) {
            continue;
        }

        $statement->execute([
            'terbayar'       => $terbayar,
            'utang'          => $utang,
            'tanggal'        => $tanggal,
            'nomor_invoice'  => $invoiceNo,
        ]);

        if ($statement->rowCount() > 0) {
            $count++;
        }
    }
    $pdo->commit();
    return $count;
}

function manager_commission_parse_number(mixed $value): float
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0.0;
    }
    $value = str_replace(',', '.', $value);
    $clean = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';
    return is_numeric($clean) ? (float) $clean : 0.0;
}

function manager_commission_excel_date(string $value): ?string
{
    if ($value === '' || ! is_numeric($value)) {
        return null;
    }
    $serial = (int) round((float) $value);
    if ($serial <= 0) {
        return null;
    }
    if ($serial >= 60) {
        $serial--;
    }
    $unix = ($serial - 1) * 86400 + mktime(0, 0, 0, 1, 1, 1900);
    $year = (int) date('Y', $unix);
    if ($year < 2000 || $year > 2100) {
        return null;
    }
    return date('Y-m-d', $unix);
}

function run_update_all_commission(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok'         => false,
            'message'    => 'File Excel storage/PENJUALAN-2026.xlsx tidak ditemukan.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        $cliPhp = 'C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe';
        if (file_exists($cliPhp)) {
            $scriptPath = dirname(__DIR__) . '/scripts/update-invoice-commission.php';
            $cmd        = '"' . $cliPhp . '" -d extension=zip "' . $scriptPath . '" 2>&1';
            $output     = shell_exec($cmd);
            if (strpos((string) $output, 'updated successfully') !== false) {
                preg_match('/invoices updated.*?:\s*(\d+)/', (string) $output, $m);
                $count = isset($m[1]) ? (int) $m[1] : 0;
                return [
                    'ok'         => true,
                    'message'    => 'Update komisi berhasil (via CLI PHP).',
                    'statements' => $count,
                    'counts'     => database_table_counts(),
                ];
            } else {
                return [
                    'ok'         => false,
                    'message'    => 'Update komisi gagal (via CLI PHP): ' . $output,
                    'statements' => 0,
                    'counts'     => database_table_counts(),
                ];
            }
        }

        return [
            'ok'         => false,
            'message'    => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif. Aktifkan extension=zip pada php.ini.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $count = update_invoice_all_commission_from_excel($pdo, $excelPath);
        $journalResult = accounting_tables_ready($pdo)
            ? regenerate_all_accounting_journals($pdo)
            : ['lines' => 0];
        return [
            'ok'         => true,
            'message'    => 'Update komisi berhasil. ' . $count . ' invoice diperbarui.',
            'statements' => $count + $journalResult['lines'],
            'counts'     => database_table_counts(),
        ];
    } catch (Throwable $exception) {
        return [
            'ok'         => false,
            'message'    => 'Update komisi gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function update_invoice_all_commission_from_excel(PDO $pdo, string $excelPath): int
{
    // Pastikan semua kolom baru sudah ada
    $cols         = $pdo->query('DESCRIBE invoices')->fetchAll(PDO::FETCH_COLUMN);
    $existingCols = array_flip($cols);

    $newCols = [
        'komisi_sales_terbayar'          => "ADD COLUMN komisi_sales_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_sales_2_persen",
        'komisi_sales_belum_terbayar'    => "ADD COLUMN komisi_sales_belum_terbayar DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER komisi_sales_terbayar",
        'status_pembayaran_komisi_sales' => "ADD COLUMN status_pembayaran_komisi_sales VARCHAR(50) NULL AFTER komisi_sales_belum_terbayar",
        'tanggal_transfer_komisi_sales'  => "ADD COLUMN tanggal_transfer_komisi_sales DATE NULL AFTER status_pembayaran_komisi_sales",
        'tanggal_transfer_komisi_manager'=> "ADD COLUMN tanggal_transfer_komisi_manager DATE NULL AFTER komisi_manager_utang",
        'tanggal_transfer_komisi_admin'  => "ADD COLUMN tanggal_transfer_komisi_admin DATE NULL AFTER komisi_admin_belum_terbayar",
    ];

    foreach ($newCols as $colName => $alterSql) {
        if (! isset($existingCols[$colName])) {
            $pdo->exec('ALTER TABLE invoices ' . $alterSql);
        }
    }

    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');

    $statement = $pdo->prepare('
        UPDATE invoices
        SET komisi_sales_terbayar           = :sales_terbayar,
            komisi_sales_belum_terbayar     = :sales_belum_terbayar,
            status_pembayaran_komisi_sales  = :status_sales,
            tanggal_transfer_komisi_sales   = :tgl_sales,
            komisi_manager_terbayar         = :manager_terbayar,
            komisi_manager_utang            = :manager_utang,
            tanggal_transfer_komisi_manager = :tgl_manager,
            tanggal_transfer_komisi_admin   = :tgl_admin
        WHERE nomor_invoice = :nomor_invoice
    ');

    $pdo->beginTransaction();
    $count = 0;

    foreach ($rows as $row) {
        $invoiceNo = trim((string) ($row['A'] ?? ''));
        if ($invoiceNo === '' || stripos($invoiceNo, 'invoice') !== false) {
            continue;
        }

        $salesTerbayar      = manager_commission_parse_number($row['S'] ?? '');
        $salesBelumTerbayar = manager_commission_parse_number($row['T'] ?? '');
        $statusSales        = trim((string) ($row['U'] ?? ''));
        $tglSales           = manager_commission_excel_date(trim((string) ($row['V'] ?? '')));
        $managerTerbayar    = manager_commission_parse_number($row['W'] ?? '');
        $managerUtang       = manager_commission_parse_number($row['X'] ?? '');
        $tglManager         = manager_commission_excel_date(trim((string) ($row['Y'] ?? '')));
        $tglAdmin           = manager_commission_excel_date(trim((string) ($row['AD'] ?? '')));
        if ($tglAdmin === null) {
            $adminPaidVal = manager_commission_parse_number($row['AB'] ?? '');
            if ($adminPaidVal > 0) {
                $tglInvStr = (string) ($row['B'] ?? '');
                if (preg_match('/\/I\/2026$/', $invoiceNo) || stripos($tglInvStr, 'Januari') !== false) {
                    $tglAdmin = '2026-04-11';
                } elseif (preg_match('/\/II\/2026$/', $invoiceNo) || stripos($tglInvStr, 'Februari') !== false) {
                    $tglAdmin = '2026-05-19';
                } elseif (preg_match('/\/III\/2026$/', $invoiceNo) || stripos($tglInvStr, 'Maret') !== false) {
                    $tglAdmin = '2026-06-10';
                }
            }
        }

        if ($salesTerbayar === 0.0 && $salesBelumTerbayar === 0.0
            && $managerTerbayar === 0.0 && $managerUtang === 0.0
            && $statusSales === '' && $tglSales === null) {
            continue;
        }

        $statement->execute([
            'sales_terbayar'      => $salesTerbayar,
            'sales_belum_terbayar'=> $salesBelumTerbayar,
            'status_sales'        => $statusSales !== '' ? $statusSales : null,
            'tgl_sales'           => $tglSales,
            'manager_terbayar'    => $managerTerbayar,
            'manager_utang'       => $managerUtang,
            'tgl_manager'         => $tglManager,
            'tgl_admin'           => $tglAdmin,
            'nomor_invoice'       => $invoiceNo,
        ]);

        if ($statement->rowCount() > 0) {
            $count++;
        }
    }

    $pdo->commit();
    return $count;
}

function run_invoice_data_generation(): array
{
    $scriptPath = dirname(__DIR__) . '/scripts/generate-invoice-data.php';

    if (! is_readable($scriptPath)) {
        return [
            'ok' => false,
            'message' => 'Script scripts/generate-invoice-data.php tidak ditemukan.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    if (! function_exists('shell_exec')) {
        return [
            'ok' => false,
            'message' => 'Fungsi shell_exec tidak aktif di server, jadi script generate invoice belum bisa dijalankan dari tombol web.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    $phpBinary = PHP_BINARY ?: 'php';
    $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' 2>&1';
    $output = trim((string) shell_exec($command));
    $ok = str_contains($output, 'Invoice CSV:') && str_contains($output, 'Detail CSV:');

    return [
        'ok' => $ok,
        'message' => $ok ? 'Generate invoice data berhasil dijalankan.' : 'Generate invoice data gagal: ' . ($output ?: 'Tidak ada output dari script.'),
        'statements' => extract_invoice_generate_count($output),
        'counts' => database_table_counts(),
        'output' => $output,
    ];
}

function extract_invoice_generate_count(string $output): int
{
    if (preg_match('/Invoice unik:\s*(\d+)/', $output, $match) === 1) {
        return (int) $match[1];
    }

    return 0;
}

function run_database_operational_seed(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok' => false,
            'message' => 'File Excel storage/PENJUALAN-2026.xlsx tidak ditemukan.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        $cliPhp = 'C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe';
        if (file_exists($cliPhp)) {
            $scriptPath = dirname(__DIR__) . '/scripts/seed-operational.php';
            $cmd = '"' . $cliPhp . '" -d extension=zip "' . $scriptPath . '" 2>&1';
            $output = shell_exec($cmd);
            if (strpos((string)$output, 'seeded successfully') !== false) {
                preg_match('/operational_expenses:\s*(\d+)/', (string)$output, $matches);
                $count = isset($matches[1]) ? (int)$matches[1] : 55;
                return [
                    'ok' => true,
                    'message' => 'Seeder data operasional berhasil dijalankan (via CLI PHP).',
                    'statements' => $count,
                    'counts' => database_table_counts(),
                ];
            } else {
                return [
                    'ok' => false,
                    'message' => 'Seed operasional gagal (via CLI PHP): ' . $output,
                    'statements' => 0,
                    'counts' => database_table_counts(),
                ];
            }
        }

        return [
            'ok' => false,
            'message' => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif di server web Anda. Silakan aktifkan ekstensi zip pada php.ini.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    try {
        if (accounting_tables_ready($pdo)) {
            $pdo->exec("DELETE FROM journal_entries WHERE source_type = 'operational_expense'");
        }
        $pdo->exec('TRUNCATE TABLE operational_expenses');
        $count = seed_operational_expenses_from_workbook($pdo, $excelPath);
        $bonusCount = seed_bonus_expenses($pdo);
        $journalResult = accounting_tables_ready($pdo)
            ? regenerate_all_accounting_journals($pdo)
            : ['lines' => 0];

        return [
            'ok'       => true,
            'message'  => 'Seeder data operasional + bonus berhasil dijalankan.',
            'statements' => $count + $bonusCount + $journalResult['lines'],
            'counts'   => database_table_counts(),
        ];
    } catch (Throwable $exception) {
        return [
            'ok'       => false,
            'message'  => 'Seed operasional gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'   => database_table_counts(),
        ];
    }
}

function database_seed_file_info(): array
{
    $path = dirname(__DIR__) . '/database/seed-data.sql';

    return [
        'exists' => is_readable($path),
        'path' => 'database/seed-data.sql',
        'size' => is_readable($path) ? filesize($path) : 0,
        'updated_at' => is_readable($path) ? date('Y-m-d H:i:s', (int) filemtime($path)) : null,
    ];
}

function database_table_counts(): array
{
    $pdo = db();

    if ($pdo === null) {
        return [];
    }

    $counts = [];

    foreach ([
        'master_barang',
        'master_customers',
        'master_sales',
        'invoices',
        'invoice_items',
        'operational_expenses',
        'partner_prive',
        'chart_of_accounts',
        'journal_entries',
        'journal_lines',
    ] as $table) {
        try {
            $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        } catch (Throwable) {
            $counts[$table] = null;
        }
    }

    return $counts;
}

function accounting_default_accounts(): array
{
    return [
        'cash' => ['1100', 'Kas / Bank', 'asset', 'debit'],
        'accounts_receivable' => ['1200', 'Piutang Usaha', 'asset', 'debit'],
        'purchase_payable' => ['2100', 'Hutang Pembelian Barang', 'liability', 'credit'],
        'operational_payable' => ['2110', 'Hutang Operasional', 'liability', 'credit'],
        'sales_commission_payable' => ['2200', 'Hutang Komisi Sales', 'liability', 'credit'],
        'manager_commission_payable' => ['2210', 'Hutang Komisi Manager', 'liability', 'credit'],
        'admin_commission_payable' => ['2220', 'Hutang Komisi Admin', 'liability', 'credit'],
        'partner_prive_payable' => ['2230', 'Hutang Prive Partner', 'liability', 'credit'],
        'tax_payable' => ['2300', 'Hutang PPh Final', 'liability', 'credit'],
        'owner_capital' => ['3100', 'Modal Pemilik', 'equity', 'credit'],
        'retained_earnings' => ['3200', 'Laba Ditahan', 'equity', 'credit'],
        'current_year_earnings' => ['3300', 'Laba Tahun Berjalan', 'equity', 'credit'],
        'partner_prive' => ['3400', 'Prive Partner (Pengurang Ekuitas)', 'equity', 'credit'],
        'sales_revenue' => ['4100', 'Pendapatan Penjualan', 'revenue', 'credit'],
        'sales_discount' => ['4110', 'Diskon Penjualan', 'expense', 'debit'],
        'cogs' => ['5100', 'HPP / Pembelian Barang', 'expense', 'debit'],
        'sales_commission_expense' => ['6100', 'Beban Komisi Sales', 'expense', 'debit'],
        'manager_commission_expense' => ['6110', 'Beban Komisi Manager', 'expense', 'debit'],
        'admin_commission_expense' => ['6120', 'Beban Komisi Admin', 'expense', 'debit'],
        'operational_expense' => ['6200', 'Beban Operasional', 'expense', 'debit'],
        'bonus_expense' => ['6210', 'Beban Bonus', 'expense', 'debit'],
        'delivery_expense' => ['6300', 'Biaya Kirim', 'expense', 'debit'],
        'bank_admin_expense' => ['6400', 'Biaya Admin Bank', 'expense', 'debit'],
        'tax_expense' => ['6500', 'Beban PPh Final', 'expense', 'debit'],
    ];
}

function accounting_tables_ready(PDO $pdo): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name IN (?, ?, ?)
    ');
    $stmt->execute(['chart_of_accounts', 'journal_entries', 'journal_lines']);

    return (int) $stmt->fetchColumn() === 3;
}

function database_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ');
    $stmt->execute([$table]);

    return (int) $stmt->fetchColumn() > 0;
}

function database_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ');
    $stmt->execute([$table, $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function ensure_journal_entries_posted_at_column(PDO $pdo): void
{
    if (! database_table_exists($pdo, 'journal_entries')) {
        return;
    }

    if (database_column_exists($pdo, 'journal_entries', 'posted_at')) {
        return;
    }

    $pdo->exec('ALTER TABLE journal_entries ADD COLUMN posted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER entry_date');
    $pdo->exec('UPDATE journal_entries SET posted_at = COALESCE(posted_at, created_at, NOW())');
}

function ensure_accounting_tables(PDO $pdo): void
{
    if (accounting_tables_ready($pdo)) {
        ensure_journal_entries_posted_at_column($pdo);
        return;
    }

    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    if (is_readable($schemaPath)) {
        execute_sql_file($pdo, $schemaPath, true);
    }

    ensure_journal_entries_posted_at_column($pdo);
}

function ensure_partner_prive_table(PDO $pdo): void
{
    if (! database_table_exists($pdo, 'partner_prive')) {
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';
        if (is_readable($schemaPath)) {
            execute_sql_file($pdo, $schemaPath, true);
        }
    }

    if (database_table_exists($pdo, 'partner_prive')) {
        $stmt = $pdo->prepare('
            SELECT COLUMN_DEFAULT
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
            LIMIT 1
        ');
        $stmt->execute(['partner_prive', 'status_pembayaran']);
        if ((string) $stmt->fetchColumn() !== 'Hutang') {
            $pdo->exec("ALTER TABLE partner_prive MODIFY status_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Hutang'");
        }
    }
}

function ensure_default_chart_of_accounts(PDO $pdo): void
{
    if (! accounting_tables_ready($pdo)) {
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO chart_of_accounts (code, name, type, normal_balance, is_active)
        VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            type = VALUES(type),
            normal_balance = VALUES(normal_balance),
            is_active = 1
    ');

    foreach (accounting_default_accounts() as $account) {
        $stmt->execute($account);
    }
}

function accounting_account_ids(PDO $pdo): array
{
    ensure_default_chart_of_accounts($pdo);

    $rows = $pdo->query('SELECT id, code FROM chart_of_accounts')->fetchAll(PDO::FETCH_ASSOC);
    $byCode = [];
    foreach ($rows as $row) {
        $byCode[(string) $row['code']] = (int) $row['id'];
    }

    $ids = [];
    foreach (accounting_default_accounts() as $key => $account) {
        $ids[$key] = $byCode[$account[0]] ?? 0;
    }

    return $ids;
}

function accounting_entry_date(mixed $date, ?string $fallback = null): string
{
    $normalized = date_input_value((string) ($date ?? ''));
    if ($normalized !== '') {
        return $normalized;
    }

    if ($fallback !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fallback) === 1) {
        return $fallback;
    }

    return date('Y-m-d');
}

function accounting_add_line(array &$lines, int $accountId, float $debit, float $credit, string $memo): void
{
    $debit = round($debit, 2);
    $credit = round($credit, 2);

    if ($accountId <= 0 || ($debit <= 0 && $credit <= 0)) {
        return;
    }

    $lines[] = [
        'account_id' => $accountId,
        'debit' => $debit,
        'credit' => $credit,
        'memo' => $memo,
    ];
}

function accounting_replace_journal(PDO $pdo, string $sourceType, string $sourceId, string $entryDate, string $description, array $lines): int
{
    ensure_accounting_tables($pdo);
    delete_accounting_journal_source($pdo, $sourceType, $sourceId);

    if ($lines === []) {
        return 0;
    }

    $debitTotal = round(array_sum(array_column($lines, 'debit')), 2);
    $creditTotal = round(array_sum(array_column($lines, 'credit')), 2);
    if (abs($debitTotal - $creditTotal) > 0.01) {
        throw new RuntimeException('Jurnal tidak balance untuk ' . $sourceType . ' ' . $sourceId . '. Debit ' . $debitTotal . ', kredit ' . $creditTotal . '.');
    }

    $entryStmt = $pdo->prepare('
        INSERT INTO journal_entries (entry_date, posted_at, source_type, source_id, description)
        VALUES (?, NOW(), ?, ?, ?)
    ');
    $entryStmt->execute([$entryDate, $sourceType, $sourceId, $description]);
    $entryId = (int) $pdo->lastInsertId();

    $lineStmt = $pdo->prepare('
        INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, memo)
        VALUES (?, ?, ?, ?, ?)
    ');

    foreach ($lines as $line) {
        $lineStmt->execute([
            $entryId,
            $line['account_id'],
            $line['debit'],
            $line['credit'],
            $line['memo'],
        ]);
    }

    return count($lines);
}

function delete_accounting_journal_source(PDO $pdo, string $sourceType, string $sourceId): void
{
    if (! accounting_tables_ready($pdo)) {
        return;
    }

    $delete = $pdo->prepare('DELETE FROM journal_entries WHERE source_type = ? AND source_id = ?');
    $delete->execute([$sourceType, $sourceId]);
}

function delete_invoice_accounting_journal(PDO $pdo, string $kodeInvoice): void
{
    delete_accounting_journal_source($pdo, 'invoice', $kodeInvoice);
}

function post_invoice_accounting_journal(PDO $pdo, string $kodeInvoice): int
{
    return generate_invoice_journal($pdo, $kodeInvoice);
}

function generate_invoice_journal(PDO $pdo, string $kodeInvoice): int
{
    if (! accounting_tables_ready($pdo)) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE kode_invoice = ?');
    $stmt->execute([$kodeInvoice]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if (! $invoice) {
        return 0;
    }

    $a = accounting_account_ids($pdo);
    $lines = [];
    $nomor = (string) ($invoice['nomor_invoice'] ?? $kodeInvoice);
    $entryDate = accounting_entry_date($invoice['tanggal_invoice'] ?? null, substr((string) ($invoice['created_at'] ?? ''), 0, 10));

    $subtotal = round((float) ($invoice['subtotal'] ?? 0), 2);
    $discount = round((float) ($invoice['discount_amount'] ?? 0), 2);
    $storedNetSales = round((float) ($invoice['total_harga_jual'] ?? 0), 2);
    $netSales = $subtotal > 0 ? max($subtotal - $discount, 0) : $storedNetSales;
    if ($netSales <= 0 && $storedNetSales > 0) {
        $netSales = $storedNetSales;
    }

    if ($subtotal > 0) {
        $assetAccount = strtolower(trim((string) ($invoice['status_pembayaran'] ?? ''))) === 'lunas'
            ? $a['cash']
            : $a['accounts_receivable'];
        accounting_add_line($lines, $assetAccount, $netSales, 0, 'Nilai invoice ' . $nomor);
        accounting_add_line($lines, $a['sales_discount'], $discount, 0, 'Diskon invoice ' . $nomor);
        accounting_add_line($lines, $a['sales_revenue'], 0, $subtotal, 'Pendapatan invoice ' . $nomor);
    }

    $purchasePaid = round((float) ($invoice['total_pembelian_barang'] ?? 0), 2);
    $purchaseDebt = round((float) ($invoice['total_utang_pembelian_barang'] ?? 0), 2);
    $purchaseTotal = $purchasePaid + $purchaseDebt;
    if ($purchaseTotal > 0) {
        accounting_add_line($lines, $a['cogs'], $purchaseTotal, 0, 'HPP invoice ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $purchasePaid, 'Pembelian barang dibayar ' . $nomor);
        accounting_add_line($lines, $a['purchase_payable'], 0, $purchaseDebt, 'Hutang pembelian barang ' . $nomor);
    }

    $salesCommissionTotal = round($netSales * (((float) ($invoice['komisi_sales_1_persen'] ?? 0) + (float) ($invoice['komisi_sales_2_persen'] ?? 0)) / 100), 2);
    $salesCommissionPaid = round((float) ($invoice['komisi_sales_terbayar'] ?? 0), 2);
    $salesCommissionDebt = round((float) ($invoice['komisi_sales_belum_terbayar'] ?? 0), 2);
    if ($salesCommissionPaid + $salesCommissionDebt > 0) {
        $salesCommissionTotal = $salesCommissionPaid + $salesCommissionDebt;
    } elseif ($salesCommissionTotal > 0) {
        $salesCommissionDebt = max($salesCommissionTotal - $salesCommissionPaid, 0);
    }
    if ($salesCommissionTotal > 0) {
        accounting_add_line($lines, $a['sales_commission_expense'], $salesCommissionTotal, 0, 'Beban komisi sales ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $salesCommissionPaid, 'Komisi sales dibayar ' . $nomor);
        accounting_add_line($lines, $a['sales_commission_payable'], 0, $salesCommissionDebt, 'Hutang komisi sales ' . $nomor);
    }

    $managerPaid = round((float) ($invoice['komisi_manager_terbayar'] ?? 0), 2);
    $managerDebt = round((float) ($invoice['komisi_manager_utang'] ?? 0), 2);
    if ($managerPaid + $managerDebt > 0) {
        accounting_add_line($lines, $a['manager_commission_expense'], $managerPaid + $managerDebt, 0, 'Beban komisi manager ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $managerPaid, 'Komisi manager dibayar ' . $nomor);
        accounting_add_line($lines, $a['manager_commission_payable'], 0, $managerDebt, 'Hutang komisi manager ' . $nomor);
    }

    $adminPaid = round((float) ($invoice['komisi_admin_terbayar'] ?? 0), 2);
    $adminDebt = round((float) ($invoice['komisi_admin_belum_terbayar'] ?? 0), 2);
    if ($adminPaid + $adminDebt > 0) {
        accounting_add_line($lines, $a['admin_commission_expense'], $adminPaid + $adminDebt, 0, 'Beban komisi admin ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $adminPaid, 'Komisi admin dibayar ' . $nomor);
        accounting_add_line($lines, $a['admin_commission_payable'], 0, $adminDebt, 'Hutang komisi admin ' . $nomor);
    }

    $taxPaid = round((float) ($invoice['pph_final_terbayar'] ?? 0), 2);
    $taxDebt = round((float) ($invoice['pph_final_belum_terbayar'] ?? 0), 2);
    if ($taxPaid + $taxDebt > 0) {
        accounting_add_line($lines, $a['tax_expense'], $taxPaid + $taxDebt, 0, 'Beban PPh final ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $taxPaid, 'PPh final dibayar ' . $nomor);
        accounting_add_line($lines, $a['tax_payable'], 0, $taxDebt, 'Hutang PPh final ' . $nomor);
    }

    $delivery = round((float) ($invoice['biaya_kirim'] ?? 0), 2);
    if ($delivery > 0) {
        accounting_add_line($lines, $a['delivery_expense'], $delivery, 0, 'Biaya kirim ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $delivery, 'Biaya kirim dibayar ' . $nomor);
    }

    $bankAdmin = round((float) ($invoice['biaya_admin_bank'] ?? 0), 2);
    if ($bankAdmin > 0) {
        accounting_add_line($lines, $a['bank_admin_expense'], $bankAdmin, 0, 'Biaya admin bank ' . $nomor);
        accounting_add_line($lines, $a['cash'], 0, $bankAdmin, 'Biaya admin bank dibayar ' . $nomor);
    }

    return accounting_replace_journal($pdo, 'invoice', $kodeInvoice, $entryDate, 'Jurnal otomatis invoice ' . $nomor, $lines);
}

function generate_operational_expense_journal(PDO $pdo, int $expenseId): int
{
    if (! accounting_tables_ready($pdo)) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT * FROM operational_expenses WHERE id = ?');
    $stmt->execute([$expenseId]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    if (! $expense) {
        return 0;
    }

    $a = accounting_account_ids($pdo);
    $amount = round((float) ($expense['jumlah'] ?? 0), 2);
    $lines = [];
    $name = trim((string) ($expense['nama_pengeluaran'] ?? 'Pengeluaran operasional'));
    $expenseAccount = strtolower(trim((string) ($expense['kategori'] ?? 'operational'))) === 'bonus'
        ? $a['bonus_expense']
        : $a['operational_expense'];
    $creditAccount = strtolower(trim((string) ($expense['status_pembayaran'] ?? ''))) === 'lunas'
        ? $a['cash']
        : $a['operational_payable'];

    accounting_add_line($lines, $expenseAccount, $amount, 0, $name);
    accounting_add_line($lines, $creditAccount, 0, $amount, $name);

    return accounting_replace_journal(
        $pdo,
        'operational_expense',
        (string) $expenseId,
        accounting_entry_date($expense['tanggal'] ?? null, substr((string) ($expense['created_at'] ?? ''), 0, 10)),
        'Jurnal otomatis operasional: ' . $name,
        $lines
    );
}

function generate_partner_prive_journal(PDO $pdo, int $priveId): int
{
    ensure_accounting_tables($pdo);
    ensure_partner_prive_table($pdo);
    if (! accounting_tables_ready($pdo)) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT * FROM partner_prive WHERE id = ?');
    $stmt->execute([$priveId]);
    $prive = $stmt->fetch(PDO::FETCH_ASSOC);
    if (! $prive) {
        return 0;
    }

    $a = accounting_account_ids($pdo);
    $amount = round((float) ($prive['jumlah'] ?? 0), 2);
    $partner = normalize_spaces((string) ($prive['partner'] ?? 'Partner'));
    $status = strtolower(trim((string) ($prive['status_pembayaran'] ?? '')));
    $creditAccount = $status === 'lunas' ? $a['cash'] : $a['partner_prive_payable'];
    $memo = 'Prive partner ' . $partner;
    $lines = [];

    accounting_add_line($lines, $a['partner_prive'], $amount, 0, $memo);
    accounting_add_line($lines, $creditAccount, 0, $amount, $memo);

    return accounting_replace_journal(
        $pdo,
        'partner_prive',
        (string) $priveId,
        accounting_entry_date($prive['tanggal'] ?? null, substr((string) ($prive['created_at'] ?? ''), 0, 10)),
        'Jurnal otomatis prive: ' . $partner,
        $lines
    );
}

function regenerate_all_accounting_journals(PDO $pdo): array
{
    if (! accounting_tables_ready($pdo)) {
        return ['entries' => 0, 'lines' => 0];
    }

    ensure_default_chart_of_accounts($pdo);
    $entries = 0;
    $lines = 0;

    $invoiceCodes = $pdo->query('SELECT kode_invoice FROM invoices ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($invoiceCodes as $kodeInvoice) {
        $lineCount = generate_invoice_journal($pdo, (string) $kodeInvoice);
        if ($lineCount > 0) {
            $entries++;
            $lines += $lineCount;
        }
    }

    $expenseIds = $pdo->query('SELECT id FROM operational_expenses ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($expenseIds as $expenseId) {
        $lineCount = generate_operational_expense_journal($pdo, (int) $expenseId);
        if ($lineCount > 0) {
            $entries++;
            $lines += $lineCount;
        }
    }

    ensure_partner_prive_table($pdo);
    if (database_table_exists($pdo, 'partner_prive')) {
        $priveIds = $pdo->query('SELECT id FROM partner_prive ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($priveIds as $priveId) {
            $lineCount = generate_partner_prive_journal($pdo, (int) $priveId);
            if ($lineCount > 0) {
                $entries++;
                $lines += $lineCount;
            }
        }
    }

    return ['entries' => $entries, 'lines' => $lines];
}

function run_accounting_journal_generation(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    try {
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';
        if (is_readable($schemaPath)) {
            execute_sql_file($pdo, $schemaPath, true);
        }

        if (! accounting_tables_ready($pdo)) {
            return [
                'ok' => false,
                'message' => 'Tabel accounting belum tersedia dan schema tidak berhasil dijalankan.',
                'statements' => 0,
                'counts' => database_table_counts(),
            ];
        }

        $pdo->beginTransaction();
        $result = regenerate_all_accounting_journals($pdo);
        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Generate jurnal akuntansi berhasil. ' . $result['entries'] . ' jurnal dibuat ulang.',
            'statements' => $result['lines'],
            'counts' => database_table_counts(),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'ok' => false,
            'message' => 'Generate jurnal akuntansi gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }
}

function fetch_laporan_coa(): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Database belum bisa dikoneksi.', 'items' => []];
    }

    try {
        ensure_accounting_tables($pdo);
        ensure_default_chart_of_accounts($pdo);

        $items = db_all('
            SELECT id, code, name, type, normal_balance, is_active
            FROM chart_of_accounts
            ORDER BY code
        ') ?? [];

        return ['ok' => true, 'items' => $items, 'error' => null];
    } catch (Throwable $exception) {
        return ['ok' => false, 'error' => $exception->getMessage(), 'items' => []];
    }
}

function fetch_laporan_jurnal_umum(string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Database belum bisa dikoneksi.', 'entries' => [], 'summary' => []];
    }

    try {
        ensure_accounting_tables($pdo);

        $sql = '
            SELECT
                je.id,
                je.entry_date,
                je.posted_at,
                je.source_type,
                je.source_id,
                je.description,
                coa.code,
                coa.name AS account_name,
                jl.debit,
                jl.credit,
                jl.memo
            FROM journal_entries je
            JOIN journal_lines jl ON jl.journal_entry_id = je.id
            JOIN chart_of_accounts coa ON coa.id = jl.account_id
            WHERE 1=1
        ';
        $params = [];

        if ($month !== '') {
            $sql .= ' AND MONTH(je.entry_date) = :month';
            $params['month'] = (int) $month;
        }
        if ($year !== '') {
            $sql .= ' AND YEAR(je.entry_date) = :year';
            $params['year'] = (int) $year;
        }

        $sql .= ' ORDER BY je.entry_date DESC, je.id DESC, jl.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $entries = [];
        foreach ($rows as $row) {
            $entryId = (int) $row['id'];
            if (! isset($entries[$entryId])) {
                $entries[$entryId] = [
                    'id' => $entryId,
                    'entry_date' => $row['entry_date'],
                    'posted_at' => $row['posted_at'],
                    'source_type' => $row['source_type'],
                    'source_id' => $row['source_id'],
                    'description' => $row['description'],
                    'lines' => [],
                    'debit_total' => 0.0,
                    'credit_total' => 0.0,
                ];
            }

            $line = [
                'code' => $row['code'],
                'account_name' => $row['account_name'],
                'debit' => (float) $row['debit'],
                'credit' => (float) $row['credit'],
                'memo' => $row['memo'],
            ];
            $entries[$entryId]['lines'][] = $line;
            $entries[$entryId]['debit_total'] += $line['debit'];
            $entries[$entryId]['credit_total'] += $line['credit'];
        }

        $entries = array_values($entries);
        return [
            'ok' => true,
            'entries' => $entries,
            'summary' => [
                'entry_count' => count($entries),
                'debit_total' => array_sum(array_column($entries, 'debit_total')),
                'credit_total' => array_sum(array_column($entries, 'credit_total')),
            ],
            'error' => null,
        ];
    } catch (Throwable $exception) {
        return ['ok' => false, 'error' => $exception->getMessage(), 'entries' => [], 'summary' => []];
    }
}

function fetch_laporan_buku_besar(string $accountCode = '', string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Database belum bisa dikoneksi.', 'accounts' => [], 'items' => [], 'selected' => null];
    }

    try {
        ensure_accounting_tables($pdo);
        ensure_default_chart_of_accounts($pdo);

        $accounts = db_all('
            SELECT id, code, name, type, normal_balance
            FROM chart_of_accounts
            WHERE is_active = 1
            ORDER BY code
        ') ?? [];

        if ($accountCode === '' && $accounts !== []) {
            $accountCode = (string) $accounts[0]['code'];
        }

        $selected = null;
        foreach ($accounts as $account) {
            if ((string) $account['code'] === $accountCode) {
                $selected = $account;
                break;
            }
        }

        if ($selected === null) {
            return ['ok' => true, 'accounts' => $accounts, 'items' => [], 'selected' => null, 'summary' => [], 'error' => null];
        }

        $sql = '
            SELECT je.entry_date, je.source_type, je.source_id, je.description, jl.debit, jl.credit, jl.memo
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            WHERE jl.account_id = :account_id
        ';
        $params = ['account_id' => (int) $selected['id']];
        if ($month !== '') {
            $sql .= ' AND MONTH(je.entry_date) = :month';
            $params['month'] = (int) $month;
        }
        if ($year !== '') {
            $sql .= ' AND YEAR(je.entry_date) = :year';
            $params['year'] = (int) $year;
        }
        $sql .= ' ORDER BY je.entry_date ASC, je.id ASC, jl.id ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $balance = 0.0;
        $items = [];
        foreach ($rows as $row) {
            $debit = (float) $row['debit'];
            $credit = (float) $row['credit'];
            $balance += ($selected['normal_balance'] === 'debit') ? ($debit - $credit) : ($credit - $debit);
            $row['debit'] = $debit;
            $row['credit'] = $credit;
            $row['balance'] = $balance;
            $items[] = $row;
        }

        return [
            'ok' => true,
            'accounts' => $accounts,
            'items' => $items,
            'selected' => $selected,
            'summary' => [
                'debit_total' => array_sum(array_column($items, 'debit')),
                'credit_total' => array_sum(array_column($items, 'credit')),
                'ending_balance' => $balance,
            ],
            'error' => null,
        ];
    } catch (Throwable $exception) {
        return ['ok' => false, 'error' => $exception->getMessage(), 'accounts' => [], 'items' => [], 'selected' => null];
    }
}

function fetch_laporan_neraca(string $asOfDate = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Database belum bisa dikoneksi.', 'groups' => []];
    }

    try {
        ensure_accounting_tables($pdo);
        ensure_default_chart_of_accounts($pdo);

        $asOfDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate) === 1 ? $asOfDate : date('Y-m-d');
        $stmt = $pdo->prepare('
            SELECT
                coa.code,
                coa.name,
                coa.type,
                coa.normal_balance,
                COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.debit ELSE 0 END), 0) AS debit_total,
                COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.credit ELSE 0 END), 0) AS credit_total
            FROM chart_of_accounts coa
            LEFT JOIN journal_lines jl ON jl.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jl.journal_entry_id AND je.entry_date <= :as_of
            WHERE coa.is_active = 1
            GROUP BY coa.id, coa.code, coa.name, coa.type, coa.normal_balance
            ORDER BY coa.code
        ');
        $stmt->execute(['as_of' => $asOfDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groups = [
            'asset' => ['label' => 'Aset', 'items' => [], 'total' => 0.0],
            'liability' => ['label' => 'Kewajiban', 'items' => [], 'total' => 0.0],
            'equity' => ['label' => 'Ekuitas', 'items' => [], 'total' => 0.0],
        ];
        $netIncome = 0.0;

        foreach ($rows as $row) {
            $debit = (float) $row['debit_total'];
            $credit = (float) $row['credit_total'];
            $balance = $row['normal_balance'] === 'debit' ? ($debit - $credit) : ($credit - $debit);

            if ($row['type'] === 'revenue') {
                $netIncome += $balance;
                continue;
            }
            if ($row['type'] === 'expense') {
                $netIncome -= $balance;
                continue;
            }
            if (! isset($groups[$row['type']])) {
                continue;
            }

            $item = $row;
            $item['balance'] = $balance;
            $groups[$row['type']]['items'][] = $item;
            $groups[$row['type']]['total'] += $balance;
        }

        $groups['equity']['items'][] = [
            'code' => '3300-PNL',
            'name' => 'Laba Tahun Berjalan dari Jurnal',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'balance' => $netIncome,
        ];
        $groups['equity']['total'] += $netIncome;

        $rightTotal = $groups['liability']['total'] + $groups['equity']['total'];

        return [
            'ok' => true,
            'as_of_date' => $asOfDate,
            'groups' => $groups,
            'summary' => [
                'asset_total' => $groups['asset']['total'],
                'liability_total' => $groups['liability']['total'],
                'equity_total' => $groups['equity']['total'],
                'liability_equity_total' => $rightTotal,
                'difference' => $groups['asset']['total'] - $rightTotal,
                'net_income' => $netIncome,
            ],
            'error' => null,
        ];
    } catch (Throwable $exception) {
        return ['ok' => false, 'error' => $exception->getMessage(), 'groups' => []];
    }
}

function run_database_migration_seed(): array
{
    $pdo = db();

    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi. Cek konfigurasi DB_HOST, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD.',
            'statements' => 0,
            'counts' => [],
        ];
    }

    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    $seedPath = dirname(__DIR__) . '/database/seed-data.sql';

    if (! is_readable($schemaPath) || ! is_readable($seedPath)) {
        return [
            'ok' => false,
            'message' => 'File schema.sql atau seed-data.sql belum tersedia.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    try {
        $statementCount = 0;
        $statementCount += execute_sql_statements($pdo, [
            'SET FOREIGN_KEY_CHECKS = 0',
            'DROP TABLE IF EXISTS `journal_lines`',
            'DROP TABLE IF EXISTS `journal_entries`',
            'DROP TABLE IF EXISTS `chart_of_accounts`',
            'DROP TABLE IF EXISTS `invoice_items`',
            'DROP TABLE IF EXISTS `invoices`',
            'DROP TABLE IF EXISTS `master_sales`',
            'DROP TABLE IF EXISTS `master_barang`',
            'DROP TABLE IF EXISTS `master_customers`',
            'DROP TABLE IF EXISTS `operational_expenses`',
            'SET FOREIGN_KEY_CHECKS = 1',
        ]);
        $statementCount += execute_sql_file($pdo, $schemaPath, true);
        $statementCount += execute_sql_file($pdo, $seedPath, false);

        $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
        if (is_readable($excelPath)) {
            if (class_exists('ZipArchive')) {
                $statementCount += seed_operational_expenses_from_workbook($pdo, $excelPath);
                $statementCount += seed_bonus_expenses($pdo);
            } else {
                $cliPhp = 'C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe';
                if (file_exists($cliPhp)) {
                    $scriptPath = dirname(__DIR__) . '/scripts/seed-operational.php';
                    $cmd = '"' . $cliPhp . '" -d extension=zip "' . $scriptPath . '" 2>&1';
                    shell_exec($cmd);
                }
            }
        }

        if (accounting_tables_ready($pdo)) {
            $journalResult = regenerate_all_accounting_journals($pdo);
            $statementCount += $journalResult['lines'];
        }

        return [
            'ok' => true,
            'message' => 'Migrate dan seed berhasil dijalankan.',
            'statements' => $statementCount,
            'counts' => database_table_counts(),
        ];
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'message' => 'Migrate/seed gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }
}

function run_database_snapshot_restore(): array
{
    $pdo = db();

    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi. Cek konfigurasi DB_HOST, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD.',
            'statements' => 0,
            'counts' => [],
        ];
    }

    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    $seedPath = dirname(__DIR__) . '/database/seed-data.sql';

    if (! is_readable($schemaPath) || ! is_readable($seedPath)) {
        return [
            'ok' => false,
            'message' => 'File schema.sql atau seed-data.sql belum tersedia.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    try {
        $statementCount = 0;
        $statementCount += execute_sql_statements($pdo, [
            'SET FOREIGN_KEY_CHECKS = 0',
            'DROP TABLE IF EXISTS `journal_lines`',
            'DROP TABLE IF EXISTS `journal_entries`',
            'DROP TABLE IF EXISTS `chart_of_accounts`',
            'DROP TABLE IF EXISTS `invoice_items`',
            'DROP TABLE IF EXISTS `invoices`',
            'DROP TABLE IF EXISTS `master_sales`',
            'DROP TABLE IF EXISTS `master_barang`',
            'DROP TABLE IF EXISTS `master_customers`',
            'DROP TABLE IF EXISTS `operational_expenses`',
            'SET FOREIGN_KEY_CHECKS = 1',
        ]);
        $statementCount += execute_sql_file($pdo, $schemaPath, true);
        $statementCount += execute_sql_file($pdo, $seedPath, false);

        return [
            'ok' => true,
            'message' => 'Restore snapshot lokal berhasil. Database hosting sudah disamakan dengan database lokal dari seed-data.sql.',
            'statements' => $statementCount,
            'counts' => database_table_counts(),
        ];
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'message' => 'Restore snapshot lokal gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }
}

function execute_sql_file(PDO $pdo, string $path, bool $skipDatabaseStatements): int
{
    $sql = (string) file_get_contents($path);
    $statements = split_sql_statements($sql);

    if ($skipDatabaseStatements) {
        $statements = array_values(array_filter($statements, static function (string $statement): bool {
            return preg_match('/^\s*(CREATE\s+DATABASE|USE)\b/i', $statement) !== 1;
        }));
    }

    return execute_sql_statements($pdo, $statements);
}

function execute_sql_statements(PDO $pdo, array $statements): int
{
    $count = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);

        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
        $count++;
    }

    return $count;
}

function split_sql_statements(string $sql): array
{
    $lines = preg_split('/\R/', $sql) ?: [];
    $sql = implode(PHP_EOL, array_filter($lines, static fn (string $line): bool => ! str_starts_with(ltrim($line), '--')));
    $statements = [];
    $current = '';
    $quote = null;
    $length = strlen($sql);

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $current .= $char;

        if ($quote !== null) {
            if ($char === '\\') {
                $index++;
                $current .= $sql[$index] ?? '';
                continue;
            }

            if ($char === $quote) {
                $quote = null;
            }

            continue;
        }

        if ($char === "'" || $char === '"') {
            $quote = $char;
            continue;
        }

        if ($char === ';') {
            $statements[] = substr($current, 0, -1);
            $current = '';
        }
    }

    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
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
    $dbInvoices = db_all('SELECT kode_invoice, nomor_invoice, tanggal_invoice, nomor_surat_jalan, tanggal_surat_jalan, po_number, kode_sales_1, nama_sales_1, kode_sales_2, nama_sales_2, komisi_sales_1_persen, komisi_sales_2_persen, kode_customer, nama_customer_master, nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat, total_item, total_qty, subtotal, harga_normal_pricelist, discount_persen, discount_amount, total_harga_jual, status_pembayaran, tanggal_pembayaran, total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, file_invoice FROM invoices ORDER BY kode_invoice');
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

function fetch_invoice_payment_log(array $filters = []): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [],
            'options' => ['years' => [date('Y')]],
            'filters' => $filters,
            'error' => 'Koneksi database gagal.',
        ];
    }

    try {
        $rows = $pdo->query('
            SELECT
                kode_invoice,
                nomor_invoice,
                tanggal_invoice,
                kode_sales_1,
                nama_sales_1,
                kode_sales_2,
                nama_sales_2,
                nama_customer_master,
                nama_customer_invoice,
                nama_laundry_invoice,
                total_harga_jual,
                status_pembayaran,
                tanggal_pembayaran,
                created_at,
                updated_at
            FROM invoices
            ORDER BY id DESC
        ')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [],
            'options' => ['years' => [date('Y')]],
            'filters' => $filters,
            'error' => $exception->getMessage(),
        ];
    }

    $month = trim((string) ($filters['month'] ?? ''));
    $year = trim((string) ($filters['year'] ?? ''));
    $status = trim((string) ($filters['status'] ?? 'unpaid'));
    $search = trim((string) ($filters['search'] ?? ''));
    $today = new DateTimeImmutable('today');

    $yearOptions = invoice_year_options($rows);

    $items = array_values(array_filter(array_map(static function (array $row) use ($today): array {
        $statusPembayaran = trim((string) ($row['status_pembayaran'] ?? ''));
        $isPaid = strcasecmp($statusPembayaran, 'Lunas') === 0;
        $total = (float) ($row['total_harga_jual'] ?? 0);
        $invoiceDate = date_input_value((string) ($row['tanggal_invoice'] ?? ''));
        $paymentDate = date_input_value((string) ($row['tanggal_pembayaran'] ?? ''));
        $start = $invoiceDate !== '' ? new DateTimeImmutable($invoiceDate) : null;
        $end = $paymentDate !== '' ? new DateTimeImmutable($paymentDate) : $today;
        $ageDays = $start !== null ? (int) $start->diff($end)->format('%r%a') : null;

        $row['is_paid'] = $isPaid;
        $row['invoice_date_input'] = $invoiceDate;
        $row['payment_date_input'] = $paymentDate;
        $row['paid_amount'] = $isPaid ? $total : 0.0;
        $row['remaining_amount'] = $isPaid ? 0.0 : $total;
        $row['age_days'] = $ageDays;

        return $row;
    }, $rows), static function (array $invoice) use ($month, $year, $status, $search): bool {
        if ($month !== '' && invoice_month_number((string) ($invoice['nomor_invoice'] ?? '')) !== (int) $month) {
            return false;
        }

        if ($year !== '' && invoice_year((string) ($invoice['nomor_invoice'] ?? '')) !== $year) {
            return false;
        }

        if ($status === 'paid' && ! ($invoice['is_paid'] ?? false)) {
            return false;
        }

        if ($status === 'unpaid' && ($invoice['is_paid'] ?? false)) {
            return false;
        }

        if ($search !== '') {
            $haystack = strtoupper(implode(' ', [
                $invoice['nomor_invoice'] ?? '',
                $invoice['kode_invoice'] ?? '',
                $invoice['nama_customer_master'] ?? '',
                $invoice['nama_customer_invoice'] ?? '',
                $invoice['nama_laundry_invoice'] ?? '',
                $invoice['nama_sales_1'] ?? '',
                $invoice['nama_sales_2'] ?? '',
            ]));

            if (! str_contains($haystack, strtoupper($search))) {
                return false;
            }
        }

        return true;
    }));

    sort_invoices_newest_first($items);

    $paidItems = array_filter($items, static fn (array $invoice): bool => (bool) ($invoice['is_paid'] ?? false));
    $unpaidItems = array_filter($items, static fn (array $invoice): bool => ! (bool) ($invoice['is_paid'] ?? false));

    return [
        'ok' => true,
        'items' => $items,
        'filters' => [
            'month' => $month,
            'year' => $year,
            'status' => $status,
            'search' => $search,
        ],
        'options' => [
            'years' => $yearOptions,
        ],
        'summary' => [
            'invoice_count' => count($items),
            'paid_count' => count($paidItems),
            'unpaid_count' => count($unpaidItems),
            'total_invoice' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['total_harga_jual'] ?? 0), $items)),
            'paid_total' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['paid_amount'] ?? 0), $items)),
            'remaining_total' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['remaining_amount'] ?? 0), $items)),
        ],
        'error' => null,
    ];
}

function update_invoice_payment_status(string $kodeInvoice, string $statusPembayaran, string $tanggalPembayaran): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    $kodeInvoice = trim($kodeInvoice);
    $statusPembayaran = strtolower(trim($statusPembayaran)) === 'lunas' ? 'Lunas' : 'Belum Lunas';
    $tanggalPembayaran = date_input_value($tanggalPembayaran);

    if ($kodeInvoice === '') {
        return ['ok' => false, 'message' => 'Kode invoice tidak boleh kosong.'];
    }

    if ($statusPembayaran === 'Lunas' && $tanggalPembayaran === '') {
        return ['ok' => false, 'message' => 'Tanggal pembayaran wajib diisi untuk invoice lunas.'];
    }

    if ($statusPembayaran !== 'Lunas') {
        $tanggalPembayaran = '';
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT kode_invoice, nomor_invoice FROM invoices WHERE kode_invoice = ? LIMIT 1');
        $stmt->execute([$kodeInvoice]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $invoice) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Invoice tidak ditemukan.'];
        }

        $stmt = $pdo->prepare('
            UPDATE invoices
            SET status_pembayaran = ?,
                tanggal_pembayaran = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE kode_invoice = ?
        ');
        $stmt->execute([
            $statusPembayaran,
            $tanggalPembayaran !== '' ? $tanggalPembayaran : null,
            $kodeInvoice,
        ]);

        post_invoice_accounting_journal($pdo, $kodeInvoice);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Pembayaran invoice ' . ($invoice['nomor_invoice'] ?? $kodeInvoice) . ' berhasil diperbarui.',
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Update pembayaran gagal: ' . $exception->getMessage()];
    }
}

function fetch_invoice_purchase_log(array $filters = []): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [],
            'options' => ['years' => [date('Y')]],
            'filters' => $filters,
            'error' => 'Koneksi database gagal.',
        ];
    }

    try {
        $rows = $pdo->query('
            SELECT
                kode_invoice,
                nomor_invoice,
                tanggal_invoice,
                nama_sales_1,
                nama_sales_2,
                nama_customer_master,
                nama_customer_invoice,
                nama_laundry_invoice,
                total_pembelian_barang,
                total_utang_pembelian_barang,
                status_pembelian_barang,
                tanggal_transfer_pembelian_barang,
                created_at,
                updated_at
            FROM invoices
            WHERE (total_pembelian_barang > 0 OR total_utang_pembelian_barang > 0)
            ORDER BY id DESC
        ')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'items' => [],
            'summary' => [],
            'options' => ['years' => [date('Y')]],
            'filters' => $filters,
            'error' => $exception->getMessage(),
        ];
    }

    $month = trim((string) ($filters['month'] ?? ''));
    $year = trim((string) ($filters['year'] ?? ''));
    $status = trim((string) ($filters['status'] ?? 'unpaid'));
    $search = trim((string) ($filters['search'] ?? ''));
    $yearOptions = invoice_year_options($rows);

    $items = array_values(array_filter(array_map(static function (array $row): array {
        $paid = round((float) ($row['total_pembelian_barang'] ?? 0), 2);
        $debt = round((float) ($row['total_utang_pembelian_barang'] ?? 0), 2);
        $total = $paid + $debt;
        $statusPembelian = trim((string) ($row['status_pembelian_barang'] ?? ''));
        $isPaid = $debt <= 0.01 || strcasecmp($statusPembelian, 'Lunas') === 0;

        $row['purchase_total'] = $total;
        $row['purchase_paid'] = $isPaid ? $total : $paid;
        $row['purchase_debt'] = $isPaid ? 0.0 : $debt;
        $row['is_paid'] = $isPaid;
        $row['transfer_date_input'] = date_input_value((string) ($row['tanggal_transfer_pembelian_barang'] ?? ''));

        return $row;
    }, $rows), static function (array $invoice) use ($month, $year, $status, $search): bool {
        if ($month !== '' && invoice_month_number((string) ($invoice['nomor_invoice'] ?? '')) !== (int) $month) {
            return false;
        }

        if ($year !== '' && invoice_year((string) ($invoice['nomor_invoice'] ?? '')) !== $year) {
            return false;
        }

        if ($status === 'paid' && ! ($invoice['is_paid'] ?? false)) {
            return false;
        }

        if ($status === 'unpaid' && ($invoice['is_paid'] ?? false)) {
            return false;
        }

        if ($search !== '') {
            $haystack = strtoupper(implode(' ', [
                $invoice['nomor_invoice'] ?? '',
                $invoice['kode_invoice'] ?? '',
                $invoice['nama_customer_master'] ?? '',
                $invoice['nama_customer_invoice'] ?? '',
                $invoice['nama_laundry_invoice'] ?? '',
                $invoice['nama_sales_1'] ?? '',
                $invoice['nama_sales_2'] ?? '',
            ]));

            if (! str_contains($haystack, strtoupper($search))) {
                return false;
            }
        }

        return true;
    }));

    sort_invoices_newest_first($items);

    $paidItems = array_filter($items, static fn (array $invoice): bool => (bool) ($invoice['is_paid'] ?? false));
    $debtItems = array_filter($items, static fn (array $invoice): bool => ! (bool) ($invoice['is_paid'] ?? false));

    return [
        'ok' => true,
        'items' => $items,
        'filters' => [
            'month' => $month,
            'year' => $year,
            'status' => $status,
            'search' => $search,
        ],
        'options' => [
            'years' => $yearOptions,
        ],
        'summary' => [
            'invoice_count' => count($items),
            'paid_count' => count($paidItems),
            'debt_count' => count($debtItems),
            'purchase_total' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['purchase_total'] ?? 0), $items)),
            'paid_total' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['purchase_paid'] ?? 0), $items)),
            'debt_total' => array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['purchase_debt'] ?? 0), $items)),
        ],
        'error' => null,
    ];
}

function update_invoice_purchase_status(string $kodeInvoice, string $statusPembelian, mixed $purchaseTotal, mixed $purchaseDebt, string $transferDate): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    $kodeInvoice = trim($kodeInvoice);
    $statusPembelian = strtolower(trim($statusPembelian)) === 'lunas' ? 'Lunas' : 'Utang';
    $purchaseTotal = max(clean_money_value($purchaseTotal), 0);
    $purchaseDebt = max(clean_money_value($purchaseDebt), 0);
    $transferDate = date_input_value($transferDate);

    if ($kodeInvoice === '') {
        return ['ok' => false, 'message' => 'Kode invoice tidak boleh kosong.'];
    }

    if ($purchaseTotal <= 0) {
        return ['ok' => false, 'message' => 'Total pembelian harus lebih besar dari 0.'];
    }

    if ($statusPembelian === 'Lunas') {
        if ($transferDate === '') {
            return ['ok' => false, 'message' => 'Tanggal transfer wajib diisi untuk pembelian lunas.'];
        }
        $purchaseDebt = 0.0;
    } else {
        $purchaseDebt = min($purchaseDebt, $purchaseTotal);
    }

    $purchasePaid = max($purchaseTotal - $purchaseDebt, 0);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT kode_invoice, nomor_invoice FROM invoices WHERE kode_invoice = ? LIMIT 1');
        $stmt->execute([$kodeInvoice]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $invoice) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Invoice tidak ditemukan.'];
        }

        $stmt = $pdo->prepare('
            UPDATE invoices
            SET total_pembelian_barang = ?,
                total_utang_pembelian_barang = ?,
                status_pembelian_barang = ?,
                tanggal_transfer_pembelian_barang = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE kode_invoice = ?
        ');
        $stmt->execute([
            $purchasePaid,
            $purchaseDebt,
            $statusPembelian,
            $transferDate !== '' ? $transferDate : null,
            $kodeInvoice,
        ]);

        post_invoice_accounting_journal($pdo, $kodeInvoice);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Pembelian barang invoice ' . ($invoice['nomor_invoice'] ?? $kodeInvoice) . ' berhasil diperbarui.',
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Update pembelian gagal: ' . $exception->getMessage()];
    }
}

function internal_sales_bonus_rules(): array
{
    return [
        'target' => 30000000.0,
        'rate' => 0.05,
        'sales' => ['Krisna', 'Wira'],
    ];
}

function internal_sales_bonus_marker(string $kodeInvoice, string $sales): string
{
    return 'internal_sales_bonus:' . $kodeInvoice . ':' . strtolower($sales);
}

function fetch_internal_sales_bonus(mixed $month = '', mixed $year = '', array $filters = []): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'items' => [], 'summary' => [], 'error' => 'Koneksi database gagal.'];
    }

    $month = (int) ($month ?: date('n'));
    $year = (int) ($year ?: date('Y'));
    if ($month < 1 || $month > 12) {
        $month = (int) date('n');
    }
    if ($year < 2000) {
        $year = (int) date('Y');
    }

    $customerStatus = trim((string) ($filters['customer_status'] ?? ''));
    $bonusStatus = trim((string) ($filters['bonus_status'] ?? ''));
    $salesFilter = normalize_spaces((string) ($filters['sales'] ?? ''));
    $allowedCustomerStatuses = ['', 'paid', 'unpaid'];
    $allowedBonusStatuses = ['', 'paid', 'unpaid'];
    if (! in_array($customerStatus, $allowedCustomerStatuses, true)) {
        $customerStatus = '';
    }
    if (! in_array($bonusStatus, $allowedBonusStatuses, true)) {
        $bonusStatus = '';
    }

    $rules = internal_sales_bonus_rules();
    if ($salesFilter !== '' && ! in_array(strtolower($salesFilter), array_map(static fn (string $sales): string => strtolower($sales), $rules['sales']), true)) {
        $salesFilter = '';
    }

    $salesTotals = [];
    foreach ($rules['sales'] as $salesName) {
        $salesTotals[$salesName] = [
            'sales' => $salesName,
            'invoice_count' => 0,
            'paid_invoice_count' => 0,
            'omzet' => 0.0,
            'paid_omzet' => 0.0,
        ];
    }

    try {
        $rows = $pdo->query('
            SELECT
                kode_invoice,
                nomor_invoice,
                tanggal_invoice,
                nama_sales_1,
                nama_sales_2,
                nama_customer_invoice,
                nama_customer_master,
                nama_laundry_invoice,
                subtotal,
                discount_amount,
                total_harga_jual,
                status_pembayaran,
                tanggal_pembayaran
            FROM invoices
            ORDER BY id
        ')->fetchAll(PDO::FETCH_ASSOC);

        $invoiceRows = [];
        foreach ($rows as $invoice) {
            if (invoice_month_number((string) ($invoice['nomor_invoice'] ?? '')) !== $month) {
                continue;
            }
            if ((int) invoice_year((string) ($invoice['nomor_invoice'] ?? '')) !== $year) {
                continue;
            }

            $omzet = round((float) ($invoice['total_harga_jual'] ?? 0), 2);
            if ($omzet <= 0) {
                $omzet = max(round((float) ($invoice['subtotal'] ?? 0) - (float) ($invoice['discount_amount'] ?? 0), 2), 0);
            }
            if ($omzet <= 0) {
                continue;
            }
            $isInvoicePaid = strcasecmp(trim((string) ($invoice['status_pembayaran'] ?? '')), 'Lunas') === 0;

            $invoiceSales = [
                normalize_spaces((string) ($invoice['nama_sales_1'] ?? '')),
                normalize_spaces((string) ($invoice['nama_sales_2'] ?? '')),
            ];

            foreach ($salesTotals as $salesName => &$total) {
                foreach ($invoiceSales as $invoiceSalesName) {
                    if ($invoiceSalesName !== '' && strcasecmp($invoiceSalesName, $salesName) === 0) {
                        $total['invoice_count']++;
                        $total['omzet'] += $omzet;
                        if ($isInvoicePaid) {
                            $total['paid_invoice_count']++;
                            $total['paid_omzet'] += $omzet;
                        }
                        $invoiceRows[] = [
                            'kode_invoice' => (string) ($invoice['kode_invoice'] ?? ''),
                            'nomor_invoice' => (string) ($invoice['nomor_invoice'] ?? ''),
                            'tanggal_invoice' => $invoice['tanggal_invoice'] ?? '',
                            'customer' => trim((string) ($invoice['nama_laundry_invoice'] ?? '')) ?: trim((string) ($invoice['nama_customer_invoice'] ?? '')) ?: trim((string) ($invoice['nama_customer_master'] ?? '')),
                            'sales' => $salesName,
                            'omzet' => $omzet,
                            'is_invoice_paid' => $isInvoicePaid,
                            'tanggal_pembayaran' => $invoice['tanggal_pembayaran'] ?? '',
                            'target' => $rules['target'],
                            'rate' => $rules['rate'],
                            'eligible' => false,
                            'bonus' => 0.0,
                            'bonus_status' => 'Belum Dibayar',
                            'bonus_paid_date' => '',
                            'expense_id' => null,
                        ];
                        break;
                    }
                }
            }
            unset($total);
        }

        foreach ($salesTotals as &$total) {
            $total['omzet'] = round((float) $total['omzet'], 2);
            $total['paid_omzet'] = round((float) $total['paid_omzet'], 2);
            $total['eligible'] = $total['omzet'] >= $rules['target'];
        }
        unset($total);

        $postedStmt = $pdo->prepare("
            SELECT id, nama_pengeluaran, jumlah, status_pembayaran, tanggal_pembayaran, keterangan, updated_at
            FROM operational_expenses
            WHERE kategori = 'bonus'
              AND bulan_pnl = ?
              AND tahun_pnl = ?
              AND keterangan LIKE 'internal_sales_bonus:%'
        ");
        $postedStmt->execute([$month, $year]);
        $postedByMarker = [];
        foreach ($postedStmt->fetchAll(PDO::FETCH_ASSOC) as $posted) {
            $parts = explode(' ', (string) ($posted['keterangan'] ?? ''), 2);
            $marker = $parts[0] ?? '';
            if ($marker !== '') {
                $postedByMarker[$marker] = $posted;
            }
        }

        foreach ($invoiceRows as &$invoiceRow) {
            $salesName = (string) $invoiceRow['sales'];
            $invoiceRow['sales_month_omzet'] = $salesTotals[$salesName]['omzet'] ?? 0.0;
            $invoiceRow['sales_paid_omzet'] = $salesTotals[$salesName]['paid_omzet'] ?? 0.0;
            $invoiceRow['eligible'] = (bool) ($salesTotals[$salesName]['eligible'] ?? false);
            $invoiceRow['bonus'] = $invoiceRow['eligible'] ? round((float) $invoiceRow['omzet'] * (float) $rules['rate'], 2) : 0.0;
            $marker = internal_sales_bonus_marker((string) $invoiceRow['kode_invoice'], $salesName);
            $posted = $postedByMarker[$marker] ?? null;
            if (is_array($posted)) {
                $invoiceRow['expense_id'] = (int) ($posted['id'] ?? 0);
                $invoiceRow['bonus_status'] = strcasecmp((string) ($posted['status_pembayaran'] ?? ''), 'Lunas') === 0 ? 'Terbayar' : 'Belum Dibayar';
                $invoiceRow['bonus_paid_date'] = date_input_value((string) ($posted['tanggal_pembayaran'] ?? ''));
            }
        }
        unset($invoiceRow);
    } catch (Throwable $exception) {
        return ['ok' => false, 'items' => [], 'summary' => [], 'error' => $exception->getMessage()];
    }

    usort($invoiceRows, static function (array $a, array $b): int {
        $dateCompare = strcmp(date_input_value((string) ($a['tanggal_invoice'] ?? '')), date_input_value((string) ($b['tanggal_invoice'] ?? '')));
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        return strnatcasecmp((string) ($a['nomor_invoice'] ?? ''), (string) ($b['nomor_invoice'] ?? ''));
    });

    $eligibleRows = array_filter($invoiceRows, static fn (array $item): bool => (bool) ($item['eligible'] ?? false) && (float) ($item['bonus'] ?? 0) > 0);
    $customerPaidRows = array_filter($eligibleRows, static fn (array $item): bool => (bool) ($item['is_invoice_paid'] ?? false));
    $salesPaidRows = array_filter($customerPaidRows, static fn (array $item): bool => (string) ($item['bonus_status'] ?? '') === 'Terbayar');
    $filteredInvoiceRows = array_values(array_filter($invoiceRows, static function (array $item) use ($customerStatus, $bonusStatus, $salesFilter): bool {
        if ($salesFilter !== '' && strcasecmp((string) ($item['sales'] ?? ''), $salesFilter) !== 0) {
            return false;
        }
        if ($customerStatus === 'paid' && ! (bool) ($item['is_invoice_paid'] ?? false)) {
            return false;
        }
        if ($customerStatus === 'unpaid' && (bool) ($item['is_invoice_paid'] ?? false)) {
            return false;
        }
        if ($bonusStatus === 'paid' && (string) ($item['bonus_status'] ?? '') !== 'Terbayar') {
            return false;
        }
        if ($bonusStatus === 'unpaid' && (string) ($item['bonus_status'] ?? '') === 'Terbayar') {
            return false;
        }

        return true;
    }));

    return [
        'ok' => true,
        'items' => $filteredInvoiceRows,
        'sales_summary' => array_values($salesTotals),
        'filters' => [
            'month' => $month,
            'year' => $year,
            'sales' => $salesFilter,
            'customer_status' => $customerStatus,
            'bonus_status' => $bonusStatus,
        ],
        'rules' => $rules,
        'summary' => [
            'omzet' => array_sum(array_map(static fn (array $item): float => (float) $item['omzet'], $salesTotals)),
            'paid_omzet' => array_sum(array_map(static fn (array $item): float => (float) $item['paid_omzet'], $salesTotals)),
            'bonus' => array_sum(array_map(static fn (array $item): float => (float) $item['bonus'], $eligibleRows)),
            'payable_bonus' => array_sum(array_map(static fn (array $item): float => (float) $item['bonus'], $customerPaidRows)),
            'paid_to_sales_bonus' => array_sum(array_map(static fn (array $item): float => (float) $item['bonus'], $salesPaidRows)),
            'unpaid_to_sales_bonus' => array_sum(array_map(static fn (array $item): float => (float) $item['bonus'], $customerPaidRows)) - array_sum(array_map(static fn (array $item): float => (float) $item['bonus'], $salesPaidRows)),
            'eligible_count' => count(array_filter($salesTotals, static fn (array $item): bool => (bool) ($item['eligible'] ?? false))),
        ],
        'error' => null,
    ];
}

function update_internal_sales_bonus_invoice_status(string $kodeInvoice, string $sales, string $status, string $paidDate, mixed $month, mixed $year): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    $month = (int) ($month ?: date('n'));
    $year = (int) ($year ?: date('Y'));
    if ($month < 1 || $month > 12 || $year < 2000) {
        return ['ok' => false, 'message' => 'Periode bonus tidak valid.'];
    }

    $kodeInvoice = trim($kodeInvoice);
    $sales = normalize_spaces($sales);
    $status = strcasecmp(trim($status), 'Terbayar') === 0 ? 'Terbayar' : 'Belum Dibayar';
    $paidDate = date_input_value($paidDate);
    if ($kodeInvoice === '' || $sales === '') {
        return ['ok' => false, 'message' => 'Invoice dan sales wajib diisi.'];
    }
    if ($status === 'Terbayar' && $paidDate === '') {
        return ['ok' => false, 'message' => 'Tanggal bayar bonus wajib diisi jika status terbayar.'];
    }

    $bonusData = fetch_internal_sales_bonus($month, $year);
    if (! ($bonusData['ok'] ?? false)) {
        return ['ok' => false, 'message' => $bonusData['error'] ?? 'Bonus gagal dihitung.'];
    }
    $targetRow = null;
    foreach (($bonusData['items'] ?? []) as $item) {
        if ((string) ($item['kode_invoice'] ?? '') === $kodeInvoice && strcasecmp((string) ($item['sales'] ?? ''), $sales) === 0) {
            $targetRow = $item;
            break;
        }
    }
    if (! is_array($targetRow)) {
        return ['ok' => false, 'message' => 'Data bonus invoice tidak ditemukan pada periode ini.'];
    }
    if (! (bool) ($targetRow['eligible'] ?? false) || (float) ($targetRow['bonus'] ?? 0) <= 0) {
        return ['ok' => false, 'message' => 'Sales belum memenuhi target bonus pada periode ini.'];
    }
    if ($status === 'Terbayar' && ! (bool) ($targetRow['is_invoice_paid'] ?? false)) {
        return ['ok' => false, 'message' => 'Bonus belum bisa dibayar karena invoice customer belum lunas.'];
    }

    try {
        $pdo->beginTransaction();

        $marker = internal_sales_bonus_marker($kodeInvoice, $sales);
        $existingStmt = $pdo->prepare("
            SELECT id FROM operational_expenses
            WHERE kategori = 'bonus'
              AND keterangan LIKE ?
            LIMIT 1
        ");
        $existingStmt->execute([$marker . '%']);
        $existingId = (int) ($existingStmt->fetchColumn() ?: 0);

        if ($existingId > 0) {
            delete_accounting_journal_source($pdo, 'operational_expense', (string) $existingId);
        }

        $periodDate = date_input_value((string) ($targetRow['tanggal_invoice'] ?? ''));
        if ($periodDate === '') {
            $periodDate = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        }
        $paymentStatus = $status === 'Terbayar' ? 'Lunas' : 'Hutang';
        $paymentDate = $status === 'Terbayar' ? $paidDate : null;
        $name = 'Bonus Sales Internal ' . $sales . ' ' . ($targetRow['nomor_invoice'] ?? $kodeInvoice);
        $note = $marker . ' | Bonus 5% dari invoice ' . ($targetRow['nomor_invoice'] ?? $kodeInvoice) . ', customer ' . ($targetRow['customer'] ?? '') . ', omzet ' . rupiah($targetRow['omzet'] ?? 0);

        if ($existingId > 0) {
            $stmt = $pdo->prepare("
                UPDATE operational_expenses
                SET tanggal = ?,
                    bulan_pnl = ?,
                    tahun_pnl = ?,
                    nama_pengeluaran = ?,
                    jumlah = ?,
                    status_pembayaran = ?,
                    tanggal_pembayaran = ?,
                    keterangan = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $periodDate,
                $month,
                $year,
                $name,
                round((float) ($targetRow['bonus'] ?? 0), 2),
                $paymentStatus,
                $paymentDate,
                $note,
                $existingId,
            ]);
            $expenseId = $existingId;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO operational_expenses
                    (tanggal, bulan_pnl, tahun_pnl, kategori, nama_pengeluaran, jumlah, status_pembayaran, tanggal_pembayaran, keterangan)
                VALUES
                    (?, ?, ?, 'bonus', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $periodDate,
                $month,
                $year,
                $name,
                round((float) ($targetRow['bonus'] ?? 0), 2),
                $paymentStatus,
                $paymentDate,
                $note,
            ]);
            $expenseId = (int) $pdo->lastInsertId();
        }

        $journalLines = generate_operational_expense_journal($pdo, $expenseId);
        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Status bonus ' . ($targetRow['nomor_invoice'] ?? $kodeInvoice) . ' untuk ' . $sales . ' berhasil diperbarui. ' . $journalLines . ' baris jurnal diposting.',
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Update bonus gagal: ' . $exception->getMessage()];
    }
}

function post_internal_sales_bonus(mixed $month, mixed $year): array
{
    return ['ok' => false, 'message' => 'Posting bonus bulanan sudah diganti dengan update status bonus per invoice.'];
}

function run_seed_krisna_april_bonus_status(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi.',
            'statements' => 0,
        ];
    }

    $paidInvoices = [
        '352/BM-INV/IV/2026',
        '353/BM-INV/IV/2026',
        '355/BM-INV/IV/2026',
        '356/BM-INV/IV/2026',
        '357/BM-INV/IV/2026',
        '358/BM-INV/IV/2026',
        '360/BM-INV/IV/2026',
        '361/BM-INV/IV/2026',
        '365/BM-INV/IV/2026',
        '369/BM-INV/IV/2026',
        '375/BM-INV/IV/2026',
        '377/BM-INV/IV/2026',
        '378/BM-INV/IV/2026',
    ];
    $unpaidInvoices = [
        '364/BM-INV/IV/2026',
        '366/BM-INV/IV/2026',
        '367/BM-INV/IV/2026',
        '379/BM-INV/IV/2026',
        '381/BM-INV/IV/2026',
        '384/BM-INV/IV/2026',
    ];

    $find = $pdo->prepare('SELECT kode_invoice FROM invoices WHERE nomor_invoice = ? LIMIT 1');
    $updated = 0;
    $failed = [];
    $logs = [];

    foreach ([
        ['Terbayar', $paidInvoices, '2026-06-26'],
        ['Belum Dibayar', $unpaidInvoices, ''],
    ] as [$status, $invoices, $paidDate]) {
        foreach ($invoices as $nomorInvoice) {
            $find->execute([$nomorInvoice]);
            $kodeInvoice = (string) ($find->fetchColumn() ?: '');
            if ($kodeInvoice === '') {
                $failed[] = $nomorInvoice . ' tidak ditemukan';
                continue;
            }

            $result = update_internal_sales_bonus_invoice_status($kodeInvoice, 'Krisna', $status, $paidDate, 4, 2026);
            if ($result['ok'] ?? false) {
                $updated++;
                $logs[] = $nomorInvoice . ' => ' . $status;
            } else {
                $failed[] = $nomorInvoice . ': ' . ($result['message'] ?? 'gagal');
            }
        }
    }

    return [
        'ok' => $failed === [],
        'message' => $failed === []
            ? 'Seeder bonus Krisna April berhasil. ' . $updated . ' invoice diperbarui.'
            : 'Seeder bonus Krisna April selesai dengan beberapa error. Berhasil ' . $updated . ', gagal ' . count($failed) . '.',
        'statements' => $updated,
        'output' => implode(PHP_EOL, array_merge($logs, $failed)),
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
        'SELECT kode_invoice, nomor_invoice, tanggal_invoice, nomor_surat_jalan, tanggal_surat_jalan, po_number, kode_sales_1, nama_sales_1, kode_sales_2, nama_sales_2, komisi_sales_1_persen, komisi_sales_2_persen, komisi_sales_terbayar, komisi_sales_belum_terbayar, status_pembayaran_komisi_sales, tanggal_transfer_komisi_sales, komisi_manager_terbayar, komisi_manager_utang, tanggal_transfer_komisi_manager, tanggal_transfer_komisi_admin, pph_final_terbayar, pph_final_belum_terbayar, komisi_admin_terbayar, komisi_admin_belum_terbayar, biaya_kirim, biaya_admin_bank, kode_customer, nama_customer_master, nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat, total_item, total_qty, subtotal, harga_normal_pricelist, discount_persen, discount_amount, total_harga_jual, status_pembayaran, tanggal_pembayaran, total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, tanggal_transfer_pembelian_barang, file_invoice FROM invoices WHERE kode_invoice = :kode_invoice OR nomor_invoice = :nomor_invoice LIMIT 1',
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

function fetch_invoice_form_options(string $code = ''): array
{
    $customers = db_all('SELECT kode_customer, nama_customer, nama_laundry, no_telepon, alamat_default FROM master_customers ORDER BY nama_laundry') ?? [];
    $sales = db_all('SELECT kode_sales, nama_sales FROM master_sales ORDER BY nama_sales') ?? [];
    $barang = db_all('SELECT kode_barang, nama_barang, ukuran, isi_default, satuan_default, harga_default FROM master_barang ORDER BY nama_barang, ukuran') ?? [];
    $edit = [
        'mode' => 'create',
        'invoice' => null,
        'items' => [],
    ];

    if (trim($code) !== '') {
        $detail = fetch_invoice_detail($code);

        if (($detail['ok'] ?? false) && is_array($detail['invoice'] ?? null)) {
            $invoice = $detail['invoice'];
            $edit = [
                'mode' => 'update',
                'invoice' => [
                    ...$invoice,
                    'tanggal_invoice_input' => date_input_value((string) ($invoice['tanggal_invoice'] ?? '')),
                    'tanggal_surat_jalan_input' => date_input_value((string) ($invoice['tanggal_surat_jalan'] ?? '')),
                ],
                'items' => array_map(static fn (array $item): array => [
                    'kode_barang' => (string) ($item['kode_barang'] ?? ''),
                    'isi' => (string) ($item['isi_invoice'] ?? ''),
                    'jumlah' => (float) ($item['jumlah'] ?? 0),
                    'satuan' => (string) ($item['satuan'] ?? ''),
                    'harga' => (float) ($item['harga'] ?? 0),
                    'total' => (float) ($item['total'] ?? 0),
                ], $detail['items'] ?? []),
            ];
        }
    }

    return [
        'ok' => true,
        'customers' => $customers,
        'sales' => $sales,
        'barang' => $barang,
        'edit' => $edit,
        'payment_statuses' => [
            'Belum Lunas',
            'Lunas',
        ],
        'commission_statuses' => [
            'Belum TF',
            'Transfer',
        ],
    ];
}

function clean_money_value(mixed $value): float
{
    $value = trim((string)$value);
    if ($value === '') {
        return 0.0;
    }
    
    // Remove any currency symbol like "Rp" and spaces
    $value = preg_replace('/[^\d,.-]/u', '', $value);
    
    if (str_contains($value, ',')) {
        // If it has comma, it's Indonesian style: dots are thousands, comma is decimal
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } else {
        // If it has only dots, check if it's thousands (e.g. 1.000 or 1.500.000)
        $parts = explode('.', $value);
        if (count($parts) > 1) {
            $isThousands = true;
            foreach (array_slice($parts, 1) as $part) {
                if (strlen($part) !== 3) {
                    $isThousands = false;
                    break;
                }
            }
            if ($isThousands) {
                $value = implode('', $parts);
            }
        }
    }
    
    return (float) $value;
}

function save_invoice_form(array $postData): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    $cleanFloat = static fn ($val) => clean_money_value($val);
    $cleanInt = static fn ($val) => (int) ($val ?? 0);
    $cleanString = static fn ($val) => trim((string) ($val ?? ''));

    $formatDateToIndonesian = static function (string $dateStr): string {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateStr, $matches)) {
            $year = $matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            $months = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $monthName = $months[$month] ?? '';
            return "$day $monthName $year";
        }
        return $dateStr;
    };

    $getSalesName = static function ($pdo, $kode) {
        if (empty($kode)) return null;
        $stmt = $pdo->prepare("SELECT nama_sales FROM master_sales WHERE kode_sales = ?");
        $stmt->execute([$kode]);
        return $stmt->fetchColumn() ?: null;
    };

    $getBarangInfo = static function ($pdo, $kode) {
        if (empty($kode)) return null;
        $stmt = $pdo->prepare("SELECT nama_barang, ukuran FROM master_barang WHERE kode_barang = ?");
        $stmt->execute([$kode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    };

    $kodeInvoice = $cleanString($postData['kode_invoice'] ?? '');
    $isUpdate = ($kodeInvoice !== '');

    $nomorInvoice = $cleanString($postData['nomor_invoice'] ?? '');
    if ($nomorInvoice === '') {
        return ['ok' => false, 'message' => 'Nomor invoice tidak boleh kosong.'];
    }

    // Get customer info
    $kodeCustomer = $cleanString($postData['kode_customer'] ?? '');
    if ($kodeCustomer === '') {
        return ['ok' => false, 'message' => 'Customer/Nama Laundry tidak boleh kosong.'];
    }
    $namaCustomerMaster = '';
    $namaLaundryInvoice = '';
    $namaCustomerInvoice = $cleanString($postData['nama_customer'] ?? '');
    if ($kodeCustomer !== '') {
        $stmt = $pdo->prepare("SELECT nama_customer, nama_laundry FROM master_customers WHERE kode_customer = ?");
        $stmt->execute([$kodeCustomer]);
        $custRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($custRow) {
            $namaCustomerMaster = $custRow['nama_customer'];
            $namaLaundryInvoice = $custRow['nama_laundry'];
        }
    }

    $tanggalInvoiceRaw = $cleanString($postData['tanggal_invoice'] ?? '');
    if ($tanggalInvoiceRaw === '') {
        return ['ok' => false, 'message' => 'Tanggal invoice tidak boleh kosong.'];
    }
    $tanggalInvoice = $formatDateToIndonesian($tanggalInvoiceRaw);

    // Calculate totals from items
    $items = $postData['items'] ?? [];
    if (empty($items)) {
        return ['ok' => false, 'message' => 'Detail barang tidak boleh kosong. Minimal masukkan 1 barang.'];
    }

    $totalItem = count($items);
    $totalQty = 0.0;
    $subtotal = 0.0;

    foreach ($items as $idx => $item) {
        $kodeBarang = trim((string)($item['kode_barang'] ?? ''));
        if ($kodeBarang === '') {
            return ['ok' => false, 'message' => 'Barang pada baris ' . ($idx + 1) . ' tidak boleh kosong.'];
        }
        $qty = (float)($item['jumlah'] ?? 0);
        if ($qty <= 0) {
            return ['ok' => false, 'message' => 'Jumlah barang pada baris ' . ($idx + 1) . ' harus lebih besar dari 0.'];
        }
        $harga = clean_money_value($item['harga'] ?? 0);
        if ($harga <= 0) {
            return ['ok' => false, 'message' => 'Harga barang pada baris ' . ($idx + 1) . ' harus lebih besar dari 0.'];
        }
        $totalQty += $qty;
        $subtotal += clean_money_value($item['total'] ?? 0);
    }

    // Form inputs mapped to DB
    $nomorSuratJalan = $cleanString($postData['nomor_surat_jalan'] ?? '');
    $tanggalSuratJalan = $formatDateToIndonesian($cleanString($postData['tanggal_surat_jalan'] ?? ''));
    $poNumber = $cleanString($postData['po_number'] ?? '');
    $noTelepon = $cleanString($postData['no_telepon'] ?? '');
    $alamat = $cleanString($postData['alamat'] ?? '');

    $kodeSales1 = $cleanString($postData['kode_sales_1'] ?? '');
    $namaSales1 = $kodeSales1 !== '' ? $getSalesName($pdo, $kodeSales1) : '';
    $kodeSales2 = $cleanString($postData['kode_sales_2'] ?? '');
    $namaSales2 = $getSalesName($pdo, $kodeSales2);

    $komisiSales1Persen = (float) ($postData['komisi_sales_1_persen'] ?? 0);
    $komisiSales2Persen = (float) ($postData['komisi_sales_2_persen'] ?? 0);
    $komisiSalesTerbayar = $cleanFloat($postData['komisi_sales_terbayar'] ?? 0);
    $statusPembayaranSales = $cleanString($postData['status_pembayaran_sales'] ?? '');
    $tanggalTransferKomisiSales = !empty($postData['tanggal_transfer_komisi_sales']) ? $postData['tanggal_transfer_komisi_sales'] : null;
    $komisiSalesBelumTerbayar = $cleanFloat($postData['komisi_sales_belum_terbayar'] ?? 0);

    // Manager
    $komisiManagerTerbayar = $cleanFloat($postData['komisi_manager_terbayar'] ?? 0);
    $komisiManagerUtang = $cleanFloat($postData['komisi_manager_utang'] ?? 0);
    $tanggalTransferKomisiManager = !empty($postData['tanggal_transfer_manager']) ? $postData['tanggal_transfer_manager'] : null;

    // Admin
    $komisiAdminTerbayar = $cleanFloat($postData['komisi_admin_terbayar'] ?? 0);
    $komisiAdminBelumTerbayar = $cleanFloat($postData['komisi_admin_belum_terbayar'] ?? 0);
    $tanggalTransferKomisiAdmin = !empty($postData['tanggal_transfer_komisi_admin']) ? $postData['tanggal_transfer_komisi_admin'] : null;

    // Tax
    $pphFinalTerbayar = $cleanFloat($postData['pph_final_terbayar'] ?? 0);
    $pphFinalBelumTerbayar = $cleanFloat($postData['pph_final_belum_terbayar'] ?? 0);

    $biayaKirim = $cleanFloat($postData['biaya_kirim'] ?? 0);
    $biayaAdminBank = $cleanFloat($postData['biaya_admin_bank'] ?? 0);

    $discountPersen = (float) ($postData['discount'] ?? 0);
    $discountAmount = $cleanFloat($postData['discount_amount'] ?? 0);
    $totalHargaJual = $cleanFloat($postData['total_harga_jual'] ?? 0);

    $statusPembayaran = $cleanString($postData['status_pembayaran'] ?? 'Lunas');
    $tanggalPembayaran = !empty($postData['tanggal_pembayaran']) ? $postData['tanggal_pembayaran'] : null;

    // Purchase / COGS
    $totalPembelianBarang = $cleanFloat($postData['pembelian_barang'] ?? 0);
    $totalUtangPembelianBarang = $cleanFloat($postData['jumlah_utang_pembelian_barang'] ?? 0);
    $tanggalTransferPembelianBarang = !empty($postData['tanggal_transfer_pembelian_barang']) ? $postData['tanggal_transfer_pembelian_barang'] : null;
    $statusPembelianBarang = $tanggalTransferPembelianBarang !== null ? 'Lunas' : 'Utang';

    try {
        $pdo->beginTransaction();

        if ($isUpdate) {
            // Check if exists
            $stmt = $pdo->prepare("SELECT file_invoice FROM invoices WHERE kode_invoice = ?");
            $stmt->execute([$kodeInvoice]);
            $existingInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existingInvoice) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'Invoice tidak ditemukan.'];
            }
            $fileInvoice = $existingInvoice['file_invoice'];

            // Update invoices
            $stmt = $pdo->prepare('
                UPDATE invoices SET
                    nomor_invoice = ?,
                    tanggal_invoice = ?,
                    nomor_surat_jalan = ?,
                    tanggal_surat_jalan = ?,
                    po_number = ?,
                    kode_sales_1 = ?,
                    nama_sales_1 = ?,
                    kode_sales_2 = ?,
                    nama_sales_2 = ?,
                    komisi_sales_1_persen = ?,
                    komisi_sales_2_persen = ?,
                    komisi_sales_terbayar = ?,
                    komisi_sales_belum_terbayar = ?,
                    status_pembayaran_komisi_sales = ?,
                    tanggal_transfer_komisi_sales = ?,
                    komisi_manager_terbayar = ?,
                    komisi_manager_utang = ?,
                    tanggal_transfer_komisi_manager = ?,
                    tanggal_transfer_komisi_admin = ?,
                    kode_customer = ?,
                    nama_customer_master = ?,
                    nama_customer_invoice = ?,
                    nama_laundry_invoice = ?,
                    no_telepon = ?,
                    alamat = ?,
                    total_item = ?,
                    total_qty = ?,
                    subtotal = ?,
                    harga_normal_pricelist = ?,
                    discount_persen = ?,
                    discount_amount = ?,
                    total_harga_jual = ?,
                    status_pembayaran = ?,
                    tanggal_pembayaran = ?,
                    pph_final_terbayar = ?,
                    pph_final_belum_terbayar = ?,
                    komisi_admin_terbayar = ?,
                    komisi_admin_belum_terbayar = ?,
                    biaya_kirim = ?,
                    biaya_admin_bank = ?,
                    total_pembelian_barang = ?,
                    total_utang_pembelian_barang = ?,
                    status_pembelian_barang = ?,
                    tanggal_transfer_pembelian_barang = ?
                WHERE kode_invoice = ?
            ');
            $stmt->execute([
                $nomorInvoice, $tanggalInvoice, $nomorSuratJalan, $tanggalSuratJalan, $poNumber,
                $kodeSales1, $namaSales1, $kodeSales2, $namaSales2,
                $komisiSales1Persen, $komisiSales2Persen, $komisiSalesTerbayar, $komisiSalesBelumTerbayar,
                $statusPembayaranSales, $tanggalTransferKomisiSales,
                $komisiManagerTerbayar, $komisiManagerUtang, $tanggalTransferKomisiManager,
                $tanggalTransferKomisiAdmin, $kodeCustomer, $namaCustomerMaster,
                $namaCustomerInvoice, $namaLaundryInvoice, $noTelepon, $alamat,
                $totalItem, $totalQty, $subtotal, $subtotal, $discountPersen, $discountAmount, $totalHargaJual,
                $statusPembayaran, $tanggalPembayaran,
                $pphFinalTerbayar, $pphFinalBelumTerbayar,
                $komisiAdminTerbayar, $komisiAdminBelumTerbayar,
                $biayaKirim, $biayaAdminBank,
                $totalPembelianBarang, $totalUtangPembelianBarang, $statusPembelianBarang,
                $tanggalTransferPembelianBarang,
                $kodeInvoice
            ]);

            // Delete existing invoice items
            $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE kode_invoice = ?");
            $stmt->execute([$kodeInvoice]);
        } else {
            // Generate next code
            $stmt = $pdo->query("SELECT kode_invoice FROM invoices WHERE kode_invoice LIKE 'INV-%'");
            $codes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $maxNum = 0;
            foreach ($codes as $code) {
                if (preg_match('/^INV-(\d+)$/', $code, $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $nextNum = $maxNum + 1;
            $kodeInvoice = 'INV-' . str_pad((string)$nextNum, 5, '0', STR_PAD_LEFT);
            $fileInvoice = null;

            // Insert invoices
            $stmt = $pdo->prepare('
                INSERT INTO invoices (
                    kode_invoice, nomor_invoice, tanggal_invoice, nomor_surat_jalan, tanggal_surat_jalan, po_number,
                    kode_sales_1, nama_sales_1, kode_sales_2, nama_sales_2,
                    komisi_sales_1_persen, komisi_sales_2_persen, komisi_sales_terbayar, komisi_sales_belum_terbayar,
                    status_pembayaran_komisi_sales, tanggal_transfer_komisi_sales,
                    komisi_manager_terbayar, komisi_manager_utang, tanggal_transfer_komisi_manager,
                    tanggal_transfer_komisi_admin, kode_customer, nama_customer_master,
                    nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat,
                    total_item, total_qty, subtotal, harga_normal_pricelist, discount_persen, discount_amount, total_harga_jual,
                    status_pembayaran, tanggal_pembayaran,
                    pph_final_terbayar, pph_final_belum_terbayar,
                    komisi_admin_terbayar, komisi_admin_belum_terbayar,
                    biaya_kirim, biaya_admin_bank,
                    total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, tanggal_transfer_pembelian_barang, file_invoice
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?
                )
            ');
            $stmt->execute([
                $kodeInvoice, $nomorInvoice, $tanggalInvoice, $nomorSuratJalan, $tanggalSuratJalan, $poNumber,
                $kodeSales1, $namaSales1, $kodeSales2, $namaSales2,
                $komisiSales1Persen, $komisiSales2Persen, $komisiSalesTerbayar, $komisiSalesBelumTerbayar,
                $statusPembayaranSales, $tanggalTransferKomisiSales,
                $komisiManagerTerbayar, $komisiManagerUtang, $tanggalTransferKomisiManager,
                $tanggalTransferKomisiAdmin, $kodeCustomer, $namaCustomerMaster,
                $namaCustomerInvoice, $namaLaundryInvoice, $noTelepon, $alamat,
                $totalItem, $totalQty, $subtotal, $subtotal, $discountPersen, $discountAmount, $totalHargaJual,
                $statusPembayaran, $tanggalPembayaran,
                $pphFinalTerbayar, $pphFinalBelumTerbayar,
                $komisiAdminTerbayar, $komisiAdminBelumTerbayar,
                $biayaKirim, $biayaAdminBank,
                $totalPembelianBarang, $totalUtangPembelianBarang, $statusPembelianBarang, $tanggalTransferPembelianBarang, $fileInvoice
            ]);
        }

        // Insert new invoice items
        $stmt = $pdo->prepare('
            INSERT INTO invoice_items (
                kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang,
                nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice,
                jumlah, satuan, harga, total, file_invoice, baris
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $baris = 23;
        foreach ($items as $item) {
            $kodeBarang = $cleanString($item['kode_barang'] ?? '');
            if ($kodeBarang === '') continue;

            $barangInfo = $getBarangInfo($pdo, $kodeBarang);
            $namaBarangMaster = $barangInfo ? $barangInfo['nama_barang'] : '';
            $ukuranMaster = $barangInfo ? $barangInfo['ukuran'] : '';
            $namaBarangInvoice = $namaBarangMaster;

            $isiInvoice = $cleanString($item['isi'] ?? '');
            $jumlah = (float) ($item['jumlah'] ?? 0);
            $satuan = $cleanString($item['satuan'] ?? '');
            $harga = $cleanFloat($item['harga'] ?? 0);
            $total = $cleanFloat($item['total'] ?? 0);

            $stmt->execute([
                $kodeInvoice, $nomorInvoice, $tanggalInvoice, $kodeCustomer, $kodeBarang,
                $namaBarangMaster, $ukuranMaster, $namaBarangInvoice, $isiInvoice,
                $jumlah, $satuan, $harga, $total, $fileInvoice, $baris
            ]);
            $baris += 2;
        }

        post_invoice_accounting_journal($pdo, $kodeInvoice);

        $pdo->commit();
        return ['ok' => true, 'kode_invoice' => $kodeInvoice];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'message' => 'Gagal menyimpan ke database: ' . $exception->getMessage()];
    }
}

function date_input_value(string $date): string
{
    $date = normalize_spaces($date);

    if ($date === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
        return $date;
    }

    if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $date, $match) !== 1) {
        return '';
    }

    $months = array_change_key_case(array_flip(invoice_months()), CASE_LOWER);
    $month = $months[strtolower($match[2])] ?? null;

    if ($month === null) {
        return '';
    }

    return sprintf('%04d-%02d-%02d', (int) $match[3], (int) $month, (int) $match[1]);
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

function clean_decimal(mixed $value, int $precision = 2): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $number = (float) $value;

    if (abs($number) < 0.0000001) {
        return '0';
    }

    return rtrim(rtrim(number_format($number, $precision, '.', ''), '0'), '.');
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

function set_google_api_error(string $message): void
{
    global $google_api_last_error;
    $google_api_last_error = $message;
}

function get_google_api_error(): ?string
{
    global $google_api_last_error;
    return $google_api_last_error ?? null;
}

function google_service_account_access_token(): array
{
    static $cachedToken = null;

    if ($cachedToken !== null && $cachedToken['expires_at'] > time() + 60) {
        return $cachedToken;
    }

    // Check if OAuth 2.0 Client credentials and Refresh Token are defined in .env
    require_once dirname(__DIR__) . '/config/env.php';
    $clientId = busamas_env('GOOGLE_CLIENT_ID');
    $clientSecret = busamas_env('GOOGLE_CLIENT_SECRET');
    $refreshToken = busamas_env('GOOGLE_REFRESH_TOKEN');

    if (!empty($clientId) && !empty($clientSecret) && !empty($refreshToken)) {
        $tokenUri = 'https://oauth2.googleapis.com/token';
        $response = http_post_form($tokenUri, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response['ok']) {
            $err = 'Gagal mendapatkan access token menggunakan refresh token: ' . $response['error'];
            set_google_api_error($err);
            return [
                'ok' => false,
                'access_token' => null,
                'expires_at' => 0,
                'error' => $err,
            ];
        }

        $payload = json_decode($response['body'], true);
        if (!is_array($payload) || empty($payload['access_token'])) {
            $err = 'Response OAuth token Google tidak valid.';
            set_google_api_error($err);
            return [
                'ok' => false,
                'access_token' => null,
                'expires_at' => 0,
                'error' => $err,
            ];
        }

        $cachedToken = [
            'ok' => true,
            'access_token' => $payload['access_token'],
            'expires_at' => time() + (int) ($payload['expires_in'] ?? 3600),
            'error' => null,
        ];

        return $cachedToken;
    }

    $credentialPath = google_service_account_path();

    if (! is_string($credentialPath) || ! is_readable($credentialPath)) {
        $err = 'File service account JSON belum tersedia atau tidak bisa dibaca: ' . $credentialPath;
        set_google_api_error($err);
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => $err,
        ];
    }

    $credentials = json_decode((string) file_get_contents($credentialPath), true);

    if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
        $err = 'Format service account JSON tidak valid.';
        set_google_api_error($err);
        return [
            'ok' => false,
            'access_token' => null,
            'expires_at' => 0,
            'error' => $err,
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
            'https://www.googleapis.com/auth/spreadsheets',
            'https://www.googleapis.com/auth/drive',
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

        for ($attempt = 1; $attempt <= 1; $attempt++) {
            $curl = curl_init($url);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
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
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
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

function fetch_laporan_penjualan(string $type = 'invoice', string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    if ($type === 'customer') {
        $invoices = db_all('SELECT kode_customer, COALESCE(nama_customer_master, nama_laundry_invoice) AS nama_customer, total_qty, total_harga_jual, nomor_invoice FROM invoices');
        $filtered = [];
        foreach ($invoices ?? [] as $inv) {
            $invNo = $inv['nomor_invoice'] ?? '';
            if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
            if ($year !== '' && invoice_year($invNo) !== $year) continue;

            $custCode = $inv['kode_customer'] ?? 'UNKNOWN';
            $custName = $inv['nama_customer'] ?? 'Unknown Customer';
            if (! isset($filtered[$custCode])) {
                $filtered[$custCode] = [
                    'kode_customer' => $custCode,
                    'nama_customer' => $custName,
                    'jumlah_invoice' => 0,
                    'total_qty' => 0.0,
                    'total_penjualan' => 0.0
                ];
            }
            $filtered[$custCode]['jumlah_invoice']++;
            $filtered[$custCode]['total_qty'] += (float)$inv['total_qty'];
            $filtered[$custCode]['total_penjualan'] += (float)$inv['total_harga_jual'];
        }
        $data = array_values($filtered);
        usort($data, static fn ($a, $b) => $b['total_penjualan'] <=> $a['total_penjualan']);

    } elseif ($type === 'produk') {
        $items = db_all('SELECT kode_barang, nama_barang_master, ukuran_master, jumlah, total, nomor_invoice FROM invoice_items');
        $filtered = [];
        foreach ($items ?? [] as $item) {
            $invNo = $item['nomor_invoice'] ?? '';
            if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
            if ($year !== '' && invoice_year($invNo) !== $year) continue;

            $code = $item['kode_barang'] ?? 'UNKNOWN';
            if (! isset($filtered[$code])) {
                $filtered[$code] = [
                    'kode_barang' => $code,
                    'nama_barang_master' => $item['nama_barang_master'] ?? 'Unknown',
                    'ukuran_master' => $item['ukuran_master'] ?? '',
                    'total_qty' => 0.0,
                    'total_penjualan' => 0.0
                ];
            }
            $filtered[$code]['total_qty'] += (float)$item['jumlah'];
            $filtered[$code]['total_penjualan'] += (float)$item['total'];
        }
        $data = array_values($filtered);
        usort($data, static fn ($a, $b) => $b['total_penjualan'] <=> $a['total_penjualan']);

    } elseif ($type === 'sales') {
        $invoices = db_all('SELECT nomor_invoice, kode_sales_1, nama_sales_1, kode_sales_2, nama_sales_2, komisi_sales_1_persen, komisi_sales_2_persen, total_harga_jual FROM invoices');
        $filtered = [];
        foreach ($invoices ?? [] as $inv) {
            $invNo = $inv['nomor_invoice'] ?? '';
            if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
            if ($year !== '' && invoice_year($invNo) !== $year) continue;

            // Sales 1
            if (! empty($inv['kode_sales_1'])) {
                $sCode = $inv['kode_sales_1'];
                $sName = $inv['nama_sales_1'];
                $penjualan = (float)$inv['total_harga_jual'];
                $komisi = $penjualan * ((float)$inv['komisi_sales_1_persen'] / 100);
                if (! isset($filtered[$sCode])) {
                    $filtered[$sCode] = [
                        'kode_sales' => $sCode,
                        'nama_sales' => $sName,
                        'jumlah_invoice' => 0,
                        'total_penjualan' => 0.0,
                        'total_komisi' => 0.0
                    ];
                }
                $filtered[$sCode]['jumlah_invoice']++;
                $filtered[$sCode]['total_penjualan'] += $penjualan;
                $filtered[$sCode]['total_komisi'] += $komisi;
            }
            // Sales 2
            if (! empty($inv['kode_sales_2'])) {
                $sCode = $inv['kode_sales_2'];
                $sName = $inv['nama_sales_2'];
                $penjualan = (float)$inv['total_harga_jual'];
                $komisi = $penjualan * ((float)$inv['komisi_sales_2_persen'] / 100);
                if (! isset($filtered[$sCode])) {
                    $filtered[$sCode] = [
                        'kode_sales' => $sCode,
                        'nama_sales' => $sName,
                        'jumlah_invoice' => 0,
                        'total_penjualan' => 0.0,
                        'total_komisi' => 0.0
                    ];
                }
                $filtered[$sCode]['jumlah_invoice']++;
                $filtered[$sCode]['total_penjualan'] += $penjualan;
                $filtered[$sCode]['total_komisi'] += $komisi;
            }
        }
        $data = array_values($filtered);
        usort($data, static fn ($a, $b) => $b['total_penjualan'] <=> $a['total_penjualan']);

    } else {
        $type = 'invoice';
        $invoices = db_all('SELECT nomor_invoice, tanggal_invoice, COALESCE(nama_customer_master, nama_laundry_invoice) AS nama_customer, nama_sales_1, total_qty, subtotal, discount_amount, total_harga_jual FROM invoices ORDER BY nomor_invoice DESC');
        $data = [];
        foreach ($invoices ?? [] as $inv) {
            $invNo = $inv['nomor_invoice'] ?? '';
            if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
            if ($year !== '' && invoice_year($invNo) !== $year) continue;
            $data[] = $inv;
        }
    }

    return [
        'ok' => true,
        'type' => $type,
        'items' => $data ?? [],
    ];
}

function fetch_laporan_profit_loss(string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    $invoices = db_all('
        SELECT nomor_invoice, subtotal, total_harga_jual, discount_amount, 
               total_pembelian_barang, total_utang_pembelian_barang, 
               komisi_sales_1_persen, komisi_sales_2_persen, komisi_sales_terbayar, komisi_sales_belum_terbayar,
               komisi_manager_terbayar, komisi_manager_utang, 
               pph_final_terbayar, pph_final_belum_terbayar, 
               komisi_admin_terbayar, komisi_admin_belum_terbayar, 
               biaya_kirim, biaya_admin_bank 
        FROM invoices
    ');

    $pendapatan = 0.0;
    $discount = 0.0;
    $komisi_sales = 0.0;
    $komisi_manager = 0.0;
    $komisi_admin = 0.0;
    $pph = 0.0;
    $pembelian_barang = 0.0;
    $biaya_admin_bank = 0.0;
    $biaya_kirim = 0.0;

    foreach ($invoices ?? [] as $inv) {
        $invNo = $inv['nomor_invoice'] ?? '';
        if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
        if ($year !== '' && invoice_year($invNo) !== $year) continue;

        $pendapatan += (float)($inv['subtotal'] ?? 0);
        $discount += (float)($inv['discount_amount'] ?? 0);
        
        $rev = (float)($inv['total_harga_jual'] ?? 0);
        $com1 = $rev * ((float)($inv['komisi_sales_1_persen'] ?? 0) / 100);
        $com2 = $rev * ((float)($inv['komisi_sales_2_persen'] ?? 0) / 100);
        $storedSalesCommission = (float)($inv['komisi_sales_terbayar'] ?? 0) + (float)($inv['komisi_sales_belum_terbayar'] ?? 0);
        $komisi_sales += $storedSalesCommission > 0 ? $storedSalesCommission : ($com1 + $com2);

        $komisi_manager += (float)($inv['komisi_manager_terbayar'] ?? 0) + (float)($inv['komisi_manager_utang'] ?? 0);
        $pph += (float)($inv['pph_final_terbayar'] ?? 0) + (float)($inv['pph_final_belum_terbayar'] ?? 0);
        $komisi_admin += (float)($inv['komisi_admin_terbayar'] ?? 0) + (float)($inv['komisi_admin_belum_terbayar'] ?? 0);
        $pembelian_barang += (float)($inv['total_pembelian_barang'] ?? 0) + (float)($inv['total_utang_pembelian_barang'] ?? 0);
        $biaya_kirim += (float)($inv['biaya_kirim'] ?? 0);
        $biaya_admin_bank += (float)($inv['biaya_admin_bank'] ?? 0);
    }

    $opExpenses = fetch_operational_summary($month, $year);
    $operational = $opExpenses['total_pengeluaran'];

    // Bonus: baca dari tabel operational_expenses kategori='bonus'
    $bonusSql = "SELECT COALESCE(SUM(jumlah), 0) FROM operational_expenses WHERE kategori = 'bonus'";
    $bonusParams = [];
    if ($month !== '' && $year !== '') {
        $bonusSql .= ' AND ((bulan_pnl IS NOT NULL AND bulan_pnl = :bm AND tahun_pnl = :by)'
                   . '  OR  (bulan_pnl IS NULL AND MONTH(tanggal) = :bm2 AND YEAR(tanggal) = :by2))';
        $bonusParams = ['bm' => (int)$month, 'by' => (int)$year, 'bm2' => (int)$month, 'by2' => (int)$year];
    } elseif ($month !== '') {
        $bonusSql .= ' AND ((bulan_pnl IS NOT NULL AND bulan_pnl = :bm) OR (bulan_pnl IS NULL AND MONTH(tanggal) = :bm2))';
        $bonusParams = ['bm' => (int)$month, 'bm2' => (int)$month];
    } elseif ($year !== '') {
        $bonusSql .= ' AND ((tahun_pnl IS NOT NULL AND tahun_pnl = :by) OR (tahun_pnl IS NULL AND YEAR(tanggal) = :by2))';
        $bonusParams = ['by' => (int)$year, 'by2' => (int)$year];
    }
    $bonusStmt = $pdo->prepare($bonusSql);
    $bonusStmt->execute($bonusParams);
    $bonus = (float) $bonusStmt->fetchColumn();

    $total_pengeluaran = $komisi_sales + $komisi_manager + $komisi_admin + $pph + $pembelian_barang + $biaya_admin_bank + $biaya_kirim + $operational + $bonus + $discount;
    $laba_bersih = $pendapatan - $total_pengeluaran;

    return [
        'ok' => true,
        'pendapatan' => $pendapatan,
        'komisi_sales' => $komisi_sales,
        'komisi_manager' => $komisi_manager,
        'komisi_admin' => $komisi_admin,
        'pph' => $pph,
        'pembelian_barang' => $pembelian_barang,
        'biaya_admin_bank' => $biaya_admin_bank,
        'biaya_kirim' => $biaya_kirim,
        'operational' => $operational,
        'bonus' => $bonus,
        'discount' => $discount,
        'total_pengeluaran' => $total_pengeluaran,
        'laba_bersih' => $laba_bersih,
    ];
}

function fetch_laporan_hutang(string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    $invoices = db_all('SELECT nomor_invoice, tanggal_invoice, COALESCE(nama_customer_master, nama_laundry_invoice) AS nama_customer, total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang FROM invoices WHERE total_utang_pembelian_barang > 0');
    $data = [];

    foreach ($invoices ?? [] as $inv) {
        $invNo = $inv['nomor_invoice'] ?? '';
        if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
        if ($year !== '' && invoice_year($invNo) !== $year) continue;

        $data[] = $inv;
    }

    usort($data, static fn ($a, $b) => (float)($b['total_utang_pembelian_barang'] ?? 0) <=> (float)($a['total_utang_pembelian_barang'] ?? 0));

    return [
        'ok' => true,
        'items' => $data,
        'total_hutang' => array_sum(array_map(static fn ($item) => (float)($item['total_utang_pembelian_barang'] ?? 0), $data)),
    ];
}

function fetch_laporan_piutang(string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    $invoices = db_all('SELECT nomor_invoice, tanggal_invoice, tanggal_pembayaran, COALESCE(nama_customer_master, nama_laundry_invoice) AS nama_customer, no_telepon, total_harga_jual, status_pembayaran FROM invoices WHERE status_pembayaran <> \'Lunas\'');
    $filtered = [];

    foreach ($invoices ?? [] as $inv) {
        $invNo = $inv['nomor_invoice'] ?? '';
        if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
        if ($year !== '' && invoice_year($invNo) !== $year) continue;

        $filtered[] = $inv;
    }

    $aging = [
        '0_30' => ['label' => '0 - 30 Hari', 'items' => [], 'total' => 0],
        '31_60' => ['label' => '31 - 60 Hari', 'items' => [], 'total' => 0],
        '61_90' => ['label' => '61 - 90 Hari', 'items' => [], 'total' => 0],
        '90_plus' => ['label' => '> 90 Hari', 'items' => [], 'total' => 0],
    ];

    $overdue = [];
    $total_piutang = 0;
    $currentDate = strtotime('2026-06-20');

    foreach ($filtered as $invoice) {
        $dateStr = date_input_value($invoice['tanggal_invoice'] ?? '');
        $days = 0;
        if ($dateStr !== '') {
            $invoiceDate = strtotime($dateStr);
            $days = (int)floor(($currentDate - $invoiceDate) / 86400);
        }

        $amount = (float)($invoice['total_harga_jual'] ?? 0);
        $invoice['days_overdue'] = $days;
        $total_piutang += $amount;

        if ($days <= 30) {
            $aging['0_30']['items'][] = $invoice;
            $aging['0_30']['total'] += $amount;
        } elseif ($days <= 60) {
            $aging['31_60']['items'][] = $invoice;
            $aging['31_60']['total'] += $amount;
            $overdue[] = $invoice;
        } elseif ($days <= 90) {
            $aging['61_90']['items'][] = $invoice;
            $aging['61_90']['total'] += $amount;
            $overdue[] = $invoice;
        } else {
            $aging['90_plus']['items'][] = $invoice;
            $aging['90_plus']['total'] += $amount;
            $overdue[] = $invoice;
        }
    }

    return [
        'ok' => true,
        'aging' => $aging,
        'overdue' => $overdue,
        'total_piutang' => $total_piutang,
    ];
}

function fetch_laporan_komisi(string $month = '', string $year = '', string $status = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    $invoices = db_all('
        SELECT 
            nomor_invoice, 
            tanggal_invoice, 
            nama_laundry_invoice,
            COALESCE(nama_customer_master, nama_customer_invoice) AS nama_customer,
            subtotal,
            total_harga_jual,
            kode_sales_1,
            nama_sales_1,
            kode_sales_2,
            nama_sales_2,
            komisi_sales_1_persen,
            komisi_sales_2_persen,
            komisi_sales_terbayar,
            komisi_sales_belum_terbayar,
            status_pembayaran_komisi_sales,
            tanggal_transfer_komisi_sales,
            komisi_manager_terbayar,
            komisi_manager_utang,
            tanggal_transfer_komisi_manager,
            komisi_admin_terbayar,
            komisi_admin_belum_terbayar,
            tanggal_transfer_komisi_admin
        FROM invoices
        ORDER BY tanggal_invoice DESC, nomor_invoice DESC
    ') ?? [];

    $filtered = [];
    
    $summary = [
        'sales_paid' => 0.0,
        'sales_unpaid' => 0.0,
        'sales_total' => 0.0,
        
        'manager_paid' => 0.0,
        'manager_unpaid' => 0.0,
        'manager_total' => 0.0,
        
        'admin_paid' => 0.0,
        'admin_unpaid' => 0.0,
        'admin_total' => 0.0,
        
        'total_paid' => 0.0,
        'total_unpaid' => 0.0,
        'total_all' => 0.0,
    ];

    $options = [
        'years' => [],
    ];

    foreach ($invoices as $inv) {
        $invNo = $inv['nomor_invoice'] ?? '';
        $invMonth = invoice_month_number($invNo);
        $invYear = (string) invoice_year($invNo);

        if ($invYear !== '') {
            $options['years'][$invYear] = true;
        }

        if ($month !== '' && $invMonth !== (int)$month) {
            continue;
        }
        if ($year !== '' && $invYear !== $year) {
            continue;
        }

        $salesPaid   = (float) ($inv['komisi_sales_terbayar'] ?? 0);
        $salesUnpaid = (float) ($inv['komisi_sales_belum_terbayar'] ?? 0);
        
        $managerPaid   = (float) ($inv['komisi_manager_terbayar'] ?? 0);
        $managerUnpaid = (float) ($inv['komisi_manager_utang'] ?? 0);
        
        $adminPaid   = (float) ($inv['komisi_admin_terbayar'] ?? 0);
        $adminUnpaid = (float) ($inv['komisi_admin_belum_terbayar'] ?? 0);

        if ($status === 'paid') {
            if ($salesPaid <= 0 && $managerPaid <= 0 && $adminPaid <= 0) {
                continue;
            }
        } elseif ($status === 'unpaid') {
            if ($salesUnpaid <= 0 && $managerUnpaid <= 0 && $adminUnpaid <= 0) {
                continue;
            }
        }

        $summary['sales_paid'] += $salesPaid;
        $summary['sales_unpaid'] += $salesUnpaid;
        $summary['sales_total'] += ($salesPaid + $salesUnpaid);

        $summary['manager_paid'] += $managerPaid;
        $summary['manager_unpaid'] += $managerUnpaid;
        $summary['manager_total'] += ($managerPaid + $managerUnpaid);

        $summary['admin_paid'] += $adminPaid;
        $summary['admin_unpaid'] += $adminUnpaid;
        $summary['admin_total'] += ($adminPaid + $adminUnpaid);

        $summary['total_paid'] += ($salesPaid + $managerPaid + $adminPaid);
        $summary['total_unpaid'] += ($salesUnpaid + $managerUnpaid + $adminUnpaid);
        $summary['total_all'] += ($salesPaid + $salesUnpaid + $managerPaid + $managerUnpaid + $adminPaid + $adminUnpaid);

        $filtered[] = $inv;
    }

    $options['years'] = array_keys($options['years']);
    sort($options['years']);
    if (empty($options['years'])) {
        $options['years'] = [date('Y')];
    }

    return [
        'ok' => true,
        'summary' => $summary,
        'items' => $filtered,
        'options' => $options,
        'filters' => [
            'month' => $month,
            'year' => $year,
            'status' => $status,
        ],
    ];
}

function fetch_laporan_profit(string $type = 'produk', string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    if ($type === 'customer') {
        $invoices = db_all('SELECT kode_customer, COALESCE(nama_customer_master, nama_laundry_invoice) AS nama_customer, total_harga_jual, total_pembelian_barang, nomor_invoice FROM invoices');
        $filtered = [];

        foreach ($invoices ?? [] as $inv) {
            $invNo = $inv['nomor_invoice'] ?? '';
            if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
            if ($year !== '' && invoice_year($invNo) !== $year) continue;

            $custCode = $inv['kode_customer'] ?? 'UNKNOWN';
            $custName = $inv['nama_customer'] ?? 'Unknown Customer';
            $sale = (float)$inv['total_harga_jual'];
            $cogs = (float)$inv['total_pembelian_barang'];

            if (! isset($filtered[$custCode])) {
                $filtered[$custCode] = [
                    'kode_customer' => $custCode,
                    'nama_customer' => $custName,
                    'total_penjualan' => 0.0,
                    'total_hpp' => 0.0,
                    'total_profit' => 0.0,
                ];
            }
            $filtered[$custCode]['total_penjualan'] += $sale;
            $filtered[$custCode]['total_hpp'] += $cogs;
            $filtered[$custCode]['total_profit'] += ($sale - $cogs);
        }
        $data = array_values($filtered);
        usort($data, static fn ($a, $b) => $b['total_profit'] <=> $a['total_profit']);

    } else {
        $type = 'produk';
        $invoices = db_all('SELECT kode_invoice, nomor_invoice, total_pembelian_barang, subtotal FROM invoices');
        $items = db_all('SELECT kode_invoice, nomor_invoice, kode_barang, nama_barang_master, ukuran_master, jumlah, total FROM invoice_items');

        $invoiceMap = [];
        foreach ($invoices ?? [] as $inv) {
            $invNo = $inv['nomor_invoice'] ?? '';
            if ($month !== '' && invoice_month_number($invNo) !== (int)$month) continue;
            if ($year !== '' && invoice_year($invNo) !== $year) continue;

            $invoiceMap[$inv['kode_invoice']] = [
                'hpp' => (float)$inv['total_pembelian_barang'],
                'subtotal' => (float)$inv['subtotal'],
            ];
        }

        $productProfit = [];
        foreach ($items ?? [] as $item) {
            $invCode = $item['kode_invoice'];
            if (! isset($invoiceMap[$invCode])) continue;

            $code = $item['kode_barang'] ?? 'UNKNOWN';
            $invSubtotal = $invoiceMap[$invCode]['subtotal'] ?? 0;
            $invHpp = $invoiceMap[$invCode]['hpp'] ?? 0;

            $itemTotal = (float)$item['total'];
            $share = $invSubtotal > 0 ? ($itemTotal / $invSubtotal) : 0;
            $allocatedHpp = $share * $invHpp;
            $allocatedProfit = $itemTotal - $allocatedHpp;

            if (! isset($productProfit[$code])) {
                $productProfit[$code] = [
                    'kode_barang' => $code,
                    'nama_barang' => $item['nama_barang_master'] ?? 'Unknown',
                    'ukuran' => $item['ukuran_master'] ?? '',
                    'total_qty' => 0.0,
                    'total_penjualan' => 0.0,
                    'total_hpp' => 0.0,
                    'total_profit' => 0.0,
                ];
            }

            $productProfit[$code]['total_qty'] += (float)$item['jumlah'];
            $productProfit[$code]['total_penjualan'] += $itemTotal;
            $productProfit[$code]['total_hpp'] += $allocatedHpp;
            $productProfit[$code]['total_profit'] += $allocatedProfit;
        }

        $data = array_values($productProfit);
        usort($data, static fn ($a, $b) => $b['total_profit'] <=> $a['total_profit']);
    }

    return [
        'ok' => true,
        'type' => $type,
        'items' => $data ?? [],
    ];
}

function fetch_dashboard_summary(string $month = '', string $year = ''): array
{
    $pl = fetch_laporan_profit_loss($month, $year);
    $piutang = fetch_laporan_piutang($month, $year);
    $hutang = fetch_laporan_hutang($month, $year);

    // Top 5 Products
    $produkRep = fetch_laporan_penjualan('produk', $month, $year);
    $topProduk = array_slice($produkRep['items'] ?? [], 0, 5);

    // Top 5 Customers
    $customerRep = fetch_laporan_penjualan('customer', $month, $year);
    $topCustomer = array_slice($customerRep['items'] ?? [], 0, 5);

    // Recent 5 Invoices
    $invoiceRep = fetch_laporan_penjualan('invoice', $month, $year);
    $recentInvoices = array_slice($invoiceRep['items'] ?? [], 0, 5);

    return [
        'ok' => true,
        'revenue' => (float)($pl['pendapatan'] ?? 0.0),
        'profit' => (float)($pl['laba_bersih'] ?? 0.0),
        'piutang' => (float)($piutang['total_piutang'] ?? 0.0),
        'hutang' => (float)($hutang['total_hutang'] ?? 0.0),
        'top_produk' => $topProduk,
        'top_customer' => $topCustomer,
        'recent_invoices' => $recentInvoices,
    ];
}

function fetch_operational_expenses(string $month = '', string $year = '', string $status = '', string $search = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'error' => 'Koneksi database gagal.'];
    }

    $sql = 'SELECT * FROM operational_expenses WHERE 1=1';
    $params = [];

    if ($month !== '') {
        $sql .= ' AND MONTH(tanggal) = :month';
        $params['month'] = (int) $month;
    }

    if ($year !== '') {
        $sql .= ' AND YEAR(tanggal) = :year';
        $params['year'] = (int) $year;
    }

    if ($status !== '') {
        $sql .= ' AND status_pembayaran = :status';
        $params['status'] = $status;
    }

    if ($search !== '') {
        $sql .= ' AND (nama_pengeluaran LIKE :search OR keterangan LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY tanggal DESC, id DESC';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        return [
            'ok' => true,
            'items' => $items,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'error' => 'Gagal mengambil data pengeluaran: ' . $e->getMessage(),
            'items' => [],
        ];
    }
}

function fetch_operational_expense_detail(mixed $id): array
{
    $id = (int) $id;
    if ($id <= 0) {
        return ['ok' => true, 'item' => null, 'error' => null];
    }

    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'item' => null, 'error' => 'Koneksi database gagal.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM operational_expenses WHERE id = ? AND kategori = 'operational' LIMIT 1");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'ok' => $item !== null,
            'item' => $item,
            'error' => $item === null ? 'Data pengeluaran tidak ditemukan.' : null,
        ];
    } catch (Throwable $exception) {
        return ['ok' => false, 'item' => null, 'error' => $exception->getMessage()];
    }
}

function save_operational_expense_form(array $postData): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    $id = (int) ($postData['operational_id'] ?? 0);
    $isUpdate = $id > 0;
    $tanggal = date_input_value((string) ($postData['tanggal'] ?? ''));
    $bulanPnl = (int) ($postData['bulan_pnl'] ?? 0);
    $tahunPnl = (int) ($postData['tahun_pnl'] ?? 0);
    $nama = normalize_spaces((string) ($postData['nama_pengeluaran'] ?? ''));
    $jumlah = clean_money_value($postData['jumlah'] ?? 0);
    $status = strcasecmp(normalize_spaces((string) ($postData['status_pembayaran'] ?? '')), 'Lunas') === 0 ? 'Lunas' : 'Hutang';
    $tanggalPembayaran = date_input_value((string) ($postData['tanggal_pembayaran'] ?? ''));
    $keterangan = normalize_spaces((string) ($postData['keterangan'] ?? ''));

    if ($tanggal === '') {
        return ['ok' => false, 'message' => 'Tanggal pengeluaran wajib diisi.'];
    }
    if ($bulanPnl < 1 || $bulanPnl > 12) {
        $bulanPnl = (int) date('n', strtotime($tanggal));
    }
    if ($tahunPnl < 2000) {
        $tahunPnl = (int) date('Y', strtotime($tanggal));
    }
    if ($nama === '') {
        return ['ok' => false, 'message' => 'Nama pengeluaran wajib diisi.'];
    }
    if ($jumlah <= 0) {
        return ['ok' => false, 'message' => 'Jumlah pengeluaran harus lebih besar dari 0.'];
    }

    if ($status === 'Lunas' && $tanggalPembayaran === '') {
        $tanggalPembayaran = $tanggal;
    }
    if ($status !== 'Lunas') {
        $tanggalPembayaran = '';
    }

    try {
        $pdo->beginTransaction();

        if ($isUpdate) {
            $check = $pdo->prepare("SELECT id FROM operational_expenses WHERE id = ? AND kategori = 'operational' LIMIT 1");
            $check->execute([$id]);
            if (! $check->fetchColumn()) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'Data pengeluaran yang akan diupdate tidak ditemukan.'];
            }

            $stmt = $pdo->prepare('
                UPDATE operational_expenses
                SET tanggal = ?,
                    bulan_pnl = ?,
                    tahun_pnl = ?,
                    nama_pengeluaran = ?,
                    jumlah = ?,
                    status_pembayaran = ?,
                    tanggal_pembayaran = ?,
                    keterangan = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
                  AND kategori = ?
            ');
            $stmt->execute([
                $tanggal,
                $bulanPnl,
                $tahunPnl,
                $nama,
                $jumlah,
                $status,
                $tanggalPembayaran !== '' ? $tanggalPembayaran : null,
                $keterangan !== '' ? $keterangan : null,
                $id,
                'operational',
            ]);
            $expenseId = $id;
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO operational_expenses
                    (tanggal, bulan_pnl, tahun_pnl, kategori, nama_pengeluaran, jumlah, status_pembayaran, tanggal_pembayaran, keterangan)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $tanggal,
                $bulanPnl,
                $tahunPnl,
                'operational',
                $nama,
                $jumlah,
                $status,
                $tanggalPembayaran !== '' ? $tanggalPembayaran : null,
                $keterangan !== '' ? $keterangan : null,
            ]);
            $expenseId = (int) $pdo->lastInsertId();
        }

        $journalLines = generate_operational_expense_journal($pdo, $expenseId);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Pengeluaran operasional berhasil ' . ($isUpdate ? 'diupdate' : 'disimpan') . '. ' . $journalLines . ' baris jurnal diposting.',
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Gagal menyimpan pengeluaran operasional: ' . $exception->getMessage()];
    }
}

function delete_operational_expense(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'ID pengeluaran tidak valid.'];
    }

    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id, nama_pengeluaran FROM operational_expenses WHERE id = ? AND kategori = 'operational' LIMIT 1");
        $stmt->execute([$id]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $expense) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Data pengeluaran tidak ditemukan.'];
        }

        delete_accounting_journal_source($pdo, 'operational_expense', (string) $id);
        $delete = $pdo->prepare("DELETE FROM operational_expenses WHERE id = ? AND kategori = 'operational'");
        $delete->execute([$id]);

        $pdo->commit();

        return ['ok' => true, 'message' => 'Pengeluaran operasional ' . ($expense['nama_pengeluaran'] ?? '') . ' berhasil dihapus beserta jurnalnya.'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Gagal menghapus pengeluaran operasional: ' . $exception->getMessage()];
    }
}

function fetch_partner_prive(array $filters = []): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'items' => [], 'summary' => [], 'error' => 'Koneksi database gagal.'];
    }

    try {
        ensure_partner_prive_table($pdo);

        $month = trim((string) ($filters['month'] ?? ''));
        $year = trim((string) ($filters['year'] ?? date('Y')));
        $partner = trim((string) ($filters['partner'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $sql = 'SELECT * FROM partner_prive WHERE 1=1';
        $params = [];
        if ($month !== '') {
            $sql .= ' AND ((bulan_pnl IS NOT NULL AND bulan_pnl = :month) OR (bulan_pnl IS NULL AND MONTH(tanggal) = :month2))';
            $params['month'] = (int) $month;
            $params['month2'] = (int) $month;
        }
        if ($year !== '') {
            $sql .= ' AND ((tahun_pnl IS NOT NULL AND tahun_pnl = :year) OR (tahun_pnl IS NULL AND YEAR(tanggal) = :year2))';
            $params['year'] = (int) $year;
            $params['year2'] = (int) $year;
        }
        if ($partner !== '') {
            $sql .= ' AND partner LIKE :partner';
            $params['partner'] = '%' . $partner . '%';
        }
        if ($status !== '') {
            $sql .= ' AND status_pembayaran = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY tanggal DESC, id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $partners = $pdo->query('SELECT DISTINCT partner FROM partner_prive ORDER BY partner')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $summary = [
            'total' => array_sum(array_map(static fn (array $item): float => (float) ($item['jumlah'] ?? 0), $items)),
            'lunas' => array_sum(array_map(static fn (array $item): float => strcasecmp((string) ($item['status_pembayaran'] ?? ''), 'Lunas') === 0 ? (float) ($item['jumlah'] ?? 0) : 0.0, $items)),
            'hutang' => array_sum(array_map(static fn (array $item): float => strcasecmp((string) ($item['status_pembayaran'] ?? ''), 'Lunas') !== 0 ? (float) ($item['jumlah'] ?? 0) : 0.0, $items)),
            'count' => count($items),
        ];

        return [
            'ok' => true,
            'items' => $items,
            'summary' => $summary,
            'partners' => $partners,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'partner' => $partner,
                'status' => $status,
            ],
            'error' => null,
        ];
    } catch (Throwable $exception) {
        return ['ok' => false, 'items' => [], 'summary' => [], 'error' => $exception->getMessage()];
    }
}

function fetch_partner_prive_detail(mixed $id): array
{
    $id = (int) $id;
    if ($id <= 0) {
        return ['ok' => true, 'item' => null, 'error' => null];
    }

    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'item' => null, 'error' => 'Koneksi database gagal.'];
    }

    try {
        ensure_partner_prive_table($pdo);
        $stmt = $pdo->prepare('SELECT * FROM partner_prive WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'ok' => $item !== null,
            'item' => $item,
            'error' => $item === null ? 'Data prive tidak ditemukan.' : null,
        ];
    } catch (Throwable $exception) {
        return ['ok' => false, 'item' => null, 'error' => $exception->getMessage()];
    }
}

function save_partner_prive_form(array $postData): array
{
    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    $id = (int) ($postData['prive_id'] ?? 0);
    $isUpdate = $id > 0;
    $tanggal = date_input_value((string) ($postData['tanggal'] ?? ''));
    $bulanPnl = (int) ($postData['bulan_pnl'] ?? 0);
    $tahunPnl = (int) ($postData['tahun_pnl'] ?? 0);
    $partner = normalize_spaces((string) ($postData['partner'] ?? ''));
    $jumlah = clean_money_value($postData['jumlah'] ?? 0);
    $status = strcasecmp(normalize_spaces((string) ($postData['status_pembayaran'] ?? '')), 'Lunas') === 0 ? 'Lunas' : 'Hutang';
    $tanggalTransfer = date_input_value((string) ($postData['tanggal_transfer'] ?? ''));
    $keterangan = normalize_spaces((string) ($postData['keterangan'] ?? ''));

    if ($tanggal === '') {
        return ['ok' => false, 'message' => 'Tanggal prive wajib diisi.'];
    }
    if ($bulanPnl < 1 || $bulanPnl > 12) {
        $bulanPnl = (int) date('n', strtotime($tanggal));
    }
    if ($tahunPnl < 2000) {
        $tahunPnl = (int) date('Y', strtotime($tanggal));
    }
    if ($partner === '') {
        return ['ok' => false, 'message' => 'Nama partner wajib diisi.'];
    }
    if ($jumlah <= 0) {
        return ['ok' => false, 'message' => 'Jumlah prive harus lebih besar dari 0.'];
    }
    if ($status === 'Lunas' && $tanggalTransfer === '') {
        $tanggalTransfer = $tanggal;
    }
    if ($status !== 'Lunas') {
        $tanggalTransfer = '';
    }

    try {
        ensure_partner_prive_table($pdo);
        $pdo->beginTransaction();

        if ($isUpdate) {
            $check = $pdo->prepare('SELECT id FROM partner_prive WHERE id = ? LIMIT 1');
            $check->execute([$id]);
            if (! $check->fetchColumn()) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'Data prive yang akan diupdate tidak ditemukan.'];
            }

            $stmt = $pdo->prepare('
                UPDATE partner_prive
                SET tanggal = ?,
                    bulan_pnl = ?,
                    tahun_pnl = ?,
                    partner = ?,
                    jumlah = ?,
                    status_pembayaran = ?,
                    tanggal_transfer = ?,
                    keterangan = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([
                $tanggal,
                $bulanPnl,
                $tahunPnl,
                $partner,
                $jumlah,
                $status,
                $tanggalTransfer !== '' ? $tanggalTransfer : null,
                $keterangan !== '' ? $keterangan : null,
                $id,
            ]);
            $priveId = $id;
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO partner_prive
                    (tanggal, bulan_pnl, tahun_pnl, partner, jumlah, status_pembayaran, tanggal_transfer, keterangan)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $tanggal,
                $bulanPnl,
                $tahunPnl,
                $partner,
                $jumlah,
                $status,
                $tanggalTransfer !== '' ? $tanggalTransfer : null,
                $keterangan !== '' ? $keterangan : null,
            ]);
            $priveId = (int) $pdo->lastInsertId();
        }

        $journalLines = generate_partner_prive_journal($pdo, $priveId);
        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Prive partner berhasil ' . ($isUpdate ? 'diupdate' : 'disimpan') . '. ' . $journalLines . ' baris jurnal diposting.',
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Gagal menyimpan prive partner: ' . $exception->getMessage()];
    }
}

function delete_partner_prive(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'ID prive tidak valid.'];
    }

    $pdo = db();
    if ($pdo === null) {
        return ['ok' => false, 'message' => 'Koneksi database gagal.'];
    }

    try {
        ensure_partner_prive_table($pdo);
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT id, partner FROM partner_prive WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $prive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $prive) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Data prive tidak ditemukan.'];
        }

        delete_accounting_journal_source($pdo, 'partner_prive', (string) $id);
        $delete = $pdo->prepare('DELETE FROM partner_prive WHERE id = ?');
        $delete->execute([$id]);

        $pdo->commit();

        return ['ok' => true, 'message' => 'Prive partner ' . ($prive['partner'] ?? '') . ' berhasil dihapus beserta jurnalnya.'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Gagal menghapus prive partner: ' . $exception->getMessage()];
    }
}

function fetch_operational_summary(string $month = '', string $year = ''): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'total_pengeluaran' => 0.0,
            'total_lunas' => 0.0,
            'total_hutang' => 0.0,
        ];
    }

    $sql = "SELECT jumlah, status_pembayaran FROM operational_expenses WHERE kategori = 'operational'";
    $params = [];

    if ($month !== '' && $year !== '') {
        // Prioritas: filter berdasarkan bulan_pnl (blok visual Excel) jika tersedia
        $sql .= ' AND ((bulan_pnl IS NOT NULL AND bulan_pnl = :bulan_pnl AND tahun_pnl = :tahun_pnl)'
              . '  OR  (bulan_pnl IS NULL AND MONTH(tanggal) = :bulan_pnl2 AND YEAR(tanggal) = :tahun_pnl2))';
        $params['bulan_pnl']  = (int) $month;
        $params['tahun_pnl']  = (int) $year;
        $params['bulan_pnl2'] = (int) $month;
        $params['tahun_pnl2'] = (int) $year;
    } elseif ($month !== '') {
        $sql .= ' AND ((bulan_pnl IS NOT NULL AND bulan_pnl = :bulan_pnl) OR (bulan_pnl IS NULL AND MONTH(tanggal) = :bulan_pnl2))';
        $params['bulan_pnl']  = (int) $month;
        $params['bulan_pnl2'] = (int) $month;
    } elseif ($year !== '') {
        $sql .= ' AND ((tahun_pnl IS NOT NULL AND tahun_pnl = :tahun_pnl) OR (tahun_pnl IS NULL AND YEAR(tanggal) = :tahun_pnl2))';
        $params['tahun_pnl']  = (int) $year;
        $params['tahun_pnl2'] = (int) $year;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $total = 0.0;
        $lunas = 0.0;
        $hutang = 0.0;

        foreach ($rows as $row) {
            $amt = (float) $row['jumlah'];
            $status = strtolower(trim($row['status_pembayaran']));
            $total += $amt;
            if ($status === 'lunas') {
                $lunas += $amt;
            } else {
                $hutang += $amt;
            }
        }

        return [
            'total_pengeluaran' => $total,
            'total_lunas' => $lunas,
            'total_hutang' => $hutang,
        ];
    } catch (Throwable) {
        return [
            'total_pengeluaran' => 0.0,
            'total_lunas' => 0.0,
            'total_hutang' => 0.0,
        ];
    }
}

function parse_excel_date_internal(mixed $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    
    $valueStr = trim((string)$value);
    if ($valueStr === '') {
        return null;
    }

    if (is_numeric($valueStr)) {
        $val = (float)$valueStr;
        $timestamp = ($val - 25569) * 86400;
        return date('Y-m-d', (int)$timestamp);
    }
    
    $ts = strtotime($valueStr);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }
    
    return null;
}

function read_operational_from_workbook_internal(string $path): array
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
        if (strcasecmp((string) $sheet['name'], 'operational') === 0) {
            $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $target = $relationships[(string) $attributes['id']] ?? '';
            $sheetPath = 'xl/' . ltrim($target, '/');
            break;
        }
    }

    if ($sheetPath === '') {
        throw new RuntimeException('Sheet operational tidak ditemukan.');
    }

    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('XML sheet operational tidak ditemukan.');
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

    // Deteksi blok bulan: baris yang punya nilai di kolom G (TOTAL) dan kolom A kosong = sub-total bulan, tandai blok
    // Urutan blok: Januari(bulan 1), Februari(2), dst. setiap kali ketemu baris G berisi angka > 0 & A kosong, bulan++
    $expenses = [];
    $currentBulan = 0;
    $currentTahun = 2026;
    $currentBlockTotal = 0.0;
    $currentBlockSum = 0.0;

    foreach ($rows as $row) {
        $colA = trim((string) ($row['A'] ?? ''));
        $colB = trim((string) ($row['B'] ?? ''));
        $colG = trim((string) ($row['G'] ?? ''));

        // Baris sub-total bulan: A kosong, B kosong, G berisi angka > 0
        if ($colA === '' && $colB === '' && is_numeric($colG) && (float)$colG > 0) {
            $currentBulan++;
            $currentBlockTotal = (float) $colG;
            $currentBlockSum = 0.0;
            continue; // skip baris total, jangan insert
        }

        if ($colB === '' || strcasecmp($colB, 'nama pengeluaran') === 0) {
            continue;
        }

        $tanggal = parse_excel_date_internal($row['A'] ?? null);
        $jumlah = (float) ($row['C'] ?? 0);
        $status = trim((string) ($row['D'] ?? 'Lunas'));
        $tanggal_pembayar = parse_excel_date_internal($row['E'] ?? null);
        $keterangan = trim((string) ($row['F'] ?? ''));

        if ($status === '') {
            $status = 'Lunas';
        }

        if ($jumlah <= 0 && $colB !== '') {
            continue; // skip baris tanpa nilai
        }

        if ($currentBlockTotal > 0 && $currentBlockSum >= $currentBlockTotal - 0.01) {
            continue;
        }

        $expenses[] = [
            'tanggal'          => $tanggal,
            'bulan_pnl'        => $currentBulan > 0 ? $currentBulan : null,
            'tahun_pnl'        => $currentBulan > 0 ? $currentTahun : null,
            'kategori'         => 'operational',
            'nama_pengeluaran' => $colB,
            'jumlah'           => $jumlah,
            'status_pembayaran'=> $status,
            'tanggal_pembayaran'=> $tanggal_pembayar,
            'keterangan'       => $keterangan,
        ];
        $currentBlockSum += $jumlah;
    }

    return $expenses;
}

function seed_operational_expenses_from_workbook(PDO $pdo, string $excelPath): int
{
    // Pastikan kolom bulan_pnl, tahun_pnl, kategori sudah ada
    $existingCols = [];
    $cols = $pdo->query('DESCRIBE operational_expenses')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cols as $c) { $existingCols[$c] = true; }

    if (!isset($existingCols['bulan_pnl'])) {
        $pdo->exec('ALTER TABLE operational_expenses ADD COLUMN bulan_pnl TINYINT UNSIGNED NULL AFTER tanggal');
    }
    if (!isset($existingCols['tahun_pnl'])) {
        $pdo->exec('ALTER TABLE operational_expenses ADD COLUMN tahun_pnl SMALLINT UNSIGNED NULL AFTER bulan_pnl');
    }
    if (!isset($existingCols['kategori'])) {
        $pdo->exec("ALTER TABLE operational_expenses ADD COLUMN kategori VARCHAR(50) NOT NULL DEFAULT 'operational' AFTER tahun_pnl");
    }

    $expenses = read_operational_from_workbook_internal($excelPath);
    $statement = $pdo->prepare('
        INSERT INTO operational_expenses (tanggal, bulan_pnl, tahun_pnl, kategori, nama_pengeluaran, jumlah, status_pembayaran, tanggal_pembayaran, keterangan)
        VALUES (:tanggal, :bulan_pnl, :tahun_pnl, :kategori, :nama_pengeluaran, :jumlah, :status_pembayaran, :tanggal_pembayaran, :keterangan)
    ');

    $count = 0;
    foreach ($expenses as $expense) {
        $statement->execute([
            'tanggal'           => $expense['tanggal'],
            'bulan_pnl'         => $expense['bulan_pnl'],
            'tahun_pnl'         => $expense['tahun_pnl'],
            'kategori'          => $expense['kategori'],
            'nama_pengeluaran'  => $expense['nama_pengeluaran'],
            'jumlah'            => $expense['jumlah'],
            'status_pembayaran' => $expense['status_pembayaran'],
            'tanggal_pembayaran'=> $expense['tanggal_pembayaran'],
            'keterangan'        => $expense['keterangan'],
        ]);
        $count++;
    }
    return $count;
}

/**
 * Seed data bonus ke tabel operational_expenses (kategori='bonus').
 * Tambahkan baris baru di $bonusData jika ada bonus periode berikutnya.
 */
function seed_bonus_expenses(PDO $pdo): int
{
    // Daftar bonus — edit array ini jika ada bonus baru
    $bonusData = [
        ['bulan_pnl' => 4, 'tahun_pnl' => 2026, 'nama' => 'Bonus Krisna April 2026', 'jumlah' => 2643400.00],
        ['bulan_pnl' => 5, 'tahun_pnl' => 2026, 'nama' => 'Bonus Krisna Mei 2026',   'jumlah' => 2041050.00],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO operational_expenses
            (tanggal, bulan_pnl, tahun_pnl, kategori, nama_pengeluaran, jumlah, status_pembayaran, keterangan)
        VALUES
            (NULL, :bulan_pnl, :tahun_pnl, 'bonus', :nama_pengeluaran, :jumlah, 'Lunas', 'Bonus dari sheet Bonus Krisna')
    ");

    $count = 0;
    foreach ($bonusData as $b) {
        $stmt->execute([
            'bulan_pnl'        => $b['bulan_pnl'],
            'tahun_pnl'        => $b['tahun_pnl'],
            'nama_pengeluaran' => $b['nama'],
            'jumlah'           => $b['jumlah'],
        ]);
        $count++;
    }
    return $count;
}

function parse_number_internal(mixed $value): float
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0.0;
    }
    $value = str_replace(',', '.', $value);
    $value = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';
    if ($value === '' || ! is_numeric($value)) {
        return 0.0;
    }
    return (float) $value;
}

function read_xlsx_sheet_rows_internal(string $path, string $sheetName): array
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
        if (strcasecmp((string)$sheet['name'], $sheetName) === 0) {
            $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $target = $relationships[(string) $attributes['id']] ?? '';
            $sheetPath = 'xl/' . ltrim($target, '/');
            break;
        }
    }

    if ($sheetPath === '') {
        throw new RuntimeException('Sheet tidak ditemukan: ' . $sheetName);
    }

    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('XML sheet tidak ditemukan.');
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $rowNum = (int)$row['r'];
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
        $rows[$rowNum] = $values;
    }
    return $rows;
}

function seed_pnl_invoice_columns(PDO $pdo, string $excelPath): void
{
    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');
    
    $statement = $pdo->prepare('
        UPDATE invoices
        SET komisi_manager_terbayar = :komisi_manager_terbayar,
            komisi_manager_utang = :komisi_manager_utang,
            pph_final_terbayar = :pph_final_terbayar,
            pph_final_belum_terbayar = :pph_final_belum_terbayar,
            komisi_admin_terbayar = :komisi_admin_terbayar,
            komisi_admin_belum_terbayar = :komisi_admin_belum_terbayar,
            biaya_kirim = :biaya_kirim,
            biaya_admin_bank = :biaya_admin_bank
        WHERE nomor_invoice = :nomor_invoice
    ');

    $pdo->beginTransaction();
    foreach ($rows as $row) {
        $invoiceNumber = trim((string) ($row['A'] ?? ''));
        if ($invoiceNumber === '' || strcasecmp($invoiceNumber, 'nomor invoice') === 0) {
            continue;
        }

        $statement->execute([
            'nomor_invoice' => $invoiceNumber,
            'komisi_manager_terbayar' => (float) parse_number_internal($row['W'] ?? 0),
            'komisi_manager_utang' => (float) parse_number_internal($row['X'] ?? 0),
            'pph_final_terbayar' => (float) parse_number_internal($row['Z'] ?? 0),
            'pph_final_belum_terbayar' => (float) parse_number_internal($row['AA'] ?? 0),
            'komisi_admin_terbayar' => (float) parse_number_internal($row['AB'] ?? 0),
            'komisi_admin_belum_terbayar' => (float) parse_number_internal($row['AC'] ?? 0),
            'biaya_kirim' => (float) parse_number_internal($row['AE'] ?? 0),
            'biaya_admin_bank' => (float) parse_number_internal($row['AF'] ?? 0),
        ]);
    }
    $pdo->commit();
}

function run_hosting_update(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts' => [],
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok' => false,
            'message' => 'File Excel storage/PENJUALAN-2026.xlsx tidak ditemukan.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    try {
        $outputLogs = [];
        
        // 1. Run database migration & seed (schema.sql + seed-data.sql)
        $migrationResult = run_database_migration_seed();
        if (!$migrationResult['ok']) {
            return $migrationResult;
        }
        $outputLogs[] = "1. Re-create Tabel & Seed Data Awal: Selesai (" . $migrationResult['statements'] . " SQL statements)";

        // 2. Sync all commissions from Excel (Sales, Manager, Admin Transfer Date)
        if (! class_exists('ZipArchive')) {
            $outputLogs[] = "2. Skip Sinkronisasi Excel karena ZipArchive tidak aktif.";
        } else {
            $commCount = update_invoice_all_commission_from_excel($pdo, $excelPath);
            $outputLogs[] = "2. Sinkronisasi Komisi Sales, Manager & Admin: Selesai ($commCount baris diperbarui)";
            
            // 3. Sync PNL columns from Excel (Manager/Admin Paid, Tax, Delivery Cost, Bank Admin Fee)
            seed_pnl_invoice_columns($pdo, $excelPath);
            $outputLogs[] = "3. Sinkronisasi Kolom PNL (Admin Paid, Tax, Kirim, Bank): Selesai";
            
            // 4. Sync Purchase Totals & Transfer Dates from Excel
            $purchaseCount = update_invoice_purchase_data_from_excel($pdo, $excelPath);
            $outputLogs[] = "4. Sinkronisasi Pembelian Barang (Lunas/Utang & Tgl Transfer): Selesai ($purchaseCount baris diperbarui)";
        }

        if (accounting_tables_ready($pdo)) {
            $journalResult = regenerate_all_accounting_journals($pdo);
            $outputLogs[] = "5. Generate Jurnal Akuntansi: Selesai ({$journalResult['entries']} jurnal, {$journalResult['lines']} baris)";
        }

        return [
            'ok' => true,
            'message' => 'Update Database Hosting Berhasil!',
            'statements' => $migrationResult['statements'],
            'counts' => database_table_counts(),
            'output' => implode("\n", $outputLogs),
        ];
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'message' => 'Update hosting gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }
}

/**
 * Update terbaru: sinkronisasi komisi, PNL, pembelian barang, dan tanggal admin
 * dari Excel TANPA mereset/truncate tabel utama. Aman dijalankan di hosting
 * jika ada invoice baru yang dibuat langsung di site.
 */
function run_latest_update(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => [],
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok'         => false,
            'message'    => 'File Excel storage/PENJUALAN-2026.xlsx tidak ditemukan. Pastikan file sudah diunggah ke folder storage/.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        return [
            'ok'         => false,
            'message'    => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif. Aktifkan extension=zip pada php.ini server.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $outputLogs = [];
        $totalUpdated = 0;

        // 1. Sinkronisasi komisi sales, manager, dan tanggal transfer admin
        $commCount = update_invoice_all_commission_from_excel($pdo, $excelPath);
        $totalUpdated += $commCount;
        $outputLogs[] = "1. Sinkronisasi Komisi Sales, Manager & Admin: Selesai ($commCount baris diperbarui)";

        // 2. Sinkronisasi kolom PNL (Admin Paid, Tax, Ongkir, Biaya Bank)
        seed_pnl_invoice_columns($pdo, $excelPath);
        $outputLogs[] = "2. Sinkronisasi Kolom PNL (Admin Terbayar, PPh, Ongkir, Bank): Selesai";

        // 3. Sinkronisasi data pembelian barang (total, utang, tanggal transfer)
        $purchaseCount = update_invoice_purchase_data_from_excel($pdo, $excelPath);
        $totalUpdated += $purchaseCount;
        $outputLogs[] = "3. Sinkronisasi Pembelian Barang (Lunas/Utang & Tgl Transfer): Selesai ($purchaseCount baris diperbarui)";

        // 4. Sinkronisasi pengeluaran operasional
        if (accounting_tables_ready($pdo)) {
            $pdo->exec("DELETE FROM journal_entries WHERE source_type = 'operational_expense'");
        }
        $pdo->exec('TRUNCATE TABLE operational_expenses');
        $opCount = seed_operational_expenses_from_workbook($pdo, $excelPath);
        $opCount += seed_bonus_expenses($pdo);
        $outputLogs[] = "4. Sinkronisasi Pengeluaran Operasional: Selesai ($opCount baris diperbarui)";

        if (accounting_tables_ready($pdo)) {
            $journalResult = regenerate_all_accounting_journals($pdo);
            $outputLogs[] = "5. Generate Jurnal Akuntansi: Selesai ({$journalResult['entries']} jurnal, {$journalResult['lines']} baris)";
            $totalUpdated += $journalResult['lines'];
        }

        return [
            'ok'         => true,
            'message'    => 'Update Terbaru Berhasil! Data komisi, PNL, pembelian, dan operasional telah disinkronisasi dari Excel.',
            'statements' => $totalUpdated,
            'counts'     => database_table_counts(),
            'output'     => implode("\n", $outputLogs),
        ];
    } catch (Throwable $exception) {
        return [
            'ok'         => false,
            'message'    => 'Update terbaru gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function run_2025_latest_update(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2025.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok'         => false,
            'message'    => 'File Excel storage/PENJUALAN-2025.xlsx tidak ditemukan.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        return [
            'ok'         => false,
            'message'    => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif. Aktifkan extension=zip pada php.ini server.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $outputLogs = [];

        $pdo->beginTransaction();
        $invoiceResult = update_2025_invoice_fields_from_excel($pdo, $excelPath);
        $operationalResult = update_2025_operational_from_excel($pdo, $excelPath);
        $pdo->commit();

        $outputLogs[] = "1. Sinkronisasi Invoice 2025: {$invoiceResult['matched']} invoice diperbarui, {$invoiceResult['unmatched']} tidak ditemukan";
        if (! empty($invoiceResult['unmatched_examples'])) {
            $outputLogs[] = '   Tidak ditemukan: ' . implode(', ', $invoiceResult['unmatched_examples']);
        }
        $outputLogs[] = "2. Sinkronisasi Operational 2025: {$operationalResult['deleted']} data lama dihapus, {$operationalResult['inserted']} data dimasukkan";

        $pdo->beginTransaction();
        $journalResult = regenerate_all_accounting_journals($pdo);
        $pdo->commit();
        $outputLogs[] = "3. Posting Ulang Jurnal Akuntansi: {$journalResult['entries']} jurnal, {$journalResult['lines']} baris";

        return [
            'ok'         => true,
            'message'    => 'Update Data 2025 Berhasil! Invoice, operational, dan jurnal akuntansi telah disinkronisasi.',
            'statements' => $invoiceResult['matched'] + $operationalResult['inserted'] + $journalResult['lines'],
            'counts'     => database_table_counts(),
            'output'     => implode("\n", $outputLogs),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'ok'         => false,
            'message'    => 'Update data 2025 gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function run_2026_operational_latest_update(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    $excelPath = dirname(__DIR__) . '/storage/PENJUALAN-2026.xlsx';
    if (! is_readable($excelPath)) {
        return [
            'ok'         => false,
            'message'    => 'File Excel storage/PENJUALAN-2026.xlsx tidak ditemukan.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        return [
            'ok'         => false,
            'message'    => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif. Aktifkan extension=zip pada server.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $pdo->beginTransaction();
        $operationalResult = update_year_operational_from_excel($pdo, $excelPath, 2026);
        $journalResult = regenerate_all_accounting_journals($pdo);
        $pdo->commit();

        return [
            'ok'         => true,
            'message'    => 'Update Operational 2026 Berhasil! Bulan PNL sudah mengikuti blok operational di Excel.',
            'statements' => $operationalResult['inserted'] + $journalResult['lines'],
            'counts'     => database_table_counts(),
            'output'     => implode("\n", [
                "1. Operational 2026: {$operationalResult['deleted']} data lama dihapus, {$operationalResult['inserted']} data dimasukkan",
                "2. Posting Ulang Jurnal Akuntansi: {$journalResult['entries']} jurnal, {$journalResult['lines']} baris",
            ]),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'ok'         => false,
            'message'    => 'Update operational 2026 gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function run_pnl_sales_commission_update(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $pdo->beginTransaction();
        $journalResult = regenerate_all_accounting_journals($pdo);
        $pdo->commit();

        $april = fetch_laporan_profit_loss('4', '2026');

        return [
            'ok'         => true,
            'message'    => 'Update PNL Komisi Sales Berhasil! PNL sekarang memakai nominal komisi sales terbayar + belum terbayar jika tersedia.',
            'statements' => $journalResult['lines'],
            'counts'     => database_table_counts(),
            'output'     => implode("\n", [
                "1. Posting Ulang Jurnal Akuntansi: {$journalResult['entries']} jurnal, {$journalResult['lines']} baris",
                '2. Komisi Sales PNL April 2026: Rp' . number_format((float) ($april['komisi_sales'] ?? 0), 0, ',', '.'),
            ]),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'ok'         => false,
            'message'    => 'Update PNL komisi sales gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function run_hosting_latest_data_update(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok'         => false,
            'message'    => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    $baseDir = dirname(__DIR__);
    $excel2026 = $baseDir . '/storage/PENJUALAN-2026.xlsx';
    $excel2025 = $baseDir . '/storage/PENJUALAN-2025.xlsx';
    $drive25 = $baseDir . '/storage/drive25';
    $importScript = $baseDir . '/scripts/import-drive25-invoices.php';

    foreach ([$excel2026, $excel2025] as $path) {
        if (! is_readable($path)) {
            return [
                'ok'         => false,
                'message'    => 'File wajib tidak ditemukan: ' . str_replace($baseDir . '/', '', $path),
                'statements' => 0,
                'counts'     => database_table_counts(),
            ];
        }
    }

    if (! is_dir($drive25)) {
        return [
            'ok'         => false,
            'message'    => 'Folder wajib tidak ditemukan: storage/drive25.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! is_readable($importScript)) {
        return [
            'ok'         => false,
            'message'    => 'Script wajib tidak ditemukan: scripts/import-drive25-invoices.php.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    if (! class_exists('ZipArchive')) {
        return [
            'ok'         => false,
            'message'    => 'Ekstensi PHP "zip" (ZipArchive) tidak aktif. Aktifkan extension=zip pada server hosting.',
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }

    try {
        $outputLogs = [];
        $statements = 0;

        $sync2026 = update_2026_invoice_fields_from_excel($pdo, $excel2026);
        $statements += $sync2026['matched'];
        $outputLogs[] = "1. Sinkronisasi Invoice 2026 dari Excel: {$sync2026['matched']} invoice diperbarui, {$sync2026['unmatched']} tidak ditemukan";
        if (! empty($sync2026['unmatched_examples'])) {
            $outputLogs[] = '   Tidak ditemukan 2026: ' . implode(', ', $sync2026['unmatched_examples']);
        }

        $op2026 = update_year_operational_from_excel($pdo, $excel2026, 2026);
        $statements += $op2026['inserted'];
        $outputLogs[] = "2. Sinkronisasi Operational 2026: {$op2026['deleted']} data lama dihapus, {$op2026['inserted']} data dimasukkan";

        $pdo->exec("DELETE FROM operational_expenses WHERE kategori = 'bonus' AND tahun_pnl = 2026");
        $bonusCount = seed_bonus_expenses($pdo);
        $statements += $bonusCount;
        $outputLogs[] = "3. Sinkronisasi Bonus 2026: $bonusCount data dimasukkan";

        $sync2025 = update_2025_invoice_fields_from_excel($pdo, $excel2025);
        $statements += $sync2025['matched'];
        $outputLogs[] = "4. Sinkronisasi Invoice 2025 dari Excel: {$sync2025['matched']} invoice diperbarui, {$sync2025['unmatched']} tidak ditemukan";
        if (! empty($sync2025['unmatched_examples'])) {
            $outputLogs[] = '   Tidak ditemukan 2025: ' . implode(', ', $sync2025['unmatched_examples']);
        }

        $op2025 = update_2025_operational_from_excel($pdo, $excel2025);
        $statements += $op2025['inserted'];
        $outputLogs[] = "5. Sinkronisasi Operational 2025: {$op2025['deleted']} data lama dihapus, {$op2025['inserted']} data dimasukkan";

        require_once $importScript;
        $importResult = import_drive25_invoices($pdo, $drive25, $excel2026);
        $statements += $importResult['processed'] + $importResult['items'];
        $outputLogs[] = "6. Import Invoice 453-462: {$importResult['created']} dibuat, {$importResult['updated']} diupdate, {$importResult['items']} item";
        if (! empty($importResult['warnings'])) {
            $outputLogs[] = '   Peringatan import: ' . implode(' | ', $importResult['warnings']);
        }

        $fix328 = apply_invoice_328_commission_fix($pdo);
        $statements += $fix328;
        $outputLogs[] = "7. Koreksi Komisi Invoice 328: $fix328 baris diperbarui";

        ensure_invoice_google_sync_columns($pdo);
        $outputLogs[] = '8. Migrasi Kolom Google Sync: Selesai';

        $journalResult = regenerate_all_accounting_journals($pdo);
        $statements += $journalResult['lines'];
        $outputLogs[] = "9. Posting Ulang Jurnal Akuntansi: {$journalResult['entries']} jurnal, {$journalResult['lines']} baris";

        return [
            'ok'         => true,
            'message'    => 'Update Paket Hosting Berhasil! Data 2025, 2026, invoice 453-462, koreksi komisi, dan jurnal telah disinkronisasi.',
            'statements' => $statements,
            'counts'     => database_table_counts(),
            'output'     => implode("\n", $outputLogs),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'ok'         => false,
            'message'    => 'Update paket hosting gagal: ' . $exception->getMessage(),
            'statements' => 0,
            'counts'     => database_table_counts(),
        ];
    }
}

function update_2026_invoice_fields_from_excel(PDO $pdo, string $excelPath): array
{
    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');
    $existingInvoices = [];

    foreach ($pdo->query('SELECT nomor_invoice FROM invoices')->fetchAll(PDO::FETCH_COLUMN) ?: [] as $invoiceNumber) {
        $existingInvoices[invoice_number_key_2025((string) $invoiceNumber)] = (string) $invoiceNumber;
    }

    $salesMap = fetch_sales_map_2025($pdo);
    $statement = $pdo->prepare('
        UPDATE invoices
        SET kode_sales_1 = :kode_sales_1,
            nama_sales_1 = :nama_sales_1,
            kode_sales_2 = :kode_sales_2,
            nama_sales_2 = :nama_sales_2,
            harga_normal_pricelist = :harga_normal_pricelist,
            discount_persen = :discount_persen,
            discount_amount = :discount_amount,
            total_harga_jual = :total_harga_jual,
            status_pembayaran = :status_pembayaran,
            tanggal_pembayaran = :tanggal_pembayaran,
            komisi_sales_1_persen = :komisi_sales_1_persen,
            komisi_sales_2_persen = :komisi_sales_2_persen,
            komisi_sales_terbayar = :komisi_sales_terbayar,
            komisi_sales_belum_terbayar = :komisi_sales_belum_terbayar,
            status_pembayaran_komisi_sales = :status_pembayaran_komisi_sales,
            tanggal_transfer_komisi_sales = :tanggal_transfer_komisi_sales,
            komisi_manager_terbayar = :komisi_manager_terbayar,
            komisi_manager_utang = :komisi_manager_utang,
            tanggal_transfer_komisi_manager = :tanggal_transfer_komisi_manager,
            pph_final_terbayar = :pph_final_terbayar,
            pph_final_belum_terbayar = :pph_final_belum_terbayar,
            komisi_admin_terbayar = :komisi_admin_terbayar,
            komisi_admin_belum_terbayar = :komisi_admin_belum_terbayar,
            tanggal_transfer_komisi_admin = :tanggal_transfer_komisi_admin,
            biaya_kirim = :biaya_kirim,
            biaya_admin_bank = :biaya_admin_bank,
            total_pembelian_barang = :total_pembelian_barang,
            total_utang_pembelian_barang = :total_utang_pembelian_barang,
            status_pembelian_barang = :status_pembelian_barang,
            tanggal_transfer_pembelian_barang = :tanggal_transfer_pembelian_barang
        WHERE nomor_invoice = :nomor_invoice
    ');

    $records = 0;
    $matched = 0;
    $unmatched = [];

    foreach ($rows as $row) {
        $invoiceNumber = normalize_spaces((string) ($row['A'] ?? ''));
        if ($invoiceNumber === '' || invoice_year($invoiceNumber) !== '2026') {
            continue;
        }

        $records++;
        $invoiceKey = invoice_number_key_2025($invoiceNumber);
        if (! isset($existingInvoices[$invoiceKey])) {
            $unmatched[] = $invoiceNumber;
            continue;
        }

        $sales1 = resolve_sales_2025($pdo, $salesMap, (string) ($row['F'] ?? ''));
        $sales2 = resolve_sales_2025($pdo, $salesMap, (string) ($row['G'] ?? ''));
        $purchaseDebt = (float) parse_number_internal($row['AH'] ?? 0);
        $purchaseTransferDate = excel_date_2025($row['AI'] ?? '');

        $statement->execute([
            'nomor_invoice' => $existingInvoices[$invoiceKey],
            'kode_sales_1' => $sales1['kode_sales'],
            'nama_sales_1' => $sales1['nama_sales'],
            'kode_sales_2' => $sales2['kode_sales'],
            'nama_sales_2' => $sales2['nama_sales'],
            'harga_normal_pricelist' => (float) parse_number_internal($row['H'] ?? 0),
            'discount_persen' => parse_percent_2025($row['I'] ?? ''),
            'discount_amount' => (float) parse_number_internal($row['J'] ?? 0),
            'total_harga_jual' => (float) parse_number_internal($row['K'] ?? 0),
            'status_pembayaran' => normalize_payment_status_2025((string) ($row['L'] ?? '')),
            'tanggal_pembayaran' => excel_date_2025($row['M'] ?? ''),
            'komisi_sales_1_persen' => parse_percent_2025($row['P'] ?? ''),
            'komisi_sales_2_persen' => parse_percent_2025($row['Q'] ?? ''),
            'komisi_sales_terbayar' => (float) parse_number_internal($row['S'] ?? 0),
            'komisi_sales_belum_terbayar' => (float) parse_number_internal($row['T'] ?? 0),
            'status_pembayaran_komisi_sales' => normalize_spaces((string) ($row['U'] ?? '')) ?: null,
            'tanggal_transfer_komisi_sales' => excel_date_2025($row['V'] ?? ''),
            'komisi_manager_terbayar' => (float) parse_number_internal($row['W'] ?? 0),
            'komisi_manager_utang' => (float) parse_number_internal($row['X'] ?? 0),
            'tanggal_transfer_komisi_manager' => excel_date_2025($row['Y'] ?? ''),
            'pph_final_terbayar' => (float) parse_number_internal($row['Z'] ?? 0),
            'pph_final_belum_terbayar' => (float) parse_number_internal($row['AA'] ?? 0),
            'komisi_admin_terbayar' => (float) parse_number_internal($row['AB'] ?? 0),
            'komisi_admin_belum_terbayar' => (float) parse_number_internal($row['AC'] ?? 0),
            'tanggal_transfer_komisi_admin' => excel_date_2025($row['AD'] ?? ''),
            'biaya_kirim' => (float) parse_number_internal($row['AE'] ?? 0),
            'biaya_admin_bank' => (float) parse_number_internal($row['AF'] ?? 0),
            'total_pembelian_barang' => (float) parse_number_internal($row['AG'] ?? 0),
            'total_utang_pembelian_barang' => $purchaseDebt,
            'status_pembelian_barang' => $purchaseTransferDate !== null ? 'Lunas' : ($purchaseDebt > 0 ? 'Utang' : 'Lunas'),
            'tanggal_transfer_pembelian_barang' => $purchaseTransferDate,
        ]);
        $matched++;
    }

    return [
        'records' => $records,
        'matched' => $matched,
        'unmatched' => count($unmatched),
        'unmatched_examples' => array_slice($unmatched, 0, 10),
    ];
}

function update_year_operational_from_excel(PDO $pdo, string $excelPath, int $year): array
{
    $expenses = read_operational_from_workbook_internal($excelPath);
    $filtered = [];

    foreach ($expenses as $expense) {
        $tanggal = $expense['tanggal'] ?? null;
        if ($tanggal === null || (int) date('Y', strtotime((string) $tanggal)) !== $year) {
            continue;
        }

        $expense['bulan_pnl'] = $expense['bulan_pnl'] ?? (int) date('n', strtotime((string) $tanggal));
        $expense['tahun_pnl'] = $expense['tahun_pnl'] ?? $year;
        $expense['kategori'] = 'operational';
        $filtered[] = $expense;
    }

    $pdo->exec("DELETE FROM journal_entries WHERE source_type = 'operational_expense'");
    $deleted = $pdo->prepare("DELETE FROM operational_expenses WHERE kategori = 'operational' AND YEAR(tanggal) = ?");
    $deleted->execute([$year]);

    $statement = $pdo->prepare('
        INSERT INTO operational_expenses (tanggal, bulan_pnl, tahun_pnl, kategori, nama_pengeluaran, jumlah, status_pembayaran, tanggal_pembayaran, keterangan)
        VALUES (:tanggal, :bulan_pnl, :tahun_pnl, :kategori, :nama_pengeluaran, :jumlah, :status_pembayaran, :tanggal_pembayaran, :keterangan)
    ');

    $inserted = 0;
    foreach ($filtered as $expense) {
        $statement->execute([
            'tanggal' => $expense['tanggal'],
            'bulan_pnl' => $expense['bulan_pnl'],
            'tahun_pnl' => $expense['tahun_pnl'],
            'kategori' => $expense['kategori'],
            'nama_pengeluaran' => $expense['nama_pengeluaran'],
            'jumlah' => $expense['jumlah'],
            'status_pembayaran' => $expense['status_pembayaran'],
            'tanggal_pembayaran' => $expense['tanggal_pembayaran'],
            'keterangan' => $expense['keterangan'],
        ]);
        $inserted++;
    }

    return [
        'deleted' => $deleted->rowCount(),
        'inserted' => $inserted,
    ];
}

function apply_invoice_328_commission_fix(PDO $pdo): int
{
    $stmt = $pdo->prepare('
        UPDATE invoices
        SET komisi_sales_belum_terbayar = 99000,
            status_pembayaran_komisi_sales = :status
        WHERE nomor_invoice = :nomor_invoice
          AND (komisi_sales_belum_terbayar <> 99000 OR status_pembayaran_komisi_sales <> :status_check OR status_pembayaran_komisi_sales IS NULL)
    ');
    $stmt->execute([
        'status' => 'Belum TF',
        'status_check' => 'Belum TF',
        'nomor_invoice' => '328/BM-INV/III/2026',
    ]);

    return $stmt->rowCount();
}

function update_2025_invoice_fields_from_excel(PDO $pdo, string $excelPath): array
{
    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan-2025');
    $existingInvoices = [];

    foreach ($pdo->query('SELECT nomor_invoice FROM invoices')->fetchAll(PDO::FETCH_COLUMN) ?: [] as $invoiceNumber) {
        $existingInvoices[invoice_number_key_2025((string) $invoiceNumber)] = (string) $invoiceNumber;
    }

    $salesMap = fetch_sales_map_2025($pdo);
    $statement = $pdo->prepare('
        UPDATE invoices
        SET kode_sales_1 = :kode_sales_1,
            nama_sales_1 = :nama_sales_1,
            kode_sales_2 = :kode_sales_2,
            nama_sales_2 = :nama_sales_2,
            harga_normal_pricelist = :harga_normal_pricelist,
            discount_persen = :discount_persen,
            discount_amount = :discount_amount,
            total_harga_jual = :total_harga_jual,
            status_pembayaran = :status_pembayaran,
            tanggal_pembayaran = :tanggal_pembayaran,
            komisi_sales_1_persen = :komisi_sales_1_persen,
            komisi_sales_2_persen = :komisi_sales_2_persen,
            komisi_sales_terbayar = :komisi_sales_terbayar,
            komisi_sales_belum_terbayar = :komisi_sales_belum_terbayar,
            status_pembayaran_komisi_sales = :status_pembayaran_komisi_sales,
            tanggal_transfer_komisi_sales = :tanggal_transfer_komisi_sales,
            komisi_manager_terbayar = :komisi_manager_terbayar,
            komisi_manager_utang = :komisi_manager_utang,
            tanggal_transfer_komisi_manager = :tanggal_transfer_komisi_manager,
            pph_final_terbayar = :pph_final_terbayar,
            pph_final_belum_terbayar = :pph_final_belum_terbayar,
            komisi_admin_terbayar = :komisi_admin_terbayar,
            komisi_admin_belum_terbayar = :komisi_admin_belum_terbayar,
            tanggal_transfer_komisi_admin = :tanggal_transfer_komisi_admin,
            biaya_kirim = :biaya_kirim,
            biaya_admin_bank = :biaya_admin_bank,
            total_pembelian_barang = :total_pembelian_barang,
            total_utang_pembelian_barang = :total_utang_pembelian_barang,
            status_pembelian_barang = :status_pembelian_barang,
            tanggal_transfer_pembelian_barang = :tanggal_transfer_pembelian_barang
        WHERE nomor_invoice = :nomor_invoice
    ');

    $records = 0;
    $matched = 0;
    $unmatched = [];

    foreach ($rows as $row) {
        $invoiceNumber = normalize_spaces((string) ($row['A'] ?? ''));
        if (! is_2025_invoice_number($invoiceNumber)) {
            continue;
        }

        $records++;
        $invoiceKey = invoice_number_key_2025($invoiceNumber);
        if (! isset($existingInvoices[$invoiceKey])) {
            $unmatched[] = $invoiceNumber;
            continue;
        }

        $sales1 = resolve_sales_2025($pdo, $salesMap, (string) ($row['F'] ?? ''));
        $sales2 = resolve_sales_2025($pdo, $salesMap, (string) ($row['G'] ?? ''));
        $purchaseDebt = (float) parse_number_internal($row['AH'] ?? 0);
        $purchaseTransferDate = excel_date_2025($row['AI'] ?? '');

        $statement->execute([
            'nomor_invoice' => $existingInvoices[$invoiceKey],
            'kode_sales_1' => $sales1['kode_sales'],
            'nama_sales_1' => $sales1['nama_sales'],
            'kode_sales_2' => $sales2['kode_sales'],
            'nama_sales_2' => $sales2['nama_sales'],
            'harga_normal_pricelist' => (float) parse_number_internal($row['H'] ?? 0),
            'discount_persen' => parse_percent_2025($row['I'] ?? ''),
            'discount_amount' => (float) parse_number_internal($row['J'] ?? 0),
            'total_harga_jual' => (float) parse_number_internal($row['K'] ?? 0),
            'status_pembayaran' => normalize_payment_status_2025((string) ($row['L'] ?? '')),
            'tanggal_pembayaran' => excel_date_2025($row['M'] ?? ''),
            'komisi_sales_1_persen' => parse_percent_2025($row['P'] ?? ''),
            'komisi_sales_2_persen' => parse_percent_2025($row['Q'] ?? ''),
            'komisi_sales_terbayar' => (float) parse_number_internal($row['S'] ?? 0),
            'komisi_sales_belum_terbayar' => (float) parse_number_internal($row['T'] ?? 0),
            'status_pembayaran_komisi_sales' => normalize_spaces((string) ($row['U'] ?? '')) ?: null,
            'tanggal_transfer_komisi_sales' => excel_date_2025($row['V'] ?? ''),
            'komisi_manager_terbayar' => (float) parse_number_internal($row['W'] ?? 0),
            'komisi_manager_utang' => (float) parse_number_internal($row['X'] ?? 0),
            'tanggal_transfer_komisi_manager' => excel_date_2025($row['Y'] ?? ''),
            'pph_final_terbayar' => (float) parse_number_internal($row['Z'] ?? 0),
            'pph_final_belum_terbayar' => (float) parse_number_internal($row['AA'] ?? 0),
            'komisi_admin_terbayar' => (float) parse_number_internal($row['AB'] ?? 0),
            'komisi_admin_belum_terbayar' => (float) parse_number_internal($row['AC'] ?? 0),
            'tanggal_transfer_komisi_admin' => excel_date_2025($row['AD'] ?? ''),
            'biaya_kirim' => (float) parse_number_internal($row['AE'] ?? 0),
            'biaya_admin_bank' => (float) parse_number_internal($row['AF'] ?? 0),
            'total_pembelian_barang' => (float) parse_number_internal($row['AG'] ?? 0),
            'total_utang_pembelian_barang' => $purchaseDebt,
            'status_pembelian_barang' => $purchaseTransferDate !== null ? 'Lunas' : ($purchaseDebt > 0 ? 'Utang' : 'Lunas'),
            'tanggal_transfer_pembelian_barang' => $purchaseTransferDate,
        ]);
        $matched++;
    }

    return [
        'records' => $records,
        'matched' => $matched,
        'unmatched' => count($unmatched),
        'unmatched_examples' => array_slice($unmatched, 0, 10),
    ];
}

function update_2025_operational_from_excel(PDO $pdo, string $excelPath): array
{
    $expenses = read_operational_from_workbook_internal($excelPath);
    $filtered = [];

    foreach ($expenses as $expense) {
        $tanggal = $expense['tanggal'] ?? null;
        if ($tanggal === null || (int) date('Y', strtotime((string) $tanggal)) !== 2025) {
            continue;
        }

        $expense['bulan_pnl'] = $expense['bulan_pnl'] ?? (int) date('n', strtotime((string) $tanggal));
        $expense['tahun_pnl'] = $expense['tahun_pnl'] ?? 2025;
        $expense['kategori'] = 'operational';
        $filtered[] = $expense;
    }

    $pdo->exec("DELETE FROM journal_entries WHERE source_type = 'operational_expense'");
    $deleted = $pdo->exec("DELETE FROM operational_expenses WHERE kategori = 'operational' AND YEAR(tanggal) = 2025");

    $statement = $pdo->prepare('
        INSERT INTO operational_expenses (tanggal, bulan_pnl, tahun_pnl, kategori, nama_pengeluaran, jumlah, status_pembayaran, tanggal_pembayaran, keterangan)
        VALUES (:tanggal, :bulan_pnl, :tahun_pnl, :kategori, :nama_pengeluaran, :jumlah, :status_pembayaran, :tanggal_pembayaran, :keterangan)
    ');

    $inserted = 0;
    foreach ($filtered as $expense) {
        $statement->execute([
            'tanggal' => $expense['tanggal'],
            'bulan_pnl' => $expense['bulan_pnl'],
            'tahun_pnl' => $expense['tahun_pnl'],
            'kategori' => $expense['kategori'],
            'nama_pengeluaran' => $expense['nama_pengeluaran'],
            'jumlah' => $expense['jumlah'],
            'status_pembayaran' => $expense['status_pembayaran'],
            'tanggal_pembayaran' => $expense['tanggal_pembayaran'],
            'keterangan' => $expense['keterangan'],
        ]);
        $inserted++;
    }

    return [
        'deleted' => (int) $deleted,
        'inserted' => $inserted,
    ];
}

function fetch_sales_map_2025(PDO $pdo): array
{
    $rows = $pdo->query('SELECT kode_sales, nama_sales FROM master_sales ORDER BY kode_sales')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $map = [];

    foreach ($rows as $row) {
        $map[sales_key_2025((string) $row['nama_sales'])] = [
            'kode_sales' => (string) $row['kode_sales'],
            'nama_sales' => (string) $row['nama_sales'],
        ];
    }

    return $map;
}

function resolve_sales_2025(PDO $pdo, array &$salesMap, string $name): array
{
    $name = normalize_sales_name_2025($name);
    if ($name === '') {
        return ['kode_sales' => null, 'nama_sales' => null];
    }

    $key = sales_key_2025($name);
    if (isset($salesMap[$key])) {
        return $salesMap[$key];
    }

    $code = next_sales_code_2025($pdo);
    $statement = $pdo->prepare('INSERT INTO master_sales (kode_sales, nama_sales) VALUES (:kode_sales, :nama_sales)');
    $statement->execute(['kode_sales' => $code, 'nama_sales' => $name]);

    $salesMap[$key] = ['kode_sales' => $code, 'nama_sales' => $name];

    return $salesMap[$key];
}

function next_sales_code_2025(PDO $pdo): string
{
    $lastCode = (string) $pdo->query('SELECT kode_sales FROM master_sales ORDER BY kode_sales DESC LIMIT 1')->fetchColumn();
    if (preg_match('/SLS-(\d+)/', $lastCode, $match) !== 1) {
        return 'SLS-0001';
    }

    return sprintf('SLS-%04d', ((int) $match[1]) + 1);
}

function normalize_sales_name_2025(string $name): string
{
    $name = normalize_spaces($name);
    $key = strtolower($name);

    return [
        'pak adi' => 'Pak Adi',
        'tim denis' => 'Denis Team',
    ][$key] ?? $name;
}

function sales_key_2025(string $name): string
{
    return strtoupper(normalize_spaces($name));
}

function parse_percent_2025(mixed $value): float
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0.0;
    }

    $value = str_replace(',', '.', $value);
    $clean = preg_replace('/[^0-9.\-Ee+]/', '', $value) ?? '';
    if ($clean === '' || ! is_numeric($clean)) {
        return 0.0;
    }

    $number = (float) $clean;

    return abs($number) <= 1 ? $number * 100 : $number;
}

function excel_date_2025(mixed $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (! is_numeric($value)) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    $serial = (int) round((float) $value);
    if ($serial <= 0) {
        return null;
    }
    if ($serial >= 60) {
        $serial--;
    }

    $unix = ($serial - 1) * 86400 + mktime(0, 0, 0, 1, 1, 1900);
    $year = (int) date('Y', $unix);
    if ($year < 2000 || $year > 2100) {
        return null;
    }

    return date('Y-m-d', $unix);
}

function normalize_payment_status_2025(string $status): string
{
    return strtolower(normalize_spaces($status)) === 'lunas' ? 'Lunas' : 'Belum Lunas';
}

function invoice_number_key_2025(string $invoiceNumber): string
{
    return strtoupper(normalize_spaces($invoiceNumber));
}

function is_2025_invoice_number(string $value): bool
{
    return preg_match('~/BM-INV/[IVXLCDM]+/2025$~i', $value) === 1;
}

function update_invoice_purchase_data_from_excel(PDO $pdo, string $excelPath): int
{
    // Pastikan semua kolom baru sudah ada
    $cols = $pdo->query('DESCRIBE invoices')->fetchAll(PDO::FETCH_COLUMN);
    $existingCols = array_flip($cols);

    if (! isset($existingCols['total_pembelian_barang'])) {
        $pdo->exec('ALTER TABLE invoices ADD COLUMN total_pembelian_barang DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER subtotal');
    }
    if (! isset($existingCols['total_utang_pembelian_barang'])) {
        $pdo->exec('ALTER TABLE invoices ADD COLUMN total_utang_pembelian_barang DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_pembelian_barang');
    }
    if (! isset($existingCols['status_pembelian_barang'])) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN status_pembelian_barang VARCHAR(20) NOT NULL DEFAULT 'Lunas' AFTER total_utang_pembelian_barang");
    }
    if (! isset($existingCols['tanggal_transfer_pembelian_barang'])) {
        $pdo->exec('ALTER TABLE invoices ADD COLUMN tanggal_transfer_pembelian_barang DATE NULL AFTER status_pembelian_barang');
    }

    $rows = read_xlsx_sheet_rows_internal($excelPath, 'Penjualan');

    $statement = $pdo->prepare('
        UPDATE invoices
        SET total_pembelian_barang = :total_pembelian_barang,
            total_utang_pembelian_barang = :total_utang_pembelian_barang,
            status_pembelian_barang = :status_pembelian_barang,
            tanggal_transfer_pembelian_barang = :tanggal_transfer_pembelian_barang
        WHERE nomor_invoice = :nomor_invoice
    ');

    $pdo->beginTransaction();
    $pdo->exec("
        UPDATE invoices
        SET total_pembelian_barang = 0,
            total_utang_pembelian_barang = 0,
            status_pembelian_barang = 'Lunas',
            tanggal_transfer_pembelian_barang = NULL
    ");

    $count = 0;
    foreach ($rows as $row) {
        $invoiceNo = trim((string) ($row['A'] ?? ''));
        if ($invoiceNo === '' || stripos($invoiceNo, 'invoice') !== false) {
            continue;
        }

        $purchaseTotal = (float) parse_number_internal($row['AG'] ?? 0);
        $debtTotal = (float) parse_number_internal($row['AH'] ?? 0);
        $transferDateRaw = trim((string) ($row['AI'] ?? ''));
        
        $transferDate = null;
        if ($transferDateRaw !== '' && is_numeric($transferDateRaw)) {
            $serial = (int) round((float) $transferDateRaw);
            if ($serial > 0) {
                if ($serial >= 60) $serial--;
                $unix = ($serial - 1) * 86400 + mktime(0, 0, 0, 1, 1, 1900);
                $year = (int) date('Y', $unix);
                if ($year >= 2000 && $year <= 2100) {
                    $transferDate = date('Y-m-d', $unix);
                }
            }
        } elseif ($transferDateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $transferDateRaw)) {
            $transferDate = $transferDateRaw;
        }

        if ($purchaseTotal === 0.0 && $debtTotal === 0.0 && $transferDate === null) {
            continue;
        }

        $status = $transferDate !== null ? 'Lunas' : ($debtTotal > 0 ? 'Utang' : 'Lunas');

        $statement->execute([
            'total_pembelian_barang' => $purchaseTotal,
            'total_utang_pembelian_barang' => $debtTotal,
            'status_pembelian_barang' => $status,
            'tanggal_transfer_pembelian_barang' => $transferDate,
            'nomor_invoice' => $invoiceNo,
        ]);

        if ($statement->rowCount() > 0) {
            $count++;
        }
    }
    $pdo->commit();
    return $count;
}

// ============================================================
// GOOGLE DRIVE & SHEETS — WRITE OPERATIONS
// ============================================================

/**
 * Buat nama file invoice di Google Drive berdasarkan data invoice.
 * Format: {nomor_seq}_{kode_bulan}_{laundry_name}.xlsx
 * Contoh: 453_BM-INV_VI_2026 ZCLEAN LAUNDRY.xlsx
 */
function invoice_drive_filename(string $nomorInvoice, string $namaLaundry): string
{
    $base = trim($nomorInvoice);
    $laundry = strtoupper(trim($namaLaundry));
    return $base . ' ' . $laundry . '.xlsx';
}

/**
 * Cari file ID di Google Drive berdasarkan nama file di folder invoice.
 */
function google_drive_find_invoice_file(string $fileName): ?string
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return null;

    $folderId = google_drive_config('folder_id');
    $query = sprintf("'%s' in parents and name = '%s' and trashed = false",
        str_replace("'", "\\'", $folderId),
        str_replace("'", "\\'", $fileName)
    );
    $params = http_build_query([
        'q' => $query,
        'fields' => 'files(id,name)',
        'supportsAllDrives' => 'true',
        'includeItemsFromAllDrives' => 'true',
    ]);
    $response = http_get('https://www.googleapis.com/drive/v3/files?' . $params, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
    if (!$response['ok']) return null;

    $data = json_decode($response['body'], true);
    $files = $data['files'] ?? [];
    return $files[0]['id'] ?? null;
}

/**
 * Upload atau replace file XLSX invoice ke Google Drive.
 * Jika $existingFileId tidak null, lakukan PATCH (update), 
 * jika null lakukan POST (create baru).
 * Kembalikan file ID baru atau null jika gagal.
 */
function google_drive_upload_invoice(string $fileName, string $xlsxBinary, ?string $existingFileId = null): ?string
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return null;

    $folderId = google_drive_config('folder_id');
    $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $boundary = 'busamas_boundary_' . uniqid();

    // Multipart body: metadata + file binary
    $metadata = json_encode([
        'name' => $fileName,
        'mimeType' => $mimeType,
        'parents' => $existingFileId ? [] : [$folderId],
    ]);
    if ($existingFileId) {
        // Remove parents from metadata for update
        $metadata = json_encode(['name' => $fileName, 'mimeType' => $mimeType]);
    }

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= $metadata . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: {$mimeType}\r\n\r\n";
    $body .= $xlsxBinary . "\r\n";
    $body .= "--{$boundary}--";

    $headers = [
        'Authorization: Bearer ' . $token['access_token'],
        'Content-Type: multipart/related; boundary=' . $boundary,
        'Content-Length: ' . strlen($body),
    ];

    if ($existingFileId) {
        $url = 'https://www.googleapis.com/upload/drive/v3/files/' . urlencode($existingFileId)
             . '?uploadType=multipart&supportsAllDrives=true';
        $method = 'PATCH';
    } else {
        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&includeItemsFromAllDrives=true';
        $method = 'POST';
    }

    if (!function_exists('curl_init')) return null;

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);
    $responseBody = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlErr = curl_error($curl);
    curl_close($curl);

    if ($responseBody === false || $statusCode >= 400) {
        $msg = "Upload Google Drive gagal. HTTP Status: {$statusCode}.";
        if ($curlErr) $msg .= " Curl Error: {$curlErr}.";
        if ($responseBody) $msg .= " Response: " . substr($responseBody, 0, 500);
        set_google_api_error($msg);
        return null;
    }

    $data = json_decode((string) $responseBody, true);
    return $data['id'] ?? null;
}

/**
 * Hapus file dari Google Drive berdasarkan file ID.
 */
function google_drive_delete_file(string $fileId): bool
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return false;

    if (!function_exists('curl_init')) return false;

    $curl = curl_init('https://www.googleapis.com/drive/v3/files/' . urlencode($fileId) . '?supportsAllDrives=true');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
    ]);
    curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    return $statusCode === 204 || $statusCode === 200;
}

/**
 * Cari baris di Google Sheets berdasarkan nilai di kolom A (nomor invoice).
 * Kembalikan 1-based row index atau null jika tidak ditemukan.
 */
function sheets_find_invoice_row(string $nomorInvoice): ?int
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return null;

    $spreadsheetId = google_sheet_config('spreadsheet_id');
    $sheetName = google_sheet_config('penjualan_sheet_name', 'Penjualan');
    $range = urlencode($sheetName . '!A:A');

    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}";
    $response = http_get($url, ['Authorization: Bearer ' . $token['access_token']]);

    if (!$response['ok']) return null;

    $data = json_decode($response['body'], true);
    $values = $data['values'] ?? [];

    foreach ($values as $rowIdx => $rowData) {
        $cellVal = trim((string) ($rowData[0] ?? ''));
        if ($cellVal === $nomorInvoice) {
            return $rowIdx + 1; // 1-based
        }
    }

    return null;
}

/**
 * Bangun array baris untuk Google Sheets berdasarkan data invoice.
 * Kolom mengikuti urutan PENJUALAN-2026.xlsx: A (nomor_invoice), B (tgl_invoice),
 * C (nomor_sj), D (tgl_sj), E (kode_customer/nama), ... dst.
 */
function build_sheets_invoice_row(array $invoice): array
{
    $fmt = static fn($v) => $v !== null ? (string) $v : '';
    $fmtNum = static fn($v) => $v !== null ? number_format((float) $v, 0, ',', '.') : '0';

    return [
        $fmt($invoice['nomor_invoice']),      // A
        $fmt($invoice['tanggal_invoice']),    // B
        $fmt($invoice['nomor_surat_jalan']),  // C
        $fmt($invoice['tanggal_surat_jalan']),// D
        $fmt($invoice['nama_laundry_invoice'] ?: $invoice['nama_customer_invoice']), // E
        $fmt($invoice['alamat']),             // F
        $fmt($invoice['no_telepon']),         // G
        '',                                   // H (reserved)
        $fmtNum($invoice['subtotal']),        // I
        $fmt($invoice['discount_persen']),    // J
        $fmtNum($invoice['discount_amount']), // K
        $fmtNum($invoice['total_harga_jual']),// L
        $fmt($invoice['status_pembayaran']),  // M
        $fmt($invoice['tanggal_pembayaran']), // N
        $fmt($invoice['nama_sales_1']),       // O
        $fmt($invoice['komisi_sales_1_persen']), // P
        $fmt($invoice['nama_sales_2']),       // Q
        $fmt($invoice['komisi_sales_2_persen']), // R
        $fmtNum($invoice['komisi_sales_terbayar']),      // S
        $fmtNum($invoice['komisi_sales_belum_terbayar']),// T
        $fmt($invoice['status_pembayaran_komisi_sales']),// U
        $fmt($invoice['tanggal_transfer_komisi_sales']), // V
        $fmtNum($invoice['komisi_manager_terbayar']),    // W
        $fmtNum($invoice['komisi_manager_utang']),       // X
        $fmt($invoice['tanggal_transfer_komisi_manager']),// Y
        $fmtNum($invoice['pph_final_terbayar']),         // Z
        $fmtNum($invoice['pph_final_belum_terbayar']),   // AA
        $fmtNum($invoice['komisi_admin_terbayar']),      // AB
        $fmtNum($invoice['komisi_admin_belum_terbayar']),// AC
        $fmt($invoice['tanggal_transfer_komisi_admin']), // AD
        $fmtNum($invoice['biaya_kirim']),     // AE
        $fmtNum($invoice['biaya_admin_bank']),// AF
        $fmtNum($invoice['total_pembelian_barang']),     // AG
        $fmtNum($invoice['total_utang_pembelian_barang']),// AH
        $fmt($invoice['tanggal_transfer_pembelian_barang']),// AI
        $fmt($invoice['po_number']),          // AJ
    ];
}

/**
 * Append baris invoice baru ke Google Sheets (INSERT).
 */
function sheets_append_invoice_row(array $invoice): bool
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return false;

    $spreadsheetId = google_sheet_config('spreadsheet_id');
    $sheetName = google_sheet_config('penjualan_sheet_name', 'Penjualan');
    $range = urlencode($sheetName . '!A:AJ');

    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append"
         . '?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS';

    $row = build_sheets_invoice_row($invoice);
    $body = json_encode(['values' => [$row]]);

    return http_post_json($url, $body, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
}

/**
 * Update baris invoice yang sudah ada di Google Sheets (UPDATE).
 * Cari baris berdasarkan nomor_invoice, lalu PATCH dengan data baru.
 */
function sheets_update_invoice_row(array $invoice): bool
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return false;

    $rowIndex = sheets_find_invoice_row($invoice['nomor_invoice'] ?? '');
    if ($rowIndex === null) {
        // Tidak ditemukan — coba append sebagai baris baru
        return sheets_append_invoice_row($invoice);
    }

    $spreadsheetId = google_sheet_config('spreadsheet_id');
    $sheetName = google_sheet_config('penjualan_sheet_name', 'Penjualan');
    $cellRange = urlencode("{$sheetName}!A{$rowIndex}:AJ{$rowIndex}");
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$cellRange}"
         . '?valueInputOption=USER_ENTERED';

    $row = build_sheets_invoice_row($invoice);
    $body = json_encode(['range' => "{$sheetName}!A{$rowIndex}:AJ{$rowIndex}", 'values' => [$row]]);

    return http_put_json($url, $body, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
}

/**
 * Hapus baris invoice dari Google Sheets menggunakan batchUpdate deleteRange.
 */
function sheets_delete_invoice_row(string $nomorInvoice): bool
{
    $token = google_service_account_access_token();
    if (!$token['ok']) return false;

    $rowIndex = sheets_find_invoice_row($nomorInvoice);
    if ($rowIndex === null) return true; // Sudah tidak ada

    $spreadsheetId = google_sheet_config('spreadsheet_id');

    // Perlu sheet ID (gid) untuk deleteRange
    $sheetGid = (int) google_sheet_config('gid', '0');

    // Cari sheetId dari nama sheet
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=sheets(properties)";
    $metaResp = http_get($url, ['Authorization: Bearer ' . $token['access_token']]);
    if ($metaResp['ok']) {
        $meta = json_decode($metaResp['body'], true);
        $sheetName = google_sheet_config('penjualan_sheet_name', 'Penjualan');
        foreach ($meta['sheets'] ?? [] as $s) {
            if (($s['properties']['title'] ?? '') === $sheetName) {
                $sheetGid = (int) $s['properties']['sheetId'];
                break;
            }
        }
    }

    $batchUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate";
    $payload = json_encode([
        'requests' => [[
            'deleteDimension' => [
                'range' => [
                    'sheetId'    => $sheetGid,
                    'dimension'  => 'ROWS',
                    'startIndex' => $rowIndex - 1, // 0-based
                    'endIndex'   => $rowIndex,
                ],
            ],
        ]],
    ]);

    return http_post_json($batchUrl, $payload, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
}

/**
 * HTTP POST dengan body JSON. Kembalikan true jika berhasil.
 */
function http_post_json(string $url, string $jsonBody, array $headers = []): bool
{
    if (!function_exists('curl_init')) {
        set_google_api_error('cURL PHP extension is not enabled on this server.');
        return false;
    }

    $allHeaders = array_merge($headers, ['Content-Type: application/json', 'Content-Length: ' . strlen($jsonBody)]);
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_HTTPHEADER => $allHeaders,
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlErr = curl_error($curl);
    curl_close($curl);

    if ($response === false || $status < 200 || $status >= 300) {
        $msg = "HTTP POST ke Google Sheets gagal. HTTP Status: {$status}.";
        if ($curlErr) $msg .= " Curl Error: {$curlErr}.";
        if ($response) $msg .= " Response: " . substr($response, 0, 500);
        set_google_api_error($msg);
        return false;
    }

    return true;
}

/**
 * HTTP PUT dengan body JSON. Kembalikan true jika berhasil.
 */
function http_put_json(string $url, string $jsonBody, array $headers = []): bool
{
    if (!function_exists('curl_init')) {
        set_google_api_error('cURL PHP extension is not enabled on this server.');
        return false;
    }

    $allHeaders = array_merge($headers, ['Content-Type: application/json', 'Content-Length: ' . strlen($jsonBody)]);
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_HTTPHEADER => $allHeaders,
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlErr = curl_error($curl);
    curl_close($curl);

    if ($response === false || $status < 200 || $status >= 300) {
        $msg = "HTTP PUT ke Google Sheets gagal. HTTP Status: {$status}.";
        if ($curlErr) $msg .= " Curl Error: {$curlErr}.";
        if ($response) $msg .= " Response: " . substr($response, 0, 500);
        set_google_api_error($msg);
        return false;
    }

    return true;
}

/**
 * Buat file XLSX minimal untuk invoice berdasarkan template yang ada.
 * Menggunakan file template dari storage/ yang sudah ada, lalu isi datanya.
 * Kembalikan binary content XLSX atau null jika gagal.
 */
function generate_invoice_xlsx_binary(array $invoice, array $items): ?string
{
    if (!class_exists('ZipArchive')) {
        set_google_api_error('ZipArchive PHP extension is not enabled on this server.');
        return null;
    }

    // Cari template dari file storage yang paling mirip
    $templates = glob(dirname(__DIR__) . '/storage/*.xlsx') ?: [];
    // Filter out PENJUALAN-2026.xlsx
    $templates = array_filter($templates, static fn($f) => stripos(basename($f), 'PENJUALAN') === false);

    if (empty($templates)) {
        set_google_api_error('Template file Excel tidak ditemukan di folder storage.');
        return null;
    }

    // Gunakan template pertama yang tersedia
    $templatePath = array_values($templates)[0];
    $templateBinary = file_get_contents($templatePath);
    if ($templateBinary === false) return null;

    // Tulis ke temp file, lalu modifikasi menggunakan ZipArchive
    $tmpFile = sys_get_temp_dir() . '/busamas_inv_' . uniqid() . '.xlsx';
    file_put_contents($tmpFile, $templateBinary);

    $zip = new ZipArchive();
    if ($zip->open($tmpFile) !== true) return null;

    try {
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            @unlink($tmpFile);
            return null;
        }

        $sheet = simplexml_load_string($sheetXml);

        $set_cell = static function ($sheet, $ref, $val, $type = 'n') {
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
            if (!$m) return;
            $rowNum = $m[2];

            // Find row
            $rowEl = null;
            foreach ($sheet->sheetData->row as $r) {
                if ((string) $r['r'] === $rowNum) {
                    $rowEl = $r;
                    break;
                }
            }
            if (!$rowEl) return;

            // Find cell
            $cellEl = null;
            foreach ($rowEl->c as $c) {
                if ((string) $c['r'] === $ref) {
                    $cellEl = $c;
                    break;
                }
            }
            if (!$cellEl) {
                $cellEl = $rowEl->addChild('c');
                $cellEl['r'] = $ref;
            }

            // Clear value
            unset($cellEl->v);
            unset($cellEl->is);

            if ($val === null || $val === '') {
                unset($cellEl['t']);
                return;
            }

            if ($type === 'inlineStr') {
                $cellEl['t'] = 'inlineStr';
                $is = $cellEl->addChild('is');
                $is->addChild('t', htmlspecialchars((string) $val, ENT_XML1, 'UTF-8'));
            } else {
                unset($cellEl['t']);
                $cellEl->addChild('v', (string) $val);
            }
        };

        // Header details
        $laundry = (string) ($invoice['nama_laundry_invoice'] ?: $invoice['nama_customer_invoice'] ?: '');
        $tanggal = (string) ($invoice['tanggal_invoice'] ?? '');
        $noInvoice = (string) ($invoice['nomor_invoice'] ?? '');
        $poNumber = (string) ($invoice['po_number'] ?? '');
        $up = (string) ($invoice['nama_customer_master'] ?: $invoice['nama_customer_invoice'] ?: '');
        $phone = (string) ($invoice['no_telepon'] ?? '');

        $alamat = (string) ($invoice['alamat'] ?? '');
        $alamat1 = $alamat;
        $alamat2 = '';
        if (strpos($alamat, "\n") !== false) {
            [$alamat1, $alamat2] = explode("\n", $alamat, 2);
        } elseif (strlen($alamat) > 40) {
            $pos = strrpos(substr($alamat, 0, 40), ' ');
            if ($pos !== false) {
                $alamat1 = substr($alamat, 0, $pos);
                $alamat2 = substr($alamat, $pos + 1);
            }
        }

        $set_cell($sheet, 'B12', $laundry, 'inlineStr');
        $set_cell($sheet, 'F12', ': ' . $tanggal, 'inlineStr');
        $set_cell($sheet, 'B13', $alamat1, 'inlineStr');
        $set_cell($sheet, 'B14', $alamat2, 'inlineStr');
        $set_cell($sheet, 'F14', ': ' . $noInvoice, 'inlineStr');
        $set_cell($sheet, 'F16', ': ' . $poNumber, 'inlineStr');
        $set_cell($sheet, 'B17', $up, 'inlineStr');
        $set_cell($sheet, 'B18', $phone !== '' ? 'hp : ' . $phone : '', 'inlineStr');

        // Fill item details (up to 7 items: row 23, 25, 27, 29, 31, 33, 35)
        $itemRows = [23, 25, 27, 29, 31, 33, 35];
        $totalItems = count($items);

        foreach ($itemRows as $index => $rNum) {
            if ($index < $totalItems) {
                $item = $items[$index];
                $name = (string) ($item['nama_barang_invoice'] ?? $item['nama_barang_master'] ?? '');
                $isi = (string) ($item['isi_invoice'] ?? '');
                $qty = (float) ($item['jumlah'] ?? 0);
                $satuan = (string) ($item['satuan'] ?? '');
                $harga = (float) ($item['harga'] ?? 0);
                $total = (float) ($item['total'] ?? 0);

                $set_cell($sheet, 'A' . $rNum, $index + 1);
                $set_cell($sheet, 'B' . $rNum, $name, 'inlineStr');
                $set_cell($sheet, 'C' . $rNum, $isi, 'inlineStr');
                $set_cell($sheet, 'D' . $rNum, $qty);
                $set_cell($sheet, 'E' . $rNum, $satuan, 'inlineStr');
                $set_cell($sheet, 'F' . $rNum, $harga);
                $set_cell($sheet, 'G' . $rNum, $total);
            } else {
                // Clear unused item slots
                $set_cell($sheet, 'A' . $rNum, '');
                $set_cell($sheet, 'B' . $rNum, '');
                $set_cell($sheet, 'C' . $rNum, '');
                $set_cell($sheet, 'D' . $rNum, '');
                $set_cell($sheet, 'E' . $rNum, '');
                $set_cell($sheet, 'F' . $rNum, '');
                $set_cell($sheet, 'G' . $rNum, '');
            }
        }

        // Subtotal / Total
        $finalTotal = (float) ($invoice['total_harga_jual'] ?? 0);
        $set_cell($sheet, 'G36', $finalTotal);

        // Date footer
        $set_cell($sheet, 'F42', '     Denpasar, ' . $tanggal, 'inlineStr');

        // Sales Signature
        $salesName = (string) ($invoice['nama_sales_1'] ?? '');
        $set_cell($sheet, 'F47', $salesName !== '' ? '( ' . $salesName . ' )' : '', 'inlineStr');

        $newSheetXml = $sheet->asXML();
        $zip->addFromString('xl/worksheets/sheet1.xml', $newSheetXml);
        $zip->close();

        $binary = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $binary !== false ? $binary : null;
    } catch (Throwable $e) {
        $zip->close();
        @unlink($tmpFile);
        return null;
    }
}

/**
 * Bangun daftar shared strings dan XML untuk XLSX invoice.
 * Kembalikan ['strings' => [...], 'xml' => '...'].
 */
function build_invoice_xlsx_shared_strings(array $invoice, array $items): array
{
    $strings = [];
    $addStr = static function (string $val) use (&$strings): int {
        $key = array_search($val, $strings, true);
        if ($key !== false) return (int) $key;
        $strings[] = $val;
        return count($strings) - 1;
    };

    // Pre-load semua string yang dibutuhkan
    $addStr($invoice['nomor_invoice'] ?? '');
    $addStr($invoice['tanggal_invoice'] ?? '');
    $addStr($invoice['nomor_surat_jalan'] ?? '');
    $addStr($invoice['tanggal_surat_jalan'] ?? '');
    $addStr($invoice['nama_laundry_invoice'] ?? $invoice['nama_customer_invoice'] ?? '');
    $addStr($invoice['alamat'] ?? '');
    $addStr($invoice['no_telepon'] ?? '');
    $addStr($invoice['po_number'] ?? '');
    $addStr($invoice['status_pembayaran'] ?? '');

    foreach ($items as $item) {
        $addStr((string) ($item['nama_barang_invoice'] ?? $item['nama_barang_master'] ?? ''));
        $addStr((string) ($item['isi_invoice'] ?? ''));
        $addStr((string) ($item['satuan'] ?? ''));
    }

    $xmlParts = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
                 '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">'];
    foreach ($strings as $s) {
        $xmlParts[] = '<si><t xml:space="preserve">' . htmlspecialchars((string) $s, ENT_XML1, 'UTF-8') . '</t></si>';
    }
    $xmlParts[] = '</sst>';

    return ['strings' => $strings, 'xml' => implode('', $xmlParts), 'addStr' => $addStr];
}

/**
 * Bangun XML sheet1.xml untuk XLSX invoice (simplified).
 */
function build_invoice_xlsx_sheet(array $invoice, array $items, array $sharedStrings): string
{
    $strings = $sharedStrings['strings'];
    $strIdx = static fn(string $val): int => (int) array_search($val, $strings, true);
    $sc = static fn(string $col, int $row, string $val): string =>
        '<c r="' . $col . $row . '" t="s"><v>' . $strIdx($val) . '</v></c>';
    $nc = static fn(string $col, int $row, float $val): string =>
        '<c r="' . $col . $row . '"><v>' . $val . '</v></c>';

    $rows = [];
    // Header rows
    $rows[] = '<row r="1"><c r="B1" t="s"><v>' . $strIdx($invoice['nomor_invoice'] ?? '') . '</v></c>'
            . '<c r="D1" t="s"><v>' . $strIdx($invoice['tanggal_invoice'] ?? '') . '</v></c></row>';
    $rows[] = '<row r="3"><c r="B3" t="s"><v>' . $strIdx($invoice['nama_laundry_invoice'] ?? $invoice['nama_customer_invoice'] ?? '') . '</v></c></row>';
    $rows[] = '<row r="4"><c r="B4" t="s"><v>' . $strIdx($invoice['alamat'] ?? '') . '</v></c></row>';
    $rows[] = '<row r="5"><c r="B5" t="s"><v>' . $strIdx($invoice['no_telepon'] ?? '') . '</v></c>'
            . '<c r="F5" t="s"><v>' . $strIdx($invoice['nomor_surat_jalan'] ?? '') . '</v></c></row>';

    // Item rows starting at row 23
    $itemRow = 23;
    foreach ($items as $item) {
        $namaBarang = (string) ($item['nama_barang_invoice'] ?? $item['nama_barang_master'] ?? '');
        $isi = (string) ($item['isi_invoice'] ?? '');
        $satuan = (string) ($item['satuan'] ?? '');
        $jumlah = (float) ($item['jumlah'] ?? 0);
        $harga = (float) ($item['harga'] ?? 0);
        $total = (float) ($item['total'] ?? 0);

        $rows[] = '<row r="' . $itemRow . '">'
            . '<c r="A' . $itemRow . '" t="s"><v>' . $strIdx($namaBarang) . '</v></c>'
            . '<c r="B' . $itemRow . '" t="s"><v>' . $strIdx($isi) . '</v></c>'
            . '<c r="C' . $itemRow . '"><v>' . $jumlah . '</v></c>'
            . '<c r="D' . $itemRow . '" t="s"><v>' . $strIdx($satuan) . '</v></c>'
            . '<c r="E' . $itemRow . '"><v>' . $harga . '</v></c>'
            . '<c r="F' . $itemRow . '"><v>' . $total . '</v></c>'
            . '</row>';
        $itemRow += 2;
    }

    // Total row
    $totalRow = $itemRow + 2;
    $rows[] = '<row r="' . $totalRow . '">'
        . '<c r="F' . $totalRow . '"><v>' . ((float) ($invoice['subtotal'] ?? 0)) . '</v></c></row>';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>' . implode('', $rows) . '</sheetData></worksheet>';
}

function ensure_invoice_google_sync_columns(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'google_drive_file_id'");
    if ($stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC)) {
        return;
    }

    $pdo->exec('ALTER TABLE invoices ADD COLUMN google_drive_file_id VARCHAR(200) NULL AFTER file_invoice');
}

function queue_invoice_google_sync(string $kodeInvoice, bool $isUpdate): array
{
    $scriptPath = dirname(__DIR__) . '/scripts/sync-invoice-google.php';
    if (!is_file($scriptPath)) {
        return [
            'queued' => false,
            'errors' => ['Invoice tersimpan, tapi script sync Google tidak ditemukan.'],
        ];
    }

    if (!function_exists('exec')) {
        return [
            'queued' => false,
            'errors' => ['Invoice tersimpan, tapi fungsi exec tidak aktif untuk menjalankan sync Google di background.'],
        ];
    }

    $logDir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $php = PHP_BINARY ?: 'php';
    $logPath = $logDir . '/google-sync.log';
    $command = escapeshellarg($php)
        . ' ' . escapeshellarg($scriptPath)
        . ' ' . escapeshellarg($kodeInvoice)
        . ' ' . ($isUpdate ? '1' : '0')
        . ' >> ' . escapeshellarg($logPath)
        . ' 2>&1 &';

    exec($command);

    return [
        'queued' => true,
        'errors' => [],
    ];
}

/**
 * Sync invoice ke Google Drive dan Google Sheets setelah disimpan/diperbarui.
 * Dipanggil setelah save_invoice_form() berhasil.
 *
 * @param string $kodeInvoice   Kode invoice yang baru disimpan
 * @param bool   $isUpdate      true = edit, false = baru
 * @return array ['drive_ok', 'sheets_ok', 'drive_file_id', 'errors']
 */
function sync_invoice_to_google(string $kodeInvoice, bool $isUpdate): array
{
    $pdo = db();
    if ($pdo === null) return ['drive_ok' => false, 'sheets_ok' => false, 'drive_file_id' => null, 'errors' => ['DB not connected']];

    ensure_invoice_google_sync_columns($pdo);

    // Clear last error
    set_google_api_error('');

    // Ambil data invoice dari database
    $invoice = $pdo->prepare('SELECT * FROM invoices WHERE kode_invoice = ?');
    $invoice->execute([$kodeInvoice]);
    $invoiceData = $invoice->fetch(PDO::FETCH_ASSOC);

    if (!$invoiceData) return ['drive_ok' => false, 'sheets_ok' => false, 'drive_file_id' => null, 'errors' => ['Invoice not found']];

    $items = $pdo->prepare('SELECT * FROM invoice_items WHERE kode_invoice = ? ORDER BY baris');
    $items->execute([$kodeInvoice]);
    $itemsData = $items->fetchAll(PDO::FETCH_ASSOC);

    $errors = [];
    $driveFileId = null;
    $driveOk = false;
    $sheetsOk = false;

    // ── 1. Generate XLSX & upload ke Drive ──────────────────────
    $fileName = invoice_drive_filename(
        $invoiceData['nomor_invoice'],
        $invoiceData['nama_laundry_invoice'] ?: $invoiceData['nama_customer_invoice'] ?: ''
    );

    $xlsxBinary = generate_invoice_xlsx_binary($invoiceData, $itemsData);

    if ($xlsxBinary !== null) {
        // Cari existing file ID: pakai yg tersimpan di DB atau cari dari Drive
        $existingFileId = ($invoiceData['google_drive_file_id'] ?? '') ?: google_drive_find_invoice_file($fileName);

        $newFileId = google_drive_upload_invoice($fileName, $xlsxBinary, $existingFileId);

        if ($newFileId !== null) {
            $driveFileId = $newFileId;
            $driveOk = true;
            // Simpan file ID ke database
            $pdo->prepare('UPDATE invoices SET google_drive_file_id = ? WHERE kode_invoice = ?')
                ->execute([$newFileId, $kodeInvoice]);
        } else {
            $errors[] = 'Upload ke Google Drive gagal: ' . (get_google_api_error() ?: 'Unknown Drive API error');
        }
    } else {
        $errors[] = 'Generate XLSX gagal: ' . (get_google_api_error() ?: 'ZipArchive tidak aktif atau template tidak ditemukan');
    }

    // Clear last error before sheets operation to isolate sheets failure message
    set_google_api_error('');

    // ── 2. Update / Append di Google Sheets ──────────────────────
    if ($isUpdate) {
        $sheetsOk = sheets_update_invoice_row($invoiceData);
    } else {
        $sheetsOk = sheets_append_invoice_row($invoiceData);
    }

    if (!$sheetsOk) {
        $errors[] = 'Sinkronisasi ke Google Sheets gagal: ' . (get_google_api_error() ?: 'Unknown Sheets API error');
    }

    return [
        'drive_ok'      => $driveOk,
        'sheets_ok'     => $sheetsOk,
        'drive_file_id' => $driveFileId,
        'errors'        => $errors,
    ];
}

/**
 * Hapus invoice dari Google Drive dan Google Sheets.
 * Dipanggil sebelum menghapus record dari database.
 */
function delete_invoice_from_google(string $kodeInvoice): array
{
    $pdo = db();
    if ($pdo === null) return ['drive_ok' => false, 'sheets_ok' => false];

    ensure_invoice_google_sync_columns($pdo);

    $invoice = $pdo->prepare('SELECT nomor_invoice, nama_laundry_invoice, nama_customer_invoice, google_drive_file_id FROM invoices WHERE kode_invoice = ?');
    $invoice->execute([$kodeInvoice]);
    $invoiceData = $invoice->fetch(PDO::FETCH_ASSOC);

    if (!$invoiceData) return ['drive_ok' => true, 'sheets_ok' => true]; // Sudah tidak ada

    $driveOk = true;
    $sheetsOk = true;

    // Hapus dari Drive
    $fileId = $invoiceData['google_drive_file_id'];
    if (!$fileId) {
        // Coba cari dari Drive berdasarkan nama file
        $fileName = invoice_drive_filename(
            $invoiceData['nomor_invoice'],
            $invoiceData['nama_laundry_invoice'] ?: $invoiceData['nama_customer_invoice'] ?: ''
        );
        $fileId = google_drive_find_invoice_file($fileName);
    }

    if ($fileId) {
        $driveOk = google_drive_delete_file($fileId);
    }

    // Hapus dari Sheets
    $sheetsOk = sheets_delete_invoice_row($invoiceData['nomor_invoice']);

    return ['drive_ok' => $driveOk, 'sheets_ok' => $sheetsOk];
}

/**
 * Hapus invoice beserta item-itemnya dari database.
 */
function delete_invoice(string $kodeInvoice): array
{
    $pdo = db();
    if ($pdo === null) return ['ok' => false, 'message' => 'Koneksi database gagal.'];

    // Cek invoice ada
    $check = $pdo->prepare('SELECT nomor_invoice FROM invoices WHERE kode_invoice = ?');
    $check->execute([$kodeInvoice]);
    $inv = $check->fetch(PDO::FETCH_ASSOC);

    if (!$inv) return ['ok' => false, 'message' => 'Invoice tidak ditemukan.'];

    try {
        // Hapus dari Google Drive & Sheets dulu
        $googleResult = delete_invoice_from_google($kodeInvoice);

        $pdo->beginTransaction();
        delete_invoice_accounting_journal($pdo, $kodeInvoice);
        $pdo->prepare('DELETE FROM invoice_items WHERE kode_invoice = ?')->execute([$kodeInvoice]);
        $pdo->prepare('DELETE FROM invoices WHERE kode_invoice = ?')->execute([$kodeInvoice]);
        $pdo->commit();

        $warnings = [];
        if (!$googleResult['drive_ok']) $warnings[] = 'Gagal hapus dari Google Drive.';
        if (!$googleResult['sheets_ok']) $warnings[] = 'Gagal hapus dari Google Sheets.';

        return [
            'ok' => true,
            'message' => 'Invoice ' . $inv['nomor_invoice'] . ' berhasil dihapus.',
            'warnings' => $warnings,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'message' => 'Gagal menghapus invoice: ' . $e->getMessage()];
    }
}

function run_create_test_data(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    // Fetch a real customer and barang row from master tables
    $customer = $pdo->query("SELECT kode_customer, nama_customer, nama_laundry, no_telepon, alamat_default FROM master_customers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $barang = $pdo->query("SELECT kode_barang, nama_barang, ukuran, harga_default, satuan_default FROM master_barang LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$customer || !$barang) {
        return [
            'ok' => false,
            'message' => 'Data master customer atau barang kosong di database. Silakan jalankan seeder terlebih dahulu.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    // Generate unique code sequence
    $stmt = $pdo->query("SELECT kode_invoice FROM invoices WHERE kode_invoice LIKE 'INV-TEST-%'");
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $maxNum = 0;
    foreach ($codes as $code) {
        if (preg_match('/^INV-TEST-(\d+)$/', $code, $matches)) {
            $num = (int)$matches[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    $nextNum = $maxNum + 1;
    
    $kodeInvoice = 'INV-TEST-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    $nomorInvoice = '999/BM-INV/TEST-' . $nextNum . '/2026';

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $todayStr = date('d') . ' ' . $months[(int)date('m')] . ' ' . date('Y');

    // Calculate totals using actual price from master
    $hargaBarang = (float)($barang['harga_default'] ?: 100000);
    $jumlahQty = 5.0;
    $subtotal = $hargaBarang * $jumlahQty;

    try {
        $pdo->beginTransaction();

        // Clean up first if exists (just in case)
        delete_invoice_accounting_journal($pdo, $kodeInvoice);
        $pdo->prepare("DELETE FROM invoice_items WHERE kode_invoice = ?")->execute([$kodeInvoice]);
        $pdo->prepare("DELETE FROM invoices WHERE kode_invoice = ?")->execute([$kodeInvoice]);

        // Insert invoice
        $stmt = $pdo->prepare('
            INSERT INTO invoices (
                kode_invoice, nomor_invoice, tanggal_invoice, nomor_surat_jalan, tanggal_surat_jalan, po_number,
                kode_customer, nama_customer_invoice, nama_laundry_invoice, no_telepon, alamat,
                total_item, total_qty, subtotal, discount_persen, discount_amount, total_harga_jual,
                status_pembayaran, tanggal_pembayaran,
                komisi_sales_1_persen, komisi_sales_2_persen, komisi_sales_terbayar, komisi_sales_belum_terbayar,
                status_pembayaran_komisi_sales, tanggal_transfer_komisi_sales,
                komisi_manager_terbayar, komisi_manager_utang, tanggal_transfer_komisi_manager,
                pph_final_terbayar, pph_final_belum_terbayar,
                komisi_admin_terbayar, komisi_admin_belum_terbayar, tanggal_transfer_komisi_admin,
                biaya_kirim, biaya_admin_bank,
                total_pembelian_barang, total_utang_pembelian_barang, status_pembelian_barang, tanggal_transfer_pembelian_barang
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?
            )
        ');

        $stmt->execute([
            $kodeInvoice, $nomorInvoice, $todayStr, 'SJ-TEST-' . $nextNum, $todayStr, 'PO-TEST-' . $nextNum,
            $customer['kode_customer'], $customer['nama_customer'], $customer['nama_laundry'], $customer['no_telepon'], $customer['alamat_default'],
            1, $jumlahQty, $subtotal, 0, 0, $subtotal,
            'Belum Lunas', null,
            10, 0, 0, $subtotal * 0.1,
            'Belum TF', null,
            0, 0, null,
            0, $subtotal * 0.005,
            0, $subtotal * 0.05, null,
            10000, 0,
            $subtotal * 0.5, $subtotal * 0.5, 'Utang', null
        ]);

        // Insert item
        $stmt = $pdo->prepare('
            INSERT INTO invoice_items (
                kode_invoice, nomor_invoice, tanggal_invoice, kode_customer, kode_barang,
                nama_barang_master, ukuran_master, nama_barang_invoice, isi_invoice,
                jumlah, satuan, harga, total, baris
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $kodeInvoice, $nomorInvoice, $todayStr, $customer['kode_customer'], $barang['kode_barang'],
            $barang['nama_barang'], $barang['ukuran'], $barang['nama_barang'], 'Ukuran ' . $barang['ukuran'],
            $jumlahQty, $barang['satuan_default'] ?: 'Pail', $hargaBarang, $subtotal, 23
        ]);

        post_invoice_accounting_journal($pdo, $kodeInvoice);

        $pdo->commit();

        // Sync ke Google Drive & Sheets (seperti flow di /invoice-create)
        $syncResult = sync_invoice_to_google($kodeInvoice, false);

        $msg = 'Berhasil membuat invoice test ' . $nomorInvoice . ' di database.';
        if (!empty($syncResult['errors'])) {
            $msg .= ' Sinkronisasi Google Drive/Sheets menemui masalah: ' . implode(', ', $syncResult['errors']);
        } else {
            $msg .= ' Berhasil disinkronisasikan ke Google Drive & Sheets.';
        }

        return [
            'ok' => true,
            'message' => $msg,
            'statements' => 1,
            'counts' => database_table_counts(),
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'ok' => false,
            'message' => 'Gagal menyimpan invoice test ke database: ' . $e->getMessage(),
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }
}

function run_delete_test_data(): array
{
    $pdo = db();
    if ($pdo === null) {
        return [
            'ok' => false,
            'message' => 'Database belum bisa dikoneksi.',
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }

    try {
        // Cari semua invoice test
        $stmt = $pdo->query("SELECT kode_invoice, nomor_invoice FROM invoices WHERE kode_invoice LIKE 'INV-TEST-%' OR nomor_invoice LIKE '%TEST%'");
        $testInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $deletedCount = 0;
        $errors = [];

        foreach ($testInvoices as $inv) {
            $kode = $inv['kode_invoice'];
            $no = $inv['nomor_invoice'];
            
            // Hapus dari Google Drive & Sheets dan database lokal
            $delResult = delete_invoice($kode);
            if ($delResult['ok']) {
                $deletedCount++;
                if (!empty($delResult['warnings'])) {
                    $errors = array_merge($errors, $delResult['warnings']);
                }
            } else {
                $errors[] = 'Gagal menghapus ' . $no . ': ' . $delResult['message'];
            }
        }

        // Clean up orphaned test records just in case
        $pdo->exec("DELETE FROM journal_entries WHERE source_type = 'invoice' AND source_id LIKE 'INV-TEST-%'");
        $pdo->exec("DELETE FROM invoice_items WHERE kode_invoice LIKE 'INV-TEST-%'");

        $msg = 'Berhasil menghapus ' . $deletedCount . ' data test.';
        if (!empty($errors)) {
            $msg .= ' Peringatan selama penghapusan: ' . implode(' | ', array_unique($errors));
        }

        return [
            'ok' => true,
            'message' => $msg,
            'statements' => $deletedCount,
            'counts' => database_table_counts(),
        ];

    } catch (Throwable $e) {
        return [
            'ok' => false,
            'message' => 'Gagal membersihkan data test: ' . $e->getMessage(),
            'statements' => 0,
            'counts' => database_table_counts(),
        ];
    }
}
