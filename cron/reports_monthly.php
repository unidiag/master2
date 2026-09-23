#!/usr/bin/php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/bootstrap.php';

/** @var PDO $pdo */
/** @var array $config */

$distributors =
    isset($config['digital']['distributors'])
    && is_array(
        $config['digital']['distributors']
    )
        ? $config['digital']['distributors']
        : [];

$dateEnd = (
    new DateTimeImmutable(
        'last day of previous month'
    )
)->format('Y-m-d');

$reportService =
    new ReportService(
        $pdo,
        $config,
        dirname(__DIR__)
    );

foreach ($distributors as $distributor) {
    if (!is_array($distributor)) {
        continue;
    }

    $name = trim(
        (string) (
            $distributor['name']
            ?? ''
        )
    );

    $mail = trim(
        (string) (
            $distributor['mail']
            ?? ''
        )
    );

    $reportTpl = trim(
        (string) (
            $distributor['report_tpl']
            ?? ''
        )
    );

    /*
     * Отправляем только тем дистрибьюторам,
     * для которых настроен отчёт и e-mail.
     */
    if (
        $name === ''
        || $mail === ''
        || $reportTpl === ''
    ) {
        continue;
    }

    /*
     * Защита от повторной отправки.
     */
    if (
        $reportService->wasSent(
            $name,
            $dateEnd
        )
    ) {
        echo $name
            . ': already sent'
            . PHP_EOL;

        continue;
    }

    try {
        $result =
            $reportService->send(
                $distributor,
                $dateEnd,
                'auto',
                '',
                'cron/reports_monthly.php'
            );

        if (!empty($result['ok'])) {
            echo $name
                . ': sent to '
                . $result['email']
                . PHP_EOL;
        } else {
            echo $name
                . ': ERROR: '
                . (
                    $result['error']
                    ?? 'unknown error'
                )
                . PHP_EOL;
        }
    } catch (Throwable $exception) {
        echo $name
            . ': EXCEPTION: '
            . $exception->getMessage()
            . PHP_EOL;

        error_log(
            'Monthly report '
            . $name
            . ': '
            . $exception->getMessage()
        );
    }
}