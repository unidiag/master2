<?php

declare(strict_types=1);

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


require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/TelegramService.php';
require __DIR__ . '/app/index/public_qr_api.php';
require __DIR__ . '/app/index/public_stroka_api.php';
require __DIR__ . '/app/index/context.php';
require __DIR__ . '/app/index/ajax.php';
require __DIR__ . '/app/index/telegram_messages.php';
require __DIR__ . '/app/index/stat_actions.php';
require __DIR__ . '/app/index/main_actions.php';
require __DIR__ . '/app/index/stroka_actions.php';
require __DIR__ . '/app/index/modules.php';

$flashes = consume_flashes();

require __DIR__ . '/app/view.php';

