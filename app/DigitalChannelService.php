<?php

declare(strict_types=1);

final class DigitalChannelService
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getAll(): array
    {
        $connections = $this->config['astras'] ?? [];

        $channels = [];
        $servers = [];

        foreach ($connections as $connection) {
            $connection = trim((string) $connection);

            if ($connection === '') {
                continue;
            }

            try {
                $dsn = $this->parseDsn($connection);

                $config = $this->loadConfig($dsn);

                $serverChannels = [];

                foreach ($config['make_stream'] ?? [] as $stream) {
                    if (!is_array($stream)) {
                        continue;
                    }

                    if (($stream['enable'] ?? false) !== true) {
                        continue;
                    }

                    $channel = [
                        'astra' => $dsn['host'] . ':' . $dsn['port'],
                        'id' => (string) ($stream['id'] ?? ''),
                        'name' => trim(
                            (string) ($stream['name'] ?? '')
                        ),
                        'service_name' => trim(
                            (string) ($stream['service_name'] ?? '')
                        ),
                        'service_provider' => trim(
                            (string) ($stream['service_provider'] ?? '')
                        ),
                        'input' => array_values(
                            array_map(
                                function ($value): string {
                                    return $this->normalizeStreamUrl(
                                        (string) $value
                                    );
                                },
                                (array) ($stream['input'] ?? [])
                            )
                        ),

                        'output' => array_values(
                            array_map(
                                function ($value): string {
                                    return $this->normalizeStreamUrl(
                                        (string) $value
                                    );
                                },
                                (array) ($stream['output'] ?? [])
                            )
                        ),
                    ];

                    $channels[] = $channel;
                    $serverChannels[] = $channel;
                }

                $servers[] = [
                    'address' => $dsn['host'] . ':' . $dsn['port'],
                    'online' => true,
                    'channels' => count($serverChannels),
                    'error' => '',
                ];
            } catch (Throwable $e) {
                $servers[] = [
                    'address' => $this->maskDsn($connection),
                    'online' => false,
                    'channels' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        usort(
            $channels,
            static function (array $a, array $b): int {
                return strcasecmp(
                    (string) ($a['name'] ?? ''),
                    (string) ($b['name'] ?? '')
                );
            }
        );

        return [
            'channels' => $channels,
            'servers' => $servers,
        ];
    }

    private function loadConfig(array $dsn): array
    {
        $url = sprintf(
            'http://%s:%d/control/',
            $dsn['host'],
            $dsn['port']
        );

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException(
                'Не удалось инициализировать cURL'
            );
        }

        $body = json_encode(
            ['cmd' => 'load'],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD =>
                $dsn['username'] . ':' . $dsn['password'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException($error);
        }

        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException(
                'HTTP ' . $httpCode
            );
        }

        $data = json_decode(
            $response,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                'Некорректный ответ Astra'
            );
        }

        return $data;
    }

    private function parseDsn(string $dsn): array
    {
        if (!preg_match(
            '~^([^:]+):([^@]*)@([^:]+):([0-9]+)$~',
            $dsn,
            $matches
        )) {
            throw new InvalidArgumentException(
                'Некорректный адрес Astra'
            );
        }

        $port = (int) $matches[4];

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(
                'Некорректный порт Astra'
            );
        }

        return [
            'username' => $matches[1],
            'password' => $matches[2],
            'host' => $matches[3],
            'port' => $port,
        ];
    }


    private function normalizeStreamUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $url = preg_replace(
            '~^(udp://)192\.168\.2\.15@~',
            '$1@',
            $url
        ) ?? $url;

        $url = preg_replace(
            '~#sync(?:&.*)?$~',
            '',
            $url
        ) ?? $url;

        return $url;
    }

    private function maskDsn(string $dsn): string
    {
        return preg_replace(
            '~^([^:]+):([^@]*)@~',
            '$1:***@',
            $dsn
        ) ?? $dsn;
    }








    public function sync(
        DigitalChannelRepository $repository
    ): array {
        $connections =
            $this->config['astras']
            ?? [];

        $result = [];

        foreach ($connections as $connection) {
            $connection = trim(
                (string) $connection
            );

            if ($connection === '') {
                continue;
            }

            try {
                $dsn =
                    $this->parseDsn(
                        $connection
                    );

                $server =
                    $dsn['host']
                    . ':'
                    . $dsn['port'];

                $config =
                    $this->loadConfig(
                        $dsn
                    );

                $channels = [];

                foreach (
                    $config['make_stream']
                        ?? []
                    as $stream
                ) {
                    if (!is_array($stream)) {
                        continue;
                    }

                    /*
                    * Нас интересуют только
                    * включённые stream.
                    */
                    if (
                        ($stream['enable'] ?? false)
                        !== true
                    ) {
                        continue;
                    }

                    $inputs = array_map(
                        function ($value): string {
                            return $this
                                ->normalizeStreamUrl(
                                    (string) $value
                                );
                        },
                        (array) (
                            $stream['input']
                            ?? []
                        )
                    );

                    $outputs = array_map(
                        function ($value): string {
                            return $this
                                ->normalizeStreamUrl(
                                    (string) $value
                                );
                        },
                        (array) (
                            $stream['output']
                            ?? []
                        )
                    );

                    $channels[] = [
                        'id' => (string) (
                            $stream['id']
                            ?? ''
                        ),

                        'name' => trim(
                            (string) (
                                $stream['name']
                                ?? ''
                            )
                        ),

                        'service_name' => trim(
                            (string) (
                                $stream['service_name']
                                ?? ''
                            )
                        ),

                        'service_provider' => trim(
                            (string) (
                                $stream['service_provider']
                                ?? ''
                            )
                        ),

                        'input' =>
                            array_values(
                                $inputs
                            ),

                        'output' =>
                            array_values(
                                $outputs
                            ),
                    ];
                }

                $saveResult = $repository->saveChannels(
                    $server,
                    $channels
                );

                $result[] = [
                    'server' => $server,
                    'success' => true,
                    'channels' => count($channels),

                    'inserted' => (int) (
                        $saveResult['inserted']
                        ?? 0
                    ),

                    'updated' => (int) (
                        $saveResult['updated']
                        ?? 0
                    ),

                    'error' => '',
                ];
            } catch (Throwable $exception) {
                /*
                * Базу для недоступной Astra
                * НЕ очищаем.
                */
                $result[] = [
                    'server' => $this->maskDsn(
                        $connection
                    ),

                    'success' => false,
                    'channels' => 0,
                    'inserted' => 0,
                    'updated' => 0,

                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }



}