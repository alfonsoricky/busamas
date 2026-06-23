<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "Testing Google Service Account Write Access to Production Sheet...\n";
$token = google_service_account_access_token();
if (!$token['ok']) {
    echo "ERROR: Failed to get access token.\n";
    exit(1);
}
echo "SUCCESS: Got access token.\n";

$spreadsheetId = '14zDzNoKXHA-8IgZXnxwWNUAw_CvnLDOtEvI7QEtErhw';
$sheetName = 'Penjualan';
$range = urlencode($sheetName . '!A:AJ');
$url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append"
     . '?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS';

echo "Appending row to production spreadsheet: $spreadsheetId\n";
$row = [
    '999/BM-INV/TEST/2026', '23 Juni 2026', 'SJ-99999', '23 Juni 2026',
    'TEST LAUNDRY AGENT', 'Jl. Test No. 99', '08123456789', '',
    '1.000.000', '10', '100.000', '900.000', 'Belum Lunas'
];
$payload = json_encode(['values' => [$row]]);

$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token['access_token'],
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_POST => true,
]);
$sheetResp = curl_exec($curl);
$sheetStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

echo "Sheet HTTP Status Code: $sheetStatus\n";
echo "Sheet Response Body: $sheetResp\n";
