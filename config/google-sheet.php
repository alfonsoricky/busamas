<?php

require_once __DIR__ . '/env.php';

return [
    'spreadsheet_id' => busamas_env('GOOGLE_SHEET_ID', '14zDzNoKXHA-8IgZXnxwWNUAw_CvnLDOtEvI7QEtErhw'),
    'penjualan_sheet_name' => busamas_env('GOOGLE_PENJUALAN_SHEET', 'Penjualan'),
    'gid' => '0',
    'range' => busamas_env('GOOGLE_SHEET_RANGE', 'A:Z'),
    'access_mode' => busamas_env('GOOGLE_SHEET_ACCESS_MODE', 'service_account'),
    'service_account_path' => busamas_env('GOOGLE_SERVICE_ACCOUNT_JSON')
        ?: (glob(dirname(__DIR__) . '/storage/*.json')[0] ?? dirname(__DIR__) . '/storage/google-service-account.json'),
];
