<?php

require dirname(__DIR__) . '/app/helpers.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

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
