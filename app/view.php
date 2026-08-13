<?php

declare(strict_types=1);

require_once __DIR__ . '/SmsButton.php';

/** @var string $title */
/** @var string $module */
/** @var string $status */
/** @var array $content */
/** @var array $flashes */
/** @var string $search */
/** @var string $action */
/** @var array $config */
/** @var array $data */
/** @var int $page */
/** @var int $perPage */
/** @var int $house */
/** @var array $apartments */
/** @var array $payments */
/** @var string $personal */
/** @var string $subscriber */
/** @var string $subscriberAddress */
/** @var string $subscriberPhone */
/** @var string $subscriberTariff */
/** @var string $subscriberOnKarandash */
/** @var string $subscriberKarandashDescr */
/** @var string $houseDescr */
/** @var float $subscriberDebt */
/** @var bool $withoutCharges */
/** @var bool $withoutPayments */
$subscriberDebt = isset($subscriberDebt)
    ? (float) $subscriberDebt
    : 0.0;

$houseDescr = isset($houseDescr)
    ? trim((string) $houseDescr)
    : '';

$withoutCharges = isset($withoutCharges)
    ? (bool) $withoutCharges
    : false;

$withoutPayments = isset($withoutPayments)
    ? (bool) $withoutPayments
    : false;

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title><?= e($title) ?> — <?= e($config['app']['name'] ?? 'Master') ?></title>
<?php
$cssFile = __DIR__ . '/../assets/app.css';
$cssVersion = is_file($cssFile)
    ? filemtime($cssFile)
    : time();


$jsFile = __DIR__ . '/../assets/app.js';
$jsVersion = is_file($jsFile)
    ? filemtime($jsFile)
    : time();    
?>

<?php if ($module === 'terminal'): ?>
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@xterm/xterm/css/xterm.css"
>

<script
    src="https://cdn.jsdelivr.net/npm/@xterm/xterm/lib/xterm.js"
    defer
></script>

<script
    src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit/lib/addon-fit.js"
    defer
></script>
<?php endif; ?>



<link
    rel="stylesheet"
    href="assets/app.css?v=<?= e((string) $cssVersion) ?>"
>
    <script src="assets/app.js?v=<?= e((string) $jsVersion) ?>" defer></script>
</head>
<body>

<div
    class="page-loader"
    data-page-loader
    role="status"
    aria-live="polite"
    aria-label="Загрузка страницы"
>
    <div class="page-loader__spinner"></div>
    <div class="page-loader__text">Загрузка…</div>
</div>

<header class="topbar">
    <button class="icon-button menu-button" type="button" data-menu-toggle aria-label="Открыть меню">☰</button>
    <a class="brand" href="<?= e(url(['module' => 'zayavki'])) ?>">Master2</a>
    <div class="topbar-title"><?= e($title) ?></div>
    <form
        method="post"
        class="user-badge user-badge--logout"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="auth_action"
            value="logout"
        >

        <button
            type="submit"
            class="user-badge__button"
            title="Выйти"
            aria-label="Выйти из системы"
        >
            <?= e(current_user()) ?>
        </button>
    </form>
</header>
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <nav>
            <?php
                $navigation = [
                    ['zayavki', 'Заявки', '☑'],
                    ['podkluchki', 'Подключения', '🔌'],
                    ['database', 'Абоненты', '👥'],
                    ['graph', 'График', '⌁'],
                    ['stat', 'Статистика', '▦'],
                    ['debtors', 'Должники', '₽'],
                    ['karandash', 'Карандаш', '✎'],
                    ['analog', 'Аналог', '▤'],
                    ['digital', 'Цифра', '▥'],
                ];

                if (current_user() === 'admin') {
                    $navigation[] = [
                        'terminal',
                        'Terminal',
                        '⌨',
                    ];
                }

            ?>

            <?php foreach ($navigation as [$key, $label, $icon]): ?>
                <a class="nav-item <?= $module === $key ? 'active' : '' ?>" href="<?= e(url(['module' => $key])) ?>"><span><?= $icon ?></span><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <button class="backdrop" type="button" data-menu-close aria-label="Закрыть меню"></button>
    <main class="content">
        <?php foreach ($flashes as $flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endforeach; ?>
 
        <?php if (in_array($module, ['zayavki','podkluchki','database'], true) && !($module === 'database' && $action === 'history')): ?>
            <section class="toolbar">
                <form class="search-form" method="get">
                    <input type="hidden" name="module" value="<?= e($module) ?>">
                    <input class="input" type="search" name="search" value="<?= e($search) ?>" placeholder="Поиск…" autocomplete="off">
                    <?php if ($module !== 'database'): ?>
                    <select
                        class="input select"
                        id="status-filter"
                        name="status"
                    >
                        <option value="all">
                            Все
                        </option>

                        <option value="open" selected>
                            Открытые
                        </option>

                        <option value="done">
                            Выполненные
                        </option>
                    </select>
                <?php endif; ?>

                <button class="button" type="submit">
                    Найти
                </button>

                <?php if ($module === 'database'): ?>

                    <label class="search-checkbox">
                        <input
                            type="checkbox"
                            name="without_charges"
                            value="1"
                            <?= $withoutCharges ? 'checked' : '' ?>
                        >
                        <span>Без начислений</span>
                    </label>

                    <label class="search-checkbox">
                        <input
                            type="checkbox"
                            name="without_payments"
                            value="1"
                            <?= $withoutPayments ? 'checked' : '' ?>
                        >
                        <span>Без оплаты</span>
                    </label>

                <?php endif; ?>

                </form>
                <?php if ($module !== 'database'): ?>
                    <button
                        class="button primary"
                        type="button"
                        data-modal-open="create-modal"
                    >
                        ＋ Добавить
                    </button>

                <?php else: ?>

                    <button
                        class="button primary"
                        type="button"
                        data-modal-open="database-import-modal"
                    >
                        ↑ Импорт
                    </button>

                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($module === 'zayavki'): ?>
            <div class="cards">
            <?php foreach ($data['rows'] as $row): ?>
                <article
                    class="card <?= is_done($row) ? 'done' : '' ?>"
                    data-status="<?= is_done($row) ? 'done' : 'open' ?>"
                >
                    <div class="card-head"><div><span class="id">#<?= e($row['id']) ?></span><h2><?= e($row['abonent'] ?: $row['abonent_ajax'] ?: 'Без имени') ?></h2></div><span class="status <?= is_done($row)?'status-done':'status-open' ?>"><?= e(format_datetime($row['time'])) ?></span></div>
                    <?php
                    $ticketAddress = trim(
                        (string) (
                            $row['address']
                            ?: $row['address_ajax']
                            ?: ''
                        )
                    );
                    ?>

                    <div class="address">
                        <?php if ($ticketAddress !== ''): ?>
                            <a
                                class="address-link"
                                href="<?= e(url([
                                    'module' => 'stat',
                                    'house' => $ticketAddress,
                                ])) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?= e($ticketAddress) ?>
                            </a>
                        <?php else: ?>
                            Адрес не указан
                        <?php endif; ?>
                    </div>
                    <p><?= e($row['desc']) ?></p>
                    <?php if ($row['other']): ?><div class="muted"><?= e($row['other']) ?></div><?php endif; ?>
                    <dl class="meta"><?php if (is_done($row)): ?><div><dt>Мастер</dt><dd><?= e($row['master']) ?></dd></div><div><dt>Результат</dt><dd><?= e($row['result']) ?></dd></div><div><dt>Стоимость</dt><dd><?= e($row['cost'] ?: '—') ?></dd></div><?php endif; ?></dl>
                    <div class="actions">
                        <?php if (!is_done($row)): ?>
                            <button
                                class="button primary"
                                type="button"
                                data-complete='<?= e(json_encode([
                                    'id' => $row['id'],
                                    'type' => 'ticket',
                                ], JSON_UNESCAPED_UNICODE)) ?>'
                            >
                                Выполнить
                            </button>

                            <form
                                method="post"
                                onsubmit="return confirm('Снять заявку? Она будет перенесена в выполненные.')"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input type="hidden" name="action" value="withdraw">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= e($row['id']) ?>"
                                >

                                <button class="button danger" type="submit">
                                    Снять
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
        <?php elseif ($module === 'podkluchki'): ?>
            <div class="cards">
            <?php foreach ($data['rows'] as $row): ?>
                <article
                    class="card <?= is_done($row) ? 'done' : '' ?>"
                    data-status="<?= is_done($row) ? 'done' : 'open' ?>"
                >
                    <div class="card-head"><div><span class="id">#<?= e($row['id']) ?></span><h2><?= e($row['abonent'] ?: 'Без имени') ?></h2></div><span class="status <?= is_done($row)?'status-done':'status-open' ?>"><?= e(format_datetime($row['time'])) ?></span></div>
                    <?php
                    $connectionAddress = trim(
                        (string) ($row['address'] ?? '')
                    );
                    ?>

                    <div class="address">
                        <?php if ($connectionAddress !== ''): ?>
                            <a
                                class="address-link"
                                href="<?= e(url([
                                    'module' => 'stat',
                                    'house' => $connectionAddress,
                                ])) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?= e($connectionAddress) ?>
                            </a>
                        <?php else: ?>
                            Адрес не указан
                        <?php endif; ?>
                    </div>                    
                    <p><?= e($row['desc']) ?></p>
                    <dl class="meta"><?php if (is_done($row)): ?><div><dt>Мастер</dt><dd><?= e($row['master']) ?></dd></div><div><dt>Результат</dt><dd><?= e($row['result']) ?></dd></div><?php endif; ?></dl>

                    <div class="actions">
                        <?php if (!is_done($row)): ?>
                            <button
                                class="button primary"
                                type="button"
                                data-complete='<?= e(json_encode([
                                    'id' => $row['id'],
                                    'type' => 'connection',
                                ], JSON_UNESCAPED_UNICODE)) ?>'
                            >
                                Завершить
                            </button>

                            <form
                                method="post"
                                onsubmit="return confirm('Снять подключение? Оно будет перенесено в выполненные.')"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input type="hidden" name="action" value="withdraw">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= e($row['id']) ?>"
                                >

                                <button class="button danger" type="submit">
                                    Снять
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>                        

                </article>
            <?php endforeach; ?></div>
        <?php elseif ($module === 'database'): ?>
            <?php if ($action === 'history'): ?><a class="button" href="<?= e(url(['module'=>'database'])) ?>">← Назад</a><h1>История лицевого счёта <?= e(get_string('personal',10)) ?></h1><?php endif; ?>
            <div class="subscriber-list">
                <?php foreach ($data['rows'] as $row): ?>
                    <?php
                    $address = trim(
                        (string) ($row['address'] ?? '')
                    );

                    $addressParts = explode('-', $address);

                    if (count($addressParts) >= 3) {
                        array_pop($addressParts);

                        $subscriberHouse = implode(
                            '-',
                            $addressParts
                        );
                    } else {
                        $subscriberHouse = $address;
                    }

                    $tariff = trim(
                        (string) ($row['tarif'] ?? '')
                    );

                    $subscriberCardClass = str_contains(
                        mb_strtolower($tariff, 'UTF-8'),
                        'государствен'
                    )
                        ? ' subscriber-card--state-package'
                        : '';

                    ?>



                    <div
                        class="subscriber-card<?= $subscriberCardClass ?>"
                    >
                        <div>
                            <a
                                class="subscriber-card__name"
                                href="<?= e(url([
                                    'module' => 'stat',
                                    'house' => $subscriberHouse,
                                    'personal' => (string) ($row['personal'] ?? ''),
                                ])) ?>"
                            >
                                <strong>
                                    <?= e((string) ($row['account'] ?? '')) ?>
                                </strong>
                            </a>

                            <div class="muted">
                                <?= e($address) ?>
                            </div>
                        </div>

                        <div>
                            <span class="label">Лицевой счёт</span>
                            <?= e((string) ($row['personal'] ?? '')) ?>
                        </div>

                        <div>
                            <span class="label">Телефон</span>

                            <?php if (
                                trim((string) ($row['phone'] ?? '')) !== ''
                            ): ?>
                                <span>
                                    <?= e((string) $row['phone']) ?>
                                </span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <span class="label">Тариф</span>
                            <?= e((string) ($row['tarif'] ?? '')) ?>
                        </div>

                        <div>
                            <span class="label">Сумма</span>

                            <?= e(
                                is_numeric($row['summ'] ?? null)
                                    ? number_format(
                                        ((float) $row['summ']),
                                        2,
                                        ',',
                                        ' '
                                    )
                                    : (string) ($row['summ'] ?? '')
                            ) ?>
                        </div>

                        <div>
                            <?php
                            render_sms_button(
                                $address,
                                (string) ($row['phone'] ?? ''),
                                (string) ($row['personal'] ?? ''),
                                is_numeric($row['summ'] ?? null)
                                    ? ((float) $row['summ'])
                                    : 0.0,
                                $subscriberHouse,
                                $tickets->countByAddress($address),
                                (string) ($row['account'] ?? '')
                            );
                            ?>
                        </div>
                    </div>

                <?php endforeach; ?>




<?php elseif ($module === 'terminal'): ?>

<section class="terminal-page">
    <div class="page-heading">
        <div>
            <h1>Terminal</h1>

            <div class="page-heading__counter">
                Server console
            </div>
        </div>

        <div
            class="terminal-status"
            data-terminal-status
        >
            Подключение…
        </div>
    </div>

    <div
        class="terminal-container"
        id="terminal"
        data-terminal
    ></div>
</section>




<?php elseif ($module === 'karandash'): ?>
    <section class="karandash-page">
        <div class="page-heading">
            <div>
                <h1>Карандаш</h1>

                <div class="page-heading__counter">
                    <?= e(number_format(
                        (int) ($data['total'] ?? 0),
                        0,
                        ',',
                        ' '
                    )) ?>
                    записей
                </div>
            </div>
        </div>

        <?php
        $karandashHouses = $data['houses'] ?? [];
        ?>

        <?php if (!$karandashHouses): ?>
            <div class="empty-state">
                <strong>На карандаше никого нет</strong>
                <span>
                    Записи появятся после добавления абонентов
                    из раздела статистики.
                </span>
            </div>
        <?php else: ?>
            <div class="karandash-houses">
                <?php foreach ($karandashHouses as $houseData): ?>
                    <?php
                    $houseName = (string) (
                        $houseData['house'] ?? ''
                    );

                    $items = $houseData['items'] ?? [];
                    ?>

                    <section class="karandash-house">
                        <a
                            class="karandash-house__heading"
                            href="<?= e(url([
                                'module' => 'stat',
                                'house' => $houseName,
                            ])) ?>"
                        >
                            <div>
                                <h2><?= e($houseName) ?></h2>

                                <span>
                                    <?= e(number_format(
                                        count($items),
                                        0,
                                        ',',
                                        ' '
                                    )) ?>
                                    записей
                                </span>
                            </div>

                            <span>→</span>
                        </a>

                        <div class="karandash-list">
                            <?php foreach ($items as $item): ?>

                                <?php
                                $descr = trim(
                                    (string) ($item['descr'] ?? '')
                                );

                                $karandashCardClass = $descr === ''
                                    ? ' karandash-card--empty'
                                    : '';
                                ?>

                                <article
                                    class="karandash-card karandash-card--editable<?= $karandashCardClass ?>"
                                    role="button"
                                    tabindex="0"
                                    data-karandash-edit='<?= e(json_encode([
                                        'address' => (string) ($item['address'] ?? ''),
                                        'descr' => (string) ($item['descr'] ?? ''),
                                        'apartment' => (string) ($item['apartment'] ?? ''),
                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                >
                                    <div class="karandash-card__head">
                                        <strong>
                                            <?php if (
                                                (string) (
                                                    $item['apartment'] ?? ''
                                                ) !== ''
                                            ): ?>
                                                кв.
                                                <?= e(
                                                    (string) $item['apartment']
                                                ) ?>
                                            <?php else: ?>
                                                <?= e(
                                                    (string) (
                                                        $item['address'] ?? ''
                                                    )
                                                ) ?>
                                            <?php endif; ?>
                                        </strong>

                                        <time
                                            datetime="<?= e(date(
                                                DATE_ATOM,
                                                (int) (
                                                    $item['update'] ?? 0
                                                )
                                            )) ?>"
                                        >
                                            <?= e(format_unix_time(
                                                (string) (
                                                    $item['update'] ?? ''
                                                ),
                                                'd.m.Y H:i'
                                            )) ?>
                                        </time>
                                    </div>

                                    <div class="karandash-card__address">
                                        <?= e(
                                            (string) (
                                                $item['account']
                                                ?: 'Абонент не найден'
                                            )
                                        ) ?>
                                    </div>

                                    <?php
                                    $descr = trim(
                                        (string) ($item['descr'] ?? '')
                                    );
                                    ?>

                                    <?php if ($descr !== ''): ?>
                                        <p class="karandash-card__descr">
                                            <?= nl2br(e($descr)) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (
                                        (string) ($item['time'] ?? '')
                                        !==
                                        (string) ($item['update'] ?? '')
                                    ): ?>
                                        <div class="karandash-card__created">
                                            Добавлено:
                                            <?= e(format_unix_time(
                                                (string) (
                                                    $item['time'] ?? ''
                                                ),
                                                'd.m.Y H:i'
                                            )) ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<dialog class="modal" id="karandash-edit-modal">
    <form method="post">
        <div class="modal-head">
            <h2>Изменить запись</h2>

            <button
                type="button"
                class="icon-button"
                data-modal-close
                aria-label="Закрыть"
            >
                ×
            </button>
        </div>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="return_module"
            value="karandash"
        >

        <input
            type="hidden"
            name="address"
            id="karandash-edit-address"
            value=""
        >

        <input
            type="hidden"
            name="house"
            value=""
        >

        <input
            type="hidden"
            name="personal"
            value=""
        >

        <div class="karandash-address">
            <small>Адрес</small>

            <strong id="karandash-edit-address-label">
                —
            </strong>
        </div>

        <label>
            Причина

            <textarea
                class="input"
                id="karandash-edit-descr"
                name="descr"
                rows="6"
                maxlength="2000"
                placeholder="Почему абонент находится на карандаше"
            ></textarea>
        </label>

        <div class="modal-actions">
            <?php if (current_user() === 'admin'): ?>
                <button
                    class="button danger"
                    type="submit"
                    name="action"
                    value="karandash_delete"
                    formnovalidate
                    onclick="return confirm(
                        'Удалить эту запись с карандаша?'
                    )"
                >
                    Удалить
                </button>
            <?php endif; ?>

            <button
                class="button"
                type="button"
                data-modal-close
            >
                Отмена
            </button>

            <button
                class="button primary"
                type="submit"
                name="action"
                value="karandash_add"
            >
                Сохранить
            </button>
        </div>
    </form>
</dialog>













<?php elseif ($module === 'analog'): ?>

    <section class="channels-page">

        <div class="page-heading">
            <div>
                <h1>Аналоговые каналы</h1>

                <div class="page-heading__counter">
                    <?= e(number_format(
                        count($data['channels'] ?? []),
                        0,
                        ',',
                        ' '
                    )) ?>
                    каналов
                </div>
            </div>
        </div>

        <?php $channelRows = $data['channels'] ?? []; ?>

        <?php if (!$channelRows): ?>

            <div class="empty-state">
                Каналы не найдены.
            </div>

        <?php else: ?>

            <div class="channel-grid">

                <?php
                    $ii = 0;
                    foreach ($channelRows as $channel):
                        $ii++;
                ?>

                    <article
                        class="channel-card"
                        title="<?= e(
                            (string) ($channel['ip'] ?? '')
                        ) ?>"
                    >

                        <div class="channel-card__head">


                            <div class="channel-card__number">
                                <?= $ii ?>
                            </div>

                            <div class="channel-card__frequency">
                                <?= e(number_format(
                                    (float) ($channel['freq_mhz'] ?? 0),
                                    0,
                                    ',',
                                    ''
                                )) ?>

                                <span>МГц</span>
                            </div>

                        </div>

                        <div
                            class="channel-card__name"
                            title="<?= e(
                                (string) (
                                    $channel['service_name']
                                    ?? ''
                                )
                            ) ?>"
                        >
                            <?= e(
                                (string) (
                                    $channel['service_name']
                                    ?: 'Без названия'
                                )
                            ) ?>
                        </div>

                        <div class="channel-card__footer">

                            <span>
                                CH
                                <?= e(
                                    (string) (
                                        $channel['channel']
                                        ?? ''
                                    )
                                ) ?>
                            </span>

                            <strong>
                                <?= e(number_format(
                                    (float) (
                                        $channel['level_db']
                                        ?? 0
                                    ),
                                    1,
                                    ',',
                                    ''
                                )) ?>
                                дБ
                            </strong>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>






<?php elseif ($module === 'digital'): ?>

    <?php
    $digitalRows = $data['channels'] ?? [];
    $digitalServers = $data['servers'] ?? [];
    $digitalDistributors =
        $data['distributors']
        ?? [];

    if (!is_array($digitalDistributors)) {
        $digitalDistributors = [];
    }

    $onlineServers = 0;

    foreach ($digitalServers as $server) {
        if (!empty($server['online'])) {
            $onlineServers++;
        }
    }
    ?>

    <section class="channels-page digital-channels-page">

        <div class="page-heading">
            <div>
                <h1>Цифровые каналы</h1>

                <div
                    class="page-heading__counter"
                    id="digital-channel-counter"
                    data-total="<?= count($digitalRows) ?>"
                >
                    <?= e(number_format(
                        count($digitalRows),
                        0,
                        ',',
                        ' '
                    )) ?>
                    каналов
                </div>
            </div>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="digital_refresh"
                >

                <button
                    type="submit"
                    class="button primary"
                    onclick="return confirm(
                        'Получить актуальный список каналов с серверов Astra?'
                    )"
                >
                    ↻ Обновить
                </button>
            </form>
        </div>

        <?php if (
            $digitalServers
            || $digitalDistributors
        ): ?>

            <div class="digital-filter-bar">

                <select
                    id="digital-server-select"
                    class="digital-server-filter__select"
                >
                    <option value="">
                        Все сервера
                    </option>

                    <?php foreach (
                        $digitalServers
                        as $server
                    ): ?>

                        <?php
                        $serverAddress = trim(
                            (string) (
                                $server['address']
                                ?? ''
                            )
                        );

                        if ($serverAddress === '') {
                            continue;
                        }

                        $serverOnline =
                            !empty($server['online']);

                        $serverChannels = (int) (
                            $server['channels']
                            ?? 0
                        );
                        ?>

                        <option
                            value="<?= e($serverAddress) ?>"
                        >
                            <?= e($serverAddress) ?>

                            <?php if ($serverOnline): ?>
                                — <?= $serverChannels ?> каналов
                            <?php else: ?>
                                — недоступен
                            <?php endif; ?>
                        </option>

                    <?php endforeach; ?>
                </select>


                <select
                    id="digital-distrib-select"
                    class="digital-server-filter__select"
                >
                    <option value="">
                        Все дистрибьюторы
                    </option>

                    <?php foreach (
                        $digitalDistributors
                        as $distributor
                    ): ?>

                        <?php
                        $distributor = trim(
                            (string) $distributor
                        );

                        if ($distributor === '') {
                            continue;
                        }
                        ?>

                        <option
                            value="<?= e($distributor) ?>"
                        >
                            <?= e($distributor) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <label class="digital-expensive-filter">
                    <input
                        type="checkbox"
                        id="digital-expensive-checkbox"
                    >

                    <span>Дорогие</span>
                </label>


                <div class="digital-filter-total">
                    <span>
                        Сумма
                    </span>

                    <strong id="digital-visible-summ">
                        0 $
                    </strong>
                </div>

            </div>

        <?php endif; ?>

        <?php if (!$digitalRows): ?>

            <div class="empty-state">

                <strong>
                    Каналы не найдены
                </strong>

                <span>
                    Проверьте доступность Cesbo Astra
                    и настройки digital.astras.
                </span>

            </div>

        <?php else: ?>

            <div class="digital-channel-grid">

                <?php
                $digitalNumber = 0;

                foreach ($digitalRows as $channel):

                    $digitalNumber++;

                    $name = trim(
                        (string) (
                            $channel['name']
                            ?? ''
                        )
                    );

                    $serviceName = trim(
                        (string) (
                            $channel['service_name']
                            ?? ''
                        )
                    );

                    $provider = trim(
                        (string) (
                            $channel['service_provider']
                            ?? ''
                        )
                    );

                    $astra = trim(
                        (string) (
                            $channel['astra']
                            ?? ''
                        )
                    );

                    $inputs = $channel['input'] ?? [];
                    $outputs = $channel['output'] ?? [];

                    if (!is_array($inputs)) {
                        $inputs = [];
                    }

                    if (!is_array($outputs)) {
                        $outputs = [];
                    }

                    $displayName =
                        $name !== ''
                            ? $name
                            : (
                                $serviceName !== ''
                                    ? $serviceName
                                    : 'Без названия'
                            );


                    $lcn = (int) (
                        $channel['lcn']
                        ?? 0
                    );

                    $distrib = trim(
                        (string) (
                            $channel['distrib']
                            ?? ''
                        )
                    );

                    $summ = (int) round(
                        (float) (
                            $channel['summ']
                            ?? 0
                        )
                    );

                    $info = trim(
                        (string) (
                            $channel['info']
                            ?? ''
                        )
                    );



                ?>

                    <article
                        class="digital-channel-card digital-channel-card--editable
                            <?= $lcn === 0
                                ? 'digital-channel-card--no-lcn'
                                : ''
                            ?>
                            <?= $distrib !== ''
                                ? 'digital-channel-card--has-distrib'
                                : ''
                            ?>
                            <?= $summ > 0
                                ? 'digital-channel-card--has-summ'
                                : ''
                            ?>                            
                        "
                        role="button"
                        tabindex="0"

                        data-digital-server="<?= e($astra) ?>"
                        data-digital-distrib="<?= e($distrib) ?>"
                        data-digital-summ="<?= e((string) $summ) ?>"
                        data-digital-order="<?= e((string) $digitalNumber) ?>"

                        data-digital-edit='<?= e(json_encode([
                            'id' => (int) ($channel['id'] ?? 0),
                            'name' => $displayName,
                            'server' => $astra,
                            'lcn' => $lcn,
                            'distrib' => $distrib,
                            'summ' => $summ,
                            'info' => $info,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                    >

                        <div class="digital-channel-card__head">

                            <div class="digital-channel-card__number">
                                <?= $lcn > 0
                                    ? e((string) $lcn)
                                    : '—'
                                ?>
                            </div>

                            <div
                                class="digital-channel-card__name"
                                title="<?= e($displayName) ?>"
                            >
                                <?= e($displayName) ?>
                            </div>

                        </div>

                        <?php if (
                            $distrib !== ''
                            || $summ > 0
                        ): ?>

                            <div class="digital-channel-card__commercial">

                                <?php if ($distrib !== ''): ?>
                                    <span>
                                        <?= e($distrib) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($summ > 0): ?>
                                    <strong>
                                        <?= e(number_format(
                                            $summ,
                                            0,
                                            ',',
                                            ' '
                                        )) ?> $
                                    </strong>
                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                        <?php if ($info !== ''): ?>

                            <div
                                class="digital-channel-card__info"
                                title="<?= e($info) ?>"
                            >
                                <?= nl2br(e($info)) ?>
                            </div>

                        <?php endif; ?>



                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>







<dialog
    class="modal"
    id="digital-edit-modal"
>
    <form method="post">

        <div class="modal-head">
            <div>
                <h2 id="digital-edit-title">
                    Телеканал
                </h2>

                <div
                    class="muted"
                    id="digital-edit-server"
                ></div>
            </div>

            <button
                type="button"
                class="icon-button"
                data-modal-close
                aria-label="Закрыть"
            >
                ×
            </button>
        </div>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="id"
            id="digital-edit-id"
            value=""
        >

        <label>
            LCN

            <input
                class="input"
                type="number"
                name="lcn"
                id="digital-edit-lcn"
                min="0"
                max="999"
                step="1"
                value="0"
            >
        </label>

        <label>
            Дистрибьютор

            <select
                class="input select"
                name="distrib"
                id="digital-edit-distrib"
            >
                <option value="">
                    Не указан
                </option>

                <?php foreach (
                    $digitalDistributors
                    as $distributor
                ): ?>

                    <?php
                    $distributor = trim(
                        (string) $distributor
                    );

                    if ($distributor === '') {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= e($distributor) ?>"
                    >
                        <?= e($distributor) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Оплата в месяц, $
            <input
                class="input"
                type="number"
                name="summ"
                id="digital-edit-summ"
                min="0"
                step="1"
                value="0"
            >
        </label>

        <label>
            Дополнительная информация

            <textarea
                class="input"
                name="info"
                id="digital-edit-info"
                rows="4"
                maxlength="100"
                placeholder="До 100 символов"
            ></textarea>
        </label>

        <div
            class="modal-actions digital-modal-actions"
        >
            <button
                type="submit"
                class="button danger"
                name="action"
                value="digital_delete"
                formnovalidate
                onclick="return confirm(
                    'Удалить этот телеканал из базы данных?'
                )"
            >
                Удалить
            </button>

            <div class="digital-modal-actions__right">

                <button
                    type="button"
                    class="button"
                    data-modal-close
                >
                    Отмена
                </button>

                <button
                    type="submit"
                    class="button primary"
                    name="action"
                    value="digital_save"
                >
                    Сохранить
                </button>

            </div>
        </div>

    </form>
</dialog>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const modal =
            document.getElementById(
                'digital-edit-modal'
            );

        if (!modal) {
            return;
        }

        const cards = document.querySelectorAll(
            '[data-digital-edit]'
        );

        const idInput =
            document.getElementById(
                'digital-edit-id'
            );

        const lcnInput =
            document.getElementById(
                'digital-edit-lcn'
            );

        const distribInput =
            document.getElementById(
                'digital-edit-distrib'
            );

        const summInput =
            document.getElementById(
                'digital-edit-summ'
            );

        const infoInput =
            document.getElementById(
                'digital-edit-info'
            );

        const title =
            document.getElementById(
                'digital-edit-title'
            );

        const server =
            document.getElementById(
                'digital-edit-server'
            );

        function openDigitalEdit(card) {
            let data;

            try {
                data = JSON.parse(
                    card.dataset.digitalEdit
                    || '{}'
                );
            } catch (error) {
                return;
            }

            idInput.value =
                data.id || '';

            lcnInput.value =
                data.lcn || 0;

            distribInput.value =
                data.distrib || '';

            summInput.value =
                data.summ || '0';

            infoInput.value =
                data.info || '';

            title.textContent =
                data.name || 'Телеканал';

            server.textContent =
                data.server || '';

            modal.showModal();
        }

        cards.forEach(function (card) {
            card.addEventListener(
                'click',
                function () {
                    openDigitalEdit(card);
                }
            );

            card.addEventListener(
                'keydown',
                function (event) {
                    if (
                        event.key !== 'Enter'
                        && event.key !== ' '
                    ) {
                        return;
                    }

                    event.preventDefault();

                    openDigitalEdit(card);
                }
            );
        });
    }
);
</script>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const serverSelect =
            document.getElementById(
                'digital-server-select'
            );

        const distribSelect =
            document.getElementById(
                'digital-distrib-select'
            );

        const expensiveCheckbox =
            document.getElementById(
                'digital-expensive-checkbox'
            );

        const counter =
            document.getElementById(
                'digital-channel-counter'
            );

        const summElement =
            document.getElementById(
                'digital-visible-summ'
            );

        const cards = Array.from(
            document.querySelectorAll(
                '.digital-channel-card'
            )
        );

        const grid =
            document.querySelector(
                '.digital-channel-grid'
            );

        function updateDigitalChannels() {
            const selectedServer =
                serverSelect
                    ? serverSelect.value.trim()
                    : '';

            const selectedDistrib =
                distribSelect
                    ? distribSelect.value.trim()
                    : '';

            let visibleCount = 0;
            let visibleSumm = 0;

            cards.forEach(function (card) {
                const server = (
                    card.dataset.digitalServer
                    || ''
                ).trim();

                const distrib = (
                    card.dataset.digitalDistrib
                    || ''
                ).trim();

                const summ = parseFloat(
                    card.dataset.digitalSumm
                    || '0'
                ) || 0;

                const serverVisible =
                    selectedServer === ''
                    || server === selectedServer;

                const distribVisible =
                    selectedDistrib === ''
                    || distrib === selectedDistrib;

                const visible =
                    serverVisible
                    && distribVisible;

                card.style.display =
                    visible
                        ? ''
                        : 'none';

                if (!visible) {
                    return;
                }

                visibleCount++;
                visibleSumm += summ;
            });

            if (grid) {
                const sortedCards = [...cards];

                if (
                    expensiveCheckbox
                    && expensiveCheckbox.checked
                ) {
                    sortedCards.sort(function (a, b) {
                        const summA = parseFloat(
                            a.dataset.digitalSumm || '0'
                        ) || 0;

                        const summB = parseFloat(
                            b.dataset.digitalSumm || '0'
                        ) || 0;

                        return summB - summA;
                    });
                } else {
                    sortedCards.sort(function (a, b) {
                        return (
                            parseInt(
                                a.dataset.digitalOrder || '0',
                                10
                            )
                            -
                            parseInt(
                                b.dataset.digitalOrder || '0',
                                10
                            )
                        );
                    });
                }

                sortedCards.forEach(function (card) {
                    grid.appendChild(card);
                });
            }            

            if (counter) {
                counter.textContent =
                    new Intl.NumberFormat(
                        'ru-RU'
                    ).format(visibleCount)
                    + ' каналов';
            }

            if (summElement) {
                summElement.textContent =
                    new Intl.NumberFormat(
                        'ru-RU',
                        {
                            maximumFractionDigits: 0
                        }
                    ).format(visibleSumm)
                    + ' $';
            }
        }

        if (serverSelect) {
            serverSelect.addEventListener(
                'change',
                updateDigitalChannels
            );
        }

        if (distribSelect) {
            distribSelect.addEventListener(
                'change',
                updateDigitalChannels
            );
        }

        if (expensiveCheckbox) {
            expensiveCheckbox.addEventListener(
                'change',
                updateDigitalChannels
            );
        }

        updateDigitalChannels();
    }
);
</script>


            

<?php elseif ($module === 'debtors'): ?>
    <?php
    $debtorHouses = $data['houses'] ?? [];
    $databaseUpdate = (string) ($data['update'] ?? '');
    $debtorsTotal = (int) ($data['debtors_total'] ?? 0);
    $debtTotal = (float) ($data['debt_total'] ?? 0);
    ?>

    <section class="page-heading debtors-heading">
        <div>
            <h1>Должники</h1>

            <p class="page-heading__description">
                Последнее обновление:
                <strong>
                    <?= e(format_unix_time(
                        $databaseUpdate,
                        'd.m.Y H:i'
                    )) ?>
                </strong>
            </p>
        </div>

        <div class="debtors-heading__filter">
            <label for="debtors-payment-filter">
                Последняя оплата
            </label>

            <select
                id="debtors-payment-filter"
                class="debtors-heading__select"
            >
                <option value="all">Все</option>
                <option value="1">Более 1 месяца</option>
                <option value="3" selected>Более 3 месяцев</option>
                <option value="6">Более 6 месяцев</option>
            </select>
        </div>

        <div class="page-heading__counter debtors-heading__counter">
            <div>
                <span id="debtors-visible-count">
                    <?= e(number_format(
                        $debtorsTotal,
                        0,
                        ',',
                        ' '
                    )) ?>
                </span>
                должников
            </div>

            <strong id="debtors-visible-total">
                <?= e(number_format(
                    $debtTotal,
                    2,
                    ',',
                    ' '
                )) ?>
            </strong>
        </div>
    </section>

    <?php if (!$debtorHouses): ?>
        <div class="empty-state">
            <strong>Должники отсутствуют</strong>

            <span>
                В последнем обновлении нет абонентов с положительной
                задолженностью и действующим договором.
            </span>
        </div>
    <?php else: ?>
        <div class="debtor-houses">
            <?php foreach ($debtorHouses as $debtorHouse): ?>
                <?php
                $houseName = (string) (
                    $debtorHouse['house'] ?? ''
                );

                $houseDebt = (float) (
                    $debtorHouse['debt'] ?? 0
                );

                $houseDebtors = $debtorHouse['debtors'] ?? [];
                ?>

                <section class="debtor-house">
                    <a
                        class="debtor-house__header"
                        href="<?= e(url([
                            'module' => 'stat',
                            'house' => $houseName,
                        ])) ?>"
                    >
                        <div>
                            <h2><?= e($houseName) ?></h2>

                            <span>
                                <span class="debtor-house__visible-count">
                                    <?= e(number_format(
                                        count($houseDebtors),
                                        0,
                                        ',',
                                        ' '
                                    )) ?>
                                </span>
                                должников
                            </span>
                        </div>

                        <strong class="debtor-house__total">
                            <?= e(number_format(
                                $houseDebt,
                                2,
                                ',',
                                ' '
                            )) ?>
                        </strong>
                    </a>

                    <div class="debtor-list">
                        <?php foreach ($houseDebtors as $debtor): ?>
                            <?php
                            $personal = (string) (
                                $debtor['personal'] ?? ''
                            );

                            $subscriber = (string) (
                                $debtor['subscriber'] ?? ''
                            );

                            $apartment = (string) (
                                $debtor['apartment'] ?? ''
                            );

                            $tariff = (string) (
                                $debtor['tariff'] ?? ''
                            );

                            $debt = (float) (
                                $debtor['debt'] ?? 0
                            );
                            $lastPaymentUpdate = trim(
                                (string) ($debtor['last_payment_update'] ?? '')
                            );
                            $karandashDescr = trim(
                                (string) ($debtor['karandash_descr'] ?? '')
                            );

                            ?>

                            <a
                                class="debtor-card"
                                href="<?= e(url([
                                    'module' => 'stat',
                                    'house' => $houseName,
                                    'personal' => $personal,
                                ])) ?>"
                                data-last-payment="<?= e($lastPaymentUpdate) ?>"
                                data-debt="<?= e(number_format(
                                    $debt,
                                    4,
                                    '.',
                                    ''
                                )) ?>"
                            >
                                <div class="debtor-card__apartment">
                                    кв. <?= e($apartment) ?>
                                </div>

                                <div class="debtor-card__subscriber">
                                    <?= e(
                                        $subscriber !== ''
                                            ? $subscriber
                                            : 'Без имени'
                                    ) ?>

                                    <small>
                                        <?= e($tariff) ?>
                                    </small>

                                    <small class="debtor-card__payment">
                                        Последняя оплата:
                                        <?php if ($lastPaymentUpdate !== ''): ?>
                                            <?= e(format_unix_time(
                                                $lastPaymentUpdate,
                                                'd.m.Y'
                                            )) ?>
                                        <?php else: ?>
                                            не обнаружена
                                        <?php endif; ?>
                                    </small>

                                    <?php if ($karandashDescr !== ''): ?>
                                        <span
                                            class="debtor-card__karandash"
                                            title="<?= e($karandashDescr) ?>"
                                        >
                                            <span class="debtor-card__karandash-icon">
                                                ✎
                                            </span>

                                            <span class="debtor-card__karandash-text">
                                                <?= nl2br(e($karandashDescr)) ?>
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <strong class="debtor-card__debt">
                                    <?= e(number_format(
                                        $debt,
                                        2,
                                        ',',
                                        ' '
                                    )) ?>
                                </strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>


        <div
            id="debtors-filter-empty"
            class="empty-state"
            hidden
        >
            <strong>Должники не найдены</strong>

            <span>
                Для выбранного периода должники отсутствуют.
            </span>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById(
        'debtors-payment-filter'
    );

    if (!filter) {
        return;
    }

    const houses = Array.from(
        document.querySelectorAll('.debtor-house')
    );

    const visibleCountElement = document.getElementById(
        'debtors-visible-count'
    );

    const visibleTotalElement = document.getElementById(
        'debtors-visible-total'
    );

    const emptyElement = document.getElementById(
        'debtors-filter-empty'
    );

    filter.addEventListener('change', applyFilter);

    function applyFilter() {
        const selectedMonths = filter.value === 'all'
            ? 0
            : parseInt(filter.value, 10);

        let totalVisibleCount = 0;
        let totalVisibleDebt = 0;

        houses.forEach(function (house) {
            const cards = Array.from(
                house.querySelectorAll('.debtor-card')
            );

            let houseVisibleCount = 0;
            let houseVisibleDebt = 0;

            cards.forEach(function (card) {
                const paymentTimestamp = parseInt(
                    card.dataset.lastPayment || '',
                    10
                );

                const debt = parseFloat(
                    card.dataset.debt || '0'
                ) || 0;

                let visible = true;

                if (selectedMonths > 0) {
                    /*
                     * Неизвестную дату считаем очень давней,
                     * поэтому такая запись всегда остаётся видимой.
                     */
                    if (Number.isFinite(paymentTimestamp)) {
                        const cutoff = new Date();

                        cutoff.setMonth(
                            cutoff.getMonth() - selectedMonths
                        );

                        visible =
                            paymentTimestamp
                            < Math.floor(cutoff.getTime() / 1000);
                    }
                }

                card.hidden = !visible;

                if (visible) {
                    houseVisibleCount++;
                    houseVisibleDebt += debt;
                }
            });

            house.hidden = houseVisibleCount === 0;

            const houseCountElement = house.querySelector(
                '.debtor-house__visible-count'
            );

            const houseTotalElement = house.querySelector(
                '.debtor-house__total'
            );

            if (houseCountElement) {
                houseCountElement.textContent =
                    formatInteger(houseVisibleCount);
            }

            if (houseTotalElement) {
                houseTotalElement.textContent =
                    formatMoney(houseVisibleDebt);
            }

            totalVisibleCount += houseVisibleCount;
            totalVisibleDebt += houseVisibleDebt;
        });

        if (visibleCountElement) {
            visibleCountElement.textContent =
                formatInteger(totalVisibleCount);
        }

        if (visibleTotalElement) {
            visibleTotalElement.textContent =
                formatMoney(totalVisibleDebt);
        }

        if (emptyElement) {
            emptyElement.hidden = totalVisibleCount !== 0;
        }
    }

    function formatInteger(value) {
        return new Intl.NumberFormat(
            'ru-RU',
            {
                maximumFractionDigits: 0
            }
        ).format(value);
    }

    function formatMoney(value) {
        return new Intl.NumberFormat(
            'ru-RU',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(value);
    }
    applyFilter();
});
</script>




    <?php endif; ?>

          
    



<?php elseif ($module === 'graph'): ?>

<?php
$graphSnapshots =
    $data['snapshots'] ?? [];

$graphTariffs =
    $data['tariffs'] ?? [];

$latestSnapshot =
    $graphSnapshots
        ? $graphSnapshots[
            count($graphSnapshots) - 1
        ]
        : null;

$latestUpdate = (int) (
    $latestSnapshot['update']
    ?? 0
);

$latestTotal = (int) (
    $latestSnapshot['total']
    ?? 0
);

$graphDebts = array_map(
    static function (array $snapshot): float {
        return (float) (
            $snapshot['debt']
            ?? 0
        );
    },
    $graphSnapshots
);

$graphMaxDebt = 0.0;
$graphMaxDebtDate = null;

$graphMinDebt = 0.0;
$graphMinDebtDate = null;

$graphCurrentDebt = 0.0;
$graphCurrentPeriodIncome = 0.0;

if ($graphSnapshots) {
    $graphMaxDebt = max($graphDebts);
    $graphMinDebt = min($graphDebts);

    /*
     * Текущая задолженность —
     * задолженность последнего снимка.
     */
    $graphCurrentDebt = (float) (
        $latestSnapshot['debt']
        ?? 0
    );

    foreach ($graphSnapshots as $snapshot) {
        $debt = (float) (
            $snapshot['debt']
            ?? 0
        );

        $update = (int) (
            $snapshot['update']
            ?? 0
        );

        /*
         * Нам нужна именно ПОСЛЕДНЯЯ дата,
         * когда задолженность находилась
         * на максимальном уровне.
         *
         * Поэтому здесь нет проверки === null.
         */
        if ($debt === $graphMaxDebt) {
            $graphMaxDebtDate = $update;
        }

        /*
         * Для минимальной пока оставляем
         * первую найденную дату, как было раньше.
         */
        if (
            $graphMinDebtDate === null
            && $debt === $graphMinDebt
        ) {
            $graphMinDebtDate = $update;
        }
    }

    /*
     * Сколько задолженности погашено
     * с последнего максимума до текущего момента.
     */
    $graphCurrentPeriodIncome =
        $graphMaxDebt
        - $graphCurrentDebt;
}
?>

<section class="graph-page">

    <div class="page-heading graph-heading">
        <div>
            <h1>График абонентов</h1>

            <p class="page-heading__description">
                Изменение количества действующих
                договоров по пакетам.
            </p>
        </div>

        <?php if ($latestSnapshot !== null): ?>
            <div class="graph-heading__summary">

                <div>
                    <span>Всего с договором</span>

                    <strong>
                        <?= e(number_format(
                            $latestTotal,
                            0,
                            ',',
                            ' '
                        )) ?>
                    </strong>
                </div>

                <div>
                    <span>Заработано за период</span>
                    <strong>
                        <?= e(number_format(
                            $graphCurrentPeriodIncome,
                            2,
                            ',',
                            ' '
                        )) ?> р.
                    </strong>
                </div>

                <div>
                    <span>Обновление</span>

                    <strong>
                        <?= e(date(
                            'd.m.Y H:i',
                            $latestUpdate
                        )) ?>
                    </strong>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <?php if (!$graphSnapshots): ?>

        <div class="empty-state">
            Для построения графика пока
            недостаточно данных.
        </div>

    <?php else: ?>


        <div class="graph-card">

            <div class="graph-card__canvas">
                <canvas
                    id="subscriber-graph"
                ></canvas>
            </div>

            <div
                class="graph-legend"
                id="subscriber-graph-legend"
            ></div>

        </div>


        <div class="graph-card">

            <div class="graph-card__title">
                <div>
                    Задолженность
                </div>

                <div class="graph-card__stats">
                    <span>
                        Минимальная:
                        <strong>
                            <?= e(number_format(
                                $graphMinDebt,
                                2,
                                ',',
                                ' '
                            )) ?>

                            <?php if ($graphMinDebtDate): ?>
                                (<?= e(date(
                                    'd.m.Y',
                                    $graphMinDebtDate
                                )) ?>)
                            <?php endif; ?>
                        </strong>
                    </span>

                    <span>
                        Максимальная:
                        <strong>
                            <?= e(number_format(
                                $graphMaxDebt,
                                2,
                                ',',
                                ' '
                            )) ?>

                            <?php if ($graphMaxDebtDate): ?>
                                (<?= e(date(
                                    'd.m.Y',
                                    $graphMaxDebtDate
                                )) ?>)
                            <?php endif; ?>
                        </strong>
                    </span>
                </div>
            </div>

            <div class="graph-card__canvas">
                <canvas id="debt-graph"></canvas>
            </div>

            <div class="graph-legend">
                <div class="graph-legend__item">
                    <span
                        class="graph-legend__marker"
                        style="background:#dc2626"
                    ></span>

                    <span>
                        Общая задолженность
                    </span>
                </div>
            </div>

        </div>



        <script
            type="application/json"
            id="subscriber-graph-data"
        ><?= json_encode(
            [
                'snapshots' =>
                    $graphSnapshots,

                'tariffs' =>
                    $graphTariffs,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        ) ?></script>

        <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const canvas =
                    document.getElementById(
                        'subscriber-graph'
                    );

                const debtCanvas =
                    document.getElementById(
                        'debt-graph'
                    );                    

                const dataElement =
                    document.getElementById(
                        'subscriber-graph-data'
                    );

                const legendElement =
                    document.getElementById(
                        'subscriber-graph-legend'
                    );

                if (
                    !canvas
                    || !dataElement
                ) {
                    return;
                }

                const graphData =
                    JSON.parse(
                        dataElement.textContent
                    );

                const snapshots =
                    graphData.snapshots || [];

                const tariffs =
                    graphData.tariffs || [];

                if (!snapshots.length) {
                    return;
                }

                const colors = [
                    '#2563eb',
                    '#16a34a',
                    '#ea580c',
                    '#9333ea',
                    '#0891b2',
                    '#db2777',
                    '#65a30d',
                    '#d97706'
                ];

                const series =
                    tariffs.map(
                        function (
                            tariff,
                            index
                        ) {
                            return {
                                name: tariff,
                                color:
                                    colors[
                                        index
                                        % colors.length
                                    ],

                                values:
                                    snapshots.map(
                                        function (
                                            snapshot
                                        ) {
                                            return Number(
                                                (
                                                    snapshot
                                                    .packages
                                                    || {}
                                                )[tariff]
                                                || 0
                                            );
                                        }
                                    )
                            };
                        }
                    );

                series.push({
                    name:
                        'Всего с договором',

                    color:
                        '#111827',

                    total: true,

                    values:
                        snapshots.map(
                            function (
                                snapshot
                            ) {
                                return Number(
                                    snapshot.total
                                    || 0
                                );
                            }
                        )
                });

                function formatDate(timestamp) {
                    return new Date(
                        Number(timestamp)
                        * 1000
                    ).toLocaleDateString(
                        'ru-RU',
                        {
                            day: '2-digit',
                            month: '2-digit'
                        }
                    );
                }

                function renderLegend() {
                    if (!legendElement) {
                        return;
                    }

                    legendElement
                        .replaceChildren();

                    series.forEach(
                        function (item) {
                            const element =
                                document.createElement(
                                    'div'
                                );

                            element.className =
                                'graph-legend__item';

                            const marker =
                                document.createElement(
                                    'span'
                                );

                            marker.className =
                                'graph-legend__marker';

                            marker.style
                                .backgroundColor =
                                    item.color;

                            const name =
                                document.createElement(
                                    'span'
                                );

                            name.textContent =
                                item.name;

                            element.append(
                                marker,
                                name
                            );

                            legendElement
                                .appendChild(
                                    element
                                );
                        }
                    );
                }

                function drawSubscribers() {
                    const parent =
                        canvas.parentElement;

                    const cssWidth = parent.clientWidth;

                    const cssHeight = 440;

                    const dpr =
                        window.devicePixelRatio
                        || 1;

                    canvas.width =
                        cssWidth * dpr;

                    canvas.height =
                        cssHeight * dpr;

                    canvas.style.width =
                        cssWidth + 'px';

                    canvas.style.height =
                        cssHeight + 'px';

                    const ctx =
                        canvas.getContext(
                            '2d'
                        );

                    ctx.setTransform(
                        dpr,
                        0,
                        0,
                        dpr,
                        0,
                        0
                    );

                    ctx.clearRect(
                        0,
                        0,
                        cssWidth,
                        cssHeight
                    );

                    const padding = {
                        top: 30,
                        right: 30,
                        bottom: 55,
                        left: 65
                    };

                    const width =
                        cssWidth
                        - padding.left
                        - padding.right;

                    const height =
                        cssHeight
                        - padding.top
                        - padding.bottom;

                    let maxValue = 0;

                    series.forEach(
                        function (item) {
                            item.values.forEach(
                                function (value) {
                                    maxValue =
                                        Math.max(
                                            maxValue,
                                            value
                                        );
                                }
                            );
                        }
                    );

                    if (maxValue <= 0) {
                        maxValue = 10;
                    }

                    const step =
                        Math.max(
                            1,
                            Math.ceil(
                                maxValue / 5 / 10
                            ) * 10
                        );

                    const yMax =
                        Math.ceil(
                            maxValue / step
                        ) * step;

                    ctx.font =
                        '12px sans-serif';

                    ctx.textAlign =
                        'right';

                    ctx.textBaseline =
                        'middle';

                    for (
                        let i = 0;
                        i <= 5;
                        i++
                    ) {
                        const value =
                            Math.round(
                                yMax
                                - (
                                    yMax
                                    * i / 5
                                )
                            );

                        const y =
                            padding.top
                            + (
                                height
                                * i / 5
                            );

                        ctx.beginPath();

                        ctx.strokeStyle =
                            '#e5e7eb';

                        ctx.lineWidth = 1;

                        ctx.moveTo(
                            padding.left,
                            y
                        );

                        ctx.lineTo(
                            padding.left
                            + width,
                            y
                        );

                        ctx.stroke();

                        ctx.fillStyle =
                            '#6b7280';

                        ctx.fillText(
                            new Intl.NumberFormat(
                                'ru-RU'
                            ).format(
                                value
                            ),

                            padding.left - 10,
                            y
                        );
                    }

                    const pointCount =
                        snapshots.length;

                    function xFor(index) {
                        if (
                            pointCount <= 1
                        ) {
                            return (
                                padding.left
                                + width / 2
                            );
                        }

                        return (
                            padding.left
                            + (
                                width
                                * index
                                / (
                                    pointCount
                                    - 1
                                )
                            )
                        );
                    }

                    function yFor(value) {
                        return (
                            padding.top
                            + height
                            - (
                                value
                                / yMax
                                * height
                            )
                        );
                    }

                    const maxLabels =
                        Math.max(
                            2,
                            Math.floor(
                                width / 90
                            )
                        );

                    const labelEvery =
                        Math.max(
                            1,
                            Math.ceil(
                                pointCount
                                / maxLabels
                            )
                        );

                    ctx.textAlign =
                        'center';

                    ctx.textBaseline =
                        'top';

                    snapshots.forEach(
                        function (
                            snapshot,
                            index
                        ) {
                            if (
                                index
                                % labelEvery
                                !== 0
                                && index
                                !== pointCount - 1
                            ) {
                                return;
                            }

                            const x =
                                xFor(index);

                            ctx.fillStyle =
                                '#6b7280';

                            ctx.fillText(
                                formatDate(
                                    snapshot.update
                                ),
                                x,
                                padding.top
                                + height
                                + 14
                            );
                        }
                    );

                    series.forEach(
                        function (item) {
                            ctx.beginPath();

                            ctx.strokeStyle =
                                item.color;

                            ctx.lineWidth =
                                item.total
                                    ? 3
                                    : 2;

                            ctx.lineJoin =
                                'round';

                            ctx.lineCap =
                                'round';

                            item.values.forEach(
                                function (
                                    value,
                                    index
                                ) {
                                    const x =
                                        xFor(index);

                                    const y =
                                        yFor(value);

                                    if (index === 0) {
                                        ctx.moveTo(
                                            x,
                                            y
                                        );
                                    } else {
                                        ctx.lineTo(
                                            x,
                                            y
                                        );
                                    }
                                }
                            );

                            ctx.stroke();

                            if (
                                pointCount <= 40
                            ) {
                                item.values
                                    .forEach(
                                        function (
                                            value,
                                            index
                                        ) {
                                            const x =
                                                xFor(
                                                    index
                                                );

                                            const y =
                                                yFor(
                                                    value
                                                );

                                            ctx.beginPath();

                                            ctx.fillStyle =
                                                item.color;

                                            ctx.arc(
                                                x,
                                                y,
                                                item.total
                                                    ? 3.5
                                                    : 2.5,
                                                0,
                                                Math.PI
                                                * 2
                                            );

                                            ctx.fill();
                                        }
                                    );
                            }
                        }
                    );
                }



                function drawDebt() {
                    if (!debtCanvas) {
                        return;
                    }

                    const parent =
                        debtCanvas.parentElement;

                    const cssWidth = parent.clientWidth;

                    const cssHeight = 360;

                    const dpr =
                        window.devicePixelRatio
                        || 1;

                    debtCanvas.width =
                        cssWidth * dpr;

                    debtCanvas.height =
                        cssHeight * dpr;

                    debtCanvas.style.width =
                        cssWidth + 'px';

                    debtCanvas.style.height =
                        cssHeight + 'px';

                    const ctx =
                        debtCanvas.getContext(
                            '2d'
                        );

                    ctx.setTransform(
                        dpr,
                        0,
                        0,
                        dpr,
                        0,
                        0
                    );

                    ctx.clearRect(
                        0,
                        0,
                        cssWidth,
                        cssHeight
                    );

                    const padding = {
                        top: 30,
                        right: 30,
                        bottom: 55,
                        left: 85
                    };

                    const width =
                        cssWidth
                        - padding.left
                        - padding.right;

                    const height =
                        cssHeight
                        - padding.top
                        - padding.bottom;

                    const values =
                        snapshots.map(
                            function (snapshot) {
                                return Number(
                                    snapshot.debt
                                    || 0
                                );
                            }
                        );

                    let maxValue =
                        Math.max(
                            ...values,
                            0
                        );

                    if (maxValue <= 0) {
                        maxValue = 100;
                    }

                    /*
                    * Округляем верхнюю границу
                    * до удобного значения.
                    */
                    const roughStep =
                        maxValue / 5;

                    const magnitude =
                        Math.pow(
                            10,
                            Math.floor(
                                Math.log10(
                                    Math.max(
                                        roughStep,
                                        1
                                    )
                                )
                            )
                        );

                    const step =
                        Math.ceil(
                            roughStep
                            / magnitude
                        ) * magnitude;

                    const yMax =
                        step * 5;

                    ctx.font =
                        '12px sans-serif';

                    ctx.textAlign =
                        'right';

                    ctx.textBaseline =
                        'middle';

                    for (
                        let i = 0;
                        i <= 5;
                        i++
                    ) {
                        const value =
                            yMax
                            - (
                                step * i
                            );

                        const y =
                            padding.top
                            + (
                                height
                                * i / 5
                            );

                        ctx.beginPath();

                        ctx.strokeStyle =
                            '#e5e7eb';

                        ctx.lineWidth = 1;

                        ctx.moveTo(
                            padding.left,
                            y
                        );

                        ctx.lineTo(
                            padding.left
                            + width,
                            y
                        );

                        ctx.stroke();

                        ctx.fillStyle =
                            '#6b7280';

                        ctx.fillText(
                            new Intl.NumberFormat(
                                'ru-RU',
                                {
                                    maximumFractionDigits: 0
                                }
                            ).format(
                                value
                            ),

                            padding.left - 10,
                            y
                        );
                    }

                    const pointCount =
                        snapshots.length;

                    function xFor(index) {
                        if (pointCount <= 1) {
                            return (
                                padding.left
                                + width / 2
                            );
                        }

                        return (
                            padding.left
                            + (
                                width
                                * index
                                / (
                                    pointCount
                                    - 1
                                )
                            )
                        );
                    }

                    function yFor(value) {
                        return (
                            padding.top
                            + height
                            - (
                                value
                                / yMax
                                * height
                            )
                        );
                    }

                    /*
                    * Подписи дат.
                    */
                    const maxLabels =
                        Math.max(
                            2,
                            Math.floor(
                                width / 90
                            )
                        );

                    const labelEvery =
                        Math.max(
                            1,
                            Math.ceil(
                                pointCount
                                / maxLabels
                            )
                        );

                    ctx.textAlign =
                        'center';

                    ctx.textBaseline =
                        'top';

                    snapshots.forEach(
                        function (
                            snapshot,
                            index
                        ) {
                            if (
                                index
                                % labelEvery
                                !== 0
                                && index
                                !== pointCount - 1
                            ) {
                                return;
                            }

                            ctx.fillStyle =
                                '#6b7280';

                            ctx.fillText(
                                formatDate(
                                    snapshot.update
                                ),
                                xFor(index),
                                padding.top
                                + height
                                + 14
                            );
                        }
                    );

                    /*
                    * Линия задолженности.
                    */
                    ctx.beginPath();

                    ctx.strokeStyle =
                        '#dc2626';

                    ctx.lineWidth = 3;

                    ctx.lineJoin =
                        'round';

                    ctx.lineCap =
                        'round';

                    values.forEach(
                        function (
                            value,
                            index
                        ) {
                            const x =
                                xFor(index);

                            const y =
                                yFor(value);

                            if (index === 0) {
                                ctx.moveTo(
                                    x,
                                    y
                                );
                            } else {
                                ctx.lineTo(
                                    x,
                                    y
                                );
                            }
                        }
                    );

                    ctx.stroke();

                    /*
                    * Точки показываем,
                    * пока выборок не слишком много.
                    */
                    if (pointCount <= 50) {
                        values.forEach(
                            function (
                                value,
                                index
                            ) {
                                ctx.beginPath();

                                ctx.fillStyle =
                                    '#dc2626';

                                ctx.arc(
                                    xFor(index),
                                    yFor(value),
                                    3.5,
                                    0,
                                    Math.PI * 2
                                );

                                ctx.fill();
                            }
                        );
                    }
                }



                renderLegend();
                drawSubscribers();
                drawDebt();

                let resizeTimer = null;

                window.addEventListener(
                    'resize',
                    function () {
                        clearTimeout(
                            resizeTimer
                        );

                    resizeTimer =
                        setTimeout(
                            function () {
                                drawSubscribers();
                                drawDebt();
                            },
                            100
                        );
                    }
                );
            }
        );
        </script>

    <?php endif; ?>

</section>












<?php 

// ███████╗████████╗ █████╗ ████████╗
// ██╔════╝╚══██╔══╝██╔══██╗╚══██╔══╝
// ███████╗   ██║   ███████║   ██║   
// ╚════██║   ██║   ██╔══██║   ██║   
// ███████║   ██║   ██║  ██║   ██║   
// ╚══════╝   ╚═╝   ╚═╝  ╚═╝   ╚═╝   

elseif ($module === 'stat'): ?>
<?php if ($module === 'stat' && $personal !== ''): ?>




<section class="payments-page">
    <div class="page-heading payments-heading">
        <div>
            <a
                class="apartments-heading__back"
                href="<?= e(url([
                    'module' => 'stat',
                    'house' => $house,
                ])) ?>"
            >
                ← Квартиры дома
            </a>

            <h1>
                <?= e(
                    $subscriber !== ''
                        ? $subscriber
                        : 'Абонент'
                ) ?>
            </h1>

            <?php if ($subscriberAddress !== ''): ?>
                <div class="page-heading__counter">
                    <?= e($subscriberAddress) ?>
                </div>
            <?php endif; ?>

            <div class="payments-heading__meta">
                Лицевой счёт:
                <strong><?= e($personal) ?></strong>

                <?php if ($subscriberTariff !== ''): ?>
                    · <?= e($subscriberTariff) ?>
                <?php endif; ?>
            </div>

            <?php 
            $subscriberBalance = (float) ($paymentHistory['balance'] ?? 0);
            if ($subscriberBalance < 0): ?>
                <div class="payments-heading__debt payments-heading__debt--clear">
                    <span>Аванс</span>
                    <strong>
                        <?= e(number_format(
                            abs($subscriberBalance),
                            2,
                            ',',
                            ' '
                        )) ?>
                    </strong>
                </div>
            <?php else: ?>
                <div class="<?= $subscriberDebt > 0
                    ? 'payments-heading__debt payments-heading__debt--positive'
                    : 'payments-heading__debt payments-heading__debt--clear'
                ?>">
                    <span>Задолженность</span>
                    <strong>
                        <?= e(number_format(
                            $subscriberDebt,
                            2,
                            ',',
                            ' '
                        )) ?>
                    </strong>
                </div>
            <?php endif; ?>

            <?php if ($subscriberKarandashDescr !== ''): ?>
                <button
                    class="payments-heading__karandash"
                    type="button"
                    data-modal-open="karandash-modal"
                    title="Изменить запись на карандаше"
                >
                    <?= nl2br(e($subscriberKarandashDescr)) ?>
                </button>
            <?php endif; ?>

        </div>

        <div class="payments-heading__actions">


            <?php
            render_sms_button(
                $subscriberAddress,
                $subscriberPhone,
                $personal,
                $subscriberDebt,
                (string) $house,
                $tickets->countByAddress($subscriberAddress),
                $subscriber
            );
            ?>

            <button
                class="button"
                type="button"
                data-modal-open="karandash-modal"
            >
                <?= $subscriberOnKarandash
                    ? '✎ Изменить карандаш'
                    : '✎ Взять на карандаш'
                ?>
            </button>

            <form
                method="post"
                class="payments-heading__disconnect"
                onsubmit="return confirm(
                    'Отправить сообщение об отключении абонента?'
                )"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="telegram_disconnect"
                >

                <input
                    type="hidden"
                    name="house"
                    value="<?= e($house) ?>"
                >

                <input
                    type="hidden"
                    name="personal"
                    value="<?= e($personal) ?>"
                >

                <button
                    class="button danger"
                    type="submit"
                >
                    Отключить
                </button>
            </form>
        </div>




        <dialog class="modal" id="karandash-modal">
            <form method="post">
                <div class="modal-head">
                    <h2>
                        <?= $subscriberOnKarandash
                            ? 'Изменить запись'
                            : 'Взять на карандаш'
                        ?>
                    </h2>

                    <button
                        type="button"
                        class="icon-button"
                        data-modal-close
                        aria-label="Закрыть"
                    >
                        ×
                    </button>
                </div>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="return_module"
                    value="stat"
                >

                <input
                    type="hidden"
                    name="action"
                    value="karandash_add"
                >

                <input
                    type="hidden"
                    name="house"
                    value="<?= e($house) ?>"
                >

                <input
                    type="hidden"
                    name="personal"
                    value="<?= e($personal) ?>"
                >

                <input
                    type="hidden"
                    name="address"
                    value="<?= e($subscriberAddress) ?>"
                >

                <div class="karandash-address">
                    <span>Абонент</span>

                    <strong>
                        <?= e(
                            $subscriber !== ''
                                ? $subscriber
                                : 'Без имени'
                        ) ?>
                    </strong>
                </div>

                <div class="karandash-address">
                    <span>Адрес</span>

                    <strong>
                        <?= e(
                            $subscriberAddress !== ''
                                ? $subscriberAddress
                                : $house
                        ) ?>
                    </strong>
                </div>

                <label>
                    Причина

                    <textarea
                        class="input"
                        name="descr"
                        rows="6"
                        maxlength="2000"
                        <?= $subscriberOnKarandash ? '' : 'required' ?>
                        placeholder="Почему абонент взят на карандаш"
                    ><?= e($subscriberKarandashDescr) ?></textarea>
                </label>

                <div class="modal-actions">
                    <button
                        class="button"
                        type="button"
                        data-modal-close
                    >
                        Отмена
                    </button>

                    <button
                        class="button primary"
                        type="submit"
                    >
                        Сохранить
                    </button>
                </div>
            </form>
        </dialog>



    </div>




        

        <?php if (!$payments): ?>
            <div class="empty-state">
                <strong>Операции не обнаружены</strong>
                <span>
                    В истории нет изменений задолженности.
                </span>
            </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <?php
                $paymentUpdate = (string) ($payment['update'] ?? '');
                $amount = (float) ($payment['amount'] ?? 0);
                $currentDebt = (float) ($payment['current_debt'] ?? 0);
                $type = (string) ($payment['type'] ?? 'payment');

                $isPayment = $type === 'payment';
                ?>

                <article class="payment-item payment-item--<?= $isPayment ? 'payment' : 'charge' ?>">
                    <div class="payment-item__date">
                        <?= e(format_unix_time(
                            $paymentUpdate,
                            'd.m.Y'
                        )) ?>
                    </div>

                    <div class="payment-item__description">
                        <?= $isPayment ? 'Оплата' : 'Начислено' ?>
                    </div>

                    <div class="payment-item__amount">
                        <span class="payment-item__operation-sum">
                            <?= $isPayment ? '−' : '+' ?><?= e(number_format(
                                $amount,
                                2,
                                ',',
                                ' '
                            )) ?>
                        </span>

                        <span class="payment-item__balance">
                            <?= e(number_format(
                                $currentDebt,
                                2,
                                ',',
                                ' '
                            )) ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>



<?php elseif ($module === 'stat' && $house !== ''): ?>
    <section class="apartments-page">
        <div class="page-heading apartments-heading">

            <div class="apartments-heading__top">

                <div class="apartments-heading__title">
                    <a
                        class="apartments-heading__back"
                        href="?module=stat"
                    >
                        ← Все дома
                    </a>

                    <h1><?= e($house) ?></h1>

                    <div class="page-heading__counter">
                        <?= e(number_format(
                            count($apartments),
                            0,
                            ',',
                            ' '
                        )) ?>
                        квартир
                    </div>
                </div>

                <button
                    type="button"
                    class="house-note<?= $houseDescr === ''
                        ? ' house-note--empty'
                        : ''
                    ?>"
                    data-modal-open="house-descr-modal"
                    title="<?= e(
                        $houseDescr !== ''
                            ? $houseDescr
                            : 'Нет инфо'
                    ) ?>"
                >
                    <span class="house-note__icon">✎</span>

                    <span class="house-note__text">
                        <?= e(
                            $houseDescr !== ''
                                ? $houseDescr
                                : 'Нет инфо'
                        ) ?>
                    </span>
                </button>

            </div>

            <div class="apartments-heading__controls">

                <label class="apartments-group-control">
                    <span>Группа</span>

                    <select
                        id="apartments-group-size"
                        class="apartments-group-control__select"
                        data-house="<?= e($house) ?>"
                        data-csrf-token="<?= e(csrf_token()) ?>"
                    >
                        <?php for (
                            $groupSize = 0;
                            $groupSize <= 6;
                            $groupSize++
                        ): 
                            if ($groupSize === 1) continue;
                        ?>
                            <option
                                value="<?= e((string) $groupSize) ?>"
                                <?= $groupSize === $apartmentGroupSize
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= $groupSize === 0
                                    ? 'Не группировать'
                                    : e((string) $groupSize)
                                ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>            



                <label class="apartments-sort">
                    <input
                        id="apartments-sort-debt"
                        type="checkbox"
                    >

                    <span>Задолженность</span>
                </label>
            </div>
        </div>

        <dialog
            class="modal house-descr-modal"
            id="house-descr-modal"
        >
            <form method="post">
                <div class="modal-head">
                    <h2>Информация по дому</h2>

                    <button
                        type="button"
                        class="icon-button"
                        data-modal-close
                        aria-label="Закрыть"
                    >
                        ×
                    </button>
                </div>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="house_descr"
                >

                <input
                    type="hidden"
                    name="house"
                    value="<?= e($house) ?>"
                >

                <div class="house-descr-modal__house">
                    <span>Дом</span>
                    <strong><?= e($house) ?></strong>
                </div>

                <label>
                    Заметка

                    <textarea
                        class="input house-descr-modal__textarea"
                        name="descr"
                        rows="7"
                        maxlength="2000"
                        placeholder="Коды домофонов, доступ, оборудование и другая информация"
                    ><?= e($houseDescr) ?></textarea>
                </label>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="button"
                        data-modal-close
                    >
                        Отмена
                    </button>

                    <button
                        type="submit"
                        class="button primary"
                    >
                        Сохранить
                    </button>
                </div>
            </form>
        </dialog>

    <?php if (!$apartments): ?>
        <div class="empty-state">
            В этом доме квартиры не найдены.
        </div>
    <?php else: ?>
        <div class="apartment-grid" id="apartment-grid">
            <?php foreach ($apartments as $apartment): ?>
                <?php
                $tariff = trim((string) ($apartment['tariff'] ?? ''));
                $debt = (float) ($apartment['debt'] ?? 0);


                $apartmentClass = '';

                if ($tariff === 'Нет договора') {
                    $apartmentClass = ' apartment-card--inactive';
                } elseif ($tariff === 'Аналоговый пакет') {
                    $apartmentClass = ' apartment-card--analog';
                } elseif ($tariff === 'Цифровой пакет') {
                    $apartmentClass = ' apartment-card--digital';
                } elseif ($tariff === 'IPTV') {
                    $apartmentClass = ' apartment-card--iptv';
                } elseif (
                    str_contains(
                        mb_strtolower($tariff, 'UTF-8'),
                        'государствен'
                    )
                ) {
                    $apartmentClass = ' apartment-card--state-package';
                }

                if (
                    current_user() !== 'kassa'
                    && (bool) ($apartment['debt_always_zero'] ?? false)
                ) {
                    $apartmentClass .= ' apartment-card--always-zero';
                }
                ?>

                <a
                    class="apartment-card<?= $apartmentClass ?>"
                    href="<?= e(url([
                        'module' => 'stat',
                        'house' => $house,
                        'personal' => (string) ($apartment['personal'] ?? ''),
                    ])) ?>"
                    data-debt="<?= e(number_format($debt, 2, '.', '')) ?>"
                    data-apartment="<?= e((string) ($apartment['number'] ?? '')) ?>"
                >
                    <div class="apartment-card__number">
                        <?= e((string) ($apartment['number'] ?? '')) ?>
                    </div>

                    <div
                        class="apartment-card__subscriber"
                        title="<?= e((string) ($apartment['subscriber'] ?? '')) ?>"
                    >
                        <?= e((string) ($apartment['subscriber'] ?? '')) ?>
                    </div>

                    <?php
                    $karandashDescr = trim(
                        (string) ($apartment['karandash_descr'] ?? '')
                    );
                    ?>

                    <?php if ($karandashDescr !== ''): ?>
                        <div
                            class="apartment-card__karandash"
                            title="<?= e($karandashDescr) ?>"
                        >
                            <?= nl2br(e($karandashDescr)) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($debt > 0): ?>
                        <div class="apartment-card__debt">
                            <?= e(number_format($debt, 2, ',', ' ')) ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>






        </div>

        <?php if (
            $house !== ''
            && $personal === ''
        ): ?>

            <div class="house-control">

                <form
                    method="post"
                    class="house-control__form"
                    onsubmit="return confirm('Подтвердить выполнение контроля дома?')"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="house_control"
                    >

                    <input
                        type="hidden"
                        name="house"
                        value="<?= e($house) ?>"
                    >

                    <button
                        type="submit"
                        class="button house-control__button"
                    >
                        Контроль:

                        <?php if ($houseControl !== ''): ?>

                            <?= e(
                                (new DateTimeImmutable(
                                    $houseControl
                                ))->format('d.m.Y H:i:s')
                            ) ?>

                        <?php else: ?>

                            не выполнялся

                        <?php endif; ?>
                    </button>

                </form>

            </div>

        <?php endif; ?>



        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById(
                'apartments-sort-debt'
            );

            const groupSelect = document.getElementById(
                'apartments-group-size'
            );

            const grid = document.getElementById(
                'apartment-grid'
            );

            let savedGroupSize = groupSelect.value;

            async function saveApartmentGroup(groupSize) {
            const house = groupSelect.dataset.house || '';
            const csrfToken =
                groupSelect.dataset.csrfToken || '';

            const body = new FormData();

            body.set('ajax', 'save_apartment_group');
            body.set('csrf_token', csrfToken);
            body.set('house', house);
            body.set('group_size', String(groupSize));

            const response = await fetch('index.php', {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {
                Accept: 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                result.error ||
                'Не удалось сохранить размер группы.'
                );
            }

            savedGroupSize = String(result.group_size);
            }            

            if (!checkbox || !groupSelect || !grid) {
                return;
            }

            /*
            * Сохраняем исходные карточки отдельно.
            * После группировки непосредственными дочерними
            * элементами grid станут уже блоки.
            */
            const cards = Array.from(
                grid.querySelectorAll('.apartment-card')
            );

            function compareApartments(a, b) {
                const apartmentA =
                    a.dataset.apartment || '';

                const apartmentB =
                    b.dataset.apartment || '';

                return apartmentA.localeCompare(
                    apartmentB,
                    'ru',
                    {
                        numeric: true,
                        sensitivity: 'base'
                    }
                );
            }

            function getSortedCards() {
                const sortedCards = cards.slice();

                if (checkbox.checked) {
                    sortedCards.sort(function (a, b) {
                        const debtA =
                            parseFloat(a.dataset.debt) || 0;

                        const debtB =
                            parseFloat(b.dataset.debt) || 0;

                        if (debtB !== debtA) {
                            return debtB - debtA;
                        }

                        return compareApartments(a, b);
                    });
                } else {
                    sortedCards.sort(compareApartments);
                }

                return sortedCards;
            }

            function renderApartmentGroups() {
                const groupSize = Math.max(
                0,
                Math.min(
                    6,
                    Number.parseInt(groupSelect.value, 10)
                )
                );

                const sortedCards = getSortedCards();

                grid.replaceChildren();

                if (groupSize === 0) {
                    sortedCards.forEach(function (card) {
                        grid.appendChild(card);
                });

                grid.classList.add('apartment-grid--ungrouped');
                    return;
                }

                grid.classList.remove('apartment-grid--ungrouped');

                for (
                    let index = 0;
                    index < sortedCards.length;
                    index += groupSize
                ) {
                    const group = document.createElement('div');

                    group.className = 'apartment-group';

                    group.style.setProperty(
                        '--apartment-group-size',
                        String(groupSize)
                    );

                    const groupCards = sortedCards.slice(
                        index,
                        index + groupSize
                    );

                    groupCards.forEach(function (card) {
                        group.appendChild(card);
                    });

                    grid.appendChild(group);
                }
            }

            checkbox.addEventListener(
                'change',
                renderApartmentGroups
            );

            groupSelect.addEventListener(
            'change',
            async function () {
                const previousValue = savedGroupSize;

                renderApartmentGroups();

                groupSelect.disabled = true;

                try {
                await saveApartmentGroup(
                    Number.parseInt(
                    groupSelect.value,
                    10
                    )
                );
                } catch (error) {
                console.error(
                    'Ошибка сохранения группировки:',
                    error
                );

                groupSelect.value = previousValue;
                renderApartmentGroups();

                window.alert(
                    error instanceof Error
                    ? error.message
                    : 'Не удалось сохранить группировку.'
                );
                } finally {
                groupSelect.disabled = false;
                }
            }
            );

            /*
            * При первом открытии сразу формируем блоки
            * по четыре квартиры.
            */
            renderApartmentGroups();
        });
        </script>




    <?php endif; ?>
</section>
<?php else: ?>




<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('house-sort');
    const grid = document.getElementById('house-grid');

    if (!select || !grid) {
        return;
    }

    const cookieName = 'master_house_sort';

    const allowedSorts = [
        'default',
        'debt',
        'penetration',
        'control'
    ];

    const cards = Array.from(
        grid.querySelectorAll('.house-card-link')
    );

    const savedSort = getCookie(cookieName);

    if (
        savedSort
        && allowedSorts.includes(savedSort)
    ) {
        select.value = savedSort;
    }

    select.addEventListener('change', function () {
        setCookie(
            cookieName,
            select.value,
            365
        );

        sortCards();
    });



function sortCards() {
    const sortedCards = cards.slice();

    /*
     * По умолчанию показываем все дома.
     * При сортировке "По контролю"
     * неактивные будут скрыты ниже.
     */
    cards.forEach(function (card) {
        card.hidden = false;
    });

    if (select.value === 'debt') {
        sortedCards.sort(function (a, b) {
            const debtA =
                parseFloat(a.dataset.debt) || 0;

            const debtB =
                parseFloat(b.dataset.debt) || 0;

            if (debtB !== debtA) {
                return debtB - debtA;
            }

            return compareHouses(a, b);
        });
    } else if (select.value === 'penetration') {
        sortedCards.sort(function (a, b) {
            const penetrationA =
                parseFloat(
                    a.dataset.penetration
                ) || 0;

            const penetrationB =
                parseFloat(
                    b.dataset.penetration
                ) || 0;

            if (
                penetrationB !== penetrationA
            ) {
                return (
                    penetrationB
                    - penetrationA
                );
            }

            return compareHouses(a, b);
        });
    } else if (select.value === 'control') {
        sortedCards.sort(function (a, b) {
            const controlA =
                parseInt(
                    a.dataset.control || '0',
                    10
                );

            const controlB =
                parseInt(
                    b.dataset.control || '0',
                    10
                );

            /*
             * Дома без контроля —
             * в самом начале.
             */
            if (
                controlA === 0
                && controlB !== 0
            ) {
                return -1;
            }

            if (
                controlB === 0
                && controlA !== 0
            ) {
                return 1;
            }

            /*
             * Затем от самого старого
             * контроля к самому свежему.
             */
            if (controlA !== controlB) {
                return controlA - controlB;
            }

            return compareHouses(a, b);
        });
    } else {
        sortedCards.sort(compareHouses);
    }

    sortedCards.forEach(function (card) {
        /*
         * При сортировке по контролю
         * не показываем неактивные дома.
         */
        if (
            select.value === 'control'
            && card.querySelector(
                '.house-card--inactive'
            )
        ) {
            card.hidden = true;
        }

        grid.appendChild(card);
    });
}



    function compareHouses(a, b) {
        return (
            a.dataset.house || ''
        ).localeCompare(
            b.dataset.house || '',
            'ru',
            {
                numeric: true,
                sensitivity: 'base'
            }
        );
    }

    function getCookie(name) {
        const prefix =
            encodeURIComponent(name) + '=';

        const cookies =
            document.cookie.split(';');

        for (
            let index = 0;
            index < cookies.length;
            index++
        ) {
            const cookie =
                cookies[index].trim();

            if (
                cookie.indexOf(prefix) === 0
            ) {
                return decodeURIComponent(
                    cookie.substring(
                        prefix.length
                    )
                );
            }
        }

        return '';
    }

    function setCookie(
        name,
        value,
        days
    ) {
        const expires = new Date();

        expires.setTime(
            expires.getTime()
            + days * 24 * 60 * 60 * 1000
        );

        document.cookie =
            encodeURIComponent(name)
            + '='
            + encodeURIComponent(value)
            + '; expires='
            + expires.toUTCString()
            + '; path=/'
            + '; SameSite=Lax';
    }

    /*
     * Сразу применяем сортировку,
     * восстановленную из cookie.
     */
    sortCards();
});
</script>




    <?php
    /** @var array<string, mixed> $data */

    $houses = $data['houses'] ?? [];
    $databaseUpdate = (string) ($data['update'] ?? '');
    $subscribersTotal = (int) ($data['total'] ?? 0);

    $connectedTotal = 0;

    foreach ($houses as $house) {
        $connectedTotal +=
            (int) ($house['state_channels'] ?? 0)
            + (int) ($house['analog_package'] ?? 0)
            + (int) ($house['digital_package'] ?? 0);
    }


    $stateChannelsTotal = 0;
    $analogPackageTotal = 0;
    $digitalPackageTotal = 0;
    $iptvPackageTotal = 0;

    foreach ($houses as $house) {
        $stateChannelsTotal += (int) ($house['state_channels'] ?? 0);
        $analogPackageTotal += (int) ($house['analog_package'] ?? 0);
        $digitalPackageTotal += (int) ($house['digital_package'] ?? 0);
        $iptvPackageTotal += (int) ($house['iptv_package'] ?? 0);
    }

    $connectedTotal =
        $stateChannelsTotal
        + $analogPackageTotal
        + $digitalPackageTotal
        + $iptvPackageTotal;

    $stateChannelsPercent = $connectedTotal > 0
        ? ($stateChannelsTotal / $connectedTotal) * 100
        : 0;

    $analogPackagePercent = $connectedTotal > 0
        ? ($analogPackageTotal / $connectedTotal) * 100
        : 0;

    $digitalPackagePercent = $connectedTotal > 0
        ? ($digitalPackageTotal / $connectedTotal) * 100
        : 0;

    $iptvPackagePercent = $connectedTotal > 0
        ? ($iptvPackageTotal / $connectedTotal) * 100
        : 0;



    ?>

    <section class="page-heading">
        <div>
            <h1>Статистика по домам</h1>

            <p class="page-heading__description">
                Последнее обновление:
                <strong>
                    <?= e(format_unix_time($databaseUpdate, 'd.m.Y H:i')) ?>
                </strong>
            </p>
        </div>

        <div class="house-toolbar">
            <label for="house-sort">Сортировка</label>

            <select id="house-sort" class="house-toolbar__select">
                <option value="default">По умолчанию</option>
                <option value="debt">По общей задолженности</option>
                <option value="penetration">По проникновению</option>
                <option value="control">По контролю</option>
            </select>
        </div>

        <div class="page-heading__counter">
            <div>
                <?= e(number_format(count($houses), 0, ',', ' ')) ?>
                домов ·
                <?= e(number_format($connectedTotal, 0, ',', ' ')) ?>
                из
                <?= e(number_format($subscribersTotal, 0, ',', ' ')) ?>
                абонентов
            </div>

            <div
                class="page-heading__packages"
                title="Государственные каналы / Аналоговый пакет / Цифровой пакет / IPTV"
            >
                <span class="page-heading__packages-count">
                    <?= e(number_format($stateChannelsTotal, 0, ',', ' ')) ?>/<?= e(number_format($analogPackageTotal, 0, ',', ' ')) ?>/<?= e(number_format($digitalPackageTotal, 0, ',', ' ')) ?>/<?= e(number_format($iptvPackageTotal, 0, ',', ' ')) ?>
                </span>

                <span class="page-heading__packages-percent">
                    <?= e(number_format($stateChannelsPercent, 0, ',', ' ')) ?>%/<?= e(number_format($analogPackagePercent, 0, ',', ' ')) ?>%/<?= e(number_format($digitalPackagePercent, 0, ',', ' ')) ?>%/<?= e(number_format($iptvPackagePercent, 0, ',', ' ')) ?>%
                </span>
            </div>
        </div>
    </section>

    <?php if (!$houses): ?>

        <div class="empty-state">
            <strong>Статистика отсутствует</strong>
            <span>В последнем обновлении абонентской базы нет записей.</span>
        </div>

    <?php else: ?>

        <div class="house-grid" id="house-grid">
            <?php foreach ($houses as $house): ?>
                <?php
                $debt = (float) ($house['debt'] ?? 0);
                $debtors = (int) ($house['debtors'] ?? 0);

                $control = trim(
                    (string) ($house['control'] ?? '')
                );

                $controlTimestamp = $control !== ''
                    ? strtotime($control)
                    : 0;

                if ($controlTimestamp === false) {
                    $controlTimestamp = 0;
                }

                $controlLimitTimestamp = strtotime('-3 months');

                $houseControlClass = '';

                if ($controlTimestamp > 0) {
                    $houseControlClass =
                        $controlTimestamp >= $controlLimitTimestamp
                            ? ' house-card--control-fresh'
                            : ' house-card--control-old';
                }              

                $stateChannels = (int) ($house['state_channels'] ?? 0);
                $analogPackage = (int) ($house['analog_package'] ?? 0);
                $digitalPackage = (int) ($house['digital_package'] ?? 0);
                $iptvPackage = (int) ($house['iptv_package'] ?? 0);
                $subscribers = (int) ($house['subscribers'] ?? 0);

                $connected = $stateChannels + $analogPackage + $digitalPackage + $iptvPackage;

                $penetration = $subscribers > 0
                    ? round(($connected / $subscribers) * 100, 1)
                    : 0;

                $penetration = max(0, min(100, $penetration));

                $penetrationWidth = number_format(
                    (float) $penetration,
                    1,
                    '.',
                    ''
                );

                $isEmptyHouse = $connected === 0;
                ?>
                <a
                    class="house-card-link"
                    href="?module=stat&amp;house=<?= rawurlencode((string) ($house['house'] ?? '')) ?>"
                    data-house="<?= e(mb_strtolower((string) ($house['house'] ?? ''), 'UTF-8')) ?>"
                    data-debt="<?= e(number_format($debt, 2, '.', '')) ?>"
                    data-penetration="<?= e($penetrationWidth) ?>"
                    data-control="<?= e((string) $controlTimestamp) ?>"
                >
                    <article
                        class="house-card<?= $isEmptyHouse
                            ? ' house-card--inactive'
                            : ''
                        ?><?= $houseControlClass ?>"
                    >
                    <?php
                    $debt = (float) ($house['debt'] ?? 0);
                    $debtors = (int) ($house['debtors'] ?? 0);

                    $stateChannels = (int) ($house['state_channels'] ?? 0);
                    $analogPackage = (int) ($house['analog_package'] ?? 0);
                    $digitalPackage = (int) ($house['digital_package'] ?? 0);
                    $iptvPackage = (int) ($house['iptv_package'] ?? 0);
                    $subscribers = (int) ($house['subscribers'] ?? 0);

                    $connected = $stateChannels + $analogPackage + $digitalPackage + $iptvPackage;

                    $penetration = $subscribers > 0
                        ? round(($connected / $subscribers) * 100, 1)
                        : 0;

                    $penetration = max(0, min(100, $penetration));

                    $penetrationWidth = number_format(
                        $penetration,
                        1,
                        '.',
                        ''
                    );

                    ?>

                    <header class="house-card__header">
                        <div>
                            <h2 class="house-card__title">
                                <?= e($house['house'] ?? '') ?>
                            </h2>

                            <span
                                class="house-card__subtitle"
                                title="Государственные каналы / Аналоговый пакет / Цифровой пакет / IPTV"
                            >
                                <?= e((int) ($house['state_channels'] ?? 0)) ?>/<?= e((int) ($house['analog_package'] ?? 0)) ?>/<?= e((int) ($house['digital_package'] ?? 0)) ?>/<?= e((int) ($house['iptv_package'] ?? 0)) ?>
                                из
                                <?= e((int) ($house['subscribers'] ?? 0)) ?>
                                квартир
                            </span>

                        </div>

                        <?php
                        $karandashCount = (int) ($house['karandash'] ?? 0);
                        
                        if (
                            $debt > 0
                            || $controlTimestamp > 0
                        ): ?>
                            <div class="house-card__debt">
                                <?php if ($debt > 0): ?>
                                    <strong>
                                        <?= e(number_format($debt, 2, ',', ' ')) ?>
                                        <small>(<?= e($debtors) ?> аб.)</small>
                                    </strong>
                                <?php endif; ?>

                                <?php if ($controlTimestamp > 0): ?>
                                    <div class="house-card__control">
                                        <?= e(date(
                                            'd.m.Y',
                                            $controlTimestamp
                                        )) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($karandashCount > 0): ?>
                            <div class="house-card__karandash">
                                <strong><?= e($karandashCount) ?></strong>
                            </div>
                        <?php endif; ?>
                    </header>

                    <div
                        class="house-card__penetration"
                        title="Проникновение услуг: <?= e(number_format($penetration, 1, ',', ' ')) ?>%"
                    >
                        <div
                            class="house-card__penetration-fill"
                            style="width: <?= e($penetrationWidth) ?>%;"
                        ></div>

                        <span class="house-card__penetration-value">
                            <?= e(number_format($penetration, 0, ',', ' ')) ?>%
                        </span>
                    </div>

                </article>
                </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
<?php endif; ?>
<?php endif; ?>







<?php if (
    $module !== 'stat'
    && isset($data['total'])
    && $data['total'] > $perPage
):
    $pages = (int) ceil(
        $data['total'] / $perPage
    );

    $paginationParams = [
        'module' => $module,
        'search' => $search,
        'status' => $status,
    ];

    if (
        $module === 'database'
        && $withoutCharges
    ) {
        $paginationParams['without_charges'] = '1';
    }

    if (
        $module === 'database'
        && $withoutPayments
    ) {
        $paginationParams['without_payments'] = '1';
    }
?>

    <nav class="pagination">

        <?php if ($page > 1): ?>
            <a
                class="button"
                href="<?= e(url(
                    $paginationParams + [
                        'page' => $page - 1,
                    ]
                )) ?>"
            >
                ← Назад
            </a>
        <?php endif; ?>

        <span>
            Страница <?= e($page) ?>
            из <?= e($pages) ?>
        </span>

        <?php if ($page < $pages): ?>
            <a
                class="button"
                href="<?= e(url(
                    $paginationParams + [
                        'page' => $page + 1,
                    ]
                )) ?>"
            >
                Далее →
            </a>
        <?php endif; ?>

    </nav>

<?php endif; ?>
    </main>
</div>

<?php if (in_array($module,['zayavki','podkluchki'],true)): ?>



<dialog
    class="modal create-modal"
    id="create-modal"
>
    <?php if ($module === 'zayavki'): ?>
        <form
            method="post"
            class="ticket-create-form"
            data-ticket-create-form
        >
            <div class="modal-head">
                <h2>Добавить новую заявку от абонента</h2>

                <button
                    type="button"
                    class="icon-button"
                    data-modal-close
                    aria-label="Закрыть"
                >
                    ×
                </button>
            </div>

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <div class="ticket-create-form__person">
                <label>
                    <span>Адрес абонента</span>
                    <input
                        class="input"
                        type="text"
                        name="address"
                        maxlength="50"
                        required
                        autocomplete="off"
                        data-subscriber-address
                        placeholder="Начните вводить улицу"
                    >
                </label>

                <label>
                    <span>ФИО</span>
                    <input
                        class="input"
                        type="text"
                        name="abonent"
                        maxlength="50"
                        required
                        autocomplete="name"
                        data-subscriber-name
                    >
                </label>
            </div>

            <div
                class="ticket-subscriber-info"
                data-subscriber-info
                hidden
            >
                <div class="ticket-subscriber-info__item">
                    <span>Тариф</span>
                    <button
                        type="button"
                        class="ticket-subscriber-tariff"
                        data-subscriber-tariff
                        title="Добавить тариф в примечание"
                    >
                        —
                    </button>
                </div>

                <div class="ticket-subscriber-info__item">
                    <span>Телефон</span>
                    <strong data-subscriber-phone>—</strong>
                </div>

                <div class="ticket-subscriber-info__item">
                    <span>Долг</span>
                    <strong data-subscriber-debt>—</strong>
                </div>

                <div class="ticket-subscriber-info__item">
                    <span>Всего заявок</span>
                    <a
                        data-subscriber-tickets
                        href="#"
                        target="_blank"
                        rel="noopener"
                        hidden
                        class="data-subscriber-tickets-count"
                    >
                        0
                    </a>
                    <strong data-subscriber-tickets-zero>0</strong>
                </div>

            </div>


            <label>
                <span>Описание заявки</span>

                <select
                    class="input select"
                    name="description"
                    required
                >
                    <option value="" selected disabled>
                        Выберите описание
                    </option>

                    <option value="нет трансляции">
                        нет трансляции
                    </option>

                    <option value="плохая трансляция">
                        плохая трансляция
                    </option>

                    <option value="настройка каналов">
                        настройка каналов
                    </option>

                    <option value="ремонт квартирной сети">
                        ремонт квартирной сети
                    </option>

                    <option value="авария на линии">
                        авария на линии
                    </option>

                    <option value="подключить на площадке">
                        подключить на площадке
                    </option>

                    <option value="другие услуги">
                        другие услуги
                    </option>
                </select>
            </label>

            <label>
                <span>Примечание</span>

                <input
                    class="input"
                    type="text"
                    name="other"
                    maxlength="50"
                    autocomplete="off"
                >
            </label>

            <div class="ticket-create-form__actions">
                <button
                    class="button primary"
                    type="submit"
                >
                    Добавить
                </button>

                <button
                    class="button button-link"
                    type="button"
                    data-modal-close
                >
                    Отмена
                </button>
            </div>
        </form>
    <?php else: ?>
        <form
            method="post"
            class="ticket-create-form"
            data-ticket-create-form
        >
            <div class="modal-head">
                <h2>Новое подключение</h2>
                <button
                    type="button"
                    class="icon-button"
                    data-modal-close
                    aria-label="Закрыть"
                >
                    ×
                </button>
            </div>

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <div class="ticket-create-form__person">
                <label>
                    <span>Адрес абонента</span>
                    <input
                        class="input"
                        type="text"
                        name="address"
                        maxlength="50"
                        required
                        autocomplete="off"
                        data-subscriber-address
                        placeholder="Начните вводить улицу"
                    >
                </label>

                <label>
                    <span>ФИО</span>
                    <input
                        class="input"
                        type="text"
                        name="abonent"
                        maxlength="50"
                        required
                        autocomplete="name"
                        data-subscriber-name
                    >
                </label>
            </div>

            <label>
                <span>Род коммутации</span>
                <select
                    class="input select"
                    name="description"
                    required
                >
                    <option value="" selected disabled>
                        Выберите действие
                    </option>

                    <option value="отключить всё">
                        отключить всё
                    </option>

                    <option value="отключить временно">
                        отключить временно
                    </option>

                    <option value="подключить госканалы">
                        подключить госканалы
                    </option>

                    <option value="подключить аналоговый пакет">
                        подключить аналоговый пакет
                    </option>

                    <option value="подключить цифровой пакет">
                        подключить цифровой пакет
                    </option>

                    <option value="подключить IPTV пакет">
                        подключить IPTV
                    </option>

                    <option value="переезд на другой адрес">
                        переезд на другой адрес
                    </option>
                </select>
            </label>

            <label>
                <span>Дополнительно</span>
                <input
                    class="input"
                    type="text"
                    name="other"
                    maxlength="50"
                    autocomplete="off"
                >
            </label>

            <div class="ticket-create-form__actions">
                <button
                    class="button primary"
                    type="submit"
                >
                    Сохранить
                </button>

                <button
                    class="button button-link"
                    type="button"
                    data-modal-close
                >
                    Отмена
                </button>
            </div>
        </form>
    <?php endif; ?>
</dialog>







<dialog class="modal" id="complete-modal">
    <form method="post">
        <div class="modal-head">
            <h2>Завершение</h2>

            <button
                type="button"
                class="icon-button"
                data-modal-close
            >
                ×
            </button>
        </div>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="complete"
        >

        <input
            type="hidden"
            name="id"
            id="complete-id"
        >

        <label>
            Мастер

            <input
                class="input"
                name="master"
                maxlength="50"
                value="<?= e(current_user()) ?>"
                required
            >
        </label>

        <label>
            Результат

            <input
                class="input"
                name="result"
                maxlength="50"
                value="OK"
                required
            >
        </label>

        <label id="cost-field">
            Стоимость

            <input
                class="input"
                name="cost"
                maxlength="10"
                inputmode="decimal"
                value="-"
            >
        </label>

        <button
            class="button primary full"
            type="submit"
        >
            Сохранить
        </button>
    </form>
</dialog>

<?php endif; ?>


<?php if (
    $module === 'database'
    && $action !== 'history'
): ?>

<dialog
    class="modal database-import-modal"
    id="database-import-modal"
>
    <form
        id="database-import-form"
        enctype="multipart/form-data"
    >
        <div class="modal-head">
            <h2>Импорт базы абонентов</h2>

            <button
                type="button"
                class="icon-button"
                data-modal-close
                aria-label="Закрыть"
            >
                ×
            </button>
        </div>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="ajax"
            value="database_import"
        >

        <label>
            Файл базы

            <input
                class="input"
                type="file"
                name="db"
                id="database-import-file"
                required
            >
        </label>

        <div
            class="database-import-progress"
            id="database-import-progress"
            hidden
        >
            <div class="database-import-progress__header">
                <span id="database-import-progress-text">
                    Загрузка…
                </span>

                <strong
                    id="database-import-progress-percent"
                >
                    0%
                </strong>
            </div>

            <div class="database-import-progress__track">
                <div
                    class="database-import-progress__bar"
                    id="database-import-progress-bar"
                ></div>
            </div>
        </div>

        <div
            class="database-import-result"
            id="database-import-result"
            hidden
        ></div>
    </form>
</dialog>

<?php endif; ?>


</body></html>
