<?php

declare(strict_types=1);

final class StrokaRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }





    public function list(
        string $search,
        string $status,
        string $selectedDate,
        int $limit,
        int $offset
    ): array {
        $where = [];
        $params = [];

        /*
        * Поиск.
        */
        if ($search !== '') {
            $where[] = '(
                name LIKE :search_name
                OR address LIKE :search_address
                OR phone LIKE :search_phone
                OR text LIKE :search_text
            )';

            $searchValue = '%' . $search . '%';

            $params['search_name'] = $searchValue;
            $params['search_address'] = $searchValue;
            $params['search_phone'] = $searchValue;
            $params['search_text'] = $searchValue;
        }

        $now = time();
        $today = date('Y-m-d');

        /*
        * Проверяем выбранную дату.
        */
        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $selectedDate
            )
        ) {
            $selectedDate = $today;
        }

        /*
        * Фильтр по календарю включаем только
        * если выбрана не сегодняшняя дата.
        */
        $dateFilterActive =
            $selectedDate !== $today;

        if ($dateFilterActive) {
            $dayStart = strtotime(
                $selectedDate . ' 00:00:00'
            );

            $dayEnd = strtotime(
                $selectedDate . ' 23:59:59'
            );

            /*
            * На всякий случай защищаемся
            * от некорректной даты.
            */
            if (
                $dayStart === false
                || $dayEnd === false
            ) {
                $dayStart = strtotime(
                    $today . ' 00:00:00'
                );

                $dayEnd = strtotime(
                    $today . ' 23:59:59'
                );
            }

            /*
            * При просмотре конкретного дня
            * показываем все неудалённые объявления,
            * которые показывались хотя бы часть дня.
            */
            $where[] = '`delete` = 0';

            $where[] =
                'CAST(datestart AS UNSIGNED) <= :day_end';

            $where[] =
                'CAST(dateend AS UNSIGNED) >= :day_start';

            /*
            * ВАЖНО:
            * эти параметры добавляются только здесь,
            * одновременно с появлением плейсхолдеров
            * :day_start и :day_end в SQL.
            */
            $params['day_start'] = $dayStart;
            $params['day_end'] = $dayEnd;
        } else {
            /*
            * Для сегодняшней даты работает
            * обычный фильтр статуса.
            */
            switch ($status) {
                case 'active':
                    $where[] = '`delete` = 0';

                    $where[] =
                        'CAST(datestart AS UNSIGNED) <= :now_start';

                    $where[] =
                        'CAST(dateend AS UNSIGNED) >= :now_end';

                    $params['now_start'] = $now;
                    $params['now_end'] = $now;
                    break;

                case 'scheduled':
                    $where[] = '`delete` = 0';

                    $where[] =
                        'CAST(datestart AS UNSIGNED) > :now';

                    $params['now'] = $now;
                    break;

                case 'expired':
                    $where[] = '`delete` = 0';

                    $where[] =
                        'CAST(dateend AS UNSIGNED) < :now';

                    $params['now'] = $now;
                    break;

                case 'deleted':
                    $where[] = '`delete` = 1';
                    break;

                case 'all':
                default:
                    $where[] = '`delete` = 0';
                    break;
            }
        }

        $sqlWhere = $where
            ? ' WHERE ' . implode(
                ' AND ',
                $where
            )
            : '';

        /*
        * Количество записей.
        */
        $count = $this->db->prepare(
            'SELECT COUNT(*)
            FROM trianda_stroka'
            . $sqlWhere
        );

        foreach ($params as $key => $value) {
            $count->bindValue(
                ':' . $key,
                $value,
                is_int($value)
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR
            );
        }

        $count->execute();

        /*
        * Сами записи.
        */
        $query = $this->db->prepare(
            'SELECT *
            FROM trianda_stroka'
            . $sqlWhere
            . '
            ORDER BY id DESC
            LIMIT :limit
            OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $query->bindValue(
                ':' . $key,
                $value,
                is_int($value)
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR
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
        $query = $this->db->prepare(
            'SELECT *
             FROM trianda_stroka
             WHERE id = :id'
        );

        $query->execute([
            'id' => $id,
        ]);

        return $query->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $query = $this->db->prepare(
            'INSERT INTO trianda_stroka (
                name,
                address,
                phone,
                text,
                datestart,
                dateend,
                amount,
                date,
                whoadd,
                timeadd,
                beznal,
                mystr,
                `delete`,
                sh_tv,
                sh_int,
                sh_pan,
                telegram
            ) VALUES (
                :name,
                :address,
                :phone,
                :text,
                :datestart,
                :dateend,
                :amount,
                :date,
                :whoadd,
                NOW(),
                :beznal,
                :mystr,
                0,
                :sh_tv,
                :sh_int,
                :sh_pan,
                :telegram
            )'
        );

        $query->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        array $data
    ): void {
        $data['id'] = $id;

        unset($data['telegram']);

        $query = $this->db->prepare(
            'UPDATE trianda_stroka
             SET
                name = :name,
                address = :address,
                phone = :phone,
                text = :text,
                datestart = :datestart,
                dateend = :dateend,
                amount = :amount,
                date = :date,
                whoadd = :whoadd,
                timeadd = NOW(),
                beznal = :beznal,
                mystr = :mystr,
                sh_tv = :sh_tv,
                sh_int = :sh_int,
                sh_pan = :sh_pan
             WHERE id = :id'
        );

        $query->execute($data);
    }

    public function delete(int $id): void
    {
        $query = $this->db->prepare(
            'UPDATE trianda_stroka
             SET
                `delete` = 1,
                whoadd = :whoadd,
                timeadd = NOW()
             WHERE id = :id'
        );

        $query->execute([
            'id' => $id,
            'whoadd' => current_user(),
        ]);
    }

    public function restore(int $id): void
    {
        $query = $this->db->prepare(
            'UPDATE trianda_stroka
             SET
                `delete` = 0,
                whoadd = :whoadd,
                timeadd = NOW()
             WHERE id = :id'
        );

        $query->execute([
            'id' => $id,
            'whoadd' => current_user(),
        ]);
    }



    public function statistics(): array
    {
        $now = time();

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        $currentYearAmount = 0;

        $lastMonthTimestamp = strtotime(
            'first day of last month'
        );

        $lastMonth = (int) date(
            'm',
            $lastMonthTimestamp
        );

        $lastMonthYear = (int) date(
            'Y',
            $lastMonthTimestamp
        );

        $monthNames = [
            1 => 'янв',
            2 => 'фев',
            3 => 'мар',
            4 => 'апр',
            5 => 'май',
            6 => 'июн',
            7 => 'июл',
            8 => 'авг',
            9 => 'сен',
            10 => 'окт',
            11 => 'ноя',
            12 => 'дек',
        ];

        $yearChart = [];

        for ($i = 17; $i >= 0; $i--) {
            $timestamp = strtotime(
                'first day of -' . $i . ' month'
            );

            $chartYear = (int) date(
                'Y',
                $timestamp
            );

            $chartMonth = (int) date(
                'm',
                $timestamp
            );

            $key = sprintf(
                '%04d-%02d',
                $chartYear,
                $chartMonth
            );

            $yearChart[$key] = [
                'month' => $chartMonth,
                'year' => $chartYear,
                'label' => $monthNames[$chartMonth],
                'total' => 0,
                'individual' => 0,
                'legal' => 0,
            ];
        }


        /*
        * Общее количество.
        */
        $total = (int) $this->db
            ->query(
                'SELECT COUNT(*)
                FROM trianda_stroka'
            )
            ->fetchColumn();

        /*
        * Активные объявления.
        *
        * В старом коде delete вообще не учитывался.
        * Но здесь логичнее исключить удалённые.
        */
        $query = $this->db->prepare(
            'SELECT COUNT(*)
            FROM trianda_stroka
            WHERE `delete` = 0
            AND CAST(datestart AS UNSIGNED) < :now_start
            AND CAST(dateend AS UNSIGNED) > :now_end'
        );

        $query->execute([
            'now_start' => $now,
            'now_end' => $now,
        ]);

        $active = (int) $query->fetchColumn();

        /*
        * Запланированные объявления.
        */
        $query = $this->db->prepare(
            'SELECT COUNT(*)
            FROM trianda_stroka
            WHERE `delete` = 0
            AND CAST(datestart AS UNSIGNED) > :now'
        );

        $query->execute([
            'now' => $now,
        ]);

        $scheduled = (int) $query->fetchColumn();


        /*
        * Финансовые данные.
        *
        * Поле date хранится как dd.mm.YYYY,
        * поэтому разбираем его средствами SQL.
        */
        $query = $this->db->query(
            'SELECT
                `date`,
                amount,
                beznal
            FROM trianda_stroka'
        );

        $previousYearAmount = 0;

        $currentMonthCount = 0;
        $currentMonthAmount = 0;
        $currentMonthBeznal = 0;

        $lastMonthCount = 0;
        $lastMonthAmount = 0;
        $lastMonthBeznal = 0;

        while ($row = $query->fetch()) {
            $date = trim(
                (string) (
                    $row['date']
                    ?? ''
                )
            );

            $parts = explode(
                '.',
                $date
            );

            if (count($parts) !== 3) {
                continue;
            }

            $month = (int) $parts[1];
            $year = (int) $parts[2];

            $amount = (int) (
                $row['amount']
                ?? 0
            );

            $beznal = (int) (
                $row['beznal']
                ?? 0
            ) === 1;


            $chartKey = sprintf(
                '%04d-%02d',
                $year,
                $month
            );

            if (isset($yearChart[$chartKey])) {
                $yearChart[$chartKey]['total'] += $amount;

                if ($beznal) {
                    $yearChart[$chartKey]['legal'] += $amount;
                } else {
                    $yearChart[$chartKey]['individual'] += $amount;
                }
            }



            if ($year === $currentYear - 1) {
                $previousYearAmount += $amount;
            }

            if ($year === $currentYear) {
                $currentYearAmount += $amount;
            }            

            if (
                $month === $currentMonth
                && $year === $currentYear
            ) {
                $currentMonthCount++;
                $currentMonthAmount += $amount;

                if ($beznal) {
                    $currentMonthBeznal += $amount;
                }

                continue;
            }

            if (
                $month === $lastMonth
                && $year === $lastMonthYear
            ) {
                $lastMonthCount++;
                $lastMonthAmount += $amount;

                if ($beznal) {
                    $lastMonthBeznal += $amount;
                }
            }
        }

        return [
            'total' => $total,
            'active' => $active,

            'previous_year' => [
                'year' => $currentYear - 1,
                'amount' => $previousYearAmount,
            ],

            'last_month' => [
                'month' => $lastMonth,
                'year' => $lastMonthYear,
                'count' => $lastMonthCount,
                'individual' =>
                    $lastMonthAmount
                    - $lastMonthBeznal,
                'legal' => $lastMonthBeznal,
                'total' => $lastMonthAmount,
            ],

            'current_month' => [
                'month' => $currentMonth,
                'year' => $currentYear,
                'count' => $currentMonthCount,
                'individual' =>
                    $currentMonthAmount
                    - $currentMonthBeznal,
                'legal' => $currentMonthBeznal,
                'total' => $currentMonthAmount,
            ],

            'current_year' => [
                'year' => $currentYear,
                'amount' => $currentYearAmount,
            ],           
            
            'year_chart' => array_values(
                $yearChart
            ),

            'scheduled' => $scheduled,

        ];
    }








    public function activeForPanel(): array
    {
        $now = time();

        $query = $this->db->prepare(
            'SELECT
                id,
                text,
                datestart,
                dateend,
                amount
            FROM trianda_stroka
            WHERE `delete` = 0
            AND CAST(datestart AS UNSIGNED) < :now_start
            AND CAST(dateend AS UNSIGNED) > :now_end
            AND sh_pan = 1
            ORDER BY id DESC'
        );

        $query->execute([
            'now_start' => $now,
            'now_end' => $now,
        ]);

        return $query->fetchAll();
    }

    public function activeForInfocanal(): array
    {
        $now = time();

        $query = $this->db->prepare(
            'SELECT
                id,
                text,
                mystr
            FROM trianda_stroka
            WHERE `delete` = 0
            AND CAST(datestart AS UNSIGNED) < :now_start
            AND CAST(dateend AS UNSIGNED) > :now_end
            AND sh_tv = 1
            ORDER BY id DESC'
        );

        $query->execute([
            'now_start' => $now,
            'now_end' => $now,
        ]);

        return $query->fetchAll();
    }






}