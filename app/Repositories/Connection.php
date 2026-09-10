<?php

declare(strict_types=1);

final class ConnectionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function list(
        string $search,
        string $status,
        int $limit,
        int $offset
    ): array {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(
                abonent LIKE :search_abonent
                OR address LIKE :search_address
                OR `desc` LIKE :search_desc
            )';

            $searchValue = '%' . $search . '%';

            $params = [
                'search_abonent' => $searchValue,
                'search_address' => $searchValue,
                'search_desc' => $searchValue,
            ];
        }

        if ($status === 'open') {
            $where[] = "COALESCE(result, '') = ''";
        }

        if ($status === 'done') {
            $where[] = "COALESCE(result, '') <> ''";
        }

        $sqlWhere = $where
            ? ' WHERE ' . implode(' AND ', $where)
            : '';

        $count = $this->db->prepare(
            'SELECT COUNT(*)
            FROM master_podkluchki'
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
            'SELECT *
            FROM master_podkluchki'
            . $sqlWhere
            . '
            ORDER BY time DESC, id DESC
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
            'rows' => $query->fetchAll(),
            'total' => (int) $count->fetchColumn(),
        ];
    }

    public function find(int $id): ?array
    {
        $q = $this->db->prepare('SELECT * FROM master_podkluchki WHERE id = :id');
        $q->execute(['id' => $id]);
        return $q->fetch() ?: null;
    }

    public function create(array $data): void
    {
        $q = $this->db->prepare('INSERT INTO master_podkluchki (`time`, abonent, address, other, `desc`, master, who, result) VALUES (NOW(), :abonent, :address, :other, :description, \'\', :who, \'\')');
        $q->execute($data);
    }

    public function complete(int $id, string $master, string $result): void
    {
        $q = $this->db->prepare('UPDATE master_podkluchki SET master = :master, result = :result WHERE id = :id');
        $q->execute(compact('id', 'master', 'result'));
    }

    public function withdraw(int $id, string $master): void
    {
        $query = $this->db->prepare(
            'UPDATE master_podkluchki
            SET master = :master,
                result = :result
            WHERE id = :id
            AND COALESCE(result, \'\') = \'\''
        );

        $query->execute([
            'id' => $id,
            'master' => $master,
            'result' => 'СНЯТО',
        ]);
    }
}

