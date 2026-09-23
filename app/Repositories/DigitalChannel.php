<?php

declare(strict_types=1);


final class DigitalChannelRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

public function saveChannels(
    string $server,
    array $channels
): array {
    $inserted = 0;
    $updated = 0;

    $find = $this->db->prepare(
        'SELECT id
        FROM master_dtv
        WHERE server = :server
        AND name = :name
        LIMIT 1'
    );

    $insert = $this->db->prepare(
        'INSERT INTO master_dtv (
            server,
            astra_id,
            name,
            service_name,
            service_provider,
            input,
            output,
            updated_at
        ) VALUES (
            :server,
            :astra_id,
            :name,
            :service_name,
            :service_provider,
            :input,
            :output,
            NOW()
        )'
    );

    $update = $this->db->prepare(
        'UPDATE master_dtv
        SET
            astra_id = :astra_id,
            service_name = :service_name,
            service_provider = :service_provider,
            input = :input,
            output = :output,
            updated_at = NOW()
        WHERE server = :server
        AND name = :name'
    );

    $this->db->beginTransaction();

    try {
        foreach ($channels as $channel) {
            $name = trim(
                (string) ($channel['name'] ?? '')
            );

            /*
             * Без названия идентифицировать
             * канал невозможно.
             */
            if ($name === '') {
                continue;
            }

            $params = [
                'server' => $server,

                'astra_id' => (string) (
                    $channel['id']
                    ?? ''
                ),

                'name' => $name,

                'service_name' => (string) (
                    $channel['service_name']
                    ?? ''
                ),

                'service_provider' => (string) (
                    $channel['service_provider']
                    ?? ''
                ),

                'input' => json_encode(
                    array_values(
                        (array) (
                            $channel['input']
                            ?? []
                        )
                    ),
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                ),

                'output' => json_encode(
                    array_values(
                        (array) (
                            $channel['output']
                            ?? []
                        )
                    ),
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                ),
            ];

            $find->execute([
                'server' => $server,
                'name' => $name,
            ]);

            $id = $find->fetchColumn();

            if ($id !== false) {
                $update->execute($params);

                $updated++;

                continue;
            }

            $insert->execute($params);

            $inserted++;
        }

        $this->db->commit();
    } catch (Throwable $exception) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        throw $exception;
    }

    return [
        'inserted' => $inserted,
        'updated' => $updated,
    ];
}




public function delete(
    int $id
): void {
    if ($id <= 0) {
        throw new InvalidArgumentException(
            'Некорректный ID телеканала.'
        );
    }

    $statement = $this->db->prepare(
        'DELETE FROM master_dtv
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $id,
    ]);

    if ($statement->rowCount() === 0) {
        throw new RuntimeException(
            'Телеканал не найден.'
        );
    }
}


    public function getAll(): array
    {
        $statement = $this->db->query(
            'SELECT
                id,
                server,
                astra_id,
                name,
                lcn,
                distrib,
                summ,
                info,
                service_name,
                service_provider,
                input,
                output,
                updated_at
            FROM master_dtv
            ORDER BY
                CASE
                    WHEN lcn = 0 THEN 1
                    ELSE 0
                END,
                lcn ASC,
                name ASC,
                server ASC'
        );

        $channels = [];

        while (
            $row = $statement->fetch(
                PDO::FETCH_ASSOC
            )
        ) {
            $row['astra'] = (string) (
                $row['server']
                ?? ''
            );

            $row['input'] =
                $this->decodeArray(
                    (string) (
                        $row['input']
                        ?? ''
                    )
                );

            $row['output'] =
                $this->decodeArray(
                    (string) (
                        $row['output']
                        ?? ''
                    )
                );

            $channels[] = $row;
        }

        return $channels;
    }

    public function getServers(): array
    {
        $statement = $this->db->query(
            'SELECT
                server AS address,
                COUNT(*) AS channels,
                MAX(updated_at) AS updated_at
             FROM master_dtv
             GROUP BY server
             ORDER BY server ASC'
        );

        $servers = [];

        while (
            $row = $statement->fetch(
                PDO::FETCH_ASSOC
            )
        ) {
            $servers[] = [
                'address' => (string) (
                    $row['address']
                    ?? ''
                ),

                'channels' => (int) (
                    $row['channels']
                    ?? 0
                ),

                'updated_at' => (string) (
                    $row['updated_at']
                    ?? ''
                ),

                /*
                 * Для существующего view.php.
                 */
                'online' => true,
                'error' => '',
            ];
        }

        return $servers;
    }


    public function updateInfo(
        int $id,
        int $lcn,
        string $distrib,
        float $summ,
        string $info
    ): void {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Некорректный ID телеканала.'
            );
        }

        if ($lcn < 0 || $lcn > 999) {
            throw new InvalidArgumentException(
                'LCN должен быть от 0 до 999.'
            );
        }

        $distrib = trim($distrib);
        $info = trim($info);

        if (mb_strlen($distrib, 'UTF-8') > 100) {
            throw new InvalidArgumentException(
                'Название дистрибьютора слишком длинное.'
            );
        }

        if (mb_strlen($info, 'UTF-8') > 100) {
            throw new InvalidArgumentException(
                'Дополнительная информация — максимум 100 символов.'
            );
        }

        if ($summ < 0) {
            throw new InvalidArgumentException(
                'Сумма не может быть отрицательной.'
            );
        }

        $statement = $this->db->prepare(
            'UPDATE master_dtv
            SET
                lcn = :lcn,
                distrib = :distrib,
                summ = :summ,
                info = :info
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'lcn' => $lcn,
            'distrib' => $distrib,
            'summ' => number_format(
                $summ,
                2,
                '.',
                ''
            ),
            'info' => $info,
        ]);

        if ($statement->rowCount() === 0) {
            $check = $this->db->prepare(
                'SELECT id
                FROM master_dtv
                WHERE id = :id
                LIMIT 1'
            );

            $check->execute([
                'id' => $id,
            ]);

            if ($check->fetchColumn() === false) {
                throw new RuntimeException(
                    'Телеканал не найден.'
                );
            }
        }
    }



    private function decodeArray(
        string $value
    ): array {
        if ($value === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return is_array($decoded)
                ? array_values($decoded)
                : [];
        } catch (Throwable $exception) {
            return [];
        }
    }
}




