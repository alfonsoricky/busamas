<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug PHP Error</h1>";

try {
    require_once __DIR__ . '/../app/helpers.php';
    echo "<p class='color:green'>app/helpers.php loaded successfully!</p>";
} catch (Throwable $e) {
    echo "<p class='color:red'>Failed loading app/helpers.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

try {
    // Test database connection
    $pdo = db();
    if ($pdo) {
        echo "<p class='color:green'>Database connected successfully!</p>";
    } else {
        echo "<p class='color:red'>Database connection failed.</p>";
    }
} catch (Throwable $e) {
    echo "<p class='color:red'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
