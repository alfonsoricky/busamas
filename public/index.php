<?php

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
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $result = save_invoice_form($_POST);
                if ($result['ok']) {
                    header('Location: ' . url('/invoices'));
                    exit;
                }
                $error = $result['message'];
            }
            $invoiceForm = fetch_invoice_form_options($_GET['code'] ?? $_POST['kode_invoice'] ?? '');
            if ($error !== null) {
                $invoiceForm['error'] = $error;
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
