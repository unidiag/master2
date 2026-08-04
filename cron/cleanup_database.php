<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$repository = new SubscriberRepository($pdo);

try {
    $deleted = $repository->cleanupOlderThanOneYear();

    echo sprintf(
        "[%s] master_database cleanup: deleted %d rows\n",
        date('Y-m-d H:i:s'),
        $deleted
    );
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "[%s] master_database cleanup failed: %s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage()
        )
    );

    exit(1);
}