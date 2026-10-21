#!/usr/bin/php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/TelegramService.php';

$config = require dirname(__DIR__) . '/config.php';

/** @var \PDO $pdo */
/** @var array $config */

$BASEDIR = '/var/www/master2.trianda.by';


// -----------------------------------------------------------------------------
// Авторестарт OSCam по ошибкам в логах.
// Проверяем не чаще одного раза в 15 секунд.
// -----------------------------------------------------------------------------

$checkInterval = 15;

// Последние строки лога, которые анализируем.
$logLines = 30;

// Сколько ошибок в последних строках считаем критическим количеством.
$errorLimit = 5;

// После автоматического reboot не ребутаем этот reader повторно
// минимум указанное количество секунд.
$rebootCooldown = 300;

// Telegram ID администратора.
$telegramAdminChatId = '298461914';

// Сигналы ошибок.
$errorSignals = [
    'not',
    'timeout',
    'rejected',
    'fake',
    'error',
];


// -----------------------------------------------------------------------------
// Ограничиваем выполнение этой части cron1sec.php разом в 15 секунд.
//
// main.go запускает cron1sec.php каждую секунду, поэтому используем state-файл.
// flock одновременно защищает от параллельного запуска.
// -----------------------------------------------------------------------------

$stateDir = $BASEDIR . '/runtime/cron';

if (!is_dir($stateDir)) {
    @mkdir(
        $stateDir,
        0775,
        true
    );
}

$checkStateFile =
    $stateDir
    . '/readers-autoreload-check.state';

$stateHandle = @fopen(
    $checkStateFile,
    'c+'
);

if ($stateHandle === false) {
    exit;
}

if (!flock(
    $stateHandle,
    LOCK_EX | LOCK_NB
)) {
    fclose($stateHandle);
    exit;
}

rewind($stateHandle);

$lastCheck = (int) trim(
    (string) stream_get_contents(
        $stateHandle
    )
);

$now = time();

if (
    $lastCheck > 0
    && ($now - $lastCheck) < $checkInterval
) {
    flock(
        $stateHandle,
        LOCK_UN
    );

    fclose($stateHandle);
    exit;
}

ftruncate(
    $stateHandle,
    0
);

rewind($stateHandle);

fwrite(
    $stateHandle,
    (string) $now
);

fflush($stateHandle);


// -----------------------------------------------------------------------------
// ReaderService
// -----------------------------------------------------------------------------

$readerService = new ReaderService(
    $pdo,
    $BASEDIR . '/runtime/readers'
);


// -----------------------------------------------------------------------------
// TelegramService
// -----------------------------------------------------------------------------

$telegram = null;

if (
    ($config['telegram']['enabled'] ?? false) === true
    && trim(
        (string) (
            $config['telegram']['bot_token']
            ?? ''
        )
    ) !== ''
) {
    $telegram = new TelegramService(
        (string) $config['telegram']['bot_token']
    );
}


// -----------------------------------------------------------------------------
// Берём только включённые readers, для которых разрешён autoreload.
// -----------------------------------------------------------------------------

$stmt = $pdo->query(
    '
    SELECT
        reader,
        name
    FROM master_readers
    WHERE enabled = 1
      AND autoreload = 1
    ORDER BY reader
    '
);

$readers = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


// -----------------------------------------------------------------------------
// Проверяем readers.
// -----------------------------------------------------------------------------

foreach ($readers as $row) {
    $reader = (int) (
        $row['reader'] ?? 0
    );

    if ($reader <= 0) {
        continue;
    }

    $name = trim(
        (string) (
            $row['name'] ?? ''
        )
    );

    $logPath =
        $readerService->logPath(
            $reader
        );

    if (
        !is_file($logPath)
        || !is_readable($logPath)
    ) {
        continue;
    }


    // -------------------------------------------------------------------------
    // Защита от бесконечных reboot.
    // -------------------------------------------------------------------------

    $rebootStateFile =
        $stateDir
        . '/reader-'
        . $reader
        . '-autoreload.state';

    $lastReboot = 0;

    if (is_file($rebootStateFile)) {
        $lastReboot = (int) trim(
            (string) @file_get_contents(
                $rebootStateFile
            )
        );
    }

    if (
        $lastReboot > 0
        && ($now - $lastReboot) < $rebootCooldown
    ) {
        continue;
    }


    // -------------------------------------------------------------------------
    // Получаем последние строки лога.
    //
    // Не используем ReaderService::log(), потому что для cron здесь достаточно
    // прочитать только последние строки через tail.
    // -------------------------------------------------------------------------

    $lines = [];
    $exitCode = 0;

    exec(
        '/usr/bin/tail -n '
        . $logLines
        . ' -- '
        . escapeshellarg($logPath),
        $lines,
        $exitCode
    );

    if ($exitCode !== 0) {
        continue;
    }

    if ($lines === []) {
        continue;
    }


    // -------------------------------------------------------------------------
    // Считаем строки с сигналами ошибок.
    //
    // Одна строка считается одной ошибкой, даже если в ней одновременно
    // присутствует несколько сигналов.
    // -------------------------------------------------------------------------

    $errorCount = 0;
    $matchedSignals = [];

    foreach ($lines as $line) {
        foreach ($errorSignals as $signal) {
            if (
                stripos(
                    $line,
                    $signal
                ) !== false
            ) {
                $errorCount++;

                $matchedSignals[
                    $signal
                ] = true;

                break;
            }
        }
    }


    // -------------------------------------------------------------------------
    // Ошибок недостаточно.
    // -------------------------------------------------------------------------

    if ($errorCount < $errorLimit) {
        continue;
    }


    // -------------------------------------------------------------------------
    // Сначала записываем время попытки reboot.
    //
    // Это не даст cron пытаться перезапустить один и тот же OSCam
    // каждые 15 секунд.
    // -------------------------------------------------------------------------

    @file_put_contents(
        $rebootStateFile,
        (string) $now,
        LOCK_EX
    );


    // -------------------------------------------------------------------------
    // Перезапускаем OSCam.
    // -------------------------------------------------------------------------

    $result = $readerService->reboot(
        $reader
    );


    // -------------------------------------------------------------------------
    // При успешном reboot отправляем сообщение администратору в Telegram.
    // -------------------------------------------------------------------------

    if (
        $result
        && $telegram instanceof TelegramService
    ) {
        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeSignals = htmlspecialchars(
            implode(
                ', ',
                array_keys(
                    $matchedSignals
                )
            ),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $message = sprintf(
            "⚠️ <b>Авторебут OSCam</b>\n"
            . "\n"
            . "Ридер: <b>%d — %s</b>\n"
            . "Ошибок в последних %d строках: <b>%d</b>\n"
            . "Сигналы: <b>%s</b>",
            $reader,
            $safeName,
            $logLines,
            $errorCount,
            $safeSignals
        );

        try {
            $telegramResult = $telegram->sendMessage(
                $telegramAdminChatId,
                $message
            );

            if (!$telegramResult) {
                error_log(
                    sprintf(
                        'Telegram autoreload notification failed for reader %d',
                        $reader
                    )
                );
            }
        } catch (Throwable $e) {
            error_log(
                'Telegram autoreload notification error: '
                . $e->getMessage()
            );
        }
    }


    // -------------------------------------------------------------------------
    // Вывод для диагностики при ручном запуске.
    // -------------------------------------------------------------------------

    echo sprintf(
        "[%s] reader=%d name=%s errors=%d signals=%s reboot=%s\n",
        date('Y-m-d H:i:s'),
        $reader,
        $name,
        $errorCount,
        implode(
            ',',
            array_keys(
                $matchedSignals
            )
        ),
        $result
            ? 'OK'
            : 'FAIL'
    );
}


// -----------------------------------------------------------------------------
// Освобождаем lock.
// -----------------------------------------------------------------------------

flock(
    $stateHandle,
    LOCK_UN
);

fclose(
    $stateHandle
);