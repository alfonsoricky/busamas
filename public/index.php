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
