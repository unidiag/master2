<?php

declare(strict_types=1);

/** @var array $iptvData */
$account = is_array($iptvData['user'] ?? null) ? $iptvData['user'] : null;
$subscriber = is_array($iptvData['subscriber'] ?? null) ? $iptvData['subscriber'] : null;
$operations = $iptvData['operations'] ?? [];
$login = (string) ($iptvData['login'] ?? '');
$personal = (string) ($iptvData['personal'] ?? '');
$billing = $account !== null ? (string) ($account['billing_id'] ?? '') : '';
$debt = $subscriber !== null ? (float) $subscriber['debt'] : null;

$mayPair = $account !== null
    && $subscriber !== null
    && $billing === '0';

$e = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$amount = static function ($number): string {
    return number_format((float) $number, 2, ',', ' ');
};
$datetime = static function ($value): string {
    if ($value === null || $value === '' || (string) $value === '0') {
        return '—';
    }

    $timestamp = is_numeric($value)
        ? (int) $value
        : strtotime((string) $value);

    if ($timestamp === false) {
        return (string) $value;
    }

    $date = date('Y-m-d', $timestamp);

    if ($date === date('Y-m-d')) {
        return 'Сегодня, ' . date('H:i:s', $timestamp);
    }

    if ($date === date('Y-m-d', strtotime('yesterday'))) {
        return 'Вчера, ' . date('H:i:s', $timestamp);
    }

    if ($date === date('Y-m-d', strtotime('-2 days'))) {
        return 'Позавчера, ' . date('H:i:s', $timestamp);
    }

    return date('d.m.Y H:i:s', $timestamp);
};
?>
<style>
.iptv-pair { max-width: 740px; margin: 20px auto 50px; }
.iptv-pair h2 { text-align: center; margin-bottom: 8px; }
.iptv-pair__hint { text-align:center; opacity:.7; margin-bottom:24px; }
.iptv-pair__card { border:1px solid #d9dfe6; border-radius:12px; padding:20px; margin:16px 0; background:var(--card-bg,#fff); }
.iptv-pair__card h3 { margin-top:0; }
.iptv-pair__search { display:flex; gap:10px; align-items:stretch; }
.iptv-pair__search input { flex:1; min-width:0; padding:11px 12px; border:1px solid #aab3c0; border-radius:7px; font:inherit; }
.iptv-pair__search button,.iptv-pair__submit { border:0; border-radius:7px; padding:11px 20px; background:#226cbd; color:#fff; cursor:pointer; font:inherit; }
.iptv-pair__submit { background:#267b48; width:100%; margin-top:12px; font-weight:600; }
.iptv-pair__unlink {
    background: #dc3545;
}

.iptv-pair__unlink:hover {
    background: #b02a37;
}
.iptv-pair__fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-top:16px; }
.iptv-pair__fields dt { opacity:.7; font-size:.88em; }
.iptv-pair__fields dd { margin:3px 0 0; font-weight:600; overflow-wrap:anywhere; }
.iptv-pair__message { padding:12px 14px; border:1px solid #c8d3e0; border-radius:8px; margin:14px 0; }
.iptv-pair__error { border-color:#cf8080; }
.iptv-pair__success { border-color:#70af83; }
/* Компактная таблица последних операций */
.iptv-pair__table {
    overflow-x: auto;
    margin-top: 10px;
}

.iptv-pair__ops {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    font-size: 12px;
    line-height: 1.3;
    font-variant-numeric: tabular-nums;
}

.iptv-pair__ops th,
.iptv-pair__ops td {
    padding: 5px 7px;
    border-bottom: 1px solid #e6e7eb;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.iptv-pair__ops th {
    font-size: 11px;
    font-weight: 600;
    opacity: .7;
}

.iptv-pair__ops td:nth-child(3),
.iptv-pair__ops td:nth-child(4),
.iptv-pair__ops th:nth-child(3),
.iptv-pair__ops th:nth-child(4) {
    text-align: right;
}

/* Платежи, полученные из ЕРИП */
.iptv-pair__ops tr.iptv-pair__erip {
    background: rgba(25, 135, 84, 0.07);
}

.iptv-pair__ops tr.iptv-pair__erip td:nth-child(2) {
    color: #198754;
    font-weight: 600;
}

.iptv-pair__ops tbody tr:last-child td {
    border-bottom: none;
}
.iptv-pair__status { margin-top:16px; line-height:1.5; }
@media(max-width:580px) { .iptv-pair__fields { grid-template-columns:1fr; } .iptv-pair__search { flex-direction:column; } }
</style>

<section class="iptv-pair">
    <h2>Сопряжение IPTV</h2>
    <p class="iptv-pair__hint">Привязка аккаунта IPTV к л/с абонента</p>

    <?php if (!empty($iptvData['error'])): ?>
        <div class="iptv-pair__message iptv-pair__error" role="alert"><?= $e($iptvData['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($iptvData['success'])): ?>
        <div class="iptv-pair__message iptv-pair__success" role="status"><?= $e($iptvData['success']) ?></div>
    <?php endif; ?>

    <div class="iptv-pair__card">
        <h3>1. Аккаунт IPTV</h3>

<form class="iptv-pair__search" method="get" autocomplete="off">
    <input type="hidden" name="module" value="iptv">

    <input
        type="tel"
        id="iptv-phone-search"
        name="iptv_phone"
        value="<?= $e($login !== '' ? $login : '+375') ?>"
        placeholder="+375291234567"
        maxlength="13"
        inputmode="tel"
        autocomplete="section-iptv tel"
        autocapitalize="off"
        spellcheck="false"
        required
        aria-label="Номер телефона IPTV"
    >

    <button type="submit">Поиск</button>
</form>

        <?php if ($account !== null): ?>
            <dl class="iptv-pair__fields">
                <div><dt>Время создания</dt><dd><?= $e($datetime($account['created_at'] ?? null)) ?></dd></div>
                <div><dt>Последняя активность</dt><dd><?= $e($datetime($account['last_active'] ?? null)) ?></dd></div>
                <div>
                    <dt>Последний IP-адрес</dt>
                    <dd>
                        <?php
                        $lastIp = trim((string) ($account['last_ip'] ?? ''));
                        ?>
                        <?php if (filter_var($lastIp, FILTER_VALIDATE_IP)): ?>
                            <a href="https://ip.tva.by/<?= rawurlencode($lastIp) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="Информация об IP-адресе">
                                <?= $e($lastIp) ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>
                </div>

                <div>
                    <dt>Баланс и л/с</dt>
                    <dd>
                        <?php
                        $balance = isset($account['balance']) && is_numeric($account['balance'])
                            ? $amount($account['balance']) . ' руб.'
                            : '—';

                        $billingText = $billing === '0'
                            ? 'не сопряжён'
                            : ($billing === '' ? '—' : $billing);

                        echo $e($balance . ' (' . $billingText . ')');
                        ?>
                    </dd>
                </div>
            </dl>
        <?php endif; ?>
    </div>

    <?php if ($account !== null): ?>
        <div class="iptv-pair__card">
            <h3>2. Л/c абонента</h3>
            <form class="iptv-pair__search" method="get">
                <input type="hidden" name="module" value="iptv">
                <input type="hidden" name="iptv_phone" value="<?= $e($login) ?>">
                <input type="text" name="personal" value="<?= $e($personal) ?>"
                       placeholder="Номер лицевого счёта" inputmode="numeric"
                       pattern="[1-9][0-9]*" maxlength="20" required autocomplete="off"
                       aria-label="Номер лицевого счёта">
                <button type="submit">Поиск</button>
            </form>
            <?php if ($subscriber !== null): ?>
                <dl class="iptv-pair__fields">
                    <div><dt>Абонент</dt><dd><?= $e($subscriber['subscriber'] ?? '—') ?></dd></div>
                    <div><dt>Лицевой счёт</dt><dd><?= $e($personal) ?></dd></div>
                    <div><dt>Адрес</dt><dd><?= $e($subscriber['address'] ?? '—') ?></dd></div>
                    <div><dt>Телефон</dt><dd><?= $e($subscriber['phone'] ?? '—') ?></dd></div>
                    <div><dt>Действующий пакет</dt><dd><?= $e($subscriber['tariff'] ?? '—') ?></dd></div>
                    <div>
                        <dt><?= $debt < 0 ? 'Аванс' : 'Задолженность' ?></dt>
                        <dd style="color: <?= $debt > 0 ? '#dc3545' : ($debt < 0 ? '#198754' : 'inherit') ?>;">
                            <?= $e($amount(abs($debt))) ?> руб.
                        </dd>
                    </div>
                </dl>
                <?php if (!$operations): ?>
                    <p>Операций не найдено.</p>
                <?php else: ?>
                    <div class="iptv-pair__table">
                    <table class="iptv-pair__ops">
                        <thead><tr><th>Дата</th><th>Операция</th><th>Сумма</th><th>Долг после</th></tr></thead>
                        <tbody>
                        <?php foreach ($operations as $operation): ?>
                            <?php
                            $type = (string) ($operation['type'] ?? '');

                            switch ($type) {
                                case 'payment':
                                    $name = 'Оплата';
                                    break;

                                case 'charge':
                                    $name = 'Начисление';
                                    break;

                                case 'erip':
                                    $name = 'Оплата ЕРИП';
                                    break;

                                default:
                                    $name = $type ?: 'Операция';
                            }

                            $sum = (float) ($operation['amount'] ?? 0);

                            $signedAmount = (
                                in_array($type, ['payment', 'erip'], true)
                                    ? '−'
                                    : '+'
                            ) . $amount(abs($sum));

                            $debtAfter = $operation['current_debt'] ?? null;
                            $isErip = $type === 'erip';
                            ?>
                            <tr class="<?= $isErip ? 'iptv-pair__erip' : '' ?>">
                                <td>
                                    <?= $e($datetime($operation['update'] ?? null)) ?>
                                </td>

                                <td>
                                    <?= $e($name) ?>
                                </td>

                                <td>
                                    <?= $e($signedAmount) ?>
                                </td>

                                <td>
                                    <?= $debtAfter !== null
                                        ? $e($amount($debtAfter))
                                        : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($subscriber !== null && $account !== null): ?>
        <div class="iptv-pair__card">
            <h3>3. Сопряжение</h3>
            <?php if ($mayPair): ?>
                <p class="iptv-pair__status">
                    Можно выполнить привязку к лицевому счёту
                    <strong><?= $e($personal) ?></strong>.
                    Баланс после сопряжения:
                    <strong>
                        <?= $e(number_format(
                            (float) ($config['iptv']['pair_amount'] ?? 0),
                            2,
                            ',',
                            ' '
                        )) ?> руб.
                    </strong>
                </p>

                <p style="color: #dc3545; font-weight: 500;">
                    Спросить у абонента:
                    <b>ФИО</b>,
                    <b>адрес</b>,
                    <b>когда последний раз платил</b> (СВЕРИТЬСЯ!).
                </p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $e(csrf_token()) ?>">
                    <input type="hidden" name="iptv_op" value="pair">
                    <input type="hidden" name="iptv_phone" value="<?= $e($login) ?>">
                    <input type="hidden" name="personal" value="<?= $e($personal) ?>">
                    <button class="iptv-pair__submit" type="submit">Сопряжение</button>
                </form>
            <?php elseif (ctype_digit($billing) && (int) $billing > 0): ?>
                <p class="iptv-pair__status">
                    Аккаунт уже сопряжён с л/с <strong><?= $e($billing) ?></strong>.
                </p>

                <form method="post">
                    <input type="hidden" name="csrf_token"
                        value="<?= $e(csrf_token()) ?>">

                    <input type="hidden" name="iptv_op" value="unlink">

                    <input type="hidden" name="iptv_phone"
                        value="<?= $e($login) ?>">

                    <input type="hidden" name="personal"
                        value="<?= $e($personal) ?>">

                    <button class="iptv-pair__submit iptv-pair__unlink"
                            type="submit">
                        Отвязать
                    </button>
                </form>

            <?php else: ?>
                <p class="iptv-pair__status">
                    Сопряжение недоступно.
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
