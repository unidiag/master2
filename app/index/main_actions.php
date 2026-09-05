<?php

declare(strict_types=1);

/** @var \PDO $pdo */
/** @var string $module */
/** @var array $config */

/*
 * Основные POST-действия сайта.
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */

/*
 * Основные POST-действия сайта.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $postAction = post_string(
        'action',
        30
    );





    if (
        $module === 'money'
        && in_array(
            $postAction,
            [
                'money_add',
                'money_edit',
            ],
            true
        )
    ) {
        if (current_user() === 'sanya') {
            http_response_code(403);
            exit('Доступ запрещён.');
        }

        /*
        * Поля, которые разрешено менять.
        */
        $moneyFields = [
            'date',
            'pole1',
            'pole3',
            'pole4',
            'pole5',
            'pole6',
            'pole7',
        ];

        if ($postAction === 'money_add') {
            $values = [];

            foreach ($moneyFields as $field) {
                $value = trim(
                    (string) (
                        $_POST[$field]
                        ?? ''
                    )
                );

                /*
                * Сохраняем в старом формате:
                * пробелы внутри значений убираем.
                */
                $value = str_replace(
                    [
                        '&nbsp;',
                        ' ',
                    ],
                    '',
                    $value
                );

                $values[$field] = $value;
            }

            /*
            * Дата должна быть DD.MM.YY.
            */
            if (
                !preg_match(
                    '/^\d{2}\.\d{2}\.\d{2}$/',
                    $values['date']
                )
            ) {
                flash(
                    'error',
                    'Некорректная дата.'
                );

                redirect([
                    'module' => 'money',
                ]);
            }

            try {
                $stmt = $pdo->prepare(
                    '
                    INSERT INTO master_money (
                        date,
                        pole1,
                        pole3,
                        pole4,
                        pole5,
                        pole6,
                        pole7
                    ) VALUES (
                        :date,
                        :pole1,
                        :pole3,
                        :pole4,
                        :pole5,
                        :pole6,
                        :pole7
                    )
                    '
                );

                $stmt->execute([
                    ':date' => $values['date'],
                    ':pole1' => $values['pole1'],
                    ':pole3' => $values['pole3'],
                    ':pole4' => $values['pole4'],
                    ':pole5' => $values['pole5'],
                    ':pole6' => $values['pole6'],
                    ':pole7' => $values['pole7'],
                ]);
            } catch (Throwable $exception) {
                flash(
                    'error',
                    'Не удалось добавить запись: '
                    . $exception->getMessage()
                );
            }

            redirect([
                'module' => 'money',
                'month' => substr(
                    $values['date'],
                    3,
                    2
                ),
                'year' => substr(
                    $values['date'],
                    6,
                    2
                ),
            ]);
        }

        if ($postAction === 'money_edit') {
            $id = positive_int(
                $_POST['id']
                ?? 0,
                0
            );

            $field = trim(
                (string) (
                    $_POST['field']
                    ?? ''
                )
            );

            $value = trim(
                (string) (
                    $_POST['value']
                    ?? ''
                )
            );

            if (
                $id <= 0
                || !in_array(
                    $field,
                    $moneyFields,
                    true
                )
            ) {
                flash(
                    'error',
                    'Некорректные данные.'
                );

                redirect([
                    'module' => 'money',
                ]);
            }

            $value = str_replace(
                [
                    '&nbsp;',
                    ' ',
                ],
                '',
                $value
            );

            if (
                $field === 'date'
                && !preg_match(
                    '/^\d{2}\.\d{2}\.\d{2}$/',
                    $value
                )
            ) {
                flash(
                    'error',
                    'Некорректная дата.'
                );

                redirect([
                    'module' => 'money',
                ]);
            }

            try {
                /*
                * Имя поля нельзя передать параметром PDO,
                * поэтому используем только поле из whitelist.
                */
                $stmt = $pdo->prepare(
                    '
                    UPDATE master_money
                    SET `' . $field . '` = :value
                    WHERE id = :id
                    LIMIT 1
                    '
                );

                $stmt->execute([
                    ':value' => $value,
                    ':id' => $id,
                ]);
            } catch (Throwable $exception) {
                flash(
                    'error',
                    'Не удалось изменить запись: '
                    . $exception->getMessage()
                );
            }

            redirect([
                'module' => 'money',
                'month' => post_string(
                    'month',
                    2
                ),
                'year' => post_string(
                    'year',
                    2
                ),
            ]);
        }
    }





    if ($postAction === 'sms_delete') {
        verify_csrf();

        $smsId = (int) ($_POST['sms_id'] ?? 0);

        if ($smsId <= 0) {
            flash(
                'error',
                'Некорректный идентификатор SMS.'
            );

            redirect([
                'module' => 'sms',
            ]);
        }

        try {
            $stmt = $pdo->prepare(
                '
                UPDATE master_sms
                SET deleted_at = NOW()
                WHERE id = :id
                AND deleted_at IS NULL
                '
            );

            $stmt->execute([
                ':id' => $smsId,
            ]);

            if ($stmt->rowCount() > 0) {
                flash(
                    'success',
                    'SMS удалено.'
                );
            } else {
                flash(
                    'warning',
                    'SMS не найдено или уже удалено.'
                );
            }
        } catch (Throwable $exception) {
            flash(
                'error',
                'Не удалось удалить SMS: '
                . $exception->getMessage()
            );
        }

        redirect([
            'module' => 'sms',
        ]);
    }





    if (
        $module === 'digital'
        && $postAction === 'digital_refresh'
    ) {
        try {
            $syncResult = $digitalChannels->sync(
                $digitalRepository
            );

            $inserted = 0;
            $updated = 0;
            $errors = [];

            foreach ($syncResult as $result) {
                if (!empty($result['success'])) {
                    $inserted += (int) (
                        $result['inserted']
                        ?? 0
                    );

                    $updated += (int) (
                        $result['updated']
                        ?? 0
                    );

                    continue;
                }

                $server = trim(
                    (string) (
                        $result['server']
                        ?? 'Astra'
                    )
                );

                $error = trim(
                    (string) (
                        $result['error']
                        ?? 'Ошибка обновления'
                    )
                );

                $errors[] =
                    $server
                    . ': '
                    . $error;
            }

            if ($inserted > 0 || $updated > 0) {
                flash(
                    'success',
                    'Цифровые каналы обновлены. '
                    . 'Добавлено: '
                    . $inserted
                    . ', обновлено: '
                    . $updated
                    . '.'
                );
            } elseif (!$errors) {
                flash(
                    'success',
                    'Цифровые каналы актуальны. '
                    . 'Новых или изменённых каналов нет.'
                );
            }

            foreach ($errors as $error) {
                flash(
                    'error',
                    $error
                );
            }
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'digital',
        ]);
    }

    $id = positive_int(
        $_POST['id'] ?? 0,
        0
    );





    /*
    * Ручные данные цифрового телеканала.
    */
    if (
        $module === 'digital'
        && $postAction === 'digital_save'
    ) {
        $channelId = positive_int(
            $_POST['id'] ?? 0,
            0
        );

        $lcn = (int) (
            $_POST['lcn']
            ?? 0
        );

        $distrib = post_string(
            'distrib',
            100
        );

        $info = post_string(
            'info',
            100
        );

        $summRaw = trim(
            (string) (
                $_POST['summ']
                ?? '0'
            )
        );

        /*
        * Разрешаем пользователю вводить
        * как 12.50, так и 12,50.
        */
        $summRaw = str_replace(
            ',',
            '.',
            $summRaw
        );

        $summ = is_numeric($summRaw)
            ? (float) $summRaw
            : -1;

        try {
            $digitalRepository->updateInfo(
                $channelId,
                $lcn,
                $distrib,
                $summ,
                $info
            );

            flash(
                'success',
                'Информация о телеканале сохранена.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'digital',
        ]);
    }

/*
 * Удаление цифрового телеканала.
 */
if (
    $module === 'digital'
    && $postAction === 'digital_delete'
) {
    $channelId = positive_int(
        $_POST['id'] ?? 0,
        0
    );

    try {
        $digitalRepository->delete(
            $channelId
        );

        flash(
            'success',
            'Телеканал удалён.'
        );
    } catch (Throwable $exception) {
        flash(
            'error',
            $exception->getMessage()
        );
    }

    redirect([
        'module' => 'digital',
    ]);
}






    if ($postAction === 'karandash_delete') {
        if (current_user() !== 'admin') {
            http_response_code(403);

            flash(
                'error',
                'Удаление записей разрешено только администратору.'
            );

            redirect([
                'module' => 'karandash',
            ]);
        }

        $address = post_string(
            'address',
            255
        );

        if ($address === '') {
            flash(
                'error',
                'Не удалось определить запись для удаления.'
            );

            redirect([
                'module' => 'karandash',
            ]);
        }

        try {
            $karandash->delete(
                $address
            );

            flash(
                'success',
                'Запись удалена с карандаша.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'karandash',
        ]);
    }



    if ($postAction === 'karandash_add') {
        $personal = post_string(
            'personal',
            20
        );

        $house = post_string(
            'house',
            100
        );

        $address = post_string(
            'address',
            255
        );

        $descr = post_string(
            'descr',
            2000
        );

        $returnModule = post_string(
            'return_module',
            30
        );

        $returnModule =
            $returnModule === 'karandash'
                ? 'karandash'
                : 'stat';

        if ($address === '') {
            flash(
                'error',
                'Не удалось определить адрес абонента.'
            );

            if (
                $returnModule
                === 'karandash'
            ) {
                redirect([
                    'module' => 'karandash',
                ]);
            }

            redirect([
                'module' => 'stat',
                'house' => $house,
                'personal' => $personal,
            ]);
        }

        $alreadyExists =
            $karandash->exists(
                $address
            );

        if (
            $descr === ''
            && !$alreadyExists
        ) {
            flash(
                'error',
                'Укажите причину постановки на карандаш.'
            );

            redirect([
                'module' => 'stat',
                'house' => $house,
                'personal' => $personal,
            ]);
        }

        $karandash->save(
            $address,
            $descr
        );

        flash(
            'success',
            $alreadyExists
                ? 'Информация на карандаше обновлена.'
                : 'Абонент взят на карандаш.'
        );

        if (
            $returnModule
            === 'karandash'
        ) {
            redirect([
                'module' => 'karandash',
            ]);
        }

        redirect([
            'module' => 'stat',
            'house' => $house,
            'personal' => $personal,
        ]);
    }

    /*
     * Заявки.
     */
    if ($module === 'zayavki') {
        if ($postAction === 'create') {
            $allowedDescriptions = [
                'нет трансляции',
                'плохая трансляция',
                'настройка каналов',
                'ремонт квартирной сети',
                'авария на линии',
                'подключить на площадке',
                'другие услуги',
            ];

            $address = post_string(
                'address',
                50
            );

            $abonent = post_string(
                'abonent',
                50
            );

            $description = post_string(
                'description',
                50
            );

            $other = post_string(
                'other',
                50
            );

            if ($address === '') {
                flash(
                    'error',
                    'Укажите адрес абонента.'
                );

                redirect([
                    'module' => 'zayavki',
                ]);
            }

            if ($abonent === '') {
                flash(
                    'error',
                    'Укажите ФИО абонента.'
                );

                redirect([
                    'module' => 'zayavki',
                ]);
            }

            if (
                !in_array(
                    $description,
                    $allowedDescriptions,
                    true
                )
            ) {
                flash(
                    'error',
                    'Выберите описание заявки.'
                );

                redirect([
                    'module' => 'zayavki',
                ]);
            }



            $tickets->create([
                'abonent' => $abonent,
                'abonent_ajax' => '',
                'address' => $address,
                'address_ajax' => '',
                'other' => $other,
                'description' => $description,
                'cost' => '',
                'who' => current_user(),
            ]);

            $message =
                'Новая заявка' . PHP_EOL
                . $address . PHP_EOL
                . $abonent . PHP_EOL
                . $description;

            if ($other !== '') {
                $message .= PHP_EOL . $other;
            }

            send_notification_sms(
                $pdo,
                $config,
                $message,
                $abonent,
                $address
            );

            flash(
                'success',
                'Заявка добавлена'
            );




        } elseif (
            $postAction === 'complete'
            && $id > 0
        ) {
            $ticket = $tickets->find(
                $id
            );

            if ($ticket === null) {
                flash(
                    'error',
                    'Заявка не найдена'
                );

                redirect([
                    'module' => 'zayavki',
                ]);
            }

            $master = post_string(
                'master',
                50
            );

            $result = post_string(
                'result',
                44
            );

            $result = trim($result)
                . ' '
                . date('d.m');

            $cost = post_string(
                'cost',
                10
            );

            $tickets->complete(
                $id,
                $master,
                $result,
                $cost
            );

            telegram_notify(
                $telegram,
                $config['telegram'] ?? [],
                ticket_telegram_message(
                    $ticket,
                    'complete',
                    $master,
                    $result,
                    $cost
                )
            );

            flash(
                'success',
                'Заявка выполнена'
            );
        } elseif (
            $postAction === 'withdraw'
            && $id > 0
        ) {
            $ticket = $tickets->find(
                $id
            );

            if ($ticket === null) {
                flash(
                    'error',
                    'Заявка не найдена'
                );

                redirect([
                    'module' => 'zayavki',
                ]);
            }

            $master = current_user();
            $result = 'СНЯТО';

            $tickets->withdraw(
                $id,
                $master
            );

            telegram_notify(
                $telegram,
                $config['telegram'] ?? [],
                ticket_telegram_message(
                    $ticket,
                    'withdraw',
                    $master,
                    $result
                )
            );

            flash(
                'success',
                'Заявка снята'
            );
        }

        redirect([
            'module' => 'zayavki',
        ]);
    }

    /*
     * Подключения.
     */
    if ($module === 'podkluchki') {
        if ($postAction === 'create') {
            $abonent = post_string(
                'abonent',
                50
            );

            $address = post_string(
                'address',
                50
            );

            $other = post_string(
                'other',
                50
            );

            $description = post_string(
                'description',
                50
            );

            $connections->create([
                'abonent' => $abonent,
                'address' => $address,
                'other' => $other,
                'description' => $description,
                'who' => current_user(),
            ]);

            $message =
                'Новое подключение' . PHP_EOL
                . $address . PHP_EOL
                . $abonent;

            if ($description !== '') {
                $message .=
                    PHP_EOL
                    . $description;
            }

            if ($other !== '') {
                $message .=
                    PHP_EOL
                    . $other;
            }

            send_notification_sms(
                $pdo,
                $config,
                $message,
                $abonent,
                $address
            );

            flash(
                'success',
                'Подключение добавлено'
            );
        } elseif (
            $postAction === 'complete'
            && $id > 0
        ) {
            $connection =
                $connections->find(
                    $id
                );

            if ($connection === null) {
                flash(
                    'error',
                    'Подключение не найдено'
                );

                redirect([
                    'module' => 'podkluchki',
                ]);
            }

            $master = post_string(
                'master',
                50
            );

            $result = post_string(
                'result',
                44
            );

            $result = trim($result)
                . ' '
                . date('d.m');

            $connections->complete(
                $id,
                $master,
                $result
            );

            telegram_notify(
                $telegram,
                $config['telegram'] ?? [],
                connection_telegram_message(
                    $connection,
                    'complete',
                    $master,
                    $result
                )
            );

            flash(
                'success',
                'Подключение завершено'
            );
        } elseif (
            $postAction === 'withdraw'
            && $id > 0
        ) {
            $connection =
                $connections->find(
                    $id
                );

            if ($connection === null) {
                flash(
                    'error',
                    'Подключение не найдено'
                );

                redirect([
                    'module' => 'podkluchki',
                ]);
            }

            $master = current_user();
            $result = 'СНЯТО';

            $connections->withdraw(
                $id,
                $master
            );

            telegram_notify(
                $telegram,
                $config['telegram'] ?? [],
                connection_telegram_message(
                    $connection,
                    'withdraw',
                    $master,
                    $result
                )
            );

            flash(
                'success',
                'Подключение снято'
            );
        }

        redirect([
            'module' => 'podkluchki',
        ]);
    }
}
