<?php

declare(strict_types=1);


/** @var \PDO $pdo */

/*
 * Авторизация, параметры запроса и создание Repository/Service.
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */

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

if (
    $module === 'money'
    && current_user() === 'sanya'
) {
    http_response_code(403);

    flash(
        'error',
        'Доступ к модулю запрещён.'
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


$strokaStatus = get_string(
    'stroka_status',
    20
);

if (
    !in_array(
        $strokaStatus,
        [
            'all',
            'active',
            'scheduled',
            'expired',
            'deleted',
        ],
        true
    )
) {
    $strokaStatus = 'all';
}

$strokaDate = get_string(
    'stroka_date',
    10
);

if (
    !preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $strokaDate
    )
) {
    $strokaDate = date('Y-m-d');
}


$status = 'open';

$withoutCharges =
    isset($_GET['without_charges'])
    && $_GET['without_charges'] === '1';

$withoutPayments =
    isset($_GET['without_payments'])
    && $_GET['without_payments'] === '1';

$tickets = new TicketRepository($pdo);
$connections = new ConnectionRepository($pdo);
$subscribers = new SubscriberRepository($pdo);
$karandash = new KarandashRepository($pdo);
$smsRepository = new SmsRepository($pdo);
$housesRepository = new HouseRepository($pdo);
$qrRepository = new QrRepository($pdo);
$strokaRepository = new StrokaRepository($pdo);
$channels = new ChannelService($config['channels'] ?? []);
$digitalRepository = new DigitalChannelRepository($pdo);
$digitalChannels = new DigitalChannelService($config['digital'] ?? []);


$apartmentGroupSize = 4;
$houseControl = '';
$houseDescr = '';
$qrRows = [];
