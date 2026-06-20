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
    '/master-barang' => [
        'view' => 'pages/master-barang',
        'title' => 'Master Barang',
        'data' => fn (): array => [
            'masterBarang' => fetch_master_barang(),
        ],
    ],
    '/master-customer' => [
        'view' => 'pages/master-customer',
        'title' => 'Master Customer',
        'data' => fn (): array => [
            'masterCustomer' => fetch_master_customer(),
        ],
    ],
    '/master-sales' => [
        'view' => 'pages/master-sales',
        'title' => 'Master Sales',
        'data' => fn (): array => [
            'masterSales' => fetch_master_sales(),
        ],
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
        'data' => fn (): array => [
            'invoiceForm' => fetch_invoice_form_options($_GET['code'] ?? ''),
        ],
    ],
    '/invoice-view' => [
        'view' => 'pages/invoice-view',
        'title' => 'View Invoice',
        'data' => fn (): array => [
            'invoiceDetail' => fetch_invoice_detail($_GET['code'] ?? ''),
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
