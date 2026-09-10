<?php

declare(strict_types=1);

/** @var string $module */
/** @var StrokaRepository $strokaRepository */

if (
    $module !== 'stroka'
    || $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    return;
}

verify_csrf();

$postAction = post_string(
    'action',
    30
);




$telegramConfig = $config['telegram'] ?? [];

$telegramEnabled =
    (bool) ($telegramConfig['enabled'] ?? false);

$telegramChatIds =
    is_array($telegramConfig['chat_ids'] ?? null)
        ? $telegramConfig['chat_ids']
        : [];

$telegramService = new TelegramService(
    (string) ($telegramConfig['bot_token'] ?? '')
);




/*
 * Перезапуск инфоканала.
 */
if ($postAction === 'stroka_reboot') {


    /*
    * Уведомляем всех о самой попытке reboot.
    */
    if ($telegramEnabled) {
        $user = current_user();
        $ip = client_ip();

        $message =
            "⚠️ <b>Перезапуск инфоканала</b>\n"
            . "\n"
            . "Пользователь: <b>"
            . htmlspecialchars(
                $user,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . "</b>\n"
            . "IP: <b>"
            . htmlspecialchars(
                $ip,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . "</b>\n"
            . "Время: "
            . date('d.m.Y H:i:s');

        $telegramService->sendToMany(
            $telegramChatIds,
            $message
        );
    }


    try {
        $rebootUrl = trim(
            (string) (
                $config['stroka']['reboot_url']
                ?? ''
            )
        );

        if ($rebootUrl === '') {
            throw new RuntimeException(
                'Не задан stroka.reboot_url в конфигурации.'
            );
        }

        $rebootTimeout = max(
            1,
            (int) (
                $config['stroka']['reboot_timeout']
                ?? 5
            )
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $rebootTimeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(
            $rebootUrl,
            false,
            $context
        );

        if (trim((string) $response) !== 'Browser and VLC killed') {
            throw new RuntimeException(
                'Сервер инфоканала не подтвердил перезапуск. ' . $response
            );
        }

        flash(
            'success',
            'Сигнал на перезапуск инфоканала успешно отправлен.'
        );
    } catch (Throwable $exception) {
        flash(
            'error',
            'Не удалось перезапустить инфоканал: '
            . $exception->getMessage()
        );
    }

    redirect([
        'module' => 'stroka',
    ]);
}









$id = positive_int(
    $_POST['id'] ?? 0,
    0
);

/*
 * Удаление.
 */
if (
    $postAction === 'stroka_delete'
    && $id > 0
) {
    try {
        $strokaRepository->delete($id);

        flash(
            'success',
            'Строка удалена.'
        );
    } catch (Throwable $exception) {
        flash(
            'error',
            'Не удалось удалить строку: '
            . $exception->getMessage()
        );
    }

    redirect([
        'module' => 'stroka',
    ]);
}

/*
 * Восстановление.
 */
if (
    $postAction === 'stroka_restore'
    && $id > 0
) {
    try {
        $strokaRepository->restore($id);

        flash(
            'success',
            'Строка восстановлена.'
        );
    } catch (Throwable $exception) {
        flash(
            'error',
            'Не удалось восстановить строку: '
            . $exception->getMessage()
        );
    }

    redirect([
        'module' => 'stroka',
        'stroka_status' => 'deleted',
    ]);
}

/*
 * Создание или редактирование.
 */
if (
    !in_array(
        $postAction,
        [
            'stroka_create',
            'stroka_update',
        ],
        true
    )
) {
    return;
}

$name = post_string(
    'name',
    250
);

$address = post_string(
    'address',
    250
);

$phone = post_string(
    'phone',
    50
);

$text = post_string(
    'text',
    10000
);

$amount = post_string(
    'amount',
    250
);

$date = post_string(
    'date',
    25
);

$dateStartRaw = post_string(
    'datestart',
    20
);

$dateEndRaw = post_string(
    'dateend',
    20
);

if ($text === '') {
    flash(
        'error',
        'Введите текст строки.'
    );

    redirect([
        'module' => 'stroka',
    ]);
}

$dateStart = strtotime(
    $dateStartRaw . ' 00:00:00'
);

$dateEnd = strtotime(
    $dateEndRaw . ' 23:59:59'
);

if (
    $dateStart === false
    || $dateEnd === false
) {
    flash(
        'error',
        'Укажите период показа строки.'
    );

    redirect([
        'module' => 'stroka',
    ]);
}

if ($dateEnd < $dateStart) {
    flash(
        'error',
        'Дата окончания не может быть раньше даты начала.'
    );

    redirect([
        'module' => 'stroka',
    ]);
}

/*
 * Дата заявления.
 *
 * В форме используем YYYY-MM-DD,
 * а в старой таблице сохраняем dd.mm.YYYY.
 */
$statementTimestamp = strtotime($date);

$statementDate = $statementTimestamp !== false
    ? date('d.m.Y', $statementTimestamp)
    : date('d.m.Y');

$shPan = isset($_POST['sh_pan'])
    ? 1
    : 0;

/*
 * Старое ограничение панели:
 * строка более 500 символов на панель не идёт.
 */
if (mb_strlen($text) > 500) {
    $shPan = 0;
}

$data = [
    'name' => $name,
    'address' => $address,
    'phone' => $phone,
    'text' => $text,
    'datestart' => (string) $dateStart,
    'dateend' => (string) $dateEnd,
    'amount' => $amount,
    'date' => $statementDate,
    'whoadd' => current_user(),

    'beznal' =>
        isset($_POST['beznal'])
            ? 1
            : 0,

    'mystr' =>
        isset($_POST['mystr'])
            ? 1
            : 0,

    'sh_tv' =>
        isset($_POST['sh_tv'])
            ? 1
            : 0,

    'sh_int' =>
        isset($_POST['sh_int'])
            ? 1
            : 0,

    'sh_pan' => $shPan,

    'telegram' =>
        isset($_POST['telegram'])
            ? 1
            : 0,
];

try {
    if ($postAction === 'stroka_create') {
        $strokaRepository->create($data);

        flash(
            'success',
            'Строка добавлена.'
        );
    } else {
        if ($id <= 0) {
            throw new RuntimeException(
                'Некорректный идентификатор строки.'
            );
        }

        $strokaRepository->update(
            $id,
            $data
        );

        flash(
            'success',
            'Строка сохранена.'
        );
    }
} catch (Throwable $exception) {
    flash(
        'error',
        'Не удалось сохранить строку: '
        . $exception->getMessage()
    );
}

redirect([
    'module' => 'stroka',
]);