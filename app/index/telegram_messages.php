<?php

declare(strict_types=1);

/*
 * Формирование Telegram-сообщений для заявок и подключений.
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */

function ticket_telegram_message(
    array $ticket,
    string $action,
    string $master,
    string $result,
    string $cost = ''
): string {
    $isWithdrawn = $action === 'withdraw';

    $abonent = trim(
        (string) ($ticket['abonent'] ?? '')
    );

    if ($abonent === '') {
        $abonent = trim(
            (string) ($ticket['abonent_ajax'] ?? '')
        );
    }

    $address = trim(
        (string) ($ticket['address'] ?? '')
    );

    if ($address === '') {
        $address = trim(
            (string) ($ticket['address_ajax'] ?? '')
        );
    }

    $id = telegram_html(
        $ticket['id'] ?? ''
    );

    if ($isWithdrawn) {
        $message =
            '⛔ <b>Заявка № '
            . $id
            . ' снята</b>';
    } else {
        $message =
            '✅ <b>Заявка № '
            . $id
            . ' выполнена</b>';
    }

    if ($abonent !== '') {
        $message .=
            "\n"
            . 'ФИО: '
            . telegram_html($abonent);
    }

    if ($address !== '') {
        $message .=
            "\n"
            . 'Адрес: '
            . telegram_html($address);
    }

    if ($result !== '') {
        $message .=
            "\n"
            . '<b>Результат:</b> '
            . telegram_html($result);
    }

    return $message;
}






function connection_telegram_message(
    array $connection,
    string $action,
    string $master,
    string $result
): string {
    $isWithdrawn = $action === 'withdraw';

    $abonent = trim(
        (string) ($connection['abonent'] ?? '')
    );

    $address = trim(
        (string) ($connection['address'] ?? '')
    );

    $id = telegram_html(
        (string) ($connection['id'] ?? '')
    );

    if ($isWithdrawn) {
        $message =
            '⛔ <b>Подключение № '
            . $id
            . ' снято</b>';
    } else {
        $message =
            '✅ <b>Подключение № '
            . $id
            . ' выполнено</b>';
    }

    if ($abonent !== '') {
        $message .=
            "\n"
            . 'ФИО: '
            . telegram_html($abonent);
    }

    if ($address !== '') {
        $message .=
            "\n"
            . 'Адрес: '
            . telegram_html($address);
    }

    if ($result !== '') {
        $message .=
            "\n"
            . '<b>Результат:</b> '
            . telegram_html($result);
    }

    return $message;
}
