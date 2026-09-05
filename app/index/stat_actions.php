<?php

declare(strict_types=1);

/** @var \PDO $pdo */

/*
 * POST-действия статистики, SMS, QR и Telegram-контроля.
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */

/*
 * Действия страницы статистики,
 * связанные с Telegram и контролем дома.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = post_string(
        'action',
        30
    );


    if ($postAction === 'sms_verification') {
        verify_csrf();

        $phone = post_string(
            'phone',
            20
        );

        $returnUrl = post_string(
            'return_url',
            2000
        );

        if (
            $returnUrl === ''
            || !str_starts_with($returnUrl, '/')
            || str_starts_with($returnUrl, '//')
        ) {
            $returnUrl = url([
                'module' => 'sms',
            ]);
        }

        /*
        * Разрешаем:
        *
        * +375297153616
        * 375297153616
        *
        * После удаления "+" должно быть ровно 12 цифр.
        */
        $phoneDigits = preg_replace(
            '/\D+/',
            '',
            $phone
        ) ?? '';

        if (!preg_match('/^\d{12}$/', $phoneDigits)) {
            flash(
                'error',
                'Номер телефона должен содержать 12 цифр в международном формате.'
            );

            header(
                'Location: ' . $returnUrl
            );

            exit;
        }

        /*
        * Для шлюза передаём международный номер с "+".
        * Сам MtsSmsService всё равно удаляет нецифровые
        * символы перед отправкой.
        */
        $smsPhone = '+' . $phoneDigits;

        /*
        * Криптографически безопасное случайное число
        * от 1000 до 9999.
        */
        $code = (string) random_int(
            1000,
            9999
        );

        $message =
            'Код: '
            . $code;

        try {
            $smsEnabled = (bool) (
                $config['mts_sms']['enabled']
                ?? false
            );

            if (!$smsEnabled) {
                throw new RuntimeException(
                    'Отправка SMS через MTS отключена.'
                );
            }

            $smsService = new MtsSmsService(
                $config['mts_sms'] ?? []
            );

            $messageId = $smsService->send(
                $smsPhone,
                $message
            );

            /*
            * Сохраняем SMS в общий журнал.
            *
            * abonent/address здесь пустые, поскольку
            * номер вводится вручную.
            */
            $stmt = $pdo->prepare(
                '
                INSERT INTO master_sms (
                    abonent,
                    address,
                    phone,
                    message,
                    message_id
                ) VALUES (
                    :abonent,
                    :address,
                    :phone,
                    :message,
                    :message_id
                )
                '
            );

            $stmt->execute([
                ':abonent' => '',
                ':address' => '',
                ':phone' => $phoneDigits,
                ':message' => $message,
                ':message_id' => $messageId,
            ]);

            flash(
                'success',
                'Код подтверждения отправлен на +' . $phoneDigits . '.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                'Не удалось отправить код: '
                . $exception->getMessage()
            );
        }

        header(
            'Location: ' . $returnUrl
        );

        exit;
    }


    /*
    * Отправка SMS.
    *
    * Пока вместо реальной отправки
    * записываем сообщение в /sms.txt.
    */
    if ($postAction === 'sms_send') {
        verify_csrf();

        $phone = post_string(
            'phone',
            100
        );



        $message = post_string(
            'message',
            1000
        );

        $returnUrl = post_string(
            'return_url',
            2000
        );

        if (
            $returnUrl === ''
            || !str_starts_with($returnUrl, '/')
            || str_starts_with($returnUrl, '//')
        ) {
            $returnUrl = '/index.php';
        }


        if (!preg_match(
            '/^\+375(?:29|33|44)\d{7}$/',
            $phone
        )) {
            flash(
                'error',
                'Некорректный номер телефона.'
            );

            header(
                'Location: ' . $returnUrl
            );

            exit;
        }


        $house = post_string(
            'house',
            255
        );      

        $personal = post_string(
            'personal',
            20
        );

        $address = post_string(
            'address',
            255
        );

        $abonent = post_string(
            'abonent',
            255
        );

        if ($phone === '') {
            flash(
                'error',
                'Номер телефона не указан.'
            );

            header(
                'Location: ' . $returnUrl
            );

            exit;
        }

        if ($message === '') {
            flash(
                'error',
                'Текст SMS не указан.'
            );

            header(
                'Location: ' . $returnUrl
            );

            exit;
        }


        // SMS SEND
        $smsMessageId = '';

        try {
            $smsEnabled = (bool) (
                $config['mts_sms']['enabled']
                ?? false
            );

            if (!$smsEnabled) {
                throw new RuntimeException(
                    'Отправка SMS через MTS отключена.'
                );
            }

            $smsService = new MtsSmsService(
                $config['mts_sms'] ?? []
            );

            $messageId = $smsService->send(
                $phone,
                $message
            );

            $stmt = $pdo->prepare(
                '
                INSERT INTO master_sms (
                    abonent,
                    address,
                    phone,
                    message,
                    message_id
                ) VALUES (
                    :abonent,
                    :address,
                    :phone,
                    :message,
                    :message_id
                )
                '
            );

            $stmt->execute([
                ':abonent' => $abonent,
                ':address' => $address,
                ':phone' => preg_replace('/\D+/', '', $phone),
                'message' => $message,
                ':message_id' => $messageId,
            ]);

            flash(
                'success',
                'SMS принято шлюзом МТС.'
            );



        } catch (Throwable $e) {
            flash(
                'error',
                'Не удалось отправить SMS: '
                . $e->getMessage()
            );
        }


        header(
            'Location: ' . $returnUrl
        );

        exit;
    }



    if ($postAction === 'sms_status') {
        verify_csrf();

        $id = positive_int(
            $_POST['id'] ?? 0,
            0
        );

        $returnUrl = post_string(
            'return_url',
            2000
        );

        if (
            $returnUrl === ''
            || !str_starts_with($returnUrl, '/')
            || str_starts_with($returnUrl, '//')
        ) {
            $returnUrl = url([
                'module' => 'sms',
            ]);
        }

        try {
            if ($id <= 0) {
                throw new RuntimeException(
                    'Некорректный идентификатор SMS.'
                );
            }

            $sms = $smsRepository->find($id);

            if ($sms === null) {
                throw new RuntimeException(
                    'SMS не найдена.'
                );
            }

            $messageId = trim(
                (string) ($sms['message_id'] ?? '')
            );

            if ($messageId === '') {
                throw new RuntimeException(
                    'У SMS отсутствует message_id.'
                );
            }

            $smsEnabled = (bool) (
                $config['mts_sms']['enabled']
                ?? false
            );

            if (!$smsEnabled) {
                throw new RuntimeException(
                    'MTS SMS API отключён.'
                );
            }

            $smsService = new MtsSmsService(
                $config['mts_sms'] ?? []
            );

            $delivery = $smsService->status(
                $messageId
            );

            /*
             * Ответ MTS сохраняем в поля master_sms.
             */
            $deliveryStatus = isset($delivery['status'])
                && is_numeric($delivery['status'])
                ? (int) $delivery['status']
                : null;

            $deliverySubstatus = isset($delivery['substatus'])
                && is_numeric($delivery['substatus'])
                ? (int) $delivery['substatus']
                : null;

            $deliveryMsgStatus = isset($delivery['msg_status'])
                && is_numeric($delivery['msg_status'])
                ? (int) $delivery['msg_status']
                : null;

            $deliveryStatusText = isset($delivery['status_text'])
                ? trim((string) $delivery['status_text'])
                : null;

            if ($deliveryStatusText === '') {
                $deliveryStatusText = null;
            }

            error_log(
    'MTS SMS STATUS: '
    . json_encode(
        $delivery,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    )
);

            $smsRepository->saveStatus(
                $id,
                $deliveryStatus,
                $deliverySubstatus,
                $deliveryMsgStatus,
                $deliveryStatusText
            );

            flash(
                'success',
                $deliveryStatusText !== null
                    ? 'Статус SMS: ' . $deliveryStatusText
                    : 'Статус SMS обновлён.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                'Не удалось получить статус SMS: '
                . $exception->getMessage()
            );
        }

        header(
            'Location: ' . $returnUrl
        );

        exit;
    }


    if ($postAction === 'sms_delete') {
        verify_csrf();

        $id = positive_int(
            $_POST['id'] ?? 0,
            0
        );

        $returnUrl = post_string(
            'return_url',
            2000
        );

        if (
            $returnUrl === ''
            || !str_starts_with($returnUrl, '/')
            || str_starts_with($returnUrl, '//')
        ) {
            $returnUrl = url([
                'module' => 'sms',
            ]);
        }

        try {
            if ($id <= 0) {
                throw new RuntimeException(
                    'Некорректный идентификатор SMS.'
                );
            }

            $smsRepository->delete($id);

            flash(
                'success',
                'SMS удалена.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                'Не удалось удалить SMS: '
                . $exception->getMessage()
            );
        }

        header(
            'Location: ' . $returnUrl
        );

        exit;
    }




    /*
    * Заметка по дому.
    */
    if ($postAction === 'house_descr') {
        verify_csrf();

        $house = post_string(
            'house',
            255
        );

        $descr = post_string(
            'descr',
            2000
        );

        if ($house === '') {
            flash(
                'error',
                'Название дома не указано.'
            );

            redirect([
                'module' => 'stat',
            ]);
        }

        try {
            $housesRepository->saveDescr(
                $house,
                $descr
            );

            flash(
                'success',
                $descr !== ''
                    ? 'Информация по дому сохранена.'
                    : 'Информация по дому удалена.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'stat',
            'house' => $house,
        ]);
    }



    /*
     * Контроль дома.
     */
    if ($postAction === 'house_control') {
        verify_csrf();

        $house = post_string(
            'house',
            255
        );

        if ($house === '') {
            flash(
                'error',
                'Название дома не указано.'
            );

            redirect([
                'module' => 'stat',
            ]);
        }

        try {
            /*
             * Обновляем дату/время контроля
             * в master_doma.
             */
            $control = $housesRepository->control(
                $house
            );

            if ($control === '') {
                throw new RuntimeException(
                    'Не удалось сохранить время контроля.'
                );
            }

            /*
             * Время получаем из сохранённого
             * значения базы данных.
             */
            $controlTime = new DateTimeImmutable(
                $control
            );

            $message =
                'Контроль дома '
                . telegram_html($house)
                . ' выполнен '
                . telegram_html(
                    $controlTime->format(
                        'd.m.Y H:i:s'
                    )
                );

            $telegramEnabled =
                (bool) (
                    $config['telegram']['enabled']
                    ?? false
                );

            $chatIds =
                $config['telegram']['chat_ids']
                ?? [];

            if (!$telegramEnabled) {
                flash(
                    'warning',
                    'Контроль сохранён, но Telegram отключён.'
                );
            } elseif (
                !is_array($chatIds)
                || !$chatIds
            ) {
                flash(
                    'warning',
                    'Контроль сохранён, но получатели Telegram не настроены.'
                );
            } else {
                $results = $telegram->sendToMany(
                    $chatIds,
                    $message
                );

                $sent = count(
                    array_filter(
                        $results,
                        static function (
                            bool $result
                        ): bool {
                            return $result;
                        }
                    )
                );

                $failed =
                    count($results)
                    - $sent;

                if (
                    $sent > 0
                    && $failed === 0
                ) {
                    flash(
                        'success',
                        'Контроль дома выполнен.'
                    );
                } elseif ($sent > 0) {
                    flash(
                        'warning',
                        'Контроль сохранён. Telegram: отправлено '
                        . $sent
                        . ', ошибок '
                        . $failed
                        . '.'
                    );
                } else {
                    flash(
                        'warning',
                        'Контроль сохранён, но сообщение в Telegram отправить не удалось.'
                    );
                }
            }
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'stat',
            'house' => $house,
        ]);
    }





    /*
    * Добавление QR-кода ящика.
    */
    if ($postAction === 'qr_add') {
        verify_csrf();

        $house = post_string(
            'house',
            255
        );

        $qrcode = post_string(
            'qrcode',
            4
        );

        $entrance = (int) (
            $_POST['entrance']
            ?? 0
        );

        if ($house === '') {
            flash(
                'error',
                'Название дома не указано.'
            );

            redirect([
                'module' => 'stat',
            ]);
        }

        try {
            $qrRepository->create(
                $qrcode,
                $house,
                $entrance
            );

            flash(
                'success',
                'QR-код ' . $qrcode . ' добавлен.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'stat',
            'house' => $house,
        ]);
    }

    /*
    * Удаление QR-кода ящика.
    */
    if ($postAction === 'qr_delete') {
        verify_csrf();

        $house = post_string(
            'house',
            255
        );

        $qrId = positive_int(
            $_POST['qr_id']
            ?? 0,
            0
        );

        if ($house === '') {
            flash(
                'error',
                'Название дома не указано.'
            );

            redirect([
                'module' => 'stat',
            ]);
        }

        try {
            $qrRepository->delete(
                $qrId,
                $house
            );

            flash(
                'success',
                'QR-код удалён.'
            );
        } catch (Throwable $exception) {
            flash(
                'error',
                $exception->getMessage()
            );
        }

        redirect([
            'module' => 'stat',
            'house' => $house,
        ]);
    }






    /*
     * Уведомление об отключении абонента.
     */

    if ($postAction === 'telegram_disconnect') {
        verify_csrf();

        $personal = post_string(
            'personal',
            20
        );

        $house = post_string(
            'house',
            100
        );

        $isAjax =
            (string) ($_POST['ajax'] ?? '')
            === '1';

        if ($personal === '') {
            flash(
                'error',
                'Лицевой счёт не указан.'
            );

            redirect([
                'module' => 'stat',
                'house' => $house,
            ]);
        }

        try {
            /*
            * Получаем актуальные данные абонента.
            */
            $subscriberData =
                $subscribers->paymentHistory(
                    $personal
                );

            $subscriberName = trim(
                (string) (
                    $subscriberData['subscriber']
                    ?? ''
                )
            );

            $subscriberAddress = trim(
                (string) (
                    $subscriberData['address']
                    ?? ''
                )
            );

            $subscriberPhone = trim(
                (string) (
                    $subscriberData['phone']
                    ?? ''
                )
            );

            $subscriberTariff = trim(
                (string) (
                    $subscriberData['tariff']
                    ?? ''
                )
            );

            $subscriberSumm = (float) (
                $subscriberData['balance']
                ?? 0
            );


            $disconnectAddress =
                $subscriberAddress !== ''
                    ? $subscriberAddress
                    : $house;

            /*
            * Проверяем, есть ли сейчас
            * активное отключение абонента.
            *
            * Это нужно и для AJAX с карточки квартиры,
            * и для обычной кнопки на странице абонента.
            */
            $stmt = $pdo->prepare(
                '
                SELECT
                    id,
                    created_at
                FROM master_otkluchki
                WHERE address = :address
                AND deleted_at IS NULL
                ORDER BY id DESC
                LIMIT 1
                '
            );

            $stmt->execute([
                ':address' => $disconnectAddress,
            ]);

            $disconnectRow = $stmt->fetch(
                PDO::FETCH_ASSOC
            );


            /*
            * Если абонент уже отключён
            * и запрос пришёл с карточки квартиры —
            * работает прежняя toggle-логика.
            */
            if (
                $disconnectRow !== false
                && $isAjax
            ) {
                $disconnectId = (int) (
                    $disconnectRow['id']
                    ?? 0
                );

                $createdAt = trim(
                    (string) (
                        $disconnectRow['created_at']
                        ?? ''
                    )
                );

                $createdTimestamp =
                    $createdAt !== ''
                        ? strtotime($createdAt)
                        : false;

                $ageSeconds =
                    $createdTimestamp !== false
                        ? time() - $createdTimestamp
                        : 61;

                /*
                * Если отключение сняли
                * в течение одной минуты —
                * считаем запись ошибочной
                * и удаляем её физически.
                */
                if (
                    $ageSeconds >= 0
                    && $ageSeconds <= 60
                ) {
                    $stmt = $pdo->prepare(
                        '
                        DELETE FROM master_otkluchki
                        WHERE id = :id
                        '
                    );

                    $stmt->execute([
                        ':id' => $disconnectId,
                    ]);
                } else {
                    /*
                    * Нормальное восстановление:
                    * сохраняем историю.
                    */
                    $stmt = $pdo->prepare(
                        '
                        UPDATE master_otkluchki
                        SET deleted_at = NOW()
                        WHERE id = :id
                        AND deleted_at IS NULL
                        '
                    );

                    $stmt->execute([
                        ':id' => $disconnectId,
                    ]);
                }

                header(
                    'Content-Type: application/json; charset=utf-8'
                );

                echo json_encode(
                    [
                        'success' => true,
                        'disconnected' => false,
                    ],
                    JSON_UNESCAPED_UNICODE
                );

                exit;
            }


            /*
            * Если абонент уже отключён,
            * а запрос пришёл обычной кнопкой
            * со страницы абонента —
            * это действие "Подключить".
            */
            if (
                $disconnectRow !== false
                && !$isAjax
            ) {
                $disconnectId = (int) (
                    $disconnectRow['id']
                    ?? 0
                );

                /*
                * Снимаем активное отключение.
                */
                $stmt = $pdo->prepare(
                    '
                    UPDATE master_otkluchki
                    SET deleted_at = NOW()
                    WHERE id = :id
                    AND deleted_at IS NULL
                    '
                );

                $stmt->execute([
                    ':id' => $disconnectId,
                ]);

                /*
                * Сообщение о подключении.
                */
                $message =
                    "🟢 <b>Абонент подключён</b>\n"
                    . '<b>Абонент:</b> '
                    . telegram_html(
                        $subscriberName !== ''
                            ? $subscriberName
                            : 'Без имени'
                    )
                    . "\n"
                    . '<b>Адрес:</b> '
                    . telegram_html(
                        $subscriberAddress !== ''
                            ? $subscriberAddress
                            : $house
                    )
                    . "\n"
                    . '<b>Лицевой счёт:</b> '
                    . telegram_html($personal)
                    . "\n"
                    . '<b>Состояние счёта:</b> '
                    . telegram_html(
                        number_format(
                            $subscriberSumm,
                            2,
                            ',',
                            ' '
                        )
                    )
                    . "\n"
                    . '<b>Тариф:</b> '
                    . telegram_html(
                        $subscriberTariff !== ''
                            ? $subscriberTariff
                            : 'Нет договора'
                    );

                $telegramEnabled =
                    (bool) (
                        $config['telegram']['enabled']
                        ?? false
                    );

                $chatIds =
                    $config['telegram']['chat_ids']
                    ?? [];

                if (!$telegramEnabled) {
                    flash(
                        'warning',
                        'Абонент подключён, но отправка в Telegram отключена.'
                    );
                } elseif (
                    !is_array($chatIds)
                    || !$chatIds
                ) {
                    flash(
                        'warning',
                        'Абонент подключён, но получатели Telegram не настроены.'
                    );
                } else {
                    $results =
                        $telegram->sendToMany(
                            $chatIds,
                            $message
                        );

                    $sent = count(
                        array_filter(
                            $results,
                            static fn(
                                bool $result
                            ): bool => $result
                        )
                    );

                    $failed =
                        count($results)
                        - $sent;

                    if (
                        $sent > 0
                        && $failed === 0
                    ) {
                        flash(
                            'success',
                            'Абонент подключён. Сообщение отправлено: '
                            . $sent
                            . ' получателям.'
                        );
                    } elseif ($sent > 0) {
                        flash(
                            'warning',
                            'Абонент подключён. Telegram: отправлено '
                            . $sent
                            . ', ошибок: '
                            . $failed
                            . '.'
                        );
                    } else {
                        flash(
                            'warning',
                            'Абонент подключён, но сообщение в Telegram отправить не удалось.'
                        );
                    }
                }

                redirect([
                    'module' => 'stat',
                    'house' => $house,
                    'personal' => $personal,
                ]);
            }



            /*
            * Если тариф по какой-то причине пустой,
            * считаем, что договора нет.
            */
            if ($subscriberTariff === '') {
                $subscriberTariff = 'Нет договора';
            }

            /*
            * Записываем отключение в базу ВСЕГДА,
            * в том числе для "Нет договора".
            */
            $stmt = $pdo->prepare(
                '
                INSERT INTO master_otkluchki (
                    created_at,
                    address,
                    subscriber,
                    personal,
                    phone,
                    tariff,
                    summ
                ) VALUES (
                    NOW(),
                    :address,
                    :subscriber,
                    :personal,
                    :phone,
                    :tariff,
                    :summ
                )
                '
            );

            $stmt->execute([
                ':address' => $subscriberAddress !== ''
                    ? $subscriberAddress
                    : $house,
                ':subscriber' => $subscriberName,
                ':personal' => $personal,
                ':phone' => $subscriberPhone,
                ':tariff' => $subscriberTariff,
                ':summ' => $subscriberSumm,
            ]);

            /*
            * Если договора нет —
            * запись уже сохранена,
            * Telegram не отправляем.
            */
            if ($subscriberTariff === 'Нет договора') {
                if ($isAjax) {
                    header(
                        'Content-Type: application/json; charset=utf-8'
                    );

                    echo json_encode(
                        [
                            'success' => true,
                            'disconnected' => true,
                        ],
                        JSON_UNESCAPED_UNICODE
                    );

                    exit;
                }

                flash(
                    'success',
                    'Отключение сохранено.'
                );

                redirect([
                    'module' => 'stat',
                    'house' => $house,
                    'personal' => $personal,
                ]);
            }

            /*
            * Формируем Telegram-сообщение
            * только для действующего договора.
            */
            $message =
                "🔴 <b>Абонент отключён</b>\n"
                . '<b>Абонент:</b> '
                . telegram_html(
                    $subscriberName !== ''
                        ? $subscriberName
                        : 'Без имени'
                )
                . "\n"
                . '<b>Адрес:</b> '
                . telegram_html(
                    $subscriberAddress !== ''
                        ? $subscriberAddress
                        : $house
                )
                . "\n"
                . '<b>Лицевой счёт:</b> '
                . telegram_html($personal)
                . "\n"
                . '<b>Задолженность:</b> '
                . telegram_html($subscriberSumm)
                . "\n"
                . '<b>Тариф:</b> '
                . telegram_html(
                    $subscriberTariff
                );

            $telegramEnabled =
                (bool) (
                    $config['telegram']['enabled']
                    ?? false
                );

            $chatIds =
                $config['telegram']['chat_ids']
                ?? [];

            if (!$telegramEnabled) {
                flash(
                    'warning',
                    'Отключение сохранено, но отправка в Telegram отключена.'
                );
            } elseif (
                !is_array($chatIds)
                || !$chatIds
            ) {
                flash(
                    'warning',
                    'Отключение сохранено, но получатели Telegram не настроены.'
                );
            } else {
                $results =
                    $telegram->sendToMany(
                        $chatIds,
                        $message
                    );

                $sent = count(
                    array_filter(
                        $results,
                        static fn(
                            bool $result
                        ): bool => $result
                    )
                );

                $failed =
                    count($results)
                    - $sent;

                if (
                    $sent > 0
                    && $failed === 0
                ) {
                    flash(
                        'success',
                        'Отключение сохранено. Сообщение отправлено: '
                        . $sent
                        . ' получателям.'
                    );
                } elseif ($sent > 0) {
                    flash(
                        'warning',
                        'Отключение сохранено. Telegram: отправлено '
                        . $sent
                        . ', ошибок: '
                        . $failed
                        . '.'
                    );
                } else {
                    flash(
                        'warning',
                        'Отключение сохранено, но сообщение в Telegram отправить не удалось.'
                    );
                }
            }
        } catch (Throwable $exception) {
            if ($isAjax) {
                header(
                    'Content-Type: application/json; charset=utf-8'
                );

                http_response_code(500);

                echo json_encode(
                    [
                        'success' => false,
                        'error' =>
                            'Не удалось зарегистрировать отключение: '
                            . $exception->getMessage(),
                    ],
                    JSON_UNESCAPED_UNICODE
                );

                exit;
            }

            flash(
                'error',
                'Не удалось зарегистрировать отключение: '
                . $exception->getMessage()
            );
        }

        /*
        * AJAX-вызов со страницы дома:
        * ничего не перезагружаем.
        */
        if ($isAjax) {
            header(
                'Content-Type: application/json; charset=utf-8'
            );

            echo json_encode(
                [
                    'success' => true,
                    'disconnected' => true,
                ],
                JSON_UNESCAPED_UNICODE
            );

            exit;
        }

        /*
        * Обычная кнопка "Отключить"
        * на странице абонента работает как раньше.
        */
        redirect([
            'module' => 'stat',
            'house' => $house,
            'personal' => $personal,
        ]);
    }
}
