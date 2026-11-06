<?php

declare(strict_types=1);


final class SmsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function list(
        string $search,
        int $limit,
        int $offset
    ): array {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $where = ['(
                abonent LIKE :abonent
                OR address LIKE :address
                OR phone LIKE :phone
            )'];

            $value = '%' . trim($search) . '%';

            $params = [
                'abonent' => $value,
                'address' => $value,
                'phone' => $value,
            ];
        }

        $sqlWhere = $where
            ? ' WHERE ' . implode(' AND ', $where)
            : '';

        $count = $this->db->prepare(
            'SELECT COUNT(*)
             FROM master_sms'
            . $sqlWhere
        );

        foreach ($params as $key => $value) {
            $count->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $count->execute();

        $query = $this->db->prepare(
            'SELECT
                id,
                abonent,
                address,
                phone,
                message,
                message_id,
                status,
                substatus,
                msg_status,
                status_text,
                sent_at,
                checked_at,
                deleted_at
             FROM master_sms'
            . $sqlWhere
            . '
             ORDER BY sent_at DESC, id DESC
             LIMIT :limit
             OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $query->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $query->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $query->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $query->execute();

        return [
            'rows' => $query->fetchAll(PDO::FETCH_ASSOC),
            'total' => (int) $count->fetchColumn(),
        ];
    }


    public function latestByAddress(
        string $address,
        int $limit = 3
    ): array {
        $address = trim($address);

        if ($address === '') {
            return [];
        }

        $limit = max(1, min(10, $limit));

        $statement = $this->db->prepare(
            'SELECT
                phone,
                message,
                status,
                sent_at
            FROM master_sms
            WHERE address = :address
            AND deleted_at IS NULL
            ORDER BY sent_at DESC, id DESC
            LIMIT :limit'
        );

        $statement->bindValue(
            ':address',
            $address,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }



    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT *
             FROM master_sms
             WHERE id = :id
             AND deleted_at IS NULL
             LIMIT 1'
        );

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $row
            : null;
    }


    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Некорректный идентификатор SMS.'
            );
        }

        $statement = $this->db->prepare(
            'UPDATE master_sms
            SET deleted_at = NOW()
            WHERE id = :id
            AND deleted_at IS NULL'
        );

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new RuntimeException(
                'SMS не найдена.'
            );
        }
    }


    public function saveStatus(
        int $id,
        ?int $status,
        ?int $substatus,
        ?int $msgStatus,
        ?string $statusText
    ): void {
        $statement = $this->db->prepare(
            'UPDATE master_sms
             SET
                status = :status,
                substatus = :substatus,
                msg_status = :msg_status,
                status_text = :status_text,
                checked_at = NOW()
             WHERE id = :id
             AND deleted_at IS NULL'
        );

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        if ($status === null) {
            $statement->bindValue(
                ':status',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $statement->bindValue(
                ':status',
                $status,
                PDO::PARAM_INT
            );
        }

        if ($substatus === null) {
            $statement->bindValue(
                ':substatus',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $statement->bindValue(
                ':substatus',
                $substatus,
                PDO::PARAM_INT
            );
        }

        if ($msgStatus === null) {
            $statement->bindValue(
                ':msg_status',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $statement->bindValue(
                ':msg_status',
                $msgStatus,
                PDO::PARAM_INT
            );
        }

        if ($statusText === null) {
            $statement->bindValue(
                ':status_text',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $statement->bindValue(
                ':status_text',
                $statusText,
                PDO::PARAM_STR
            );
        }

        $statement->execute();
    }
}

