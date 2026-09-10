<?php

declare(strict_types=1);

$configFile = __DIR__ . '/config.local.php';

if (!is_file($configFile)) {
    throw new RuntimeException(
        'config.local.php not found'
    );
}

return require $configFile;