<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "Searching Google Drive for recent test files...\n";
$token = google_service_account_access_token();
if (!$token['ok']) {
    echo "ERROR: Failed to get access token.\n";
    exit(1);
}

$folderId = google_drive_config('folder_id');
echo "Folder ID: $folderId\n";

$query = sprintf("'%s' in parents and name contains 'TEST' and trashed = false", $folderId);
$params = http_build_query([
    'q' => $query,
    'fields' => 'files(id,name,mimeType,createdTime)',
    'supportsAllDrives' => 'true',
    'includeItemsFromAllDrives' => 'true',
]);
$response = http_get('https://www.googleapis.com/drive/v3/files?' . $params, [
    'Authorization: Bearer ' . $token['access_token'],
]);

if ($response['ok']) {
    $data = json_decode($response['body'], true);
    $files = $data['files'] ?? [];
    echo "Found " . count($files) . " files matching 'TEST':\n";
    print_r($files);
} else {
    echo "ERROR: Drive API request failed with status: " . $response['status'] . "\n";
    echo $response['body'] . "\n";
}
