<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/helpers.php';

$expenseId = (int) ($argv[1] ?? 0);
$action = trim((string) ($argv[2] ?? '')); // 'save' atau 'delete'

if ($expenseId <= 0 || $action === '') {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Parameter tidak valid.' . PHP_EOL);
    exit(1);
}

if ($action === 'save') {
    $ok = sheets_sync_operational_save($expenseId);
} elseif ($action === 'delete') {
    $ok = sheets_sync_operational_delete($expenseId);
} else {
    $ok = false;
}

$status = $ok ? 'OK' : 'ERROR';
fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $status . ' sync Google operational ID ' . $expenseId . ' (Action: ' . $action . ')' . PHP_EOL);

exit($ok ? 0 : 1);
