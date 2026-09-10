<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';

date_default_timezone_set(
    (string) (
        $config['app']['timezone']
        ?? 'Europe/Minsk'
    )
);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $rememberDays = max(
        1,
        min(
            365+30, // 12 месяцев
            (int) (
                $config['auth']['remember_days']
                ?? 30
            )
        )
    );

    $rememberLifetime = $rememberDays * 86400;

    ini_set(
        'session.gc_maxlifetime',
        (string) $rememberLifetime
    );

    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';

foreach (
    glob(__DIR__ . '/Repositories/*.php') ?: []
    as $repositoryFile
) {
    require_once $repositoryFile;
}

require_once __DIR__ . '/ChannelService.php';
require_once __DIR__ . '/DigitalChannelService.php';
require_once __DIR__ . '/MtsSmsService.php';
require_once __DIR__ . '/ReaderService.php';



$db = new Database($config['database']);
$pdo = $db->pdo();




function client_ip(): string
{
    $realIp = trim(
        (string) ($_SERVER['HTTP_X_REAL_IP'] ?? '')
    );

    if (
        $realIp !== ''
        && filter_var($realIp, FILTER_VALIDATE_IP)
    ) {
        return $realIp;
    }

    $forwardedFor = trim(
        (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')
    );

    if ($forwardedFor !== '') {
        foreach (explode(',', $forwardedFor) as $ip) {
            $ip = trim($ip);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    $remoteAddr = trim(
        (string) ($_SERVER['REMOTE_ADDR'] ?? '')
    );

    return filter_var($remoteAddr, FILTER_VALIDATE_IP)
        ? $remoteAddr
        : '';
}