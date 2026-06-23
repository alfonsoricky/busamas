<?php
require dirname(__DIR__) . '/app/helpers.php';
$rows = read_xlsx_rows_for_invoice_view(dirname(__DIR__) . '/scratch/test_output.xlsx');
foreach ($rows as $k => $v) {
    $v = array_filter($v);
    if (!empty($v)) {
        echo 'Row ' . ($k + 1) . ': ' . json_encode($v) . PHP_EOL;
    }
}
