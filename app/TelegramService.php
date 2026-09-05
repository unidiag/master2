<?php

final class TelegramService
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = trim($token);
    }

    public function sendMessage(
        string $chatId,
        string $message
    ): bool {
        $chatId = trim($chatId);
        $message = trim($message);

        if (
            $this->token === ''
            || $chatId === ''
            || $message === ''
        ) {
            return false;
        }

        $url = sprintf(
            'https://api.telegram.org/bot%s/sendMessage',
            $this->token
        );

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $curl = curl_init($url);

        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            ),
        ]);

        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        curl_close($curl);

        if (
            !is_string($response)
            || $httpCode < 200
            || $httpCode >= 300
        ) {
            error_log(
                'Telegram send error: HTTP '
                . $httpCode
                . ', response: '
                . (string) $response
            );

            return false;
        }

        $decoded = json_decode($response, true);

        if (
            !is_array($decoded)
            || ($decoded['ok'] ?? false) !== true
        ) {
            error_log(
                'Telegram API error: ' . $response
            );

            return false;
        }

        return true;
    }


    public function sendToMany(
        array $chatIds,
        string $message
    ): array {
        $results = [];

        foreach ($chatIds as $chatId) {
            $chatId = trim((string) $chatId);

            if ($chatId === '') {
                continue;
            }

            $results[$chatId] = $this->sendMessage(
                $chatId,
                $message
            );
        }

        return $results;
    }


}

?>