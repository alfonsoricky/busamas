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
];

if (! array_key_exists($path, $routes)) {
    http_response_code(404);

    view('pages/404', [
        'title' => 'Halaman Tidak Ditemukan',
    ]);

    exit;
}

view($routes[$path]['view'], [
    'title' => $routes[$path]['title'],
]);
