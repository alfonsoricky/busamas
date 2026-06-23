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
echo "Listing files in Google Drive folder: $folderId\n";

$query = sprintf("'%s' in parents and trashed = false", str_replace("'", "\\'", $folderId));
$params = http_build_query([
    'q' => $query,
    'fields' => 'files(id,name,mimeType)',
    'supportsAllDrives' => 'true',
    'includeItemsFromAllDrives' => 'true',
]);
$url = "https://www.googleapis.com/drive/v3/files?" . $params;

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
$data = json_decode($body, true);
echo "Files:\n";
foreach ($data['files'] ?? [] as $f) {
    echo "- Name: " . $f['name'] . " | ID: " . $f['id'] . " | Mime: " . $f['mimeType'] . "\n";
}
