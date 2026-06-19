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
