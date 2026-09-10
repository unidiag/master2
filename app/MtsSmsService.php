<?php

declare(strict_types=1);

final class MtsSmsService
{
    private string $baseUrl;
    private string $login;
    private string $password;
    private int $clientId;
    private string $alphaName;
    private int $ttl;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim(
            (string) ($config['base_url'] ?? 'https://api.communicator.mts.by'),
            '/'
        );

        $this->login = (string) ($config['login'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->clientId = (int) ($config['client_id'] ?? 0);
        $this->alphaName = (string) ($config['alpha_name'] ?? 'TRIANDA');
        $this->ttl = (int) ($config['ttl'] ?? 300);

        if ($this->login === '') {
            throw new RuntimeException('Не указан логин MTS SMS API.');
        }

        if ($this->password === '') {
            throw new RuntimeException('Не указан пароль MTS SMS API.');
        }

        if ($this->clientId <= 0) {
            throw new RuntimeException('Не указан client_id MTS SMS API.');
        }

        if ($this->alphaName === '') {
            throw new RuntimeException('Не указан alpha_name MTS SMS API.');
        }
    }

    public function send(string $phone, string $text): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if ($phone === '') {
            throw new RuntimeException('Некорректный номер телефона.');
        }

        if ($text === '') {
            throw new RuntimeException('Пустой текст SMS.');
        }

        $url = sprintf(
            '%s/%d/json2/simple',
            $this->baseUrl,
            $this->clientId
        );

        $payload = [
            'phone_number' => (int) $phone,
            'channels' => [
                'sms',
            ],
            'channel_options' => [
                'sms' => [
                    'text' => $text,
                    'alpha_name' => $this->alphaName,
                    'ttl' => $this->ttl,
                ],
            ],
        ];

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new RuntimeException(
                'Не удалось сформировать JSON запроса SMS.'
            );
        }

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Не удалось создать CURL-запрос.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->login . ':' . $this->password,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Ошибка соединения с MTS Communicator: ' . $error
            );
        }

        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($ch);

        $data = json_decode(
            (string) $response,
            true
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                'MTS вернул некорректный ответ: ' . $response
            );
        }

        if (
            isset($data['error_code'])
            || isset($data['error_text'])
        ) {
            throw new RuntimeException(
                sprintf(
                    'MTS: %s%s',
                    isset($data['error_code'])
                        ? '[' . $data['error_code'] . '] '
                        : '',
                    (string) ($data['error_text'] ?? 'Ошибка отправки SMS')
                )
            );
        }

        if ($httpCode !== 200) {
            throw new RuntimeException(
                sprintf(
                    'MTS вернул HTTP %d: %s',
                    $httpCode,
                    $response
                )
            );
        }

        $messageId = trim(
            (string) ($data['message_id'] ?? '')
        );

        if ($messageId === '') {
            throw new RuntimeException(
                'MTS не вернул message_id: ' . $response
            );
        }

        return $messageId;
    }

    public function status(string $messageId): array
    {
        $messageId = trim($messageId);

        if ($messageId === '') {
            throw new RuntimeException('Не указан message_id.');
        }

        $url = sprintf(
            '%s/%d/dr/%s/simple',
            $this->baseUrl,
            $this->clientId,
            rawurlencode($messageId)
        );

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Не удалось создать CURL-запрос.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->login . ':' . $this->password,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Ошибка получения статуса MTS: ' . $error
            );
        }

        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($ch);

        $data = json_decode(
            (string) $response,
            true
        );

        if ($httpCode !== 200 || !is_array($data)) {
            throw new RuntimeException(
                sprintf(
                    'Ошибка получения статуса MTS, HTTP %d: %s',
                    $httpCode,
                    $response
                )
            );
        }

        return $data;
    }
}