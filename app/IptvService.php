<?php

declare(strict_types=1);

final class IptvService
{
    private $baseUrl;
    private $key;

    public function __construct(array $settings)
    {
        $url = rtrim(trim((string) ($settings['url'] ?? '')), '/');
        $key = trim((string) ($settings['key'] ?? ''));
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || $key === '') {
            throw new RuntimeException('IPTV: некорректный HTTPS URL или отсутствует API-ключ.');
        }
        $this->baseUrl = $url;
        $this->key = $key;
    }

    public function users(?int $status = null): array
    {
        if ($status !== null && ($status < 0 || $status > 9)) {
            throw new InvalidArgumentException('Некорректный фильтр статуса.');
        }
        $result = $this->request('GET', ['getUsers', $status === null ? null : (string) $status]);
        if (!array_is_list_compat($result)) {
            throw new RuntimeException('IPTV: вместо списка абонентов получен неожиданный ответ.');
        }
        return $result;
    }

    public function user(string $login): array
    {
        $result = $this->request('GET', ['getUser', $this->login($login)]);
        if (!isset($result['user']) || !is_array($result['user'])) {
            throw new RuntimeException('IPTV: сервер не вернул карточку абонента.');
        }
        return $result;
    }


    /**
     * Сброс всех refresh-сессий IPTV-аккаунта.
     * Сам аккаунт не удаляется.
     */
    public function deleteSessions(string $login): array
    {
        return $this->request(
            'POST',
            ['delUser', $this->login($login)]
        );
    }    

    public function setField(string $login, string $field, string $value): array
    {
        if (!in_array($field, ['status', 'info', 'billing_id', 'balance'], true)) {
            throw new InvalidArgumentException(
                'Изменение этого поля не поддерживается.'
            );
        }

        if (
            $field === 'status' &&
            (!ctype_digit($value) || (int) $value > 7)
        ) {
            throw new InvalidArgumentException(
                'Статус должен быть от 0 до 7.'
            );
        }

        // Billing ID:
        // 0 — отвязать IPTV-аккаунт
        // положительное число — сопрячь с лицевым счётом
        if (
            $field === 'billing_id' &&
            !preg_match('/^(0|[1-9][0-9]*)$/D', $value)
        ) {
            throw new InvalidArgumentException(
                'Billing ID должен быть неотрицательным числом.'
            );
        }

        if ($field === 'info' && strlen($value) > 10000) {
            throw new InvalidArgumentException(
                'Описание не может превышать 10000 байт.'
            );
        }

        // Баланс: число с максимум двумя знаками после точки.
        if ($field === 'balance') {
            if (
                !preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/D', $value)
                || (float) $value < -9999999999.99
                || (float) $value > 9999999999.99
            ) {
                throw new InvalidArgumentException(
                    'Некорректное значение баланса IPTV.'
                );
            }
        }


        return $this->request(
            'POST',
            ['setUser', $this->login($login), $field, $value]
        );
    }


    /**
     * Полностью заменяет пакеты IPTV-абонента.
     *
     * Пример:
     * setPackages($login, '2,3')
     * POST /setPackage/{login}/2/3
     */
    public function setPackages(string $login, string $packages): array
    {
        $packages = trim($packages);

        if (
            $packages === '' ||
            !preg_match(
                '/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D',
                $packages
            )
        ) {
            throw new InvalidArgumentException(
                'Некорректный список пакетов IPTV.'
            );
        }

        $ids = array_map('intval', explode(',', $packages));

        foreach ($ids as $id) {
            if ($id < 1 || $id > 4294967295) {
                throw new InvalidArgumentException(
                    'Некорректный ID пакета IPTV.'
                );
            }
        }

        $ids = array_values(array_unique($ids));

        $segments = array_merge(
            ['setPackage', $this->login($login)],
            array_map('strval', $ids)
        );

        $result = $this->request('POST', $segments);

        // Проверяем подтверждённый список пакетов.
        if (!isset($result['packages']) ||
            !is_array($result['packages'])) {
            throw new RuntimeException(
                'IPTV API не подтвердил список пакетов.'
            );
        }

        $actual = array_map('intval', $result['packages']);
        sort($actual);

        $expected = $ids;
        sort($expected);

        if ($actual !== $expected) {
            throw new RuntimeException(
                'IPTV API вернул неожиданный список пакетов.'
            );
        }

        return $result;
    }



    public function changeBalance(int $billingId, string $amount, string $idempotencyKey, bool $addOnly): array
    {
        if ($billingId < 1 || !preg_match('/^[+-]?(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/D', $amount)) {
            throw new InvalidArgumentException('Некорректные Billing ID или денежная сумма.');
        }
        if ((float) $amount === 0.0 || ($addOnly && (float) $amount < 0)) {
            throw new InvalidArgumentException('Сумма должна быть ненулевой (для пополнения — положительной).');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{16,100}$/D', $idempotencyKey)) {
            throw new InvalidArgumentException('Некорректный ключ операции.');
        }
        return $this->request('POST', [$addOnly ? 'addBalance' : 'adjBalance', (string) $billingId, $amount], $idempotencyKey);
    }

    public function resetSessions(string $login): array
    {
        return $this->request('POST', ['delUser', $this->login($login)]);
    }

    private function login(string $login): string
    {
        $login = trim($login);
        if (!preg_match('/^\+?[0-9]{5,20}$/D', $login)) {
            throw new InvalidArgumentException('Логин должен содержать от 5 до 20 цифр, возможно с +.');
        }
        return $login;
    }

    private function request(string $method, array $segments, ?string $idempotencyKey = null): array
    {
        $segments = array_values(array_filter($segments, static function ($item): bool {
            return $item !== null;
        }));
        $path = implode('/', array_map('rawurlencode', $segments));
        $ch = curl_init($this->baseUrl . '/' . $path);
        if ($ch === false) {
            throw new RuntimeException('IPTV: не удалось инициализировать cURL.');
        }
        $headers = ['X-API-Key: ' . $this->key, 'Accept: application/json'];
        if ($idempotencyKey !== null) {
            $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_MAXREDIRS => 0,
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            // For financial requests, don't automatically retry: outcome may be uncertain.
            throw new RuntimeException('IPTV: ошибка соединения (' . $error . '). Если выполнялся платёж, проверьте баланс перед повтором.');
        }
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new RuntimeException('IPTV: невалидный JSON, HTTP ' . $http . '.');
        }
        if ($http < 200 || $http >= 300 || ($payload['status'] ?? '') === 'ERROR') {
            $message = (string) ($payload['message'] ?? 'Неизвестная ошибка API');
            throw new RuntimeException('IPTV: HTTP ' . $http . ': ' . $message);
        }
        return $payload;
    }
}

if (!function_exists('array_is_list_compat')) {
    function array_is_list_compat(array $array): bool
    {
        $i = 0;
        foreach ($array as $key => $_) {
            if ($key !== $i++) {
                return false;
            }
        }
        return true;
    }
}
