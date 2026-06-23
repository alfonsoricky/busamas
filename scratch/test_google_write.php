<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "Testing Google Service Account Write Access...\n";
$token = google_service_account_access_token();
if (!$token['ok']) {
    echo "ERROR: Failed to get access token.\n";
    exit(1);
}
echo "SUCCESS: Got access token.\n";

// 1. Test upload file to Google Drive folder
$folderId = '1VBLNFMQ47oJm4E90w4SP5fyZXjBU4jw6';
echo "Uploading test file to folder: $folderId\n";

$fileName = 'TEST_UPLOAD_FILE.txt';
$fileContent = 'Hello World from Busamas Service Account!';
$mimeType = 'text/plain';
$boundary = 'busamas_boundary_' . uniqid();

$metadata = json_encode([
    'name' => $fileName,
    'parents' => [$folderId],
]);

$body  = "--{$boundary}\r\n";
$body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
$body .= $metadata . "\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: {$mimeType}\r\n\r\n";
$body .= $fileContent . "\r\n";
$body .= "--{$boundary}--";

$url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true';
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token['access_token'],
        'Content-Type: multipart/related; boundary=' . $boundary,
        'Content-Length: ' . strlen($body),
    ],
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_CUSTOMREQUEST => 'POST',
]);
$driveResp = curl_exec($curl);
$driveStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

echo "Drive HTTP Status Code: $driveStatus\n";
echo "Drive Response Body: $driveResp\n";

if ($driveStatus === 200) {
    $driveData = json_decode($driveResp, true);
    $fileId = $driveData['id'] ?? null;
    if ($fileId) {
        echo "Successfully uploaded test file! File ID: $fileId\n";
        echo "Now deleting the test file...\n";
        $delUrl = 'https://www.googleapis.com/drive/v3/files/' . urlencode($fileId) . '?supportsAllDrives=true';
        $curl = curl_init($delUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
        ]);
        curl_exec($curl);
        $delStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        echo "Delete HTTP Status Code: $delStatus\n";
    }
}

// 2. Test append row to Google Sheets
$spreadsheetId = '1ZKrj56APSSE5AzwJrAJk_ZCj3gvnKkSTus20_iN_NEg';
$sheetName = 'Penjualan';
$range = urlencode($sheetName . '!A:AJ');
$url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append"
     . '?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS';

echo "Appending row to spreadsheet: $spreadsheetId\n";
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
