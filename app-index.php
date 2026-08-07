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
$page = positive_int($_GET['page'] ?? 1);
$perPage = max(10, min(100, (int)($config['app']['per_page'] ?? 30)));
$offset = ($page - 1) * $perPage;
$search = get_string('search', 100);
$status = in_array(($_GET['status'] ?? 'all'), ['all', 'open', 'done'], true)
    ? (string) ($_GET['status'] ?? 'all')
    : 'all';

$tickets = new TicketRepository($pdo);
$connections = new ConnectionRepository($pdo);
$subscribers = new SubscriberRepository($pdo);
$karandash = new KarandashRepository($pdo);
$groups = new GroupRepository($pdo);
$channels = new ChannelService($config['channels'] ?? []);


$apartmentGroupSize = 4;


if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && get_string('ajax', 30) === 'subscriber_search'
) {
    $query = get_string('query', 100);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store');

    echo json_encode(
        [
            'items' => $subscribers->addressSuggestions(
                $query,
                10
            ),
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && post_string('ajax', 30) === 'save_apartment_group'
) {
    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store');

    try {
        verify_csrf();

        $house = post_string('house', 255);
        $groupSize = (int) (
            $_POST['group_size'] ?? 4
        );

        if ($house === '') {
            throw new InvalidArgumentException(
                'Название дома не указано.'
            );
        }

        if ($groupSize < 0 || $groupSize > 6) {
            throw new InvalidArgumentException(
                'Допустимое значение — от 0 до 6.'
            );
        }

        $groups->saveSize(
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

    $message = $isWithdrawn
        ? "⛔ <b>Заявка снята</b>\n\n"
        : "✅ <b>Заявка выполнена</b>\n\n";

    $abonent = trim(
        (string) (
            $ticket['abonent_ajax']
            ?? $ticket['abonent']
            ?? ''
        )
    );

    $address = trim(
        (string) (
            $ticket['address_ajax']
            ?? $ticket['address']
            ?? ''
        )
    );

    $description = trim(
        (string) ($ticket['desc'] ?? '')
    );

    $other = trim(
        (string) ($ticket['other'] ?? '')
    );

    $message .=
        '<b>Заявка №:</b> '
        . telegram_html($ticket['id'] ?? '')
        . "\n";

    if ($abonent !== '') {
        $message .=
            '<b>Абонент:</b> '
            . telegram_html($abonent)
            . "\n";
    }

    if ($address !== '') {
        $message .=
            '<b>Адрес:</b> '
            . telegram_html($address)
            . "\n";
    }

    if ($description !== '') {
        $message .=
            '<b>Описание:</b> '
            . telegram_html($description)
            . "\n";
    }

    if ($other !== '') {
        $message .=
            '<b>Дополнительно:</b> '
            . telegram_html($other)
            . "\n";
    }

    $message .=
        '<b>Мастер:</b> '
        . telegram_html($master)
        . "\n"
        . '<b>Результат:</b> '
        . telegram_html($result);

    if ($cost !== '') {
        $message .=
            "\n"
            . '<b>Стоимость:</b> '
            . telegram_html($cost);
    }

    $message .=
        "\n"
        . '<b>Время:</b> '
        . telegram_html(date('d.m.Y H:i:s'));

    return $message;
}

function connection_telegram_message(
    array $connection,
    string $action,
    string $master,
    string $result
): string {
    $isWithdrawn = $action === 'withdraw';

    $message = $isWithdrawn
        ? "⛔ <b>Подключение снято</b>\n\n"
        : "✅ <b>Подключение завершено</b>\n\n";

    $abonent = trim(
        (string) ($connection['abonent'] ?? '')
    );

    $address = trim(
        (string) ($connection['address'] ?? '')
    );

    $description = trim(
        (string) ($connection['desc'] ?? '')
    );

    $other = trim(
        (string) ($connection['other'] ?? '')
    );

    $message .=
        '<b>Подключение №:</b> '
        . telegram_html($connection['id'] ?? '')
        . "\n";

    if ($abonent !== '') {
        $message .=
            '<b>Абонент:</b> '
            . telegram_html($abonent)
            . "\n";
    }

    if ($address !== '') {
        $message .=
            '<b>Адрес:</b> '
            . telegram_html($address)
            . "\n";
    }

    if ($description !== '') {
        $message .=
            '<b>Описание:</b> '
            . telegram_html($description)
            . "\n";
    }

    if ($other !== '') {
        $message .=
            '<b>Дополнительно:</b> '
            . telegram_html($other)
            . "\n";
    }

    $message .=
        '<b>Мастер:</b> '
        . telegram_html($master)
        . "\n"
        . '<b>Результат:</b> '
        . telegram_html($result)
        . "\n"
        . '<b>Время:</b> '
        . telegram_html(date('d.m.Y H:i:s'));

    return $message;
}








if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = post_string('action', 30);

    if ($postAction === 'telegram_disconnect') {
        verify_csrf();

        $personal = post_string('personal', 20);
        $house = post_string('house', 100);

        if ($personal === '') {
            flash('error', 'Лицевой счёт не указан.');

            redirect([
                'module' => 'stat',
                'house' => $house,
            ]);
        }

        $subscriberData = $subscribers->paymentHistory(
            $personal
        );

        $subscriberName = trim(
            (string) ($subscriberData['subscriber'] ?? '')
        );

        $subscriberAddress = trim(
            (string) ($subscriberData['address'] ?? '')
        );

        $subscriberTariff = trim(
            (string) ($subscriberData['tariff'] ?? '')
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
                . telegram_html($subscriberTariff);
        }

        // $message .=
        //     "\n"
        //     . '<b>Сообщил:</b> '
        //     . telegram_html(current_user());

        $telegramEnabled =
            (bool) ($config['telegram']['enabled'] ?? false);

        $chatIds = $config['telegram']['chat_ids'] ?? [];

        if (!$telegramEnabled) {
            flash(
                'error',
                'Отправка в Telegram отключена.'
            );
        } elseif (!is_array($chatIds) || !$chatIds) {
            flash(
                'error',
                'Получатели Telegram не настроены.'
            );
        } else {
            $results = $telegram->sendToMany(
                $chatIds,
                $message
            );

            $sent = count(
                array_filter(
                    $results,
                    static fn(bool $result): bool => $result
                )
            );

            $failed = count($results) - $sent;

            if ($sent > 0 && $failed === 0) {
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





if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postAction = post_string('action', 30);
    $id = positive_int($_POST['id'] ?? 0, 0);




    if ($postAction === 'karandash_add') {
        $personal = post_string('personal', 20);
        $house = post_string('house', 100);
        $address = post_string('address', 255);
        $descr = post_string('descr', 2000);
        $returnModule = post_string('return_module', 30);

        $returnModule = $returnModule === 'karandash'
            ? 'karandash'
            : 'stat';

        if ($address === '') {
            flash(
                'error',
                'Не удалось определить адрес абонента.'
            );

            if ($returnModule === 'karandash') {
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

        $alreadyExists = $karandash->exists($address);

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

        if ($returnModule === 'karandash') {
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

            $address = post_string('address', 50);
            $abonent = post_string('abonent', 50);
            $description = post_string('description', 50);
            $other = post_string('other', 50);

            if ($address === '') {
                flash(
                    'error',
                    'Укажите адрес абонента.'
                );

                redirect(['module' => 'zayavki']);
            }

            if ($abonent === '') {
                flash(
                    'error',
                    'Укажите ФИО абонента.'
                );

                redirect(['module' => 'zayavki']);
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

                redirect(['module' => 'zayavki']);
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

            flash('success', 'Заявка добавлена');
        } elseif (
            $postAction === 'complete'
            && $id > 0
        ) {
            $ticket = $tickets->find($id);

            if ($ticket === null) {
                flash('error', 'Заявка не найдена');
                redirect(['module' => 'zayavki']);
            }

            $master = post_string('master', 50);
            $result = post_string('result', 50);
            $cost = post_string('cost', 10);

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

            flash('success', 'Заявка выполнена');
        } elseif (
            $postAction === 'withdraw'
            && $id > 0
        ) {
            $ticket = $tickets->find($id);

            if ($ticket === null) {
                flash('error', 'Заявка не найдена');
                redirect(['module' => 'zayavki']);
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

            flash('success', 'Заявка снята');
        }

        redirect(['module' => 'zayavki']);
    }


    









    if ($module === 'podkluchki') {
        if ($postAction === 'create') {
            $connections->create([
                'abonent' => post_string('abonent', 50),
                'address' => post_string('address', 50),
                'other' => post_string('other', 50),
                'description' => post_string(
                    'description',
                    50
                ),
                'who' => current_user(),
            ]);

            flash('success', 'Подключение добавлено');
        } elseif (
            $postAction === 'complete'
            && $id > 0
        ) {
            $connection = $connections->find($id);

            if ($connection === null) {
                flash('error', 'Подключение не найдено');
                redirect(['module' => 'podkluchki']);
            }

            $master = post_string('master', 50);
            $result = post_string('result', 50);

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

            flash('success', 'Подключение завершено');
        } elseif (
            $postAction === 'withdraw'
            && $id > 0
        ) {
            $connection = $connections->find($id);

            if ($connection === null) {
                flash('error', 'Подключение не найдено');
                redirect(['module' => 'podkluchki']);
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

            flash('success', 'Подключение снято');
        }

        redirect(['module' => 'podkluchki']);
    }






}

$titles = [
    'podkluchki' => 'Подключения',
    'database' => 'Абоненты',
    'stat' => 'Статистика',
    'debtors' => 'Должники',
    'karandash' => 'Карандаш',
    'channels' => 'Каналы',
];

$title = isset($titles[$module]) ? $titles[$module] : 'Заявки';

$data = [];
if ($module === 'zayavki') $data = $tickets->list($search, $status, $perPage, $offset);
if ($module === 'podkluchki') $data = $connections->list($search, $status, $perPage, $offset);
if ($module === 'database' && $action !== 'history') $data = $subscribers->list($search, $perPage, $offset);
if ($module === 'database' && $action === 'history') $data = ['rows' => $subscribers->history(get_string('personal', 10)), 'total' => 0];
if ($module === 'stat') {
    $selectedHouse = trim((string) ($_GET['house'] ?? ''));
    $selectedPersonal = trim((string) ($_GET['personal'] ?? ''));

    if ($selectedPersonal !== '') {
        $data = $subscribers->paymentHistory($selectedPersonal);

        $houses = [];
        $apartments = [];
        $payments = $data['payments'] ?? [];
        $house = $selectedHouse;
        $personal = $data['personal'] ?? $selectedPersonal;
        $subscriber = $data['subscriber'] ?? '';
        $subscriberAddress = $data['address'] ?? '';
        $subscriberTariff = $data['tariff'] ?? '';
        $subscriberDebt = (float) ($data['debt'] ?? 0);

        $subscriberKarandash = $subscriberAddress !== ''
            ? $karandash->findByAddress($subscriberAddress)
            : null;

        $subscriberKarandashDescr = trim(
            (string) ($subscriberKarandash['descr'] ?? '')
        );

        $subscriberOnKarandash = $subscriberKarandash !== null;

        $update = '';
    } elseif ($selectedHouse !== '') {
        $data = $subscribers->apartments(
            $selectedHouse
        );

        $houses = [];
        $apartments = $data['apartments'] ?? [];
        $payments = [];

        $house = $data['house'] ?? $selectedHouse;
        $personal = '';
        $update = $data['update'] ?? '';

        /*
        * Если дом ещё отсутствует в master_groups,
        * используется значение по умолчанию — 4.
        */
        $apartmentGroupSize = $groups->getSize(
            $house,
            4
        );
    } else {
        $data = $subscribers->houses();

        $houses = $data['houses'] ?? [];
        $apartments = [];
        $payments = [];

        $house = '';
        $personal = '';
        $update = $data['update'] ?? '';
    }
}
if ($module === 'debtors') {
    $data = $subscribers->debtors();
}

if ($module === 'karandash') {
    $data = $karandash->groupedByHouse();
}


if ($module === 'channels') {
    $data = [
        'devices' => $channels->getAll(),
    ];
}

$flashes = consume_flashes();
require __DIR__ . '/app/view.php';
