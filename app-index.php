<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/TelegramService.php';

$telegram = new TelegramService(
    (string) ($config['telegram']['bot_token'] ?? '')
);

require_auth(
    $config['auth'] ?? [],
    $telegram,
    $config['telegram'] ?? []
);

$module = get_string('module', 30) ?: 'zayavki';
$action = get_string('action', 30);

if (
    $module === 'terminal'
    && current_user() !== 'admin'
) {
    http_response_code(403);

    flash(
        'error',
        'Доступ к терминалу разрешён только администратору.'
    );

    redirect([
        'module' => 'zayavki',
    ]);
}

$page = positive_int($_GET['page'] ?? 1);

$perPage = max(
    10,
    min(
        100,
        (int) ($config['app']['per_page'] ?? 30)
    )
);

$offset = ($page - 1) * $perPage;

$search = get_string('search', 100);

$status = 'open';

$tickets = new TicketRepository($pdo);
$connections = new ConnectionRepository($pdo);
$subscribers = new SubscriberRepository($pdo);
$karandash = new KarandashRepository($pdo);
$housesRepository = new HouseRepository($pdo);
$channels = new ChannelService($config['channels'] ?? []);
$digitalRepository = new DigitalChannelRepository($pdo);
$digitalChannels = new DigitalChannelService($config['digital'] ?? []);


$apartmentGroupSize = 4;
$houseControl = '';
$houseDescr = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && get_string('ajax', 30) === 'subscriber_lookup'
) {
    $address = get_string('address', 100);

    header(
        'Content-Type: application/json; charset=utf-8'
    );
    header('Cache-Control: no-store');

    $subscriber = $subscribers->findLatestByAddress(
        $address
    );

    $ticketsCount = $tickets->countByAddress(
        $address
    );

    echo json_encode(
        [
            'found' => $subscriber !== null,
            'subscriber' => $subscriber,
            'tickets_count' => $ticketsCount,
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && post_string(
        'ajax',
        30
    ) === 'database_import'
) {
    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'Cache-Control: no-store'
    );

    try {
        verify_csrf();

        if (
            !isset($_FILES['db'])
            || !is_array($_FILES['db'])
        ) {
            throw new RuntimeException(
                'Файл не передан.'
            );
        }

        $file = $_FILES['db'];

        $error = (int) (
            $file['error']
            ?? UPLOAD_ERR_NO_FILE
        );

        if ($error !== UPLOAD_ERR_OK) {
            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = 'Файл превышает допустимый размер.';
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $message = 'Файл был загружен не полностью.';
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $message = 'Файл не выбран.';
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = 'На сервере отсутствует временная директория.';
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $message = 'Не удалось записать файл на диск.';
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $message = 'Загрузка файла остановлена расширением PHP.';
                    break;

                default:
                    $message = 'Ошибка загрузки файла.';
                    break;
            }

            throw new RuntimeException(
                $message
            );
        }

        $tmpName = (string) (
            $file['tmp_name']
            ?? ''
        );

        if (
            $tmpName === ''
            || !is_uploaded_file($tmpName)
        ) {
            throw new RuntimeException(
                'Получен некорректный загруженный файл.'
            );
        }

        $result =
            $subscribers->importDatabaseFile(
                $tmpName
            );

        echo json_encode(
            [
                'success' => true,
                'inserted' =>
                    $result['inserted'],
                'skipped' =>
                    $result['skipped'],
                'update' =>
                    $result['update'],
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    } catch (Throwable $exception) {
        http_response_code(422);

        echo json_encode(
            [
                'success' => false,
                'error' =>
                    $exception->getMessage(),
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    }

    exit;
}



if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && post_string(
        'ajax',
        30
    ) === 'save_apartment_group'
) {
    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store');

    try {
        verify_csrf();

        $house = post_string(
            'house',
            255
        );

        $groupSize = (int) (
            $_POST['group_size']
            ?? 4
        );

        if ($house === '') {
            throw new InvalidArgumentException(
                'Название дома не указано.'
            );
        }

        if (
            $groupSize < 0
            || $groupSize > 6
        ) {
            throw new InvalidArgumentException(
                'Допустимое значение — от 0 до 6.'
            );
        }

        $housesRepository->saveSize(
            $house,
            $groupSize
        );

        echo json_encode(
            [
                'success' => true,
                'house' => $house,
                'group_size' => $groupSize,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    } catch (Throwable $exception) {
        http_response_code(422);

        echo json_encode(
            [
                'success' => false,
                'error' => $exception->getMessage(),
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    exit;
}

$subscriberDebt = 0.0;





function ticket_telegram_message(
    array $ticket,
    string $action,
    string $master,
    string $result,
    string $cost = ''
): string {
    $isWithdrawn = $action === 'withdraw';

    $abonent = trim(
        (string) ($ticket['abonent'] ?? '')
    );

    if ($abonent === '') {
        $abonent = trim(
            (string) ($ticket['abonent_ajax'] ?? '')
        );
    }

    $address = trim(
        (string) ($ticket['address'] ?? '')
    );

    if ($address === '') {
        $address = trim(
            (string) ($ticket['address_ajax'] ?? '')
        );
    }

    $id = telegram_html(
        $ticket['id'] ?? ''
    );

    if ($isWithdrawn) {
        $message =
            '⛔ <b>Заявка № '
            . $id
            . ' снята</b>';
    } else {
        $message =
            '✅ <b>Заявка № '
            . $id
            . ' выполнена</b>';
    }

    if ($abonent !== '') {
        $message .=
            "\n"
            . 'ФИО: '
            . telegram_html($abonent);
    }

    if ($address !== '') {
        $message .=
            "\n"
            . 'Адрес: '
            . telegram_html($address);
    }

    if ($result !== '') {
        $message .=
            "\n"
            . '<b>Результат:</b> '
            . telegram_html($result);
    }

    return $message;
}






function connection_telegram_message(
    array $connection,
    string $action,
    string $master,
    string $result
): string {
    $isWithdrawn = $action === 'withdraw';

    $abonent = trim(
        (string) ($connection['abonent'] ?? '')
    );

    $address = trim(
        (string) ($connection['address'] ?? '')
    );

    $id = telegram_html(
        (string) ($connection['id'] ?? '')
    );

    if ($isWithdrawn) {
        $message =
            '⛔ <b>Подключение № '
            . $id
            . ' снято</b>';
    } else {
        $message =
            '✅ <b>Подключение № '
            . $id
            . ' выполнено</b>';
    }

    if ($abonent !== '') {
        $message .=
            "\n"
            . 'ФИО: '
            . telegram_html($abonent);
    }

    if ($address !== '') {
        $message .=
            "\n"
            . 'Адрес: '
            . telegram_html($address);
    }

    if ($result !== '') {
        $message .=
            "\n"
            . '<b>Результат:</b> '
            . telegram_html($result);
    }

    return $message;
}





/*
 * Действия страницы статистики,
 * связанные с Telegram и контролем дома.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = post_string(
        'action',
        30
    );



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

        $subscriberTariff = trim(
            (string) (
                $subscriberData['tariff']
                ?? ''
            )
        );

        $message =
            "🔴 <b>Абонент отключён</b>\n\n"
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
            . telegram_html($personal);

        if ($subscriberTariff !== '') {
            $message .=
                "\n"
                . '<b>Тариф:</b> '
                . telegram_html(
                    $subscriberTariff
                );
        }

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
                'error',
                'Отправка в Telegram отключена.'
            );
        } elseif (
            !is_array($chatIds)
            || !$chatIds
        ) {
            flash(
                'error',
                'Получатели Telegram не настроены.'
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
                    'Сообщение отправлено: '
                    . $sent
                    . ' получателям.'
                );
            } elseif ($sent > 0) {
                flash(
                    'warning',
                    'Отправлено: '
                    . $sent
                    . ', ошибок: '
                    . $failed
                    . '.'
                );
            } else {
                flash(
                    'error',
                    'Не удалось отправить сообщение в Telegram.'
                );
            }
        }

        redirect([
            'module' => 'stat',
            'house' => $house,
            'personal' => $personal,
        ]);
    }
}

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
            $connections->create([
                'abonent' => post_string(
                    'abonent',
                    50
                ),
                'address' => post_string(
                    'address',
                    50
                ),
                'other' => post_string(
                    'other',
                    50
                ),
                'description' => post_string(
                    'description',
                    50
                ),
                'who' => current_user(),
            ]);

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

$titles = [
    'podkluchki' => 'Подключения',
    'database' => 'Абоненты',
    'graph' => 'График',
    'stat' => 'Статистика',
    'debtors' => 'Должники',
    'karandash' => 'Карандаш',
    'analog' => 'Аналог',
    'digital' => 'Цифра',
    'terminal' => 'Terminal',
];

$title = isset($titles[$module])
    ? $titles[$module]
    : 'Заявки';

$data = [];

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

if (
    $module === 'database'
    && $action !== 'history'
) {
    $data = $subscribers->list(
        $search,
        $perPage,
        $offset
    );
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
            $data['payments']
            ?? [];

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

        $payments = [];

        $house =
            $data['house']
            ?? $selectedHouse;

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

$flashes = consume_flashes();

require __DIR__ . '/app/view.php';