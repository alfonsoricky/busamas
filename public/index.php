<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require dirname(__DIR__) . '/app/helpers.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = base_path();
$path = $requestPath;

if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $path = substr($requestPath, strlen($basePath)) ?: '/';
}

$path = '/' . trim($path, '/');
$path = $path === '/' ? '/' : rtrim($path, '/');

$routes = [
    '/' => [
        'view' => 'pages/home',
        'title' => 'Dashboard',
        'data' => fn (): array => [
            'dashboardData' => fetch_dashboard_summary($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/debug-errors' => [
        'view' => 'pages/home',
        'title' => 'Debug Errors',
        'data' => function (): array {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
            echo "<h1>Debug PHP Error & Schema Sync</h1>";
            require_once dirname(__DIR__) . '/app/helpers.php';
            echo "<p style='color:green'>app/helpers.php loaded successfully!</p>";
            $pdo = db();
            if ($pdo) {
                echo "<p style='color:green'>Database connected successfully!</p>";
            } else {
                echo "<p style='color:red'>Database connection failed.</p>";
                exit;
            }

            // Self-healing schema sync: Alter tables if missing columns
            echo "<h2>Running schema synchronization...</h2>";
            $fieldsToEnsure = [
                'google_drive_file_id' => 'VARCHAR(200) NULL',
                'file_invoice' => 'VARCHAR(255) NULL'
            ];

            foreach ($fieldsToEnsure as $col => $type) {
                try {
                    $q = $pdo->query("SHOW COLUMNS FROM invoices LIKE '$col'");
                    if (!$q->fetch()) {
                        $pdo->exec("ALTER TABLE invoices ADD COLUMN `$col` $type");
                        echo "<p style='color:green'>Success: Added column `$col` to invoices table.</p>";
                    } else {
                        echo "<p style='color:green'>Column `$col` already exists in invoices table.</p>";
                    }
                } catch (Throwable $e) {
                    echo "<p style='color:red'>Error ensuring column `$col`: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }

            try {
                $q = $pdo->query("SHOW COLUMNS FROM invoice_items LIKE 'file_invoice'");
                if (!$q->fetch()) {
                    $pdo->exec("ALTER TABLE invoice_items ADD COLUMN `file_invoice` VARCHAR(255) NULL");
                    echo "<p style='color:green'>Success: Added column `file_invoice` to invoice_items table.</p>";
                } else {
                    echo "<p style='color:green'>Column `file_invoice` already exists in invoice_items table.</p>";
                }
            } catch (Throwable $e) {
                echo "<p style='color:red'>Error ensuring column `file_invoice` in invoice_items: " . htmlspecialchars($e->getMessage()) . "</p>";
            }

            if (isset($_GET['test_submit'])) {
                try {
                    echo "<h2>Running mock invoice submission test...</h2>";
                    $postData = [
                        'nomor_invoice' => 'INV-DEBUG-TEST-1',
                        'tanggal_invoice' => date('Y-m-d'),
                        'kode_customer' => '',
                        'nama_customer' => 'Debug Customer Name',
                        'no_telepon' => '0899999999',
                        'alamat' => 'Debug Alamat Street 12',
                        'kode_sales_1' => '',
                        'status_pembayaran' => 'Belum Lunas',
                        'items' => [
                            [
                                'kode_barang' => '',
                                'isi' => '10',
                                'jumlah' => '2',
                                'satuan' => 'Btl',
                                'harga' => '25000',
                                'total' => '50000',
                            ]
                        ]
                    ];

                    $cust = $pdo->query("SELECT kode_customer FROM master_customers LIMIT 1")->fetchColumn();
                    $sales = $pdo->query("SELECT kode_sales FROM master_sales LIMIT 1")->fetchColumn();
                    $barang = $pdo->query("SELECT kode_barang FROM master_barang LIMIT 1")->fetchColumn();

                    if ($cust) $postData['kode_customer'] = $cust;
                    if ($sales) $postData['kode_sales_1'] = $sales;
                    if ($barang) $postData['items'][0]['kode_barang'] = $barang;

                    echo "<p>Mock Data: Customer = {$postData['kode_customer']}, Sales = {$postData['kode_sales_1']}, Barang = {$postData['items'][0]['kode_barang']}</p>";

                    $result = save_invoice_form($postData);
                    echo "<p style='color:blue'>save_invoice_form() completed. Output:</p>";
                    echo "<pre>" . print_r($result, true) . "</pre>";

                    if ($result['ok']) {
                        echo "<p>Attempting Google Sync...</p>";
                        $syncResult = sync_invoice_to_google($result['kode_invoice'], false);
                        echo "<p style='color:blue'>sync_invoice_to_google() completed. Output:</p>";
                        echo "<pre>" . print_r($syncResult, true) . "</pre>";

                        // Cleanup created invoice to prevent cluttering db
                        $pdo->prepare("DELETE FROM invoice_items WHERE kode_invoice = ?")->execute([$result['kode_invoice']]);
                        $pdo->prepare("DELETE FROM invoices WHERE kode_invoice = ?")->execute([$result['kode_invoice']]);
                        echo "<p style='color:green'>Cleaned up test invoice record.</p>";
                    }
                } catch (Throwable $e) {
                    echo "<p style='color:red'>Exception caught: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                }
                exit;
            }
            exit;
        }
    ],
    '/about' => [
        'view' => 'pages/about',
        'title' => 'Tentang',
    ],
    '/contact' => [
        'view' => 'pages/contact',
        'title' => 'Kontak',
    ],
    '/sheet' => [
        'view' => 'pages/sheet',
        'title' => 'Google Sheet',
        'data' => fn (): array => [
            'sheet' => fetch_google_sheet_rows(),
        ],
    ],
    '/drive' => [
        'view' => 'pages/drive',
        'title' => 'Google Drive',
        'data' => fn (): array => [
            'drive' => fetch_google_drive_files(),
        ],
    ],
    '/master' => [
        'view' => 'pages/master',
        'title' => 'Data Master',
        'data' => function (): array {
            $tab = $_GET['tab'] ?? 'barang';
            $data = ['tab' => $tab];
            if ($tab === 'customer') {
                $data['masterCustomer'] = fetch_master_customer();
            } elseif ($tab === 'sales') {
                $data['masterSales'] = fetch_master_sales();
            } else {
                $data['tab'] = 'barang';
                $data['masterBarang'] = fetch_master_barang();
            }
            return $data;
        },
    ],
    '/db-maintenance' => [
        'view' => 'pages/database',
        'title' => 'Database',
        'data' => fn (): array => [
            'databaseMaintenance' => fetch_database_maintenance(
                ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? ($_POST['action'] ?? null) : null
            ),
        ],
    ],
    '/operational' => [
        'view' => 'pages/operational',
        'title' => 'Pengeluaran Operasional',
        'data' => fn (): array => [
            'expenses' => fetch_operational_expenses($_GET['month'] ?? '', $_GET['year'] ?? date('Y'), $_GET['status'] ?? '', $_GET['search'] ?? ''),
            'summary' => fetch_operational_summary($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/invoices' => [
        'view' => 'pages/invoices',
        'title' => 'Invoice Mapping',
        'data' => fn (): array => [
            'invoiceMapping' => fetch_invoice_mapping([
                'month' => $_GET['month'] ?? '',
                'year' => $_GET['year'] ?? '',
                'laundry' => $_GET['laundry'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'direction' => $_GET['direction'] ?? '',
            ]),
        ],
    ],
    '/invoice-create' => [
        'view' => 'pages/invoice-create',
        'title' => 'Buat Invoice',
        'data' => function (): array {
            $error = null;
            $googleWarnings = [];
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $isUpdate = !empty($_POST['kode_invoice']);
                $result = save_invoice_form($_POST);
                if ($result['ok']) {
                    // Sync ke Google Drive & Sheets (best-effort, tidak block redirect)
                    $googleSync = sync_invoice_to_google($result['kode_invoice'], $isUpdate);
                    if (!empty($googleSync['errors'])) {
                        // Redirect tetap tapi dengan flash peringatan via session
                        $_SESSION['google_sync_warnings'] = $googleSync['errors'];
                    }
                    header('Location: ' . url('/invoices'));
                    exit;
                }
                $error = $result['message'];
            }
            $invoiceForm = fetch_invoice_form_options($_GET['code'] ?? $_POST['kode_invoice'] ?? '');
            if ($error !== null) {
                $invoiceForm['error'] = $error;
                
                // Preserve POST data in form fields
                $invoiceForm['edit'] = [
                    'mode' => !empty($_POST['kode_invoice']) ? 'update' : 'create',
                    'invoice' => [
                        'kode_invoice' => $_POST['kode_invoice'] ?? '',
                        'nomor_invoice' => $_POST['nomor_invoice'] ?? '',
                        'tanggal_invoice_input' => $_POST['tanggal_invoice'] ?? '',
                        'nomor_surat_jalan' => $_POST['nomor_surat_jalan'] ?? '',
                        'tanggal_surat_jalan_input' => $_POST['tanggal_surat_jalan'] ?? '',
                        'po_number' => $_POST['po_number'] ?? '',
                        'kode_customer' => $_POST['kode_customer'] ?? '',
                        'nama_customer_invoice' => $_POST['nama_customer'] ?? '',
                        'nama_laundry_invoice' => $_POST['nama_customer'] ?? '',
                        'no_telepon' => $_POST['no_telepon'] ?? '',
                        'alamat' => $_POST['alamat'] ?? '',
                        'kode_sales_1' => $_POST['kode_sales_1'] ?? '',
                        'kode_sales_2' => $_POST['kode_sales_2'] ?? '',
                        'status_pembayaran' => $_POST['status_pembayaran'] ?? 'Belum Lunas',
                        'tanggal_pembayaran' => $_POST['tanggal_pembayaran'] ?? '',
                        'jumlah_terbayar_pendapatan' => $_POST['jumlah_terbayar_pendapatan'] ?? '',
                        'discount_persen' => $_POST['discount'] ?? '',
                        'komisi_sales_1_persen' => $_POST['komisi_sales_1_persen'] ?? '',
                        'komisi_sales_2_persen' => $_POST['komisi_sales_2_persen'] ?? '',
                        'komisi_sales_terbayar' => $_POST['komisi_sales_terbayar'] ?? '',
                        'status_pembayaran_komisi_sales' => $_POST['status_pembayaran_sales'] ?? 'Belum TF',
                        'tanggal_transfer_komisi_sales' => $_POST['tanggal_transfer_komisi_sales'] ?? '',
                        'komisi_manager_terbayar' => $_POST['komisi_manager_terbayar'] ?? '',
                        'komisi_manager_utang' => $_POST['komisi_manager_utang'] ?? '',
                        'tanggal_transfer_komisi_manager' => $_POST['tanggal_transfer_manager'] ?? '',
                        'biaya_kirim' => $_POST['biaya_kirim'] ?? '',
                        'biaya_admin_bank' => $_POST['biaya_admin_bank'] ?? '',
                        'total_pembelian_barang' => $_POST['pembelian_barang'] ?? '',
                        'total_utang_pembelian_barang' => $_POST['jumlah_utang_pembelian_barang'] ?? '',
                        'tanggal_transfer_pembelian_barang' => $_POST['tanggal_transfer_pembelian_barang'] ?? '',
                    ],
                    'items' => is_array($_POST['items'] ?? null) ? array_map(static fn($it) => [
                        'kode_barang' => $it['kode_barang'] ?? '',
                        'isi' => $it['isi'] ?? '',
                        'jumlah' => (float)($it['jumlah'] ?? 0),
                        'satuan' => $it['satuan'] ?? '',
                        'harga' => (float)($it['harga'] ?? 0),
                        'total' => (float)($it['total'] ?? 0),
                    ], $_POST['items']) : [],
                ];
            }
            return [
                'invoiceForm' => $invoiceForm,
            ];
        },
    ],
    '/invoice-view' => [
        'view' => 'pages/invoice-view',
        'title' => 'View Invoice',
        'data' => fn (): array => [
            'invoiceDetail' => fetch_invoice_detail($_GET['code'] ?? ''),
        ],
    ],
    '/laporan' => [
        'view' => 'pages/laporan',
        'title' => 'Laporan Utama',
    ],
    '/laporan/penjualan' => [
        'view' => 'pages/laporan-penjualan',
        'title' => 'Laporan Penjualan',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_penjualan($_GET['group'] ?? 'invoice', $_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/profit-loss' => [
        'view' => 'pages/laporan-profit-loss',
        'title' => 'Laporan Profit & Loss',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_profit_loss($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/hutang' => [
        'view' => 'pages/laporan-hutang',
        'title' => 'Laporan Hutang Dagang',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_hutang($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/piutang' => [
        'view' => 'pages/laporan-piutang',
        'title' => 'Laporan Piutang Dagang',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_piutang($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/profit' => [
        'view' => 'pages/laporan-profit',
        'title' => 'Laporan Analisis Profit',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_profit($_GET['group'] ?? 'produk', $_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/komisi' => [
        'view' => 'pages/laporan-komisi',
        'title' => 'Laporan Komisi Sales, Manager & Admin',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_komisi($_GET['month'] ?? '', $_GET['year'] ?? date('Y'), $_GET['status'] ?? ''),
        ],
    ],
    '/invoice-delete' => [
        'view'  => 'pages/invoice-delete-result',
        'title' => 'Hapus Invoice',
        'data'  => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/invoices'));
                exit;
            }
            $kodeInvoice = trim($_POST['kode_invoice'] ?? '');
            if ($kodeInvoice === '') {
                header('Location: ' . url('/invoices'));
                exit;
            }
            $result = delete_invoice($kodeInvoice);
            if ($result['ok']) {
                header('Location: ' . url('/invoices'));
                exit;
            }
            return ['deleteResult' => $result];
        },
    ],
];

if (! array_key_exists($path, $routes)) {
    http_response_code(404);

    view('pages/404', [
        'title' => 'Halaman Tidak Ditemukan',
    ]);

    exit;
}

$route = $routes[$path];
$routeData = isset($route['data']) ? $route['data']() : [];

view($route['view'], [
    'title' => $route['title'],
    ...$routeData,
]);
