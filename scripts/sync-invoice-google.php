<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/helpers.php';

$kodeInvoice = trim((string) ($argv[1] ?? ''));
$isUpdate = ((string) ($argv[2] ?? '0')) === '1';

if ($kodeInvoice === '') {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Kode invoice kosong.' . PHP_EOL);
    exit(1);
}

$result = sync_invoice_to_google($kodeInvoice, $isUpdate);
$status = empty($result['errors']) ? 'OK' : 'WARNING';

fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $status . ' sync Google invoice ' . $kodeInvoice . ': ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL);

exit(empty($result['errors']) ? 0 : 1);
