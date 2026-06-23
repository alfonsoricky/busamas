<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "Testing Google Authentication...\n";
$token = google_service_account_access_token();
if (!$token['ok']) {
    echo "ERROR: Failed to get access token: " . ($token['error'] ?? 'Unknown error') . "\n";
    exit(1);
}
echo "SUCCESS: Got access token.\n";

$folderId = '1VBLNFMQ47oJm4E90w4SP5fyZXjBU4jw6';
echo "Testing Google Drive Folder access: $folderId\n";

$url = "https://www.googleapis.com/drive/v3/files/" . urlencode($folderId) . "?supportsAllDrives=true";
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

echo "Drive HTTP Status Code: $statusCode\n";
echo "Drive Response Body: $body\n";
