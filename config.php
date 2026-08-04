<?php

declare(strict_types=1);

$configFile = __DIR__ . '/config.local.php';
if (is_file($configFile)) {
    return require $configFile;
}

return require __DIR__ . '/config.example.php';
