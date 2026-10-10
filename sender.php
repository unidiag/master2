<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/TelegramService.php';


function response_json(
    int $status,
    array $data
): never {
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}


function is_bogon_ip(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE
        | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}


/*
 * Преобразование HTML в обычный текст
 * для Telegram и SMS.
 */
function html_to_text(string $html): string
{
    /*
     * Переносы строк.
     */
    $html = preg_replace(
        '~<\s*br\s*/?\s*>~i',
        "\n",
        $html
    );

    /*
     * Блочные элементы тоже заканчиваем переносом.
     */
    $html = preg_replace(
        '~</\s*(p|div|li|h[1-6]|tr)\s*>~i',
        "\n",
        $html
    );

    /*
     * Для li добавляем маркер.
     */
    $html = preg_replace(
        '~<\s*li(?:\s[^>]*)?>~i',
        '• ',
        $html
    );

    /*
     * Удаляем остальные HTML-теги.
     */
    $text = strip_tags($html);

    /*
     * &nbsp;, &amp;, &quot; и т.д.
     */
    $text = html_entity_decode(
        $text,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    /*
     * NBSP -> обычный пробел.
     */
    $text = str_replace(
        "\u{00A0}",
        ' ',
        $text
    );

    /*
     * CRLF / CR -> LF.
     */
    $text = str_replace(
        ["\r\n", "\r"],
        "\n",
        $text
    );

    /*
     * Убираем пробелы перед переносами.
     */
    $text = preg_replace(
        "/[ \t]+\n/u",
        "\n",
        $text
    );

    /*
     * Несколько пробелов подряд.
     */
    $text = preg_replace(
        "/[ \t]{2,}/u",
        ' ',
        $text
    );

    /*
     * Не более двух переносов подряд.
     */
    $text = preg_replace(
        "/\n{3,}/u",
        "\n\n",
        $text
    );

    return trim($text);
}



/*
 * Режим отправки.
 *
 * GET ?mode=all   - все каналы (по умолчанию)
 * GET ?mode=sms   - только SMS
 * GET ?mode=tg    - только Telegram
 * GET ?mode=email - только Email
 */
$mode = $_GET['mode'] ?? 'all';

if (
    !is_string($mode)
    || !in_array($mode, ['all', 'sms', 'tg', 'email'], true)
) {
    response_json(
        400,
        [
            'ok' => false,
            'error' => 'Invalid mode',
            'allowed' => ['all', 'sms', 'tg', 'email'],
        ]
    );
}


/*
 * Не более 2 сообщений за последние 60 секунд.
 *
 * flock() не позволяет нескольким PHP-FPM процессам
 * одновременно пройти проверку.
 */
function rate_limit_check(): bool
{
    $file = sys_get_temp_dir()
        . '/master2_sender_rate_limit.json';

    $fp = fopen($file, 'c+');

    if ($fp === false) {
        return false;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    rewind($fp);

    $raw = stream_get_contents($fp);

    $timestamps = json_decode(
        $raw ?: '[]',
        true
    );

    if (!is_array($timestamps)) {
        $timestamps = [];
    }

    $now = time();

    /*
     * Оставляем только отправки
     * за последние 60 секунд.
     */
    $timestamps = array_values(
        array_filter(
            $timestamps,
            static fn ($timestamp): bool =>
                is_numeric($timestamp)
                && (int) $timestamp > ($now - 60)
        )
    );

    if (count($timestamps) >= 2) {
        flock($fp, LOCK_UN);
        fclose($fp);

        return false;
    }

    /*
     * Сразу резервируем текущую отправку,
     * чтобы параллельный запрос уже увидел лимит.
     */
    $timestamps[] = $now;

    ftruncate($fp, 0);
    rewind($fp);

    fwrite(
        $fp,
        json_encode($timestamps)
    );

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}


/*
 * Для security-проверки используем REMOTE_ADDR,
 * а не X-Real-IP / X-Forwarded-For.
 */
$ip = trim(
    (string) ($_SERVER['REMOTE_ADDR'] ?? '')
);

if (!is_bogon_ip($ip)) {
    response_json(
        403,
        [
            'ok' => false,
            'error' => 'Forbidden',
            'ip' => $ip,
        ]
    );
}


/*
 * Только POST.
 */
if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !== 'POST'
) {
    header('Allow: POST');

    response_json(
        405,
        [
            'ok' => false,
            'error' => 'Method Not Allowed',
        ]
    );
}


/*
 * Входные данные.
 */
$subject = trim(
    (string) ($_POST['subject'] ?? '')
);

$text = trim(
    (string) ($_POST['text'] ?? '')
);


if ($subject === '') {
    response_json(
        400,
        [
            'ok' => false,
            'error' => 'Subject is required',
        ]
    );
}

if ($text === '') {
    response_json(
        400,
        [
            'ok' => false,
            'error' => 'Text is required',
        ]
    );
}


$subjectLength = mb_strlen(
    $subject,
    'UTF-8'
);

$textLength = mb_strlen(
    $text,
    'UTF-8'
);


if ($subjectLength > 150) {
    response_json(
        400,
        [
            'ok' => false,
            'error' => 'Subject too long',
            'max_length' => 150,
        ]
    );
}

if ($textLength > 10000) {
    response_json(
        400,
        [
            'ok' => false,
            'error' => 'Text too long',
            'max_length' => 10000,
        ]
    );
}


/*
 * Максимум 2 сообщения за 60 секунд.
 */
if (!rate_limit_check()) {
    response_json(
        429,
        [
            'ok' => false,
            'error' => 'Too Many Requests',
        ]
    );
}


/*
 * HTML -> plain text
 * для Telegram и SMS.
 */
$plainText = html_to_text($text);


/*
 * Общий текст Telegram / SMS:
 *
 * [subject]
 *
 * text...
 */
$message = '['
    . $subject
    . ']'
    . "\n\n"
    . $plainText;

/*
 * Telegram.
 */
if ($mode === 'all' || $mode === 'tg') {
    $telegramConfig = $config['telegram'] ?? [];

    $telegram = new TelegramService(
        (string) (
            $telegramConfig['bot_token']
            ?? ''
        )
    );

    telegram_notify(
        $telegram,
        $telegramConfig,
        telegram_html($message)
    );
}


/*
 * SMS на mts_sms.notification_phone.
 *
 * Первые 150 Unicode-символов.
 */
if ($mode === 'all' || $mode === 'sms') {
    $smsMessage = mb_substr(
        $message,
        0,
        150,
        'UTF-8'
    );

    send_notification_sms(
        $pdo,
        $config,
        $smsMessage
    );
}


/*
 * Email.
 *
 * HTML-разметку сохраняем.
 */
if ($mode === 'all' || $mode === 'email') {
    $mailResult = send_mail_smtp(
        $config,
        'info@trianda.by',
        $subject,
        $text
    );

    if (empty($mailResult['ok'])) {
        error_log(
            'sender.php mail error: '
            . (string) (
                $mailResult['error']
                ?? 'Unknown error'
            )
        );
    }
}



/*
 * Успешный ответ.
 */
response_json(
    200,
    [
        'ok' => true,
    ]
);
