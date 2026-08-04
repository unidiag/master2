<?php

declare(strict_types=1);

final class TicketRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function list(string $search, string $status, int $limit, int $offset): array
    {
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(abonent LIKE :search OR abonent_ajax LIKE :search OR address LIKE :search OR address_ajax LIKE :search OR `desc` LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($status === 'open') $where[] = "COALESCE(result, '') = ''";
        if ($status === 'done') $where[] = "COALESCE(result, '') <> ''";
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $this->db->prepare('SELECT COUNT(*) FROM master_zayavki' . $sqlWhere);
        $count->execute($params);

        $query = $this->db->prepare('SELECT * FROM master_zayavki' . $sqlWhere . ' ORDER BY time DESC, id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $query->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return ['rows' => $query->fetchAll(), 'total' => (int)$count->fetchColumn()];
    }

    public function find(int $id): ?array
    {
        $q = $this->db->prepare('SELECT * FROM master_zayavki WHERE id = :id');
        $q->execute(['id' => $id]);
        return $q->fetch() ?: null;
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

    public function list(string $search, string $status, int $limit, int $offset): array
    {
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(abonent LIKE :search OR address LIKE :search OR `desc` LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($status === 'open') $where[] = "COALESCE(result, '') = ''";
        if ($status === 'done') $where[] = "COALESCE(result, '') <> ''";
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $this->db->prepare('SELECT COUNT(*) FROM master_podkluchki' . $sqlWhere);
        $count->execute($params);
        $q = $this->db->prepare('SELECT * FROM master_podkluchki' . $sqlWhere . ' ORDER BY time DESC, id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $q->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $q->bindValue(':limit', $limit, PDO::PARAM_INT);
        $q->bindValue(':offset', $offset, PDO::PARAM_INT);
        $q->execute();
        return ['rows' => $q->fetchAll(), 'total' => (int)$count->fetchColumn()];
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
        

    public function list(string $search, int $limit, int $offset): array
    {
        $update = $this->latestUpdate();

        $where = '`update` = :update';

        $params = [
            'update' => $update,
        ];

        if ($search !== '') {
            $where .= '
                AND (
                    personal LIKE :personal
                    OR account LIKE :account
                    OR address LIKE :address
                )
            ';

            $params['personal'] = $search . '%';
            $params['account'] = $search . '%';
            $params['address'] = '%' . $search . '%';
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
            ORDER BY personal ASC, id DESC
            LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $query->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);

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
                    'debt_raw' => 0,
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

            $sumRaw = $this->parseLegacySum($row['summ'] ?? '');
            $tariff = trim((string) ($row['tarif'] ?? ''));

            $houses[$house]['subscribers']++;

            if (
                trim((string) ($row['karandash_descr'] ?? '')) !== ''
            ) {
                $houses[$house]['karandash']++;
            }

            if ($tariff !== 'Нет договора') {
                if ($sumRaw > 0) {
                    // Задолженность только по действующим договорам.
                    $houses[$house]['debt_raw'] += $sumRaw;
                    $houses[$house]['debtors']++;
                } elseif ($sumRaw < 0) {
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
            $house['debt'] = $house['debt_raw'] / 10000;
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
                db.summ,
                db.tarif,
                db.time,
                db.`update`,
                COALESCE(k.descr, \'\') AS karandash_descr
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

        $apartments = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $address = trim((string) ($row['address'] ?? ''));

            if ($address === '') {
                continue;
            }

            $parts = explode('-', $address);
            $apartmentNumber = trim((string) array_pop($parts));

            if ($apartmentNumber === '') {
                continue;
            }

            $sumRaw = $this->parseLegacySum($row['summ'] ?? '');
            $tariff = trim((string) ($row['tarif'] ?? ''));




            $debt = max(0, $sumRaw) / 10000;




            $apartments[] = [
                'number' => $apartmentNumber,
                'number_sort' => (int) $apartmentNumber,
                'personal' => trim((string) ($row['personal'] ?? '')),
                'subscriber' => trim((string) ($row['account'] ?? '')),
                'tariff' => $tariff,
                'debt' => $debt,
                'address' => $address,
                'karandash_descr' => trim(
                    (string) ($row['karandash_descr'] ?? '')
                ),
            ];
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
                'payments' => [],
                'history' => [],
            ];
        }

        $statement = $this->db->prepare(
            'SELECT
                id,
                personal,
                account,
                address,
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

        $payments = [];
        $history = [];
        $previousDebtRaw = null;

        foreach ($rows as $row) {
            $debtRaw = $this->parseLegacySum($row['summ'] ?? '');
            $update = trim((string) ($row['update'] ?? ''));

            $history[] = [
                'update' => $update,
                'debt' => $debtRaw / 10000,
                'period' => trim((string) ($row['period'] ?? '')),
            ];

            if (
                $previousDebtRaw !== null
                && $previousDebtRaw > 0
                && $debtRaw <= 0
            ) {
                $payments[] = [
                    'update' => $update,
                    'previous_debt' => $previousDebtRaw / 10000,
                    'current_debt' => $debtRaw / 10000,
                    'period' => trim((string) ($row['period'] ?? '')),
                ];
            }

            $previousDebtRaw = $debtRaw;
        }

        $lastRow = $rows ? $rows[count($rows) - 1] : [];

        /*
        * Новые оплаты сверху.
        */
        $payments = array_reverse($payments);

        return [
            'personal' => $personal,
            'subscriber' => trim((string) ($lastRow['account'] ?? '')),
            'address' => trim((string) ($lastRow['address'] ?? '')),
            'tariff' => trim((string) ($lastRow['tarif'] ?? '')),
            'payments' => $payments,
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

            $currentDebtRaw = $this->parseLegacySum(
                $row['summ'] ?? ''
            );

            $previousDebtRaw =
                $previousDebtByPersonal[$personal] ?? null;

            /*
             * Оплата обнаружена:
             *
             * до обновления задолженность была положительной,
             * после обновления стала нулевой или отрицательной.
             */
            if (
                $previousDebtRaw !== null
                && $previousDebtRaw > 0
                && $currentDebtRaw <= 0
            ) {
                /*
                 * История идёт от старой к новой.
                 * Поэтому последнее присвоение и будет
                 * последней обнаруженной оплатой.
                 */
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
            id,
            personal,
            account,
            address,
            summ,
            tarif,
            `update`
        FROM master_database
        WHERE `update` = :update
          AND tarif <> :no_contract
        ORDER BY id ASC'
    );

    $statement->execute([
        'update' => $update,
        'no_contract' => 'Нет договора',
    ]);

    $houses = [];
    $debtorPersonals = [];

    $debtorsTotal = 0;
    $debtTotalRaw = 0;

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $sumRaw = $this->parseLegacySum(
            $row['summ'] ?? ''
        );

        if ($sumRaw <= 0) {
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
                'debt_raw' => 0,
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

        $houses[$houseName]['debt_raw'] += $sumRaw;

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
            'debt' => $sumRaw / 10000,
            'last_payment_update' => '',
        ];

        $debtorsTotal++;
        $debtTotalRaw += $sumRaw;
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
            ((int) ($houseData['debt_raw'] ?? 0)) / 10000;
    }
    unset($houseData);

    uasort(
        $houses,
        static function (array $a, array $b): int {
            $debtA = (int) ($a['debt_raw'] ?? 0);
            $debtB = (int) ($b['debt_raw'] ?? 0);

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
        'debt_total' => $debtTotalRaw / 10000,
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

    private function parseLegacySum($value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $value = str_replace(
            [' ', "\xC2\xA0", ','],
            ['', '', '.'],
            $value
        );

        if (!is_numeric($value)) {
            return 0;
        }

        return (int) $value;
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