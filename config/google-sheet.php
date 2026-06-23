<?php

return [
    'spreadsheet_id' => getenv('GOOGLE_SHEET_ID') ?: '14zDzNoKXHA-8IgZXnxwWNUAw_CvnLDOtEvI7QEtErhw',
    'penjualan_sheet_name' => getenv('GOOGLE_PENJUALAN_SHEET') ?: 'Penjualan',
    'gid' => '0',
    'range' => getenv('GOOGLE_SHEET_RANGE') ?: 'A:Z',
    'access_mode' => getenv('GOOGLE_SHEET_ACCESS_MODE') ?: 'service_account',
    'service_account_path' => getenv('GOOGLE_SERVICE_ACCOUNT_JSON')
        ?: (glob(dirname(__DIR__) . '/storage/*.json')[0] ?? dirname(__DIR__) . '/storage/google-service-account.json'),
];
