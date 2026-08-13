<?php

declare(strict_types=1);

final class TicketRepository
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
                OR abonent_ajax LIKE :search_abonent_ajax
                OR address LIKE :search_address
                OR address_ajax LIKE :search_address_ajax
                OR `desc` LIKE :search_desc
            )';

            $searchValue = '%' . $search . '%';

            $params = [
                'search_abonent' => $searchValue,
                'search_abonent_ajax' => $searchValue,
                'search_address' => $searchValue,
                'search_address_ajax' => $searchValue,
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
            FROM master_zayavki'
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
            FROM master_zayavki'
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
        $q = $this->db->prepare('SELECT * FROM master_zayavki WHERE id = :id');
        $q->execute(['id' => $id]);
        return $q->fetch() ?: null;
    }


    public function countByAddress(string $address): int
    {
        $address = trim($address);

        if ($address === '') {
            return 0;
        }

        $statement = $this->db->prepare(
            'SELECT COUNT(*)
            FROM master_zayavki
            WHERE address = :address
            OR address_ajax = :address_ajax'
        );

        $statement->execute([
            'address' => $address,
            'address_ajax' => $address,
        ]);

        return (int) $statement->fetchColumn();
    }

    

    public function create(array $data): void
    {
        $q = $this->db->prepare('INSERT INTO master_zayavki (`time`, abonent, abonent_ajax, address, address_ajax, other, `desc`, result, master, cost, who) VALUES (NOW(), :abonent, :abonent_ajax, :address, :address_ajax, :other, :description, \'\', \'\', :cost, :who)');
        $q->execute($data);
    }

    public function complete(int $id, string $master, string $result, string $cost): void
    {
        $q = $this->db->prepare('UPDATE master_zayavki SET master = :master, result = :result, cost = :cost WHERE id = :id');
        $q->execute(compact('id', 'master', 'result', 'cost'));
    }

    public function withdraw(int $id, string $master): void
    {
        $query = $this->db->prepare(
            'UPDATE master_zayavki
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

final class SubscriberRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function latestUpdate()
    {
        $stmt = $this->db->query(
            "SELECT `update`
            FROM master_database
            WHERE `update` REGEXP '^[0-9]+$'
            ORDER BY CAST(`update` AS UNSIGNED) DESC
            LIMIT 1"
        );

        return (string) $stmt->fetchColumn();
    }





public function graph(): array
{
    $tariffs = [
        'Государственные каналы',
        'Аналоговый пакет',
        'Цифровой пакет',
    ];

    $statement = $this->db->query(
        "SELECT
            CAST(`update` AS UNSIGNED) AS update_ts,
            TRIM(tarif) AS tariff,
            COUNT(*) AS subscribers,
            SUM(
                CASE
                    WHEN summ > 0
                    THEN summ
                    ELSE 0
                END
            ) AS debt
        FROM master_database
        WHERE `update` REGEXP '^[0-9]+$'
          AND TRIM(tarif) IN (
              'Государственные каналы',
              'Аналоговый пакет',
              'Цифровой пакет'
          )
        GROUP BY
            CAST(`update` AS UNSIGNED),
            TRIM(tarif)
        ORDER BY
            CAST(`update` AS UNSIGNED) ASC"
    );

    $snapshots = [];

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $update = (int) ($row['update_ts'] ?? 0);
        $tariff = trim((string) ($row['tariff'] ?? ''));

        if ($update <= 0 || !in_array($tariff, $tariffs, true)) {
            continue;
        }

        if (!isset($snapshots[$update])) {
            $snapshots[$update] = [
                'update' => $update,
                'packages' => [
                    'Государственные каналы' => 0,
                    'Аналоговый пакет' => 0,
                    'Цифровой пакет' => 0,
                ],
                'total' => 0,
                'debt' => 0.0,
            ];
        }

        $subscribers = (int) ($row['subscribers'] ?? 0);
        $debt = (float) ($row['debt'] ?? 0);

        $snapshots[$update]['packages'][$tariff] = $subscribers;
        $snapshots[$update]['total'] += $subscribers;

        $snapshots[$update]['debt'] += $debt;
    }

    return [
        'snapshots' => array_values($snapshots),
        'tariffs' => $tariffs,
    ];
}


    public function importDatabaseFile(
        string $filename
    ): array {
        if (
            $filename === ''
            || !is_file($filename)
            || !is_readable($filename)
        ) {
            throw new RuntimeException(
                'Не удалось прочитать загруженный файл.'
            );
        }

        $contents = file_get_contents($filename);

        if ($contents === false) {
            throw new RuntimeException(
                'Не удалось прочитать содержимое файла.'
            );
        }

        /*
        * Старый экспорт приходит в Windows-1251.
        */
        $converted = iconv(
            'Windows-1251',
            'UTF-8//IGNORE',
            $contents
        );

        if ($converted === false) {
            throw new RuntimeException(
                'Не удалось преобразовать файл из Windows-1251.'
            );
        }

        /*
        * Разбираем любые варианты CRLF / LF / CR.
        */
        $lines = preg_split(
            '/\R/u',
            $converted
        );

        if ($lines === false || !$lines) {
            throw new RuntimeException(
                'Файл не содержит данных.'
            );
        }

        $update = time();

        $insert = $this->db->prepare(
            'INSERT INTO master_database (
                personal,
                account,
                address,
                phone,
                period,
                summ,
                tarif_id,
                tarif,
                time,
                `update`
            ) VALUES (
                :personal,
                :account,
                :address,
                :phone,
                :period,
                :summ,
                :tarif_id,
                :tarif,
                :time,
                :update
            )'
        );

        $inserted = 0;
        $skipped = 0;

        $this->db->beginTransaction();

        try {
            foreach ($lines as $index => $line) {
                /*
                * Первая строка — заголовок.
                */
                if ($index === 0) {
                    continue;
                }

                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $fields = explode(
                    '^',
                    $line
                );

                /*
                * Старый формат использует как минимум
                * поля до индекса 11 включительно.
                */
                if (count($fields) < 12) {
                    $skipped++;
                    continue;
                }

                $personal = trim(
                    (string) $fields[1]
                );

                $account = trim(
                    (string) $fields[2]
                );

                $address = trim(
                    (string) $fields[3]
                );

                $period = trim(
                    (string) $fields[4]
                );

                $summ = $this->normalizeImportedSum(
                    (string) $fields[5]
                );

                $time = $this->parseImportedDate(
                    (string) $fields[7]
                );

                $tariffId = trim(
                    (string) $fields[10]
                );

                $tariff = trim(
                    (string) $fields[11]
                );

                $phone = '';

                $phone = '';

                if (isset($fields[12])) {
                    $phone = $this->normalizeImportedPhones(
                        (string) $fields[12]
                    );
                }

                /*
                * Лицевой счёт и адрес считаем
                * минимально необходимыми данными.
                */
                if (
                    $personal === ''
                    && $address === ''
                ) {
                    $skipped++;
                    continue;
                }

                $insert->execute([
                    'personal' => $personal,
                    'account' => $account,
                    'address' => $address,
                    'phone' => $phone !== ''
                        ? $phone
                        : null,
                    'period' => $period,
                    'summ' => $summ,
                    'tarif_id' => $tariffId,
                    'tarif' => $tariff,
                    'time' => $time,
                    'update' => $update,
                ]);

                $inserted++;
            }

            if ($inserted === 0) {
                throw new RuntimeException(
                    'В файле не найдено ни одной корректной записи.'
                );
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
            'skipped' => $skipped,
            'update' => $update,
        ];
    }





    private function normalizeImportedPhones(
        string $value
    ): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $phones = explode(',', $value);

        $result = [];

        foreach ($phones as $phone) {
            $phone = preg_replace(
                '/\D+/',
                '',
                trim($phone)
            ) ?? '';

            if ($phone === '') {
                continue;
            }

            if (!preg_match('/^\d{5,12}$/', $phone)) {
                continue;
            }

            $result[] = $phone;
        }

        $result = array_slice(
            array_values(array_unique($result)),
            0,
            2
        );

        return implode(',', $result);
    }



    private function parseImportedDate(
        string $value
    ): int {
        /*
        * Старый формат:
        *
        * YYYYMMDDHHMMSS
        */
        $value = trim($value);

        if (
            strlen($value) < 14
            || !ctype_digit(
                substr($value, 0, 14)
            )
        ) {
            return 0;
        }

        $year = (int) substr(
            $value,
            0,
            4
        );

        $month = (int) substr(
            $value,
            4,
            2
        );

        $day = (int) substr(
            $value,
            6,
            2
        );

        $hour = (int) substr(
            $value,
            8,
            2
        );

        $minute = (int) substr(
            $value,
            10,
            2
        );

        $second = (int) substr(
            $value,
            12,
            2
        );

        if (
            !checkdate(
                $month,
                $day,
                $year
            )
        ) {
            return 0;
        }

        return mktime(
            $hour,
            $minute,
            $second,
            $month,
            $day,
            $year
        );
    }


private function normalizeImportedSum(
    string $value
): string {
    $value = trim($value);

    if ($value === '') {
        return '0.00';
    }

    $value = str_replace(
        [
            ' ',
            "\xC2\xA0",
            ',',
        ],
        [
            '',
            '',
            '.',
        ],
        $value
    );

    if (!is_numeric($value)) {
        return '0.00';
    }

    return number_format(
        (float) $value,
        2,
        '.',
        ''
    );
}





    public function cleanupOlderThanOneYear(
        int $batchSize = 5000
    ): int {
        $cutoff = (new DateTimeImmutable('now'))
            ->modify('-1 year')
            ->getTimestamp();

        $batchSize = max(100, min(50000, $batchSize));
        $deletedTotal = 0;

        do {
            $statement = $this->db->prepare(
                "DELETE FROM master_database
                WHERE `update` REGEXP '^[0-9]+$'
                AND CAST(`update` AS UNSIGNED) < :cutoff
                LIMIT {$batchSize}"
            );

            $statement->bindValue(
                ':cutoff',
                $cutoff,
                PDO::PARAM_INT
            );

            $statement->execute();

            $deleted = $statement->rowCount();
            $deletedTotal += $deleted;
        } while ($deleted === $batchSize);

        return $deletedTotal;
    }





    public function findLatestByAddress(string $address): ?array
    {
        $address = trim($address);

        if ($address === '') {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT
                id,
                personal,
                address,
                account,
                phone,
                tarif,
                summ,
                `update`
            FROM master_database
            WHERE address = :address
            ORDER BY
                CASE
                    WHEN `update` REGEXP \'^[0-9]+$\'
                    THEN CAST(`update` AS UNSIGNED)
                    ELSE 0
                END DESC,
                id DESC
            LIMIT 1'
        );

        $statement->execute([
            'address' => $address,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $sum = $this->parseSum(
            $row['summ'] ?? ''
        );

        return [
            'personal' => trim(
                (string) ($row['personal'] ?? '')
            ),
            'address' => trim(
                (string) ($row['address'] ?? '')
            ),
            'name' => trim(
                (string) ($row['account'] ?? '')
            ),
            'phone' => trim(
                (string) ($row['phone'] ?? '')
            ),
            'tariff' => trim(
                (string) ($row['tarif'] ?? '')
            ),
            'debt' => max(0, $sum),
            'balance' => $sum,
        ];
    }


        

public function list(string $search, int $limit, int $offset): array
{
    $update = $this->latestUpdate();

    $where = '`update` = :update';

    $params = [
        'update' => $update,
    ];

    if ($search !== '') {
        $search = trim($search);

        $where .= '
            AND (
                personal LIKE :personal
                OR account LIKE :account
                OR address LIKE :address
        ';

        $params['personal'] = $search . '%';
        $params['account'] = $search . '%';
        $params['address'] = '%' . $search . '%';

        $phoneSearch = preg_replace(
            '/\D+/',
            '',
            $search
        ) ?? '';

        if ($phoneSearch !== '') {
            $where .= '
                OR phone LIKE :phone
            ';

            $params['phone'] = '%' . $phoneSearch . '%';
        }

        $where .= '
            )
        ';
    }

    $count = $this->db->prepare(
        'SELECT COUNT(*)
        FROM master_database
        WHERE ' . $where
    );

    foreach ($params as $key => $value) {
        $count->bindValue(
            ':' . $key,
            $value,
            PDO::PARAM_STR
        );
    }

    $count->execute();

    $total = (int) $count->fetchColumn();

    $query = $this->db->prepare(
        'SELECT *
        FROM master_database
        WHERE ' . $where . '
        ORDER BY
            CASE
                WHEN TRIM(tarif) = \'Нет договора\' THEN 1
                ELSE 0
            END ASC,
            CAST(summ AS SIGNED) DESC
        LIMIT :limit OFFSET :offset'
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
        'total' => $total,
        'update' => $update,
    ];
}

    public function history(string $personal): array
    {
        $q = $this->db->prepare('SELECT * FROM master_database WHERE personal = :personal ORDER BY id DESC LIMIT 50');
        $q->execute(['personal' => $personal]);
        return $q->fetchAll();
    }









    public function houses(): array
    {
        $update = $this->latestUpdate();

        if ($update === '') {
            return [
                'update' => '',
                'houses' => [],
                'total' => 0,
            ];
        }

        $statement = $this->db->prepare(
            'SELECT
                db.id,
                db.personal,
                db.account,
                db.address,
                db.period,
                db.summ,
                db.tarif_id,
                db.tarif,
                db.time,
                db.`update`,
                k.descr AS karandash_descr
            FROM master_database AS db
            LEFT JOIN master_karandash AS k
                ON k.address = db.address
            WHERE db.`update` = :update
            ORDER BY db.address ASC, db.id ASC'
        );

        $statement->execute([
            'update' => $update,
        ]);

        $houses = [];
        $total = 0;

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $address = trim((string) ($row['address'] ?? ''));

            if ($address === '') {
                continue;
            }

            $house = $this->extractHouse($address);

            if ($house === '') {
                continue;
            }

            if (!isset($houses[$house])) {
                $houses[$house] = [
                    'house' => $house,
                    'subscribers' => 0,
                    'debt_total' => 0,
                    'debtors' => 0,
                    'good_payers' => 0,
                    'overpayment' => 0,
                    'state_channels' => 0,
                    'analog_package' => 0,
                    'digital_package' => 0,
                    'karandash' => 0,
                    'update' => $update,
                    'time' => '',
                ];
            }

            $sum = $this->parseSum($row['summ'] ?? '');
            $tariff = trim((string) ($row['tarif'] ?? ''));

            $houses[$house]['subscribers']++;

            if (
                trim((string) ($row['karandash_descr'] ?? '')) !== ''
            ) {
                $houses[$house]['karandash']++;
            }

            if ($tariff !== 'Нет договора') {
                if ($sum > 0) {
                    // Задолженность только по действующим договорам.
                    $houses[$house]['debt_total'] += $sum;
                    $houses[$house]['debtors']++;
                } elseif ($sum < 0) {
                    $houses[$house]['overpayment']++;
                } else {
                    $houses[$house]['good_payers']++;
                }
            }

            $tariff = trim((string) ($row['tarif'] ?? ''));

            if ($tariff === 'Государственные каналы') {
                $houses[$house]['state_channels']++;
            } elseif ($tariff === 'Аналоговый пакет') {
                $houses[$house]['analog_package']++;
            } elseif ($tariff === 'Цифровой пакет') {
                $houses[$house]['digital_package']++;
            }

            $rowTime = trim((string) ($row['time'] ?? ''));

            if (
                $rowTime !== ''
                && (
                    $houses[$house]['time'] === ''
                    || (int) $rowTime > (int) $houses[$house]['time']
                )
            ) {
                $houses[$house]['time'] = $rowTime;
            }

            $total++;
        }





        foreach ($houses as &$house) {
            $house['debt'] = $house['debt_total'];
        }

        $controlsStatement = $this->db->query(
            'SELECT house, control
            FROM master_doma
            WHERE control IS NOT NULL'
        );

        $controls = [];

        while (
            $controlRow = $controlsStatement->fetch(
                PDO::FETCH_ASSOC
            )
        ) {
            $controlHouse = trim(
                (string) ($controlRow['house'] ?? '')
            );

            if ($controlHouse === '') {
                continue;
            }

            $controls[$controlHouse] = trim(
                (string) ($controlRow['control'] ?? '')
            );
        }

        foreach ($houses as $houseName => &$house) {
            $house['control'] =
                $controls[$houseName] ?? '';
        }

        unset($house);


        uksort($houses, 'strnatcasecmp');

        return [
            'update' => $update,
            'houses' => array_values($houses),
            'total' => $total,
        ];
    }








    public function apartments($house)
    {
        $house = trim((string) $house);
        $update = $this->latestUpdate();

        if ($house === '' || $update === '') {
            return [
                'update' => $update,
                'house' => $house,
                'apartments' => [],
            ];
        }

        $statement = $this->db->prepare(
            'SELECT
                db.id,
                db.personal,
                db.account,
                db.address,
                db.phone,
                db.summ,
                db.tarif,
                db.time,
                db.`update`,
                COALESCE(k.descr, \'\') AS karandash_descr,

                CASE
                    WHEN db.tarif NOT IN (
                        \'Нет договора\',
                        \'Пустая\'
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM master_database AS history
                        WHERE history.personal = db.personal
                        AND history.tarif NOT IN (
                            \'Нет договора\',
                            \'Пустая\'
                        )
                        AND CAST(
                            REPLACE(
                                REPLACE(
                                    TRIM(history.summ),
                                    \' \',
                                    \'\'
                                ),
                                \',\',
                                \'.\'
                            ) AS DECIMAL(20,4)
                        ) <> 0
                    )
                    THEN 1
                    ELSE 0
                END AS debt_always_zero

            FROM master_database AS db
            LEFT JOIN master_karandash AS k
                ON k.address = db.address
            WHERE db.`update` = :update
            AND db.address LIKE :address
            ORDER BY db.id ASC'
        );

        $statement->execute([
            'update' => $update,
            'address' => $house . '-%',
        ]);

        $apartmentsByNumber = [];
        $additionalApartments = [];
        $maxApartmentNumber = 0;

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $address = trim((string) ($row['address'] ?? ''));

            if ($address === '') {
                continue;
            }

            $parts = explode('-', $address);
            $apartmentNumber = trim(
                (string) array_pop($parts)
            );

            if ($apartmentNumber === '') {
                continue;
            }

            $sum = $this->parseSum(
                $row['summ'] ?? ''
            );

            $tariff = trim(
                (string) ($row['tarif'] ?? '')
            );

            $debt = max(0, $sum);

            $apartment = [
                'number' => $apartmentNumber,
                'number_sort' => (int) $apartmentNumber,
                'personal' => trim(
                    (string) ($row['personal'] ?? '')
                ),
                'subscriber' => trim(
                    (string) ($row['account'] ?? '')
                ),
                'phone' => trim(
                    (string) ($row['phone'] ?? '')
                ),
                'tariff' => $tariff,
                'debt' => $debt,
                'address' => $address,
                'karandash_descr' => trim(
                    (string) ($row['karandash_descr'] ?? '')
                ),
                'exists' => true,
                'debt_always_zero' => (int) ($row['debt_always_zero'] ?? 0) === 1,
            ];

            /*
            * Обычные номера квартир: 1, 2, 3...
            */
            if (ctype_digit($apartmentNumber)) {
                $number = (int) $apartmentNumber;

                if ($number <= 0) {
                    continue;
                }

                $apartmentsByNumber[$number] = $apartment;
                $maxApartmentNumber = max(
                    $maxApartmentNumber,
                    $number
                );

                continue;
            }

            /*
            * Нестандартные номера вроде 12А, 12/1 и т.п.
            * Не теряем, но и не используем для заполнения диапазона.
            */
            $additionalApartments[] = $apartment;
        }

        $apartments = [];

        /*
        * Создаём непрерывный список квартир от 1
        * до максимального найденного номера.
        */
        for (
            $number = 1;
            $number <= $maxApartmentNumber;
            $number++
        ) {
            if (isset($apartmentsByNumber[$number])) {
                $apartments[] = $apartmentsByNumber[$number];
                continue;
            }

            $apartments[] = [
                'number' => (string) $number,
                'number_sort' => $number,
                'personal' => '',
                'subscriber' => '',
                'phone' => '',
                'tariff' => 'Нет договора',
                'debt' => 0,
                'address' => $house . '-' . $number,
                'karandash_descr' => '',
                'debt_always_zero' => false,
                'exists' => false,
            ];
        }

        /*
        * Добавляем нестандартные номера квартир.
        */
        foreach ($additionalApartments as $apartment) {
            $apartments[] = $apartment;
        }

        usort($apartments, function ($a, $b) {
            $numberA = (int) ($a['number_sort'] ?? 0);
            $numberB = (int) ($b['number_sort'] ?? 0);

            if ($numberA === $numberB) {
                return strnatcasecmp(
                    (string) ($a['number'] ?? ''),
                    (string) ($b['number'] ?? '')
                );
            }

            return $numberA <=> $numberB;
        });



        return [
            'update' => $update,
            'house' => $house,
            'apartments' => $apartments,
        ];
    }


    public function paymentHistory($personal)
    {
        $personal = trim((string) $personal);

        if ($personal === '') {
            return [
                'personal' => '',
                'subscriber' => '',
                'address' => '',
                'phone' => '',
                'operations' => [],
                'history' => [],
            ];
        }

        $statement = $this->db->prepare(
            'SELECT
                id,
                personal,
                account,
                address,
                phone,
                summ,
                tarif,
                period,
                time,
                `update`
            FROM master_database
            WHERE personal = :personal
            ORDER BY CAST(`update` AS UNSIGNED) ASC, id ASC'
        );

        $statement->execute([
            'personal' => $personal,
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $operations = [];
        $history = [];
        $previousDebtRaw = null;

        foreach ($rows as $row) {
            $debt = $this->parseSum($row['summ'] ?? '');
            $update = trim((string) ($row['update'] ?? ''));

            $history[] = [
                'update' => $update,
                'debt' => $debt,
                'period' => trim((string) ($row['period'] ?? '')),
            ];

            if ($previousDebtRaw !== null) {
                /*
                * Задолженность уменьшилась — была оплата.
                */
                if ($debt < $previousDebtRaw) {
                    $operations[] = [
                        'type' => 'payment',
                        'update' => $update,
                        'previous_debt' => $previousDebtRaw,
                        'current_debt' => $debt,
                        'amount' => $previousDebtRaw - $debt,
                        'period' => trim((string) ($row['period'] ?? '')),
                    ];
                }

                /*
                * Задолженность увеличилась — было начисление.
                */
                if ($debt > $previousDebtRaw) {
                    $operations[] = [
                        'type' => 'charge',
                        'update' => $update,
                        'previous_debt' => $previousDebtRaw,
                        'current_debt' => $debt,
                        'amount' => $debt - $previousDebtRaw,
                        'period' => trim((string) ($row['period'] ?? '')),
                    ];
                }
            }

            $previousDebtRaw = $debt;
        }

        $operations = array_reverse($operations);

        $lastRow = $rows ? $rows[count($rows) - 1] : [];
        $currentDebtRaw = $lastRow
            ? $this->parseSum($lastRow['summ'] ?? '')
            : 0;


        return [
            'personal' => $personal,
            'subscriber' => trim((string) ($lastRow['account'] ?? '')),
            'address' => trim((string) ($lastRow['address'] ?? '')),
            'phone' => trim((string) ($lastRow['phone'] ?? '')),
            'tariff' => trim((string) ($lastRow['tarif'] ?? '')),
            'debt' => max(0, $currentDebtRaw),
            'balance' => $currentDebtRaw,

            'operations' => $operations,

            'history' => $history,
        ];
    }







private function lastPaymentUpdates(array $personals): array
{
    $personals = array_values(
        array_unique(
            array_filter(
                array_map(
                    static fn($personal): string =>
                        trim((string) $personal),
                    $personals
                ),
                static fn(string $personal): bool =>
                    $personal !== ''
            )
        )
    );

    if (!$personals) {
        return [];
    }

    $result = [];

    /*
     * Запросы выполняем пакетами, чтобы не создавать
     * слишком большой список IN().
     */
    foreach (array_chunk($personals, 500) as $chunk) {
        $placeholders = implode(
            ',',
            array_fill(0, count($chunk), '?')
        );

        $statement = $this->db->prepare(
            'SELECT
                id,
                personal,
                summ,
                `update`
            FROM master_database
            WHERE personal IN (' . $placeholders . ')
            ORDER BY
                personal ASC,
                CAST(`update` AS UNSIGNED) ASC,
                id ASC'
        );

        $statement->execute(array_values($chunk));

        /*
         * Предыдущее состояние задолженности храним
         * отдельно для каждого лицевого счёта.
         */
        $previousDebtByPersonal = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $personal = trim(
                (string) ($row['personal'] ?? '')
            );

            if ($personal === '') {
                continue;
            }

            $currentDebtRaw = $this->parseSum(
                $row['summ'] ?? ''
            );

            $previousDebtRaw =
                $previousDebtByPersonal[$personal] ?? null;

            /*
            * Любое уменьшение задолженности считаем оплатой.
            * Это также учитывает аванс (отрицательную задолженность).
            */
            if (
                $previousDebtRaw !== null
                && $currentDebtRaw < $previousDebtRaw
            ) {
                $result[$personal] = trim(
                    (string) ($row['update'] ?? '')
                );
            }

            $previousDebtByPersonal[$personal] =
                $currentDebtRaw;
        }
    }

    return $result;
}







public function debtors(): array
{
    $update = $this->latestUpdate();

    if ($update === '') {
        return [
            'update' => '',
            'houses' => [],
            'debtors_total' => 0,
            'debt_total' => 0,
        ];
    }

    $statement = $this->db->prepare(
        'SELECT
            db.id,
            db.personal,
            db.account,
            db.address,
            db.summ,
            db.tarif,
            db.`update`,
            COALESCE(k.descr, \'\') AS karandash_descr
        FROM master_database AS db
        LEFT JOIN master_karandash AS k
            ON k.address = db.address
        WHERE db.`update` = :update
        AND db.tarif <> :no_contract
        ORDER BY db.id ASC'
    );

    $statement->execute([
        'update' => $update,
        'no_contract' => 'Нет договора',
    ]);

    $houses = [];
    $debtorPersonals = [];

    $debtorsTotal = 0;
    $debtTotal = 0;

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $sum = $this->parseSum(
            $row['summ'] ?? ''
        );

        if ($sum <= 0) {
            continue;
        }

        $address = trim(
            (string) ($row['address'] ?? '')
        );

        if ($address === '') {
            continue;
        }

        $houseName = $this->extractHouse($address);

        if ($houseName === '') {
            continue;
        }

        $parts = explode('-', $address);

        $apartmentNumber = trim(
            (string) array_pop($parts)
        );

        if (!isset($houses[$houseName])) {
            $houses[$houseName] = [
                'house' => $houseName,
                'debt_total' => 0,
                'items' => [],
            ];
        }

        $personal = trim(
            (string) ($row['personal'] ?? '')
        );

        if ($personal !== '') {
            /*
             * Ключ массива обеспечивает уникальность лицевых счетов.
             */
            $debtorPersonals[$personal] = $personal;
        }

        $houses[$houseName]['debt_total'] += $sum;

        $houses[$houseName]['items'][] = [
            'personal' => $personal,
            'subscriber' => trim(
                (string) ($row['account'] ?? '')
            ),
            'address' => $address,
            'apartment' => $apartmentNumber,
            'apartment_sort' => (int) $apartmentNumber,
            'tariff' => trim(
                (string) ($row['tarif'] ?? '')
            ),
            'debt' => $sum,
            'last_payment_update' => '',
            'karandash_descr' => trim(
                (string) ($row['karandash_descr'] ?? '')
            ),
        ];

        $debtorsTotal++;
        $debtTotal += $sum;
    }

    $lastPaymentUpdates = $this->lastPaymentUpdates(
        array_values($debtorPersonals)
    );

    foreach ($houses as &$houseData) {
        $items = $houseData['items'] ?? [];

        foreach ($items as &$debtor) {
            $personal = trim(
                (string) ($debtor['personal'] ?? '')
            );

            $debtor['last_payment_update'] =
                $lastPaymentUpdates[$personal] ?? '';
        }
        unset($debtor);

        usort(
            $items,
            static function (array $a, array $b): int {
                $debtA = (float) ($a['debt'] ?? 0);
                $debtB = (float) ($b['debt'] ?? 0);

                if ($debtA !== $debtB) {
                    return $debtA < $debtB ? 1 : -1;
                }

                return
                    (int) ($a['apartment_sort'] ?? 0)
                    <=>
                    (int) ($b['apartment_sort'] ?? 0);
            }
        );

        $houseData['debtors'] = $items;
        unset($houseData['items']);

        $houseData['debt'] =
            ((float) ($houseData['debt_total'] ?? 0));
    }
    unset($houseData);

    uasort(
        $houses,
        static function (array $a, array $b): int {
            $debtA = (float) ($a['debt_total'] ?? 0);
            $debtB = (float) ($b['debt_total'] ?? 0);

            if ($debtA !== $debtB) {
                return $debtA < $debtB ? 1 : -1;
            }

            return strnatcasecmp(
                (string) ($a['house'] ?? ''),
                (string) ($b['house'] ?? '')
            );
        }
    );

    return [
        'update' => $update,
        'houses' => array_values($houses),
        'debtors_total' => $debtorsTotal,
        'debt_total' => $debtTotal,
    ];
}



    

    private function extractHouse(string $address): string
    {
        $address = trim($address);

        if ($address === '') {
            return '';
        }

        $parts = explode('-', $address);

        if (count($parts) < 3) {
            return $address;
        }

        // Последняя часть адреса всегда является номером квартиры.
        array_pop($parts);

        return trim(implode('-', $parts), " \t\n\r\0\x0B-");
    }


    private function parseSum($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        return is_numeric($value)
            ? (float) $value
            : 0.0;
    }





}


// ██╗  ██╗ █████╗ ██████╗  █████╗ ███╗   ██╗██████╗  █████╗ ███████╗██╗  ██╗
// ██║ ██╔╝██╔══██╗██╔══██╗██╔══██╗████╗  ██║██╔══██╗██╔══██╗██╔════╝██║  ██║
// █████╔╝ ███████║██████╔╝███████║██╔██╗ ██║██║  ██║███████║███████╗███████║
// ██╔═██╗ ██╔══██║██╔══██╗██╔══██║██║╚██╗██║██║  ██║██╔══██║╚════██║██╔══██║
// ██║  ██╗██║  ██║██║  ██║██║  ██║██║ ╚████║██████╔╝██║  ██║███████║██║  ██║
// ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ ╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
                                                                          

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