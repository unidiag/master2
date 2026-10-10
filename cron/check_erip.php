<?php
/**
 * Уведомления ЕРИП: оплата отключённых абонентов и объявлений.
 *
 * Использование:
 *   php check_erip.php --dry-run
 *   php check_erip.php
 *
 * Можно также подключать через require из cron1min.php.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
// Если запускаем напрямую, загружаем окружение.
if (
    !isset($pdo, $config)
    || !($pdo instanceof PDO)
) {
    require_once dirname(__DIR__) . '/app/bootstrap.php';
}
/** @var PDO $pdo */
/** @var array $config */
$dryRun = in_array(
    '--dry-run',
    $_SERVER['argv'] ?? [],
    true
);
$telegramConfig = $config['telegram'] ?? [];
$smsConfig = $config['mts_sms'] ?? [];
$telegramEnabled = !empty($telegramConfig['enabled']);
$smsEnabled = !empty($smsConfig['enabled']);
$telegramToken = trim(
    (string) ($telegramConfig['bot_token'] ?? '')
);
$telegramChats = array_unique(
    array_map(
        'strval',
        $telegramConfig['chat_ids'] ?? []
    )
);
$smsPhone = trim(
    (string) ($smsConfig['notification_phone'] ?? '')
);
$recipients = [];
// Получатели Telegram.
if ($telegramEnabled && $telegramToken !== '') {
    foreach ($telegramChats as $chatId) {
        $chatId = trim($chatId);
        if ($chatId === '') {
            continue;
        }
        $recipients[] = [
            'channel' => 'telegram',
            'recipient' => $chatId,
        ];
    }
}
// Получатель SMS.
if ($smsEnabled && $smsPhone !== '') {
    $phone = preg_replace('/\D+/', '', $smsPhone) ?? '';
    if ($phone !== '') {
        $recipients[] = [
            'channel' => 'sms',
            'recipient' => $phone,
        ];
    }
}
if (!$recipients) {
    echo "ERIP alerts: no enabled recipients\n";
    return;
}
// Защита от параллельных запусков на одном сервере.
$lockFile = __DIR__ . '/check_erip.lock';
$lock = fopen($lockFile, 'c');
if ($lock === false) {
    throw new RuntimeException('Cannot open ERIP lock file');
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    echo "ERIP alerts: already running\n";
    return;
}
try {
    /*
     * Ищем платежи, сделанные после отключения.
     *
     * p.account — лицевой счёт ЕРИП.
     * o.personal — лицевой счёт абонента.
     *
     * Берём только отключения, видимые на странице
     * модуля otkluchki.
     */
    $sql = '
        SELECT
            o.id AS disconnect_id,
            o.created_at AS disconnected_at,
            o.personal,
            o.subscriber,
            o.address,
            o.summ AS disconnect_amount,
            p.id AS payment_id,
            p.amount AS payment_amount,
            p.payment_at,
            p.operation_id
        FROM master_otkluchki o
        INNER JOIN erip_payments p
            ON TRIM(p.account) = TRIM(o.personal)
            AND p.payment_at > o.created_at
            AND p.amount > 0
            AND p.service <> "2"
        WHERE o.deleted_at IS NULL
          AND (
              o.hidden_at IS NULL
              OR o.hidden_at > NOW()
          )
          AND o.personal IS NOT NULL
          AND TRIM(o.personal) <> ""
        ORDER BY p.payment_at ASC, p.id ASC
    ';
    $stmt = $pdo->query($sql);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Оплата услуги 2: одно уведомление на operation_id.
    // Используем минимальный p.id как стабильный ID операции в журнале.
    $adsSql = "
        SELECT
            0 AS disconnect_id,
            NULL AS disconnected_at,
            NULL AS personal,
            NULL AS subscriber,
            NULL AS address,
            NULL AS disconnect_amount,
            p.id AS payment_id,
            p.account AS payer_name,
            p.amount AS payment_amount,
            p.payment_at,
            p.operation_id,
            'advertisement' AS alert_type
        FROM erip_payments p
        WHERE p.service = '2'
          AND p.amount > 0
          AND p.operation_id IS NOT NULL
          AND TRIM(p.operation_id) <> ''
          AND p.id = (
              SELECT MIN(p2.id)
              FROM erip_payments p2
              WHERE p2.service = '2'
                AND p2.operation_id = p.operation_id
          )
        ORDER BY p.payment_at ASC, p.id ASC
    ";
    $adsPayments = $pdo->query($adsSql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($payments as &$payment) {
        $payment['alert_type'] = 'disconnect';
    }
    unset($payment);
    echo sprintf(
        "ERIP alerts: disconnect=%d, advertisements=%d\n",
        count($payments),
        count($adsPayments)
    );
    $payments = array_merge($payments, $adsPayments);
    if (!$payments) {
        return;
    }
    /*
     * SQL для журнала.
     *
     * UNIQUE KEY предотвращает создание нескольких
     * записей для одного платежа, канала и получателя.
     */
    $insertStmt = $pdo->prepare(
        'INSERT IGNORE INTO master_erip_alerts
            (
                disconnect_id,
                payment_id,
                channel,
                recipient
            )
         VALUES (?, ?, ?, ?)'
    );
    $getStmt = $pdo->prepare(
        'SELECT id, sent_at, attempts
         FROM master_erip_alerts
         WHERE disconnect_id = ?
           AND payment_id = ?
           AND channel = ?
           AND recipient = ?
         LIMIT 1'
    );
    $successStmt = $pdo->prepare(
        'UPDATE master_erip_alerts
         SET
             sent_at = NOW(),
             message_id = ?,
             attempts = attempts + 1,
             last_error = NULL
         WHERE id = ?'
    );
    $errorStmt = $pdo->prepare(
        'UPDATE master_erip_alerts
         SET
             attempts = attempts + 1,
             last_error = ?
         WHERE id = ?'
    );
    // MTS создаём только если включён SMS-канал.
    $smsService = null;
    if ($smsEnabled && $smsPhone !== '' && !$dryRun) {
        $smsService = new MtsSmsService($smsConfig);
    }
    foreach ($payments as $payment) {
        $disconnectId = (int) $payment['disconnect_id'];
        $paymentId = (int) $payment['payment_id'];
        $amount = number_format(
            (float) $payment['payment_amount'],
            2,
            ',',
            ' '
        );
        if ($payment['alert_type'] === 'advertisement') {
            $message = implode("\n", [
                'ОПЛАТА ОБЪЯВЛЕНИЯ',
                'ФИО: ' . trim((string) $payment['payer_name']),
                'Сумма: ' . $amount,
            ]);
            $telegramMessage = $message;
            $smsMessage = $message;
        } else {
            $personal = trim((string) $payment['personal']);
            $subscriber = trim((string) $payment['subscriber']);
            $address = trim((string) $payment['address']);
            $paymentDate = date(
                'd.m.Y H:i',
                strtotime((string) $payment['payment_at'])
            );
            $disconnectDate = date(
                'd.m.Y H:i',
                strtotime((string) $payment['disconnected_at'])
            );
            $lines = [
                'ОПЛАТА ЕРИП ОТКЛЮЧЁННОГО АБОНЕНТА',
                '',
                'Абонент: ' . $subscriber,
                'Адрес: ' . $address,
                'Лицевой счёт: ' . $personal,
                'Сумма: ' . $amount . ' руб.',
                'Дата оплаты: ' . $paymentDate,
                'Отключён: ' . $disconnectDate,
            ];
            $message = implode("\n", $lines);
            $telegramMessage = "💰 " . $message;
            $smsMessage = implode("\n", [
                'ЕРИП: оплата отключенца',
                'Абонент: ' . $subscriber,
                'Адрес: ' . $address,
                'Сумма: ' . $amount . ' руб.',
            ]);
        }
        foreach ($recipients as $recipientData) {
            $channel = $recipientData['channel'];
            $recipient = $recipientData['recipient'];
            $key = [
                $disconnectId,
                $paymentId,
                $channel,
                $recipient,
            ];
            /*
             * Dry-run ничего не меняет в БД
             * и не отправляет сообщения.
             */
            if ($dryRun) {
                $getStmt->execute($key);
                $existing = $getStmt->fetch(PDO::FETCH_ASSOC);
                if ($existing && $existing['sent_at'] !== null) {
                    continue;
                }
                echo sprintf(
                    "[DRY RUN] disconnect=%d payment=%d %s -> %s\n",
                    $disconnectId,
                    $paymentId,
                    $channel,
                    $recipient
                );
                echo $message . "\n\n";
                continue;
            }
            // Регистрируем уведомление.
            $insertStmt->execute($key);
            $getStmt->execute($key);
            $alert = $getStmt->fetch(PDO::FETCH_ASSOC);
            if (!$alert) {
                throw new RuntimeException(
                    'Cannot read alert journal'
                );
            }
            // Уже отправлено — повторять не нужно.
            if ($alert['sent_at'] !== null) {
                continue;
            }
            $alertId = (int) $alert['id'];
            try {
                $messageId = null;
                if ($channel === 'telegram') {
                    /*
                     * Telegram Bot API.
                     * Отправляем конкретному получателю.
                     */
                    $url = 'https://api.telegram.org/bot'
                        . $telegramToken
                        . '/sendMessage';
                    $ch = curl_init($url);
                    if ($ch === false) {
                        throw new RuntimeException(
                            'Cannot initialize Telegram cURL'
                        );
                    }
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POSTFIELDS => [
                            'chat_id' => $recipient,
                            'text' => $telegramMessage,
                            'disable_web_page_preview' => 'true',
                        ],
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT => 15,
                    ]);
                    $response = curl_exec($ch);
                    $httpCode = (int) curl_getinfo(
                        $ch,
                        CURLINFO_RESPONSE_CODE
                    );
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    if ($response === false) {
                        throw new RuntimeException(
                            'Telegram network error: ' . $curlError
                        );
                    }
                    $responseData = json_decode(
                        (string) $response,
                        true
                    );
                    if (
                        $httpCode !== 200
                        || !is_array($responseData)
                        || empty($responseData['ok'])
                    ) {
                        throw new RuntimeException(
                            'Telegram API error: HTTP '
                            . $httpCode . ' '
                            . mb_substr((string) $response, 0, 500)
                        );
                    }
                    $messageId = (string) (
                        $responseData['result']['message_id'] ?? ''
                    );
                } elseif ($channel === 'sms') {
                    if ($smsService === null) {
                        throw new RuntimeException(
                            'MTS SMS service not initialized'
                        );
                    }
                    // Реальный метод из app/MtsSmsService.php.
                    $messageId = $smsService->send(
                        $recipient,
                        $smsMessage
                    );
                } else {
                    continue;
                }
                // Фиксируем успешную отправку.
                $successStmt->execute([
                    $messageId,
                    $alertId,
                ]);
                echo sprintf(
                    "[SENT] disconnect=%d payment=%d %s -> %s\n",
                    $disconnectId,
                    $paymentId,
                    $channel,
                    $recipient
                );
            } catch (Throwable $e) {
                $error = mb_substr(
                    $e->getMessage(),
                    0,
                    2000
                );
                $errorStmt->execute([
                    $error,
                    $alertId,
                ]);
                error_log(sprintf(
                    'ERIP alert failed: payment=%d '
                    . 'channel=%s recipient=%s error=%s',
                    $paymentId,
                    $channel,
                    $recipient,
                    $error
                ));
                echo sprintf(
                    "[ERROR] payment=%d %s -> %s: %s\n",
                    $paymentId,
                    $channel,
                    $recipient,
                    $error
                );
            }
        }
    }
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
