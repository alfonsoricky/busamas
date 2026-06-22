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

function fetch_database_maintenance(?string $action = null): array
{
    $result = null;
    $counts = database_table_counts();

    if ($action === 'update-hosting') {
        $result = run_hosting_update();
        $counts = database_table_counts();
    } elseif ($action === 'update-latest') {
        $result = run_latest_update();
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
        return [
            'ok'         => true,
            'message'    => 'Update komisi manager berhasil. ' . $count . ' invoice diperbarui.',
            'statements' => $count,
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
        return [
            'ok'         => true,
            'message'    => 'Update komisi berhasil. ' . $count . ' invoice diperbarui.',
            'statements' => $count,
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
        $pdo->exec('TRUNCATE TABLE operational_expenses');
        $count = seed_operational_expenses_from_workbook($pdo, $excelPath);
        $bonusCount = seed_bonus_expenses($pdo);

        return [
            'ok'       => true,
            'message'  => 'Seeder data operasional + bonus berhasil dijalankan.',
            'statements' => $count + $bonusCount,
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

    foreach (['master_barang', 'master_customers', 'master_sales', 'invoices', 'invoice_items', 'operational_expenses'] as $table) {
        try {
            $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        } catch (Throwable) {
            $counts[$table] = null;
        }
    }

    return $counts;
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
    $tanggalInvoice = $formatDateToIndonesian($tanggalInvoiceRaw);

    // Calculate totals from items
    $items = $postData['items'] ?? [];
    $totalItem = count($items);
    $totalQty = 0.0;
    $subtotal = 0.0;

    foreach ($items as $item) {
        $totalQty += (float) ($item['jumlah'] ?? 0);
        $subtotal += $cleanFloat($item['total'] ?? 0);
    }

    // Form inputs mapped to DB
    $nomorSuratJalan = $cleanString($postData['nomor_surat_jalan'] ?? '');
    $tanggalSuratJalan = $formatDateToIndonesian($cleanString($postData['tanggal_surat_jalan'] ?? ''));
    $poNumber = $cleanString($postData['po_number'] ?? '');
    $noTelepon = $cleanString($postData['no_telepon'] ?? '');
    $alamat = $cleanString($postData['alamat'] ?? '');

    $kodeSales1 = $cleanString($postData['kode_sales_1'] ?? '');
    $namaSales1 = $getSalesName($pdo, $kodeSales1);
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
            $maxCode = $pdo->query("SELECT MAX(kode_invoice) FROM invoices")->fetchColumn();
            if ($maxCode && preg_match('/^INV-(\d+)$/', $maxCode, $matches)) {
                $nextNum = (int)$matches[1] + 1;
            } else {
                $nextNum = 1;
            }
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
               komisi_sales_1_persen, komisi_sales_2_persen, 
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
        $komisi_sales += ($com1 + $com2);

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

    foreach ($rows as $row) {
        $colA = trim((string) ($row['A'] ?? ''));
        $colB = trim((string) ($row['B'] ?? ''));
        $colG = trim((string) ($row['G'] ?? ''));

        // Baris sub-total bulan: A kosong, B kosong, G berisi angka > 0
        if ($colA === '' && $colB === '' && is_numeric($colG) && (float)$colG > 0) {
            $currentBulan++;
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
        $pdo->exec('TRUNCATE TABLE operational_expenses');
        $opCount = seed_operational_expenses_from_workbook($pdo, $excelPath);
        $opCount += seed_bonus_expenses($pdo);
        $outputLogs[] = "4. Sinkronisasi Pengeluaran Operasional: Selesai ($opCount baris diperbarui)";

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
