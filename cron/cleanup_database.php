#!/usr/bin/php
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

    /*
     * OPTIMIZE имеет смысл только если
     * действительно были удалены записи.
     */
    if ($deleted > 1000) {
        echo sprintf(
            "[%s] master_database optimize: started\n",
            date('Y-m-d H:i:s')
        );

        $pdo->exec(
            'OPTIMIZE TABLE master_database'
        );

        echo sprintf(
            "[%s] master_database optimize: completed\n",
            date('Y-m-d H:i:s')
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "[%s] master_database maintenance failed: %s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage()
        )
    );

    exit(1);
}