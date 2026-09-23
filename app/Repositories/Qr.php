<?php

declare(strict_types=1);

final class QrRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByQrcode(string $qrcode): ?array
    {
        $qrcode = trim($qrcode);

        if (!preg_match('/^\d{4}$/', $qrcode)) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT
                address,
                entrance
            FROM master_qr
            WHERE qrcode = :qrcode
            LIMIT 1'
        );

        $statement->execute([
            'qrcode' => $qrcode,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row !== false
            ? $row
            : null;
    }    

    public function listByAddress(string $address): array
    {
        $address = trim($address);

        if ($address === '') {
            return [];
        }

        $statement = $this->db->prepare(
            'SELECT
                id,
                qrcode,
                address,
                entrance
            FROM master_qr
            WHERE address = :address
            ORDER BY entrance ASC, qrcode ASC, id ASC'
        );

        $statement->execute([
            'address' => $address,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }



    public function create(
        string $qrcode,
        string $address,
        int $entrance
    ): void {
        $qrcode = trim($qrcode);
        $address = trim($address);

        if (!preg_match('/^\d{4}$/', $qrcode)) {
            throw new InvalidArgumentException(
                'QR-код должен состоять ровно из 4 цифр.'
            );
        }

        if ($address === '') {
            throw new InvalidArgumentException(
                'Адрес не указан.'
            );
        }

        if ($entrance < 0 || $entrance > 20) {
            throw new InvalidArgumentException(
                'Номер подъезда должен быть от 0 до 20.'
            );
        }

        $existing = $this->findByQrcode($qrcode);

        if ($existing !== null) {
            $message =
                'QR-код '
                . $qrcode
                . ' уже используется по адресу '
                . (string) $existing['address'];

            $existingEntrance = (int) (
                $existing['entrance']
                ?? 0
            );

            if ($existingEntrance > 0) {
                $message .=
                    ', подъезд '
                    . $existingEntrance;
            }

            $message .= '.';

            throw new RuntimeException($message);
        }

        $statement = $this->db->prepare(
            'INSERT INTO master_qr (
                qrcode,
                address,
                entrance
            ) VALUES (
                :qrcode,
                :address,
                :entrance
            )'
        );

        try {
            $statement->bindValue(
                ':qrcode',
                $qrcode,
                PDO::PARAM_STR
            );

            $statement->bindValue(
                ':address',
                $address,
                PDO::PARAM_STR
            );

            $statement->bindValue(
                ':entrance',
                $entrance,
                PDO::PARAM_INT
            );

            $statement->execute();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException(
                    'QR-код ' . $qrcode . ' уже используется.'
                );
            }

            throw $exception;
        }
    }





    public function delete(
        int $id,
        string $address
    ): void {
        $address = trim($address);

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Некорректный идентификатор записи.'
            );
        }

        if ($address === '') {
            throw new InvalidArgumentException(
                'Адрес не указан.'
            );
        }

        $statement = $this->db->prepare(
            'DELETE FROM master_qr
            WHERE id = :id
              AND address = :address'
        );

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':address',
            $address,
            PDO::PARAM_STR
        );

        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new RuntimeException(
                'Запись QR не найдена.'
            );
        }
    }
}

