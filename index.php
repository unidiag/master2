<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

register_shutdown_function(function () {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
    ];

    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=UTF-8');
    }

    echo "\nFatal PHP error\n";
    echo "Message: " . $error['message'] . "\n";
    echo "File: " . $error['file'] . "\n";
    echo "Line: " . $error['line'] . "\n";
});

require __DIR__ . '/app-index.php';
