<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "Testing Google Authentication and Sheet Access...\n";
$token = google_service_account_access_token();
if (!$token['ok']) {
    echo "ERROR: Failed to get access token.\n";
    exit(1);
}
echo "SUCCESS: Got access token.\n";

$spreadsheetId = '1ZKrj56APSSE5AzwJrAJk_ZCj3gvnKkSTus20_iN_NEg';
echo "Spreadsheet ID: $spreadsheetId\n";

$url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}";
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token['access_token']
    ],
]);
$body = curl_exec($curl);
$statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

echo "HTTP Status Code: $statusCode\n";
if ($statusCode === 200) {
    $data = json_decode($body, true);
    echo "Sheets in this spreadsheet:\n";
    foreach ($data['sheets'] ?? [] as $s) {
        echo "- Title: " . $s['properties']['title'] . " | ID: " . $s['properties']['sheetId'] . "\n";
    }
} else {
    echo "Response Body: $body\n";
}
