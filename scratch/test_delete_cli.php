<?php
require dirname(__DIR__) . '/app/helpers.php';

echo "Running run_delete_test_data() via CLI...\n";
$result = run_delete_test_data();

print_r($result);
