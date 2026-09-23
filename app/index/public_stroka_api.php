<?php

declare(strict_types=1);

/** @var PDO $pdo */

$requestPath = parse_url(
    (string) ($_SERVER['REQUEST_URI'] ?? ''),
    PHP_URL_PATH
);

$requestPath = rtrim(
    (string) $requestPath,
    '/'
);

$strokaEndpoint = null;

if ($requestPath === '/stroka/infocanal.txt') {
    $strokaEndpoint = 'infocanal';
} elseif ($requestPath === '/stroka/new.json') {
    $strokaEndpoint = 'json';
}

if ($strokaEndpoint === null) {
    return;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    header('Allow: GET');
    header(
        'Content-Type: text/plain; charset=UTF-8'
    );

    echo 'Method Not Allowed';

    exit;
}

$strokaRepository =
    new StrokaRepository($pdo);


/*
 * ------------------------------------------------------------
 * new.json
 * ------------------------------------------------------------
 */
if ($strokaEndpoint === 'json') {



    $telegramConfig = $config['telegram'] ?? [];

    $telegramEnabled =
        (bool) ($telegramConfig['enabled'] ?? false);

    $telegramChatIds =
        is_array($telegramConfig['chat_ids'] ?? null)
            ? $telegramConfig['chat_ids']
            : [];

    if ($telegramEnabled && $telegramChatIds) {
        $telegramService = new TelegramService(
            (string) ($telegramConfig['bot_token'] ?? '')
        );

        $userAgent = trim(
            (string) (
                $_SERVER['HTTP_USER_AGENT']
                ?? 'unknown'
            )
        );

        $message =
            "📡 <b>Обновление бегущей строки</b>\n"
            . "IP: <b>"
            . htmlspecialchars(
                client_ip(),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . "</b>\n"
            . "User-Agent: "
            . htmlspecialchars(
                $userAgent,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

        $telegramService->sendToMany(
            $telegramChatIds,
            $message
        );
    }



    header(
        'Content-Type: application/json; charset=UTF-8'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate'
    );

    $rows =
        $strokaRepository->activeForPanel();

    /*
     * Считаем количество коммерческих объявлений.
     */
    $commercialCount = 0;

    foreach ($rows as $row) {
        if (
            (int) (
                $row['amount']
                ?? 0
            ) > 0
        ) {
            $commercialCount++;
        }
    }

    $advertisements = [];

    foreach ($rows as $row) {
        $amount = (int) (
            $row['amount']
            ?? 0
        );

        /*
         * Повторяем legacy-логику:
         * если коммерческих объявлений больше двух,
         * бесплатные не выводим.
         */
        if (
            $amount === 0
            && $commercialCount > 2
        ) {
            continue;
        }

        $advertisements[] = [
            'border' => false,

            'text' => (string) (
                $row['text']
                ?? ''
            ),

            'datestart' => (string) (
                $row['datestart']
                ?? ''
            ),

            'dateend' => (string) (
                $row['dateend']
                ?? ''
            ),
        ];
    }

    echo json_encode(
        [
            'adv' => $advertisements,
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
 * ------------------------------------------------------------
 * infocanal.txt
 * ------------------------------------------------------------
 */
if ($strokaEndpoint === 'infocanal') {
    header(
        'Content-Type: text/plain; charset=UTF-8'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate'
    );

    $rows =
        $strokaRepository->activeForInfocanal();

    $output = [];

    foreach ($rows as $row) {
        $text = trim(
            (string) (
                $row['text']
                ?? ''
            )
        );

        if ($text === '') {
            continue;
        }

        /*
         * В legacy использовался explode(" ", ...)
         * и array_chunk(..., 50).
         *
         * Здесь чуть устойчивее обрабатываем
         * несколько пробелов и переводы строк.
         */
        $words = preg_split(
            '/\s+/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (!$words) {
            continue;
        }

        $chunks = array_chunk(
            $words,
            50
        );

        $chunkCount = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $part = implode(
                ' ',
                $chunk
            );

            if ($index > 0) {
                $part =
                    '→ '
                    . $part;
            }

            if ($index < $chunkCount - 1) {
                $part .= ' →';
            }

            /*
             * "Наша строка" в старом infocanal.txt
             * выводилась жёлтым цветом.
             */
            if (
                (int) (
                    $row['mystr']
                    ?? 0
                ) === 1
            ) {
                $part =
                    "<span style='color:#fdfb67 !important;'>"
                    . $part
                    . '</span>';
            }

            $output[] = $part;
        }
    }

    echo implode(
        '|||',
        $output
    );

    exit;
}