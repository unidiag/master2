<?php

declare(strict_types=1);

/** @var string $module */
/** @var string $search */
/** @var string $action */
/** @var int $perPage */
/** @var int $offset */
/** @var string $strokaStatus */
/** @var bool $withoutPayments */
/** @var bool $withoutCharges */
/** @var string $strokaDate */
/** @var PDO $pdo */


/*
 * Загрузка данных и заголовков модулей перед render view.
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */

$titles = [
    'stroka' => 'Строка',
    'podkluchki' => 'Подключения',
    'otkluchki' => 'Отключки',
    'database' => 'Абоненты',
    'graph' => 'График',
    'stat' => 'Статистика',
    'debtors' => 'Должники',
    'karandash' => 'Карандаш',
    'analog' => 'Аналог',
    'digital' => 'Цифра',
    'sms' => 'SMS',
    'terminal' => 'Terminal',
    'money' => 'Money',
    'readers' => 'Ридеры',
];

$title = isset($titles[$module])
    ? $titles[$module]
    : 'Заявки';

$data = [];

if ($module === 'readers') {
    /*
     * Readers является административным модулем.
     */
    if (current_user() !== 'admin') {
        http_response_code(403);

        redirect([
            'module' => 'zayavki',
        ]);
    }

    $readerService = new ReaderService(
        $pdo,
        dirname(__DIR__, 2) . '/runtime/readers'
    );

    $selectedReader = positive_int(
        $_GET['reader'] ?? 1,
        1
    );

    $readerLogRows = positive_int(
        $_GET['rows'] ?? 50,
        50
    );

    $readerLogRows = min(
        $readerLogRows,
        5000
    );

    $readerLogSearch = trim(
        (string)($_GET['log_search'] ?? '')
    );

    $data = [
        'readers' => $readerService->all(),

        'selected' => $readerService->find(
            $selectedReader
        ),

        'log' => $readerService->log(
            $selectedReader,
            $readerLogRows,
            $readerLogSearch
        ),

        'reader' => $selectedReader,
        'rows' => $readerLogRows,
        'log_search' => $readerLogSearch,
    ];

    if (
        $module === 'readers'
        && ($_GET['ajax'] ?? '') === 'log'
        && current_user() === 'admin'
    ) {
        foreach ($data['log'] as $line) {
            echo '<div class="reader-log-line">'
                . $readerService->formatLogLine(
                    (string) $line,
                    (string) ($data['log_search'] ?? '')
                )
                . '</div>';
        }

        exit;
    }



    if (
        $module === 'readers'
        && ($_GET['ajax'] ?? '') === 'reboot'
    ) {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (current_user() !== 'admin') {
                throw new RuntimeException('Access denied');
            }

            $reader = (int)($_GET['reader'] ?? 0);

            if ($reader <= 0) {
                throw new RuntimeException('Invalid reader');
            }

            $ok = $readerService->reboot($reader);

            echo json_encode([
                'ok' => $ok,
                'reader' => $reader,
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }


}

if ($module === 'zayavki') {
    $data = $tickets->list(
        $search,
        'all',
        $perPage,
        $offset
    );
}

if ($module === 'podkluchki') {
    $data = $connections->list(
        $search,
        'all',
        $perPage,
        $offset
    );
}

if ($module === 'sms') {
    $data = $smsRepository->list(
        $search,
        $perPage,
        $offset
    );
}





if (
    $module === 'database'
    && $action !== 'history'
) {








    /*
    * Экспорт найденных абонентов
    * в старом TXT-формате.
    */
    if (
        $module === 'database'
        && $action !== 'history'
        && isset($_GET['export'])
        && $_GET['export'] === '1'
    ) {
        if (current_user() !== 'admin') {
            http_response_code(403);
            exit('Доступ запрещён.');
        }

        /*
        * Получаем все записи с теми же
        * поиском и фильтрами, что на странице.
        */
        $exportData = $subscribers->list(
            $search,
            $withoutCharges,
            $withoutPayments,
            1000000,
            0
        );

        $exportRows = $exportData['rows'] ?? [];

        $lines = [];

        foreach ($exportRows as $row) {
            /*
            * Импорт ожидает минимум 12 полей
            * с разделителем ^.
            *
            * Используем 13 полей:
            *
            * 0  - служебное
            * 1  - personal
            * 2  - account
            * 3  - address
            * 4  - period
            * 5  - summ
            * 6  - служебное
            * 7  - YYYYMMDDHHMMSS
            * 8  - служебное
            * 9  - служебное
            * 10 - tarif_id
            * 11 - tarif
            * 12 - phone
            */

            $time = (int) (
                $row['time']
                ?? 0
            );

            $date = $time > 0
                ? date('YmdHis', $time)
                : '';

            $fields = [
                '',
                trim((string) (
                    $row['personal']
                    ?? ''
                )),
                trim((string) (
                    $row['account']
                    ?? ''
                )),
                trim((string) (
                    $row['address']
                    ?? ''
                )),
                trim((string) (
                    $row['period']
                    ?? ''
                )),
                trim((string) (
                    $row['summ']
                    ?? ''
                )),
                '',
                $date,
                '',
                '',
                trim((string) (
                    $row['tarif_id']
                    ?? ''
                )),
                trim((string) (
                    $row['tarif']
                    ?? ''
                )),
                trim((string) (
                    $row['phone']
                    ?? ''
                )),
            ];

            /*
            * На всякий случай убираем символ-разделитель
            * и переводы строк из данных.
            */
            foreach ($fields as &$field) {
                $field = str_replace(
                    [
                        '^',
                        "\r",
                        "\n",
                    ],
                    [
                        ' ',
                        ' ',
                        ' ',
                    ],
                    $field
                );
            }

            unset($field);

            $lines[] = implode(
                '^',
                $fields
            );
        }

        $contents = implode(
            "\r\n",
            $lines
        );

        /*
        * Импорт ожидает Windows-1251.
        */
        $encoded = iconv(
            'UTF-8',
            'Windows-1251//TRANSLIT',
            $contents
        );

        if ($encoded === false) {
            http_response_code(500);
            exit(
                'Ошибка преобразования файла.'
            );
        }

        $filename =
            'database-'
            . date('Y-m-d_H-i-s')
            . '.txt';

        header(
            'Content-Type: text/plain; charset=windows-1251'
        );

        header(
            'Content-Disposition: attachment; filename="'
            . $filename
            . '"'
        );

        header(
            'Content-Length: '
            . strlen($encoded)
        );

        echo $encoded;
        exit;
    }















    $data = $subscribers->list(
        $search,
        $withoutCharges,
        $withoutPayments,
        $perPage,
        $offset
    );

    $databaseDisconnects = [];
    $databaseKarandash = [];

    $rows = $data['rows'] ?? [];

    if ($rows) {
        $personals = [];
        $addresses = [];

        foreach ($rows as $row) {
            $personal = trim(
                (string) (
                    $row['personal']
                    ?? ''
                )
            );

            if ($personal !== '') {
                $personals[] = $personal;
            }

            $address = trim(
                (string) (
                    $row['address']
                    ?? ''
                )
            );

            if ($address !== '') {
                $addresses[] = $address;
            }
        }

        $personals = array_values(
            array_unique($personals)
        );

        $addresses = array_values(
            array_unique($addresses)
        );

        /*
         * Активные отключения.
         */
        if ($personals) {
            $placeholders = implode(
                ',',
                array_fill(
                    0,
                    count($personals),
                    '?'
                )
            );

            $stmt = $pdo->prepare(
                '
                SELECT
                    personal,
                    created_at
                FROM master_otkluchki
                WHERE deleted_at IS NULL
                  AND personal IN (' . $placeholders . ')
                ORDER BY created_at DESC
                '
            );

            $stmt->execute($personals);

            foreach (
                $stmt->fetchAll(PDO::FETCH_ASSOC)
                as $disconnect
            ) {
                $personal = trim(
                    (string) (
                        $disconnect['personal']
                        ?? ''
                    )
                );

                if (
                    $personal !== ''
                    && !isset(
                        $databaseDisconnects[
                            $personal
                        ]
                    )
                ) {
                    $databaseDisconnects[
                        $personal
                    ] = (string) (
                        $disconnect['created_at']
                        ?? ''
                    );
                }
            }
        }

        /*
         * Карандаш.
         */
        if ($addresses) {
            $placeholders = implode(
                ',',
                array_fill(
                    0,
                    count($addresses),
                    '?'
                )
            );

            $stmt = $pdo->prepare(
                '
                SELECT
                    address,
                    descr
                FROM master_karandash
                WHERE address IN (' . $placeholders . ')
                '
            );

            $stmt->execute($addresses);

            foreach (
                $stmt->fetchAll(PDO::FETCH_ASSOC)
                as $karandashRow
            ) {
                $karandashAddress = trim(
                    (string) (
                        $karandashRow['address']
                        ?? ''
                    )
                );

                $karandashDescr = trim(
                    (string) (
                        $karandashRow['descr']
                        ?? ''
                    )
                );

                if (
                    $karandashAddress !== ''
                    && $karandashDescr !== ''
                ) {
                    $databaseKarandash[
                        $karandashAddress
                    ] = $karandashDescr;
                }
            }
        }
    }
}







if (
    $module === 'database'
    && $action === 'history'
) {
    $data = [
        'rows' => $subscribers->history(
            get_string(
                'personal',
                10
            )
        ),
        'total' => 0,
    ];
}

if ($module === 'stroka') {
    $data = $strokaRepository->list(
        $search,
        $strokaStatus,
        $strokaDate,
        $perPage,
        $offset
    );

    $strokaStatistics =
        $strokaRepository->statistics();    
}




if ($module === 'money') {
    $moneyMonths = [
        '01' => 'Январь',
        '02' => 'Февраль',
        '03' => 'Март',
        '04' => 'Апрель',
        '05' => 'Май',
        '06' => 'Июнь',
        '07' => 'Июль',
        '08' => 'Август',
        '09' => 'Сентябрь',
        '10' => 'Октябрь',
        '11' => 'Ноябрь',
        '12' => 'Декабрь',
    ];

    $moneyMonth = trim(
        (string) (
            $_GET['month']
            ?? date('m')
        )
    );

    $moneyYear = trim(
        (string) (
            $_GET['year']
            ?? date('y')
        )
    );

    if (!isset($moneyMonths[$moneyMonth])) {
        $moneyMonth = date('m');
    }

    if (
        !preg_match(
            '/^\d{2}$/',
            $moneyYear
        )
    ) {
        $moneyYear = date('y');
    }

    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            date,
            pole1,
            pole3,
            pole4,
            pole5,
            pole6,
            pole7
        FROM master_money
        WHERE date LIKE :date
        ORDER BY
            STR_TO_DATE(
                date,
                "%d.%m.%y"
            ) ASC,
            id ASC
        '
    );

    $stmt->execute([
        ':date' =>
            '%'
            . $moneyMonth
            . '.'
            . $moneyYear,
    ]);

    $moneyRows = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    $moneyAdsStart = (
        new DateTimeImmutable(
            'first day of this month'
        )
    )->modify('-83 months');

    $moneyAdsEnd = new DateTimeImmutable(
        'first day of next month'
    );

    $stmt = $pdo->prepare(
        '
        SELECT
            DATE_FORMAT(
                STR_TO_DATE(`date`, "%d.%m.%y"),
                "%Y-%m"
            ) AS ym,
            ROUND(
                SUM(
                    CASE
                        WHEN TRIM(COALESCE(pole4, "")) = ""
                            THEN 0
                        ELSE CAST(
                            REPLACE(
                                REPLACE(
                                    TRIM(pole4),
                                    " ",
                                    ""
                                ),
                                ",",
                                "."
                            ) AS DECIMAL(12, 2)
                        )
                    END
                ),
                2
            ) AS total
        FROM master_money
        WHERE STR_TO_DATE(`date`, "%d.%m.%y") >= :start_date
        AND STR_TO_DATE(`date`, "%d.%m.%y") < :end_date
        GROUP BY ym
        ORDER BY ym
        '
    );

    $stmt->execute([
        ':start_date' => $moneyAdsStart->format('Y-m-d'),
        ':end_date' => $moneyAdsEnd->format('Y-m-d'),
    ]);

    $moneyAdsMap = [];

    foreach (
        $stmt->fetchAll(PDO::FETCH_ASSOC)
        as $chartRow
    ) {
        $moneyAdsMap[
            (string) (
                $chartRow['ym']
                ?? ''
            )
        ] = (float) (
            $chartRow['total']
            ?? 0
        );
    }

    $moneyAdsChartLabels = [];
    $moneyAdsChartValues = [];
    $moneyAdsChartTotal = 0.0;

    $cursor = $moneyAdsStart;

    while (
        $cursor->format('Y-m')
        !== $moneyAdsEnd->format('Y-m')
    ) {
        $key = $cursor->format('Y-m');

        $value = (float) (
            $moneyAdsMap[$key]
            ?? 0
        );

        $moneyAdsChartLabels[] = $cursor->format('m.Y');
        $moneyAdsChartValues[] = $value;
        $moneyAdsChartTotal += $value;

        $cursor = $cursor->modify(
            '+1 month'
        );
    }

    $data = [
        'rows' => $moneyRows,
        'total' => count($moneyRows),
    ];











    /*
    * ---------------------------------------------------------
    * График количества абонентов.
    *
    * config:
    *
    * money.graph = Г/А/Ц
    *
    * Изменения:
    *
    * pole1 + новые договора
    * pole5 - расторжения
    * pole6 - расторжения должников
    * ---------------------------------------------------------
    */


    /*
    * Разбирает значение вида:
    *
    * 1/2/3
    *
    * в:
    *
    * [1, 2, 3]
    */
    $moneyParseTriplet = static function (
        mixed $value
    ): array {
        $parts = explode(
            '/',
            trim((string) $value)
        );

        $result = [
            0,
            0,
            0,
        ];

        for ($i = 0; $i < 3; $i++) {
            $part = trim(
                (string) (
                    $parts[$i]
                    ?? ''
                )
            );

            /*
            * На случай старых значений
            * с пробелами или запятой.
            */
            $part = str_replace(
                [
                    ' ',
                    ',',
                ],
                [
                    '',
                    '.',
                ],
                $part
            );

            if (is_numeric($part)) {
                $result[$i] =
                    (int) round(
                        (float) $part
                    );
            }
        }

        return $result;
    };


    /*
    * Стартовые значения из config.local.php.
    *
    * Г / А / Ц
    */
    $moneyGraphStart =
        $moneyParseTriplet(
            $config['money']['graph']
            ?? '0/0/0'
        );


    /*
    * Получаем ВСЮ историю.
    *
    * Это принципиально:
    *
    * стартовые значения относятся к началу
    * таблицы, а не к началу отображаемых
    * последних 7 лет.
    */
    $stmt = $pdo->query(
        '
        SELECT
            `date`,
            pole1,
            pole5,
            pole6
        FROM master_money
        WHERE STR_TO_DATE(
            `date`,
            "%d.%m.%y"
        ) IS NOT NULL
        ORDER BY
            STR_TO_DATE(
                `date`,
                "%d.%m.%y"
            ) ASC,
            id ASC
        '
    );

    $moneySubscriberRows =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    * Суммируем изменения
    * внутри каждого месяца.
    */
    $moneySubscriberMonthly = [];

    foreach (
        $moneySubscriberRows
        as $row
    ) {
        $dateString = trim(
            (string) (
                $row['date']
                ?? ''
            )
        );

        $date = DateTimeImmutable::createFromFormat(
            '!d.m.y',
            $dateString
        );

        if (!$date) {
            continue;
        }

        $monthKey =
            $date->format('Y-m');

        if (
            !isset(
                $moneySubscriberMonthly[
                    $monthKey
                ]
            )
        ) {
            $moneySubscriberMonthly[
                $monthKey
            ] = [
                'add' => [
                    0,
                    0,
                    0,
                ],
                'remove' => [
                    0,
                    0,
                    0,
                ],
            ];
        }


        /*
        * Новые договора.
        */
        $add =
            $moneyParseTriplet(
                $row['pole1']
                ?? ''
            );


        /*
        * Обычные расторжения.
        */
        $remove =
            $moneyParseTriplet(
                $row['pole5']
                ?? ''
            );


        /*
        * Расторжения должников.
        */
        $removeDebt =
            $moneyParseTriplet(
                $row['pole6']
                ?? ''
            );


        for ($i = 0; $i < 3; $i++) {
            $moneySubscriberMonthly[
                $monthKey
            ]['add'][$i] +=
                $add[$i];

            $moneySubscriberMonthly[
                $monthKey
            ]['remove'][$i] +=
                $remove[$i]
                + $removeDebt[$i];
        }
    }


    /*
    * Массив всех точек графика
    * от начала истории.
    */
    $moneySubscribersChartAll = [];


    /*
    * Текущее состояние:
    *
    * 0 = Г
    * 1 = А
    * 2 = Ц
    */
    $currentSubscribers =
        $moneyGraphStart;


    /*
    * Первый месяц таблицы.
    */
    if ($moneySubscriberRows) {
        $firstDate = null;

        foreach (
            $moneySubscriberRows
            as $row
        ) {
            $date = DateTimeImmutable::createFromFormat(
                '!d.m.y',
                trim(
                    (string) (
                        $row['date']
                        ?? ''
                    )
                )
            );

            if ($date) {
                $firstDate = $date;
                break;
            }
        }


        if ($firstDate) {
            $cursor =
                $firstDate->modify(
                    'first day of this month'
                );

            $lastMonth =
                new DateTimeImmutable(
                    'first day of this month'
                );


            /*
            * Идём месяц за месяцем.
            *
            * Даже если в каком-то месяце
            * записей нет, точку всё равно
            * создаём, а число абонентов
            * просто остаётся прежним.
            */
            while (
                $cursor <= $lastMonth
            ) {
                $monthKey =
                    $cursor->format('Y-m');

                $monthData =
                    $moneySubscriberMonthly[
                        $monthKey
                    ]
                    ?? [
                        'add' => [
                            0,
                            0,
                            0,
                        ],
                        'remove' => [
                            0,
                            0,
                            0,
                        ],
                    ];


                for ($i = 0; $i < 3; $i++) {
                    $currentSubscribers[$i] +=
                        $monthData['add'][$i];

                    $currentSubscribers[$i] -=
                        $monthData['remove'][$i];
                }


                $totalSubscribers =
                    $currentSubscribers[0]
                    + $currentSubscribers[1]
                    + $currentSubscribers[2];


                $moneySubscribersChartAll[] = [
                    'month' =>
                        $cursor->format(
                            'm.Y'
                        ),

                    'g' =>
                        $currentSubscribers[0],

                    'a' =>
                        $currentSubscribers[1],

                    'c' =>
                        $currentSubscribers[2],

                    'total' =>
                        $totalSubscribers,
                ];


                $cursor =
                    $cursor->modify(
                        '+1 month'
                    );
            }
        }
    }


    /*
    * Пользователю показываем
    * последние 7 лет:
    *
    * 7 * 12 = 84 месяца.
    */
    $moneySubscribersChart =
        array_slice(
            $moneySubscribersChartAll,
            -84
        );







}



if ($module === 'graph') {
    $data = $subscribers->graph();
}

if ($module === 'stat') {
    $selectedHouse = trim(
        (string) (
            $_GET['house']
            ?? ''
        )
    );

    $selectedPersonal = trim(
        (string) (
            $_GET['personal']
            ?? ''
        )
    );

    /*
     * Если personal не указан,
     * проверяем house как точный адрес.
     *
     * Например:
     * Заводская-6-5
     */
    if (
        $selectedHouse !== ''
        && $selectedPersonal === ''
    ) {
        $subscriberByAddress =
            $subscribers->findLatestByAddress(
                $selectedHouse
            );

        if ($subscriberByAddress !== null) {
            $addressPersonal = trim(
                (string) (
                    $subscriberByAddress['personal']
                    ?? ''
                )
            );

            if ($addressPersonal !== '') {
                $selectedPersonal =
                    $addressPersonal;

                /*
                 * Из точного адреса получаем дом:
                 *
                 * Заводская-6-5
                 * ->
                 * Заводская-6
                 */
                $addressParts = explode(
                    '-',
                    $selectedHouse
                );

                if (count($addressParts) >= 3) {
                    array_pop($addressParts);

                    $selectedHouse = implode(
                        '-',
                        $addressParts
                    );
                }
            }
        }
    }

    /*
     * В шапке сайта показываем название
     * выбранного дома вместо "Статистика".
     */
    if ($selectedHouse !== '') {
        $title = $selectedHouse;
    }

    /*
     * История конкретного абонента.
     */
    if ($selectedPersonal !== '') {
        $data =
            $subscribers->paymentHistory(
                $selectedPersonal
            );

        $houses = [];
        $apartments = [];

        $payments =
            $data['operations']
            ?? [];


        /*
        * Проверяем, отключён ли абонент сейчас.
        */
        $stmt = $pdo->prepare(
            '
            SELECT
                id,
                created_at
            FROM master_otkluchki
            WHERE personal = :personal
            AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
            '
        );

        $stmt->execute([
            ':personal' => $selectedPersonal,
        ]);

        $subscriberDisconnect = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        $subscriberDisconnected =
            $subscriberDisconnect !== false;


        /*
        * Добавляем в историю все отключения абонента,
        * включая уже снятые.
        */
        $stmt = $pdo->prepare(
            '
            SELECT
                id,
                created_at,
                deleted_at,
                tariff,
                summ
            FROM master_otkluchki
            WHERE personal = :personal
            ORDER BY created_at DESC
            '
        );

        $stmt->execute([
            ':personal' => $selectedPersonal,
        ]);

        $disconnectRows = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

        foreach ($disconnectRows as $disconnectRow) {
            /*
            * Само отключение.
            */
            $createdAt = trim(
                (string) (
                    $disconnectRow['created_at']
                    ?? ''
                )
            );

            if ($createdAt !== '') {
                $createdTimestamp = strtotime(
                    $createdAt
                );

                if ($createdTimestamp !== false) {
                    $payments[] = [
                        'update' => (string) $createdTimestamp,
                        'amount' => 0,
                        'current_debt' => (float) (
                            $disconnectRow['summ']
                            ?? 0
                        ),
                        'type' => 'disconnect_created',
                        'tariff' => (string) (
                            $disconnectRow['tariff']
                            ?? ''
                        ),
                    ];
                }
            }

            /*
            * Если отключение потом было снято,
            * добавляем это отдельным событием.
            */
            $deletedAt = trim(
                (string) (
                    $disconnectRow['deleted_at']
                    ?? ''
                )
            );

            if ($deletedAt !== '') {
                $deletedTimestamp = strtotime(
                    $deletedAt
                );

                if ($deletedTimestamp !== false) {
                    $payments[] = [
                        'update' => (string) $deletedTimestamp,
                        'amount' => 0,
                        'current_debt' => (float) (
                            $disconnectRow['summ']
                            ?? 0
                        ),
                        'type' => 'disconnect_deleted',
                        'tariff' => (string) (
                            $disconnectRow['tariff']
                            ?? ''
                        ),
                    ];
                }
            }
        }

        /*
        * После объединения платежей, начислений
        * и отключений снова сортируем всю историю
        * от нового к старому.
        */
        usort(
            $payments,
            static function (
                array $a,
                array $b
            ): int {
                return (int) ($b['update'] ?? 0)
                    <=> (int) ($a['update'] ?? 0);
            }
        );










        $house = $selectedHouse;

        $personal =
            $data['personal']
            ?? $selectedPersonal;

        $subscriber =
            $data['subscriber']
            ?? '';

        $subscriberAddress =
            $data['address']
            ?? '';

        $subscriberPhone =
            $data['phone']
            ?? '';

        $recentSms = $subscriberAddress !== ''
            ? $smsRepository->latestByAddress(
                $subscriberAddress,
                3
            )
            : [];



        $subscriberTariff =
            $data['tariff']
            ?? '';

        $subscriberDebt = (float) (
            $data['debt']
            ?? 0
        );

        $subscriberKarandash =
            $subscriberAddress !== ''
                ? $karandash->findByAddress(
                    $subscriberAddress
                )
                : null;

        $subscriberKarandashDescr =
            trim(
                (string) (
                    $subscriberKarandash['descr']
                    ?? ''
                )
            );

        $subscriberOnKarandash =
            $subscriberKarandash !== null;

        $update = '';

        /*
         * Если дом присутствует в URL,
         * получаем его информацию.
         */
        if ($house !== '') {
            $houseInfo =
                $housesRepository->get(
                    $house
                );

            $houseControl = trim(
                (string) (
                    $houseInfo['control']
                    ?? ''
                )
            );

            $houseDescr = trim(
                (string) (
                    $houseInfo['descr']
                    ?? ''
                )
            );
        }
    } elseif ($selectedHouse !== '') {
        /*
         * Список квартир выбранного дома.
         */
        $data = $subscribers->apartments(
            $selectedHouse
        );

        $houses = [];

        $apartments =
            $data['apartments']
            ?? [];

        /*
        * Адреса квартир, которые уже отмечены
        * как отключённые.
        */
        $disconnectedAddresses = [];

        if ($apartments) {
            $stmt = $pdo->prepare(
                '
                SELECT DISTINCT address
                FROM master_otkluchki
                WHERE address LIKE :house
                AND deleted_at IS NULL
                '
            );

            $stmt->execute([
                ':house' => $selectedHouse . '-%',
            ]);

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $address) {
                $address = trim((string) $address);

                if ($address !== '') {
                    $disconnectedAddresses[$address] = true;
                }
            }
        }



        $payments = [];

        $house =
            $data['house']
            ?? $selectedHouse;

        $qrRows = $qrRepository->listByAddress(
            $house
        );

        $personal = '';

        $update =
            $data['update']
            ?? '';

        /*
         * Информация о доме:
         * group_size, control, descr.
         */
        $houseInfo =
            $housesRepository->get(
                $house
            );

        $houseControl = trim(
            (string) (
                $houseInfo['control']
                ?? ''
            )
        );

        $houseDescr = trim(
            (string) (
                $houseInfo['descr']
                ?? ''
            )
        );

        if (
            array_key_exists(
                'group_size',
                $houseInfo
            )
        ) {
            $apartmentGroupSize =
                (int) $houseInfo['group_size'];
        } else {
            $apartmentGroupSize = 4;
        }

        if (
            $apartmentGroupSize < 0
            || $apartmentGroupSize > 6
        ) {
            $apartmentGroupSize = 4;
        }
    } else {
        /*
         * Список домов.
         */
        $data = $subscribers->houses();

        $houses =
            $data['houses']
            ?? [];

        $apartments = [];
        $payments = [];

        $house = '';
        $personal = '';

        $update =
            $data['update']
            ?? '';

        $houseControl = '';
        $houseDescr = '';
    }
}

if ($module === 'debtors') {
    $data = $subscribers->debtors();

    $debtorDisconnects = [];

    $debtorHouses =
        $data['houses']
        ?? [];

    $personals = [];

    /*
     * Собираем лицевые счета всех должников,
     * отображаемых на странице.
     */
    foreach ($debtorHouses as $debtorHouse) {
        $houseDebtors =
            $debtorHouse['debtors']
            ?? [];

        foreach ($houseDebtors as $debtor) {
            $debtorPersonal = trim(
                (string) (
                    $debtor['personal']
                    ?? ''
                )
            );

            if ($debtorPersonal !== '') {
                $personals[] =
                    $debtorPersonal;
            }
        }
    }

    $personals = array_values(
        array_unique($personals)
    );

    /*
     * Получаем активные отключения
     * одним запросом.
     */
    if ($personals) {
        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($personals),
                '?'
            )
        );

        $stmt = $pdo->prepare(
            '
            SELECT
                personal,
                created_at
            FROM master_otkluchki
            WHERE deleted_at IS NULL
              AND personal IN (' . $placeholders . ')
            ORDER BY created_at DESC
            '
        );

        $stmt->execute(
            $personals
        );

        foreach (
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            )
            as $disconnect
        ) {
            $disconnectPersonal = trim(
                (string) (
                    $disconnect['personal']
                    ?? ''
                )
            );

            /*
             * Так как SQL отсортирован DESC,
             * оставляем самое свежее
             * активное отключение.
             */
            if (
                $disconnectPersonal !== ''
                && !isset(
                    $debtorDisconnects[
                        $disconnectPersonal
                    ]
                )
            ) {
                $debtorDisconnects[
                    $disconnectPersonal
                ] = (string) (
                    $disconnect['created_at']
                    ?? ''
                );
            }
        }
    }
}

if ($module === 'karandash') {
    $data =
        $karandash->groupedByHouse();
}

/*
 * Аналоговое телевидение.
 */
if ($module === 'analog') {
    $data = [
        'channels' => $channels->getAll(),
    ];
}

/*
 * Цифровое телевидение.
 *
 * При обычном открытии страницы Astra не опрашиваем.
 * Данные выводятся только из таблицы master_dtv.
 */
if ($module === 'digital') {
    $data = [
        'channels' =>
            $digitalRepository->getAll(),

        'servers' =>
            $digitalRepository->getServers(),

        'distributors' =>
            $config['digital']['distributors']
            ?? [],
    ];
}




if ($module === 'otkluchki') {
    $where = [
        'deleted_at IS NULL',
        '(hidden_at IS NULL OR hidden_at > NOW())',
    ];

    $params = [];

    if ($search !== '') {
        $where[] = '
            (
                address LIKE :search
                OR subscriber LIKE :search
                OR personal LIKE :search
                OR phone LIKE :search
                OR tariff LIKE :search
            )
        ';

        $params[':search'] = '%' . $search . '%';
    }

    $whereSql = implode(
        ' AND ',
        $where
    );

    /*
     * Общее количество записей
     * для пагинации.
     */
    $stmt = $pdo->prepare(
        '
        SELECT COUNT(*)
        FROM master_otkluchki
        WHERE ' . $whereSql
    );

    $stmt->execute($params);

    $total = (int) $stmt->fetchColumn();

    /*
     * Список отключек.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            created_at,
            deleted_at,
            hidden_at,
            address,
            subscriber,
            personal,
            phone,
            tariff,
            summ
        FROM master_otkluchki
        WHERE ' . $whereSql . '
        ORDER BY created_at DESC
        LIMIT :limit
        OFFSET :offset
        '
    );

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );
    }

    $stmt->bindValue(
        ':limit',
        $perPage,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $data = [
        'rows' => $stmt->fetchAll(
            PDO::FETCH_ASSOC
        ),
        'total' => $total,
    ];
}