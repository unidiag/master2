<?php

final class ChannelService
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getAll(): array
    {
        $devices = $this->config['devices'] ?? [];
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');

        $result = [];

        foreach ($devices as $device) {
            $ip = trim((string) $device);

            if ($ip === '') {
                continue;
            }

            try {
                $modulator = $this->requestDevice(
                    $ip,
                    $username,
                    $password,
                    'modulator_dvbc',
                    [
                        'op_code' => 5,
                        'modu_index' => 0,
                    ],
                    "http://{$ip}/product/ru/modulator_dvbc.php"
                );

                $outputInfo = $this->requestDevice(
                    $ip,
                    $username,
                    $password,
                    'output_info',
                    [
                        'op_code' => 4,
                        'modu_index' => 0,
                    ],
                    "http://{$ip}/product/ru/output.php"
                );

                foreach ($modulator['channels'] ?? [] as $channel) {
                    if ((int) ($channel['enable'] ?? 0) !== 1) {
                        continue;
                    }

                    $index = (int) ($channel['ch_index'] ?? 0);

                    $program = $outputInfo['prg_info'][$index] ?? [];

                    $result[] = [
                        'ip' => $ip,
                        'channel' => $index + 1,
                        'freq_mhz' => (float) ($channel['freq'] ?? 0),
                        'level_db' => (float) ($channel['level'] ?? 0),
                        'service_name' => $this->decodeServiceName(
                            (string) ($program['service_name'] ?? '')
                        ),
                    ];
                }
            } catch (Throwable $e) {
                /*
                * Пока просто пропускаем недоступное устройство.
                * Позже при желании можно вывести предупреждение.
                */
            }
        }

        usort(
            $result,
            static function (array $a, array $b): int {
                return ($a['freq_mhz'] <=> $b['freq_mhz']);
            }
        );

        return $result;
    }

    private function requestDevice(
        string $ip,
        string $username,
        string $password,
        string $proctype,
        array $post = [],
        ?string $referer = null
    ): array {
        $ch = curl_init(
            "http://{$ip}/cgi.php?proctype={$proctype}"
        );

        if ($ch === false) {
            throw new RuntimeException(
                "{$ip}: cannot initialize cURL"
            );
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "{$username}:{$password}",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post),
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
        ]);

        if ($referer !== null) {
            curl_setopt(
                $ch,
                CURLOPT_REFERER,
                $referer
            );
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);

            curl_close($ch);

            throw new RuntimeException(
                "{$ip}: {$error}"
            );
        }

        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException(
                "{$ip}: HTTP {$httpCode}"
            );
        }

        if ($response === '') {
            throw new RuntimeException(
                "{$ip}: empty response from {$proctype}"
            );
        }

        $data = json_decode(
            $response,
            true
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                "{$ip}: invalid JSON from {$proctype}"
            );
        }

        return $data;
    }

    private function decodeServiceName(string $name): string
    {
        if ($name === '' || $name === 'NONE') {
            return '';
        }

        $name = rawurldecode($name);

        if ($name === '') {
            return '';
        }

        if (ord($name[0]) === 0x01) {
            $name = substr($name, 1);

            return trim(
                mb_convert_encoding(
                    $name,
                    'UTF-8',
                    'ISO-8859-5'
                )
            );
        }

        if (mb_check_encoding($name, 'UTF-8')) {
            return trim($name);
        }

        return trim(
            mb_convert_encoding(
                $name,
                'UTF-8',
                'ISO-8859-5'
            )
        );
    }
}