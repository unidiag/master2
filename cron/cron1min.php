#!/usr/bin/php
<?php


declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);

    exit('CLI only');
}

require dirname(__DIR__) . '/app/bootstrap.php';
$config = require dirname(__DIR__) . '/config.php';

/** @var PDO $pdo */
/** @var array $config */


$BASEDIR = "/var/www/master2.trianda.by";


// обновляем картинку прогноза погоды каждый час..
if (date("i")=="18"){
    exec("/usr/bin/wkhtmltoimage --width 920 https://master2.trianda.by/meteo.php {$BASEDIR}/assets/meteo.png");
}


// раз в сутки подчищаем базу данных
if (date("Hi") == "0003"){
    exec($BASEDIR . "/cron/cleanup_database.php");
}

















/*
 * Отправка объявлений в Telegram в 09:30.
 */
if (date('Hi') === '0930') {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            text
        FROM trianda_stroka
        WHERE datestart < :now
        AND telegram = 0
        AND sh_int = 1
        ORDER BY id'
    );

    $stmt->execute([
        'now' => time(),
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($rows);

    if ($total === 0) {
        exit;
    }

    foreach ($rows as $index => $row) {
        $message = (string) $row['text'];

        if ($index === $total - 1) {
            $message .=
                "\n\n---\n"
                . 'Свои объявления, вопросы и новости '
                . 'присылайте боту @novolukoml_bot';
        }

        /*
        * Здесь вызываем TelegramService.
        * telegram = 1 ставим ТОЛЬКО после успешной отправки.
        */
        if (grp2tlg($message, $config['telegram']['bot_group'])) {
            $update = $pdo->prepare(
                'UPDATE trianda_stroka
                SET telegram = 1
                WHERE id = :id'
            );

            $update->execute([
                'id' => (int) $row['id'],
            ]);
        }
    }
}




// отправка сообщения в группу
function grp2tlg(string $msg, string $token): bool
{
    exec(
        'curl -sS --fail -X POST '
        . escapeshellarg(
            'https://api.telegram.org/bot' . $token
            . '/sendMessage'
        ) 
        . ' -d '
        . escapeshellarg(
            'chat_id=-1001151528681'
        )
        . ' -d '
        . escapeshellarg(
            'text=' . $msg
        ),
        $output,
        $exitCode
    );

    return $exitCode === 0;
}

?>