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

    if ($module === 'zayavki') {
        if ($postAction === 'create') {
            $tickets->create([
                'abonent' => post_string('abonent', 50),
                'abonent_ajax' => post_string('abonent_ajax', 50),
                'address' => post_string('address', 50),
                'address_ajax' => post_string('address_ajax', 50),
                'other' => post_string('other', 50),
                'description' => post_string('description', 50),
                'cost' => post_string('cost', 10),
                'who' => current_user(),
            ]);
            flash('success', 'Заявка добавлена');
        } elseif ($postAction === 'complete' && $id > 0) {
            $tickets->complete($id, post_string('master', 50), post_string('result', 50), post_string('cost', 10));
            flash('success', 'Заявка выполнена');
        } elseif ($postAction === 'delete' && $id > 0) {
            $tickets->delete($id);
            flash('success', 'Заявка удалена');
        }
        redirect(['module' => 'zayavki']);
    }

    if ($module === 'podkluchki') {
        if ($postAction === 'create') {
            $connections->create([
                'abonent' => post_string('abonent', 50),
                'address' => post_string('address', 50),
                'other' => post_string('other', 50),
                'description' => post_string('description', 50),
                'who' => current_user(),
            ]);
            flash('success', 'Подключение добавлено');
        } elseif ($postAction === 'complete' && $id > 0) {
            $connections->complete($id, post_string('master', 50), post_string('result', 50));
            flash('success', 'Подключение завершено');
        } elseif ($postAction === 'delete' && $id > 0) {
            $connections->delete($id);
            flash('success', 'Подключение удалено');
        }
        redirect(['module' => 'podkluchki']);
    }
}

$titles = [
    'podkluchki' => 'Подключения',
    'database' => 'Абоненты',
    'stat' => 'Статистика',
    'debtors' => 'Должники',
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
        $update = '';
    } elseif ($selectedHouse !== '') {
        $data = $subscribers->apartments($selectedHouse);

        $houses = [];
        $apartments = $data['apartments'] ?? [];
        $payments = [];

        $house = $data['house'] ?? $selectedHouse;
        $personal = '';
        $update = $data['update'] ?? '';
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

$flashes = consume_flashes();
require __DIR__ . '/app/view.php';
