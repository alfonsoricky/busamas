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
