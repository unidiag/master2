<?php

declare(strict_types=1);

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
/** @var string $subscriberTariff */
/** @var string $subscriberOnKarandash */
/** @var string $subscriberKarandashDescr */
/** @var float $subscriberDebt */
$subscriberDebt = isset($subscriberDebt)
    ? (float) $subscriberDebt
    : 0.0;

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
?>

<link
    rel="stylesheet"
    href="assets/app.css?v=<?= e((string) $cssVersion) ?>"
>
    <script src="assets/app.js?v=1" defer></script>
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
    <div class="user-badge"><?= e(current_user()) ?></div>
</header>
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <nav>
            <?php
                $navigation = [
                    ['zayavki', 'Заявки', '☑'],
                    ['podkluchki', 'Подключения', '🔌'],
                    ['database', 'Абоненты', '👥'],
                    ['stat', 'Статистика', '▦'],
                    ['debtors', 'Должники', '₽'],
                    ['karandash', 'Карандаш', '✎'],
                ];
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
                    <select class="input select" name="status">
                        <option value="all" <?= $status==='all'?'selected':'' ?>>Все</option>
                        <option value="open" <?= $status==='open'?'selected':'' ?>>Открытые</option>
                        <option value="done" <?= $status==='done'?'selected':'' ?>>Выполненные</option>
                    </select>
                    <?php endif; ?>
                    <button class="button" type="submit">Найти</button>
                </form>
                <?php if ($module !== 'database'): ?><button class="button primary" type="button" data-modal-open="create-modal">＋ Добавить</button><?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($module === 'zayavki'): ?>
            <div class="cards">
            <?php foreach ($data['rows'] as $row): ?>
                <article class="card <?= is_done($row)?'done':'' ?>">
                    <div class="card-head"><div><span class="id">#<?= e($row['id']) ?></span><h2><?= e($row['abonent'] ?: $row['abonent_ajax'] ?: 'Без имени') ?></h2></div><span class="status <?= is_done($row)?'status-done':'status-open' ?>"><?= is_done($row)?'Выполнена':'Открыта' ?></span></div>
                    <div class="address"><?= e($row['address'] ?: $row['address_ajax'] ?: 'Адрес не указан') ?></div>
                    <p><?= e($row['desc']) ?></p>
                    <?php if ($row['other']): ?><div class="muted"><?= e($row['other']) ?></div><?php endif; ?>
                    <dl class="meta"><div><dt>Создана</dt><dd><?= e(format_datetime($row['time'])) ?></dd></div><div><dt>Принял</dt><dd><?= e($row['who']) ?></dd></div><?php if (is_done($row)): ?><div><dt>Мастер</dt><dd><?= e($row['master']) ?></dd></div><div><dt>Результат</dt><dd><?= e($row['result']) ?></dd></div><div><dt>Стоимость</dt><dd><?= e($row['cost'] ?: '—') ?></dd></div><?php endif; ?></dl>
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
                <article class="card <?= is_done($row)?'done':'' ?>">
                    <div class="card-head"><div><span class="id">#<?= e($row['id']) ?></span><h2><?= e($row['abonent'] ?: 'Без имени') ?></h2></div><span class="status <?= is_done($row)?'status-done':'status-open' ?>"><?= is_done($row)?'Завершено':'Открыто' ?></span></div>
                    <div class="address"><?= e($row['address'] ?: 'Адрес не указан') ?></div><p><?= e($row['desc']) ?></p>
                    <dl class="meta"><div><dt>Создано</dt><dd><?= e(format_datetime($row['time'])) ?></dd></div><div><dt>Принял</dt><dd><?= e($row['who']) ?></dd></div><?php if (is_done($row)): ?><div><dt>Мастер</dt><dd><?= e($row['master']) ?></dd></div><div><dt>Результат</dt><dd><?= e($row['result']) ?></dd></div><?php endif; ?></dl>

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

                    <a
                        class="subscriber-card subscriber-card--link<?= $subscriberCardClass ?>"
                        href="<?= e(url([
                            'module' => 'stat',
                            'house' => $subscriberHouse,
                            'personal' => (string) ($row['personal'] ?? ''),
                        ])) ?>"
                    >
                        <div>
                            <strong>
                                <?= e((string) ($row['account'] ?? '')) ?>
                            </strong>

                            <div class="muted">
                                <?= e($address) ?>
                            </div>
                        </div>

                        <div>
                            <span class="label">Лицевой счёт</span>
                            <?= e((string) ($row['personal'] ?? '')) ?>
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
                                        ((float) $row['summ']) / 10000,
                                        2,
                                        ',',
                                        ' '
                                    )
                                    : (string) ($row['summ'] ?? '')
                            ) ?>
                        </div>

                        <div>
                            <span class="label">Обновление</span>

                            <?= e(format_unix_time(
                                (string) ($row['update'] ?? '')
                            )) ?>
                        </div>
                    </a>
                <?php endforeach; ?>


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
            name="action"
            value="karandash_add"
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
                <option value="3">Более 3 месяцев</option>
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
});
</script>




    <?php endif; ?>

                                  

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
                <strong>Оплаты не обнаружены</strong>

                <span>
                    В истории нет переходов от задолженности к нулевому
                    или отрицательному балансу.
                </span>
            </div>
        <?php else: ?>
            <div class="payment-list">
                <?php foreach ($payments as $payment): ?>
                    <?php
                    $paymentUpdate = (string) ($payment['update'] ?? '');
                    $previousDebt = (float) ($payment['previous_debt'] ?? 0);
                    ?>

                    <article class="payment-item">
                        <div class="payment-item__date">
                            <?= e(format_unix_time(
                                $paymentUpdate,
                                'd.m.Y'
                            )) ?>
                        </div>

                        <div class="payment-item__description">
                            Погашена задолженность
                        </div>

                        <div class="payment-item__amount">
                            <?= e(number_format(
                                $previousDebt,
                                2,
                                ',',
                                ' '
                            )) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>



<?php elseif ($module === 'stat' && $house !== ''): ?>
    <section class="apartments-page">
        <div class="page-heading apartments-heading">
            <div>
                <a class="apartments-heading__back" href="?module=stat">
                    ← Все дома
                </a>

                <h1><?= e($house) ?></h1>

                <div class="page-heading__counter">
                    <?= e(number_format(count($apartments), 0, ',', ' ')) ?>
                    квартир
                </div>
            </div>

            <label class="apartments-sort">
                <input
                    id="apartments-sort-debt"
                    type="checkbox"
                >

                <span>Задолженность</span>
            </label>
        </div>

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
                } elseif (
                    str_contains(
                        mb_strtolower($tariff, 'UTF-8'),
                        'государствен'
                    )
                ) {
                    $apartmentClass = ' apartment-card--state-package';
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


    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById(
            'apartments-sort-debt'
        );

        const grid = document.getElementById('apartment-grid');

        if (!checkbox || !grid) {
            return;
        }

        const cards = Array.from(
            grid.querySelectorAll('.apartment-card')
        );

        checkbox.addEventListener('change', function () {
            const sortedCards = cards.slice();

            if (checkbox.checked) {
                sortedCards.sort(function (a, b) {
                    const debtA = parseFloat(a.dataset.debt) || 0;
                    const debtB = parseFloat(b.dataset.debt) || 0;

                    if (debtB !== debtA) {
                        return debtB - debtA;
                    }

                    return compareApartments(a, b);
                });
            } else {
                sortedCards.sort(compareApartments);
            }

            sortedCards.forEach(function (card) {
                grid.appendChild(card);
            });
        });

        function compareApartments(a, b) {
            const apartmentA = a.dataset.apartment || '';
            const apartmentB = b.dataset.apartment || '';

            return apartmentA.localeCompare(
                apartmentB,
                'ru',
                {
                    numeric: true,
                    sensitivity: 'base'
                }
            );
        }
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

    const cards = Array.from(
        grid.querySelectorAll('.house-card-link')
    );

    select.addEventListener('change', function () {
        const sortedCards = cards.slice();

        if (select.value === 'debt') {
            sortedCards.sort(function (a, b) {
                const debtA = parseFloat(a.dataset.debt) || 0;
                const debtB = parseFloat(b.dataset.debt) || 0;

                if (debtB !== debtA) {
                    return debtB - debtA;
                }

                return compareHouses(a, b);
            });
        } else if (select.value === 'penetration') {
            sortedCards.sort(function (a, b) {
                const penetrationA =
                    parseFloat(a.dataset.penetration) || 0;

                const penetrationB =
                    parseFloat(b.dataset.penetration) || 0;

                if (penetrationB !== penetrationA) {
                    return penetrationB - penetrationA;
                }

                return compareHouses(a, b);
            });
        } else {
            sortedCards.sort(compareHouses);
        }

        sortedCards.forEach(function (card) {
            grid.appendChild(card);
        });
    });

    function compareHouses(a, b) {
        return (a.dataset.house || '').localeCompare(
            b.dataset.house || '',
            'ru',
            {
                numeric: true,
                sensitivity: 'base'
            }
        );
    }
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

    foreach ($houses as $house) {
        $stateChannelsTotal += (int) ($house['state_channels'] ?? 0);
        $analogPackageTotal += (int) ($house['analog_package'] ?? 0);
        $digitalPackageTotal += (int) ($house['digital_package'] ?? 0);
    }

    $connectedTotal =
        $stateChannelsTotal
        + $analogPackageTotal
        + $digitalPackageTotal;

    $stateChannelsPercent = $connectedTotal > 0
        ? ($stateChannelsTotal / $connectedTotal) * 100
        : 0;

    $analogPackagePercent = $connectedTotal > 0
        ? ($analogPackageTotal / $connectedTotal) * 100
        : 0;

    $digitalPackagePercent = $connectedTotal > 0
        ? ($digitalPackageTotal / $connectedTotal) * 100
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
                title="Государственные каналы / Аналоговый пакет / Цифровой пакет"
            >
                <span class="page-heading__packages-count">
                    <?= e(number_format($stateChannelsTotal, 0, ',', ' ')) ?>/<?= e(number_format($analogPackageTotal, 0, ',', ' ')) ?>/<?= e(number_format($digitalPackageTotal, 0, ',', ' ')) ?>
                </span>

                <span class="page-heading__packages-percent">
                    <?= e(number_format($stateChannelsPercent, 0, ',', ' ')) ?>%/<?= e(number_format($analogPackagePercent, 0, ',', ' ')) ?>%/<?= e(number_format($digitalPackagePercent, 0, ',', ' ')) ?>%
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

                $stateChannels = (int) ($house['state_channels'] ?? 0);
                $analogPackage = (int) ($house['analog_package'] ?? 0);
                $digitalPackage = (int) ($house['digital_package'] ?? 0);
                $subscribers = (int) ($house['subscribers'] ?? 0);

                $connected = $stateChannels + $analogPackage + $digitalPackage;

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
                >
                    <article class="house-card<?= $isEmptyHouse ? ' house-card--inactive' : '' ?>">
                    <?php
                    $debt = (float) ($house['debt'] ?? 0);
                    $debtors = (int) ($house['debtors'] ?? 0);

                    $stateChannels = (int) ($house['state_channels'] ?? 0);
                    $analogPackage = (int) ($house['analog_package'] ?? 0);
                    $digitalPackage = (int) ($house['digital_package'] ?? 0);
                    $subscribers = (int) ($house['subscribers'] ?? 0);

                    $connected = $stateChannels + $analogPackage + $digitalPackage;

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
                                title="Государственные каналы / Аналоговый пакет / Цифровой пакет"
                            >
                                <?= e((int) ($house['state_channels'] ?? 0)) ?>/<?= e((int) ($house['analog_package'] ?? 0)) ?>/<?= e((int) ($house['digital_package'] ?? 0)) ?>
                                из
                                <?= e((int) ($house['subscribers'] ?? 0)) ?>
                                квартир
                            </span>

                        </div>

                        <?php
                        $karandashCount = (int) ($house['karandash'] ?? 0);
                        
                        if ($debt > 0): ?>
                            <div class="house-card__debt">
                                <strong>
                                    <?= e(number_format($debt, 2, ',', ' ')) ?>
                                    <small>(<?= e($debtors) ?> аб.)</small>
                                </strong>
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







        <?php if ($module !== 'stat' && isset($data['total']) && $data['total'] > $perPage): $pages=(int)ceil($data['total']/$perPage); ?>
        <nav class="pagination"><?php if($page>1): ?><a class="button" href="<?= e(url(['module'=>$module,'search'=>$search,'status'=>$status,'page'=>$page-1])) ?>">← Назад</a><?php endif; ?><span>Страница <?= e($page) ?> из <?= e($pages) ?></span><?php if($page<$pages): ?><a class="button" href="<?= e(url(['module'=>$module,'search'=>$search,'status'=>$status,'page'=>$page+1])) ?>">Далее →</a><?php endif; ?></nav>
        <?php endif; ?>
    </main>
</div>

<?php if (in_array($module,['zayavki','podkluchki'],true)): ?>
<dialog class="modal" id="create-modal"><form method="post"><div class="modal-head"><h2><?= $module==='zayavki'?'Новая заявка':'Новое подключение' ?></h2><button type="button" class="icon-button" data-modal-close>×</button></div><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><label>Абонент<input class="input" name="abonent" maxlength="50" required autocomplete="name"></label><?php if($module==='zayavki'): ?><label>Дополнительное имя<input class="input" name="abonent_ajax" maxlength="50"></label><?php endif; ?><label>Адрес<input class="input" name="address" maxlength="50" required autocomplete="street-address"></label><?php if($module==='zayavki'): ?><label>Дополнительный адрес<input class="input" name="address_ajax" maxlength="50"></label><?php endif; ?><label>Описание<input class="input" name="description" maxlength="50" required></label><label>Дополнительно<input class="input" name="other" maxlength="50"></label><?php if($module==='zayavki'): ?><label>Стоимость<input class="input" name="cost" maxlength="10" inputmode="decimal"></label><?php endif; ?><button class="button primary full" type="submit">Сохранить</button></form></dialog>
<dialog class="modal" id="complete-modal"><form method="post"><div class="modal-head"><h2>Завершение</h2><button type="button" class="icon-button" data-modal-close>×</button></div><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="complete"><input type="hidden" name="id" id="complete-id"><label>Мастер<input class="input" name="master" maxlength="50" required></label><label>Результат<input class="input" name="result" maxlength="50" required></label><label id="cost-field">Стоимость<input class="input" name="cost" maxlength="10" inputmode="decimal"></label><button class="button primary full" type="submit">Сохранить</button></form></dialog>
<?php endif; ?>
</body></html>
