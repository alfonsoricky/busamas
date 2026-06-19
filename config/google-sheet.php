<?php

return [
    'spreadsheet_id' => '1ZKrj56APSSE5AzwJrAJk_ZCj3gvnKkSTus20_iN_NEg',
    'gid' => '0',
    'range' => getenv('GOOGLE_SHEET_RANGE') ?: 'A:Z',
    'access_mode' => getenv('GOOGLE_SHEET_ACCESS_MODE') ?: 'service_account',
    'service_account_path' => getenv('GOOGLE_SERVICE_ACCOUNT_JSON')
        ?: dirname(__DIR__) . '/storage/google-service-account.json',
];
