<?php

declare(strict_types=1);

final class HouseRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function get(string $house): array
    {
        $house = trim($house);

        if ($house === '') {
            return [];
        }

        $statement = $this->db->prepare(
            'SELECT
                house,
                group_size,
                control,
                descr
            FROM master_doma
            WHERE house = :house
            LIMIT 1'
        );

        $statement->execute([
            'house' => $house,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $row
            : [];
    }

    public function getSize(
        string $house,
        int $default = 4
    ): int {
        $house = trim($house);

        if ($house === '') {
            return $default;
        }

        $statement = $this->db->prepare(
            'SELECT group_size
            FROM master_doma
            WHERE house = :house
            LIMIT 1'
        );

        $statement->execute([
            'house' => $house,
        ]);

        $value = $statement->fetchColumn();

        if ($value === false) {
            return $default;
        }

        $groupSize = (int) $value;

        if ($groupSize < 0 || $groupSize > 6) {
            return $default;
        }

        return $groupSize;
    }

    public function saveSize(
        string $house,
        int $groupSize
    ): void {
        $house = trim($house);

        if ($house === '') {
            throw new InvalidArgumentException(
                'Название дома не указано.'
            );
        }

        if ($groupSize < 0 || $groupSize > 6) {
            throw new InvalidArgumentException(
                'Размер группы должен быть от 0 до 6.'
            );
        }

        $statement = $this->db->prepare(
            'INSERT INTO master_doma (
                house,
                group_size
            ) VALUES (
                :house,
                :group_size
            )
            ON DUPLICATE KEY UPDATE
                group_size = VALUES(group_size),
                updated_at = CURRENT_TIMESTAMP'
        );

        $statement->bindValue(
            ':house',
            $house,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':group_size',
            $groupSize,
            PDO::PARAM_INT
        );

        $statement->execute();
    }



    public function saveDescr(
        string $house,
        string $descr
    ): void {
        $house = trim($house);
        $descr = trim($descr);

        if ($house === '') {
            throw new InvalidArgumentException(
                'Название дома не указано.'
            );
        }

        $statement = $this->db->prepare(
            'INSERT INTO master_doma (
                house,
                group_size,
                descr
            ) VALUES (
                :house,
                4,
                :descr
            )
            ON DUPLICATE KEY UPDATE
                descr = VALUES(descr),
                updated_at = CURRENT_TIMESTAMP'
        );

        $statement->execute([
            'house' => $house,
            'descr' => $descr,
        ]);
    }


    public function control(string $house): string
    {
        $house = trim($house);

        if ($house === '') {
            throw new InvalidArgumentException(
                'Название дома не указано.'
            );
        }

        $statement = $this->db->prepare(
            'INSERT INTO master_doma (
                house,
                group_size,
                control
            ) VALUES (
                :house,
                4,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                control = NOW(),
                updated_at = CURRENT_TIMESTAMP'
        );

        $statement->execute([
            'house' => $house,
        ]);

        $statement = $this->db->prepare(
            'SELECT control
            FROM master_doma
            WHERE house = :house
            LIMIT 1'
        );

        $statement->execute([
            'house' => $house,
        ]);

        $value = $statement->fetchColumn();

        return $value === false
            ? ''
            : (string) $value;
    }
}

