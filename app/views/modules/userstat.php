<?php

declare(strict_types=1);

/** @var array $data */
/** @var array $config */

$rows = $data['rows'] ?? [];

$filterUsername = (string) (
    $data['username']
    ?? ''
);

$users = $config['auth']['users'] ?? [];

if (!is_array($users)) {
    $users = [];
}

$usernames = array_keys($users);

sort(
    $usernames,
    SORT_NATURAL | SORT_FLAG_CASE
);

?>

<section class="userstat-page">

    <style>
        .userstat-table a{
            color: #10659d !important;
        }

        .userstat-page {
            width: 100%;
        }

        .userstat-info {
            margin-bottom: 12px;
            color: var(--muted, #777);
            font-size: 13px;
        }

        .userstat-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .userstat-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .userstat-table th,
        .userstat-table td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(128, 128, 128, 0.25);
            text-align: left;
            vertical-align: top;
        }

        .userstat-table th {
            white-space: nowrap;
            font-weight: 600;
        }

        .userstat-table tbody tr:hover {
            background: rgba(128, 128, 128, 0.08);
        }

        .userstat-id {
            white-space: nowrap;
            width: 1%;
        }

        .userstat-user {
            white-space: nowrap;
            font-weight: 600;
        }

        .userstat-date {
            white-space: nowrap;
        }

        .userstat-ip {
            white-space: nowrap;
            font-family: monospace;
            font-size: 12px;
        }

        .userstat-agent {
            min-width: 250px;
            max-width: 500px;
            overflow-wrap: anywhere;
            color: var(--muted, #666);
        }

        .userstat-url {
            min-width: 250px;
            overflow-wrap: anywhere;
            font-family: monospace;
            font-size: 12px;
        }

        .userstat-empty {
            padding: 30px !important;
            text-align: center !important;
            color: var(--muted, #777);
        }


.userstat-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.userstat-toolbar .userstat-info {
    margin-bottom: 0;
}

.userstat-filter {
    flex: 0 0 auto;
}

.userstat-filter label {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.userstat-filter select {
    min-width: 180px;
}

@media (max-width: 700px) {
    .userstat-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .userstat-filter label {
        justify-content: space-between;
    }

    .userstat-filter select {
        flex: 1;
    }
}

    </style>


    <div class="userstat-toolbar">

        <div class="userstat-info">

            <?php if ($filterUsername !== ''): ?>

                Последние <?= count($rows) ?> переходов пользователя
                <strong><?= e($filterUsername) ?></strong>

            <?php else: ?>

                Последние <?= count($rows) ?> переходов

            <?php endif; ?>

        </div>

        <div class="userstat-filter">

            <label>
                Пользователь

                <select
                    class="input"
                    onchange="
                        window.location.href = this.value
                            ? '?module=userstat&username=' + encodeURIComponent(this.value)
                            : '?module=userstat';
                    "
                >
                    <option value="">
                        Все пользователи
                    </option>

                    <?php foreach ($usernames as $username): ?>

                        <option
                            value="<?= e($username) ?>"
                            <?= $filterUsername === $username ? 'selected' : '' ?>
                        >
                            <?= e($username) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </label>

        </div>

    </div>


    <div class="userstat-table-wrap">

        <table class="userstat-table">

            <thead>
                <tr>
                    <th>Время</th>
                    <th>Пользователь</th>
                    <th>IP</th>
                    <th>Агент</th>
                    <th>Адрес</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>
                        <td
                            colspan="5"
                            class="userstat-empty"
                        >
                            Записей пока нет
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $row): ?>

                        <tr>

                            <td class="userstat-date">
                                <?php
                                    $createdAt = (string) (
                                        $row['created_at']
                                        ?? ''
                                    );

                                    $dateText = '';
                                    $isToday = false;

                                    if ($createdAt !== '') {
                                        $date = new DateTimeImmutable($createdAt);

                                        $today = new DateTimeImmutable('today');
                                        $yesterday = $today->modify('-1 day');
                                        $dayBeforeYesterday = $today->modify('-2 days');

                                        $dateYmd = $date->format('Y-m-d');

                                        if (
                                            $dateYmd
                                            === $today->format('Y-m-d')
                                        ) {
                                            $dateText = 'Сегодня в '
                                                . $date->format('H:i');

                                            $isToday = true;
                                        } elseif (
                                            $dateYmd
                                            === $yesterday->format('Y-m-d')
                                        ) {
                                            $dateText = 'Вчера в '
                                                . $date->format('H:i');
                                        } elseif (
                                            $dateYmd
                                            === $dayBeforeYesterday->format('Y-m-d')
                                        ) {
                                            $dateText = 'Позавчера в '
                                                . $date->format('H:i');
                                        } else {
                                            $dateText = $date->format(
                                                'd.m.Y H:i'
                                            );
                                        }
                                    }
                                ?>

                                <?php if ($isToday): ?>
                                    <strong><?= e($dateText) ?></strong>
                                <?php else: ?>
                                    <?= e($dateText) ?>
                                <?php endif; ?>
                            </td>

                            <td class="userstat-user">
                                <?php
                                    $username = (string) (
                                        $row['username']
                                        ?? ''
                                    );
                                ?>

                                <a
                                    href="?module=userstat&username=<?= urlencode($username) ?>"
                                    title="Показать последние 150 записей пользователя <?= e($username) ?>"
                                >
                                    <?= e($username) ?>
                                </a>
                            </td>



                            <td class="userstat-ip">
                                <?= e(
                                    (string) (
                                        $row['ip']
                                        ?? ''
                                    )
                                ) ?>
                            </td>

                            <td
                                class="userstat-agent"
                                title="<?= e(
                                    (string) (
                                        $row['user_agent']
                                        ?? ''
                                    )
                                ) ?>"
                            >
                                <?php
                                    $userAgent = (string) (
                                        $row['user_agent']
                                        ?? ''
                                    );

                                    $userAgentShort = mb_strlen(
                                        $userAgent,
                                        'UTF-8'
                                    ) > 50
                                        ? mb_substr(
                                            $userAgent,
                                            0,
                                            50,
                                            'UTF-8'
                                        ) . '...'
                                        : $userAgent;
                                ?>

                                <?= e($userAgentShort) ?>
                            </td>

                            <td class="userstat-url">
                                <?php
                                    $url = (string) (
                                        $row['url']
                                        ?? ''
                                    );

                                    $urlText = str_replace("/index.php", "/", urldecode($url));
                                ?>

                                <a
                                    href="<?= e($url) ?>"
                                    title="<?= e($urlText) ?>"
                                    target="_blank"
                                >
                                    <?= e($urlText) ?>
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>