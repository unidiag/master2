<?php

declare(strict_types=1);



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


private function latestUpdates(int $limit = 2): array
{
    $limit = max(1, min(10, $limit));

    $statement = $this->db->query(
        "SELECT DISTINCT `update`
         FROM master_database
         WHERE `update` IS NOT NULL
           AND `update` <> ''
         ORDER BY `update` DESC
         LIMIT " . $limit
    );

    return array_values(
        array_filter(
            array_map(
                static fn($value): string => trim((string) $value),
                $statement->fetchAll(PDO::FETCH_COLUMN)
            ),
            static fn(string $value): bool => $value !== ''
        )
    );
}


public function graph(): array
{
    $tariffs = [
        'Государственные каналы',
        'Аналоговый пакет',
        'Цифровой пакет',
        'IPTV'
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
              'Цифровой пакет',
              'IPTV'
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
                    'IPTV' => 0,
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


        

public function list(
    string $search,
    bool $withoutCharges,
    bool $withoutPayments,
    int $limit,
    int $offset
): array {
    $update = $this->latestUpdate();

    $where = 'current_db.`update` = :update';

    $params = [
        'update' => $update,
    ];

    /*
     * Поиск.
     */
    if ($search !== '') {
        $search = trim($search);

        $where .= '
            AND (
                current_db.personal LIKE :personal
                OR current_db.account LIKE :account
                OR current_db.address LIKE :address
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
                OR current_db.phone LIKE :phone
            ';

            $params['phone'] = '%' . $phoneSearch . '%';
        }

        $where .= '
            )
        ';
    }

    /*
     * Если включён хотя бы один фильтр:
     *
     *   Без начислений
     *   Без оплаты
     *
     * рассматриваем только абонентов с нужными тарифами.
     */
    if ($withoutCharges || $withoutPayments) {
        $where .= '
            AND current_db.tarif IN (
                \'Цифровой пакет\',
                \'Аналоговый пакет\',
                \'IPTV\'
            )
        ';
    }


    /*
     * ============================================================
     * БЕЗ НАЧИСЛЕНИЙ + БЕЗ ОПЛАТЫ
     * ============================================================
     *
     * Если включены обе кнопки, summ не должен был
     * изменяться вообще ни в одном снимке.
     *
     * Пример:
     *
     *     100 -> 100 -> 100 -> 100   подходит
     *     100 -> 120 -> 120 -> 120   не подходит
     *     100 -> 80  -> 80  -> 80    не подходит
     *     100 -> 120 -> 100 -> 100   не подходит
     *
     * Для этого LAG не нужен.
     *
     * Если MIN(summ) = MAX(summ), значит сумма
     * была одинаковой во всех снимках.
     */
    if (
        $withoutCharges
        && $withoutPayments
    ) {
        $where .= '
            AND current_db.personal IN (
                SELECT history_db.personal
                FROM master_database AS history_db
                WHERE history_db.personal IS NOT NULL
                AND history_db.personal <> \'\'
                GROUP BY history_db.personal
                HAVING
                    MIN(
                        CAST(
                            REPLACE(
                                REPLACE(
                                    TRIM(history_db.summ),
                                    \' \',
                                    \'\'
                                ),
                                \',\',
                                \'.\'
                            ) AS DECIMAL(20,4)
                        )
                    )
                    =
                    MAX(
                        CAST(
                            REPLACE(
                                REPLACE(
                                    TRIM(history_db.summ),
                                    \' \',
                                    \'\'
                                ),
                                \',\',
                                \'.\'
                            ) AS DECIMAL(20,4)
                        )
                    )
            )
        ';
    } elseif ($withoutCharges) {
        /*
         * ========================================================
         * БЕЗ НАЧИСЛЕНИЙ
         * ========================================================
         *
         * Начисление:
         *
         *     новый summ > предыдущий summ
         *
         * Нам нужны абоненты, у которых за всю историю
         * такого изменения ни разу не происходило.
         *
         * Поэтому получаем через LAG предыдущий summ
         * каждого снимка и исключаем personal, у которых
         * обнаружено увеличение.
         */
        $where .= '
            AND current_db.personal NOT IN (
                SELECT changed.personal
                FROM (
                    SELECT
                        ordered_history.personal,
                        ordered_history.summ_value,
                        LAG(
                            ordered_history.summ_value
                        ) OVER (
                            PARTITION BY ordered_history.personal
                            ORDER BY ordered_history.update_value
                        ) AS previous_summ
                    FROM (
                        SELECT
                            history_db.personal,
                            history_db.`update` AS update_value,
                            CAST(
                                REPLACE(
                                    REPLACE(
                                        TRIM(history_db.summ),
                                        \' \',
                                        \'\'
                                    ),
                                    \',\',
                                    \'.\'
                                ) AS DECIMAL(20,4)
                            ) AS summ_value
                        FROM master_database AS history_db
                        WHERE history_db.personal IS NOT NULL
                        AND history_db.personal <> \'\'
                    ) AS ordered_history
                ) AS changed
                WHERE changed.previous_summ IS NOT NULL
                AND changed.summ_value > changed.previous_summ
            )
        ';
    } elseif ($withoutPayments) {
        /*
         * ========================================================
         * БЕЗ ОПЛАТЫ
         * ========================================================
         *
         * Оплата:
         *
         *     новый summ < предыдущий summ
         *
         * Нам нужны абоненты, у которых задолженность
         * ни разу не уменьшалась.
         */
        $where .= '
            AND current_db.personal NOT IN (
                SELECT changed.personal
                FROM (
                    SELECT
                        ordered_history.personal,
                        ordered_history.summ_value,
                        LAG(
                            ordered_history.summ_value
                        ) OVER (
                            PARTITION BY ordered_history.personal
                            ORDER BY ordered_history.update_value
                        ) AS previous_summ
                    FROM (
                        SELECT
                            history_db.personal,
                            history_db.`update` AS update_value,
                            CAST(
                                REPLACE(
                                    REPLACE(
                                        TRIM(history_db.summ),
                                        \' \',
                                        \'\'
                                    ),
                                    \',\',
                                    \'.\'
                                ) AS DECIMAL(20,4)
                            ) AS summ_value
                        FROM master_database AS history_db
                        WHERE history_db.personal IS NOT NULL
                        AND history_db.personal <> \'\'
                    ) AS ordered_history
                ) AS changed
                WHERE changed.previous_summ IS NOT NULL
                AND changed.summ_value < changed.previous_summ
            )
        ';
    }

    /*
     * Количество записей.
     */
    $count = $this->db->prepare(
        'SELECT COUNT(*)
        FROM master_database AS current_db
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

    /*
     * Текущая страница.
     */
    $query = $this->db->prepare(
        'SELECT current_db.*
        FROM master_database AS current_db
        WHERE ' . $where . '
        ORDER BY
            CASE
                WHEN TRIM(current_db.tarif) = \'Нет договора\'
                THEN 1
                ELSE 0
            END ASC,
            CAST(
                REPLACE(
                    REPLACE(
                        TRIM(current_db.summ),
                        \' \',
                        \'\'
                    ),
                    \',\',
                    \'.\'
                ) AS DECIMAL(20,4)
            ) DESC,
            current_db.id DESC
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
            } elseif ($tariff === 'IPTV') {
                $houses[$house]['iptv_package']++;
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

        /*
        * Тариф считается действующим, если это не
        * "Нет договора", не "Пустая" и не пустая строка.
        */
        $isActiveTariff = static function (string $tariff): bool {
            $tariff = trim($tariff);

            return !in_array(
                $tariff,
                [
                    '',
                    'Нет договора',
                    'Пустая',
                ],
                true
            );
        };

        /*
        * Добавляет строку к уже существующей,
        * не создавая повторов.
        *
        * Например:
        *
        * Иванов Иван / Петров Пётр
        */
        $appendValue = static function (
            string $current,
            string $value
        ): string {
            $current = trim($current);
            $value = trim($value);

            if ($value === '') {
                return $current;
            }

            if ($current === '') {
                return $value;
            }

            $values = array_map(
                'trim',
                explode(' / ', $current)
            );

            if (in_array($value, $values, true)) {
                return $current;
            }

            return $current . ' / ' . $value;
        };

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $address = trim(
                (string) ($row['address'] ?? '')
            );

            if ($address === '') {
                continue;
            }

            $parts = explode('-', $address);

            $rawApartmentNumber = trim(
                (string) array_pop($parts)
            );

            if ($rawApartmentNumber === '') {
                continue;
            }



            $numericApartment = null;
            $apartmentNumber = $rawApartmentNumber;

            /*
            * Нормализация номера квартиры.
            *
            * Все варианты:
            *
            * 4
            * 4/
            * 4/1
            * 4/2
            * 4а
            * 4б
            * 4в
            * 4А
            * 4Б
            *
            * считаются одной физической квартирой №4.
            */
            if (
                preg_match(
                    '/^(\d+)/u',
                    $rawApartmentNumber,
                    $matches
                )
            ) {
                $numericApartment = (int) $matches[1];
                $apartmentNumber = (string) $numericApartment;
            }

            $sum = $this->parseSum(
                $row['summ'] ?? ''
            );

            $tariff = trim(
                (string) ($row['tarif'] ?? '')
            );

            //$debt = max(0, $sum);
            $debt = $sum;

            $apartment = [
                'number' => $apartmentNumber,
                'number_sort' =>
                    $numericApartment !== null
                        ? $numericApartment
                        : (int) $rawApartmentNumber,

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

                /*
                * Для объединённой карточки показываем
                * нормализованный адрес квартиры.
                */
                'address' =>
                    $numericApartment !== null
                        ? $house . '-' . $numericApartment
                        : $address,

                'karandash_descr' => trim(
                    (string) (
                        $row['karandash_descr']
                        ?? ''
                    )
                ),

                'exists' => true,

                'debt_always_zero' =>
                    (int) (
                        $row['debt_always_zero']
                        ?? 0
                    ) === 1,
            ];

            /*
            * Обычная или дробная цифровая квартира.
            */
            if ($numericApartment !== null) {
                $number = $numericApartment;

                if ($number <= 0) {
                    continue;
                }

                $maxApartmentNumber = max(
                    $maxApartmentNumber,
                    $number
                );

                /*
                * Первая запись этой физической квартиры.
                */
                if (!isset($apartmentsByNumber[$number])) {
                    $apartmentsByNumber[$number] = $apartment;
                    continue;
                }

                /*
                * Такая физическая квартира уже есть.
                *
                * Например:
                *
                * Панчука-2-4
                * Панчука-2-4/
                * Панчука-2-4/1
                */
                $current = &$apartmentsByNumber[$number];

                /*
                * ФИО всех абонентов выводим через "/".
                */
                $current['subscriber'] = $appendValue(
                    (string) ($current['subscriber'] ?? ''),
                    (string) ($apartment['subscriber'] ?? '')
                );

                /*
                * Если телефоны понадобятся дальше —
                * тоже сохраняем все.
                */
                $current['phone'] = $appendValue(
                    (string) ($current['phone'] ?? ''),
                    (string) ($apartment['phone'] ?? '')
                );

                /*
                * Общая задолженность физической квартиры.
                */
                $current['debt'] =
                    (float) ($current['debt'] ?? 0)
                    + (float) ($apartment['debt'] ?? 0);

                /*
                * Заметки "карандаш" также не теряем.
                */
                $current['karandash_descr'] = $appendValue(
                    (string) (
                        $current['karandash_descr']
                        ?? ''
                    ),
                    (string) (
                        $apartment['karandash_descr']
                        ?? ''
                    )
                );

                /*
                * Если хотя бы у одной дроби есть
                * debt_always_zero — отмечаем всю карточку.
                */
                $current['debt_always_zero'] =
                    (bool) (
                        $current['debt_always_zero']
                        ?? false
                    )
                    ||
                    (bool) (
                        $apartment['debt_always_zero']
                        ?? false
                    );

                /*
                * Самая важная часть.
                *
                * Если текущая запись была без договора,
                * а одна из дробей подключена —
                * берём тариф подключённой дроби.
                *
                * Вместе с тарифом сохраняем её personal,
                * чтобы клик по карточке открывал
                * существующий лицевой счёт.
                */
                if (
                    !$isActiveTariff(
                        (string) ($current['tariff'] ?? '')
                    )
                    &&
                    $isActiveTariff(
                        (string) ($apartment['tariff'] ?? '')
                    )
                ) {
                    $current['tariff'] =
                        $apartment['tariff'];

                    $current['personal'] =
                        $apartment['personal'];
                }

                unset($current);

                continue;
            }

            /*
            * Остальные нестандартные номера:
            *
            * 12А
            * 12Б
            * и т.п.
            *
            * Их оставляем отдельными карточками.
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
            db.phone,
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
            'phone' => trim(
                (string) ($row['phone'] ?? '')
            ),
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
