<?php

declare(strict_types=1);


final class KarandashRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(string $address, string $descr): void
    {
        $timestamp = time();

        $statement = $this->db->prepare(
            'INSERT INTO master_karandash (
                address,
                descr,
                `time`,
                `update`
            ) VALUES (
                :address,
                :descr,
                :time,
                :update
            )
            ON DUPLICATE KEY UPDATE
                descr = VALUES(descr),
                `update` = VALUES(`update`)'
        );

        $statement->execute([
            'address' => $address,
            'descr' => $descr,
            'time' => $timestamp,
            'update' => $timestamp,
        ]);
    }


    public function delete(string $address): void
    {
        $statement = $this->db->prepare(
            'DELETE FROM master_karandash
            WHERE address = :address'
        );

        $statement->execute([
            'address' => $address,
        ]);
    }    


    public function exists(string $address): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1
             FROM master_karandash
             WHERE address = :address
             LIMIT 1'
        );

        $statement->execute([
            'address' => $address,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findByAddress(string $address): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                id,
                address,
                descr,
                `time`,
                `update`
            FROM master_karandash
            WHERE address = :address
            LIMIT 1'
        );

        $statement->execute([
            'address' => $address,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function groupedByHouse(): array
    {
        $statement = $this->db->query(
            'SELECT
                k.id,
                k.address,
                k.descr,
                k.`time`,
                k.`update`,

                COALESCE((
                    SELECT db.account
                    FROM master_database AS db
                    WHERE db.address = k.address
                    ORDER BY
                        CASE
                            WHEN db.`update` REGEXP \'^[0-9]+$\'
                            THEN CAST(db.`update` AS UNSIGNED)
                            ELSE 0
                        END DESC,
                        db.id DESC
                    LIMIT 1
                ), \'\') AS account

            FROM master_karandash AS k
            ORDER BY
                k.address ASC,
                k.`update` DESC,
                k.id DESC'
        );

        $houses = [];
        $total = 0;

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $address = trim(
                (string) ($row['address'] ?? '')
            );

            if ($address === '') {
                continue;
            }

            $house = $this->extractHouse($address);

            if (!isset($houses[$house])) {
                $houses[$house] = [
                    'house' => $house,
                    'items' => [],
                ];
            }

            $row['apartment'] = $this->extractApartment($address);

            $houses[$house]['items'][] = $row;
            $total++;
        }

        uksort($houses, 'strnatcasecmp');

        foreach ($houses as &$house) {
            usort(
                $house['items'],
                static function (array $a, array $b): int {
                    $apartmentA = (string) (
                        $a['apartment'] ?? ''
                    );

                    $apartmentB = (string) (
                        $b['apartment'] ?? ''
                    );

                    return strnatcasecmp(
                        $apartmentA,
                        $apartmentB
                    );
                }
            );
        }

        unset($house);

        return [
            'houses' => array_values($houses),
            'total' => $total,
        ];
    }

    private function extractHouse(string $address): string
    {
        $parts = explode('-', trim($address));

        if (count($parts) < 3) {
            return trim($address);
        }

        array_pop($parts);

        return trim(
            implode('-', $parts),
            " \t\n\r\0\x0B-"
        );
    }

    private function extractApartment(string $address): string
    {
        $parts = explode('-', trim($address));

        if (count($parts) < 3) {
            return '';
        }

        return trim(
            (string) array_pop($parts)
        );
    }
}




