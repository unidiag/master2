<?php

declare(strict_types=1);

/** @var array $data */

$rows = $data['rows'] ?? [];
$lastSync = $data['last_sync'] ?? null;
$lastConnected = $data['last_connected'] ?? null;
$eripSearch = (string) ($data['search'] ?? '');
$total = (int) ($data['total'] ?? 0);
$totalAmount = (float) ($data['total_amount'] ?? 0);
$todayTotal = (int) ($data['today_total'] ?? 0);
$todayAmount = (float) ($data['today_amount'] ?? 0);
$periodEnabled = (bool) ($data['period_enabled'] ?? false);
$dateFrom = (string) ($data['date_from'] ?? '');
$dateTo = (string) ($data['date_to'] ?? '');








$formatDate = static function ($value): string {
    if (empty($value)) {
        return '—';
    }

    try {
        $date = new DateTimeImmutable((string) $value);
        $today = new DateTimeImmutable('today');

        if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Сегодня в ' . $date->format('H:i:s');
        }

        if ($date->format('Y-m-d') === $today->modify('-1 day')->format('Y-m-d')) {
            return 'Вчера в ' . $date->format('H:i:s');
        }

        return $date->format('d.m.Y H:i:s');

    } catch (Throwable $e) {
        return '—';
    }
};










$formatMoney = static function ($value): string {
    return number_format((float) $value, 2, '.', ' ');
};

?>

<style>
.erip-page {
    width: 100%;
    font-size: 13px;
}

.erip-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.erip-summary-item {
    flex: 1 1 210px;
    padding: 15px;
    background: rgba(128,128,128,.07);
    border: 1px solid rgba(128,128,128,.18);
    border-radius: 8px;
}

.erip-summary-label {
    color: var(--muted, #777);
    font-size: 12px;
    margin-bottom: 5px;
}

.erip-summary-value {
    font-size: 17px;
    font-weight: 600;
}

.erip-ok {
    color: #17864a;
}

.erip-error {
    color: #cb4141;
}

.erip-sync-error {
    margin-bottom: 14px;
    padding: 10px 12px;
    border: 1px solid #d88;
    border-radius: 6px;
    overflow-wrap: anywhere;
}




.erip-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.erip-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.erip-table th,
.erip-table td {
    padding: 9px 10px;
    border-bottom: 1px solid rgba(128,128,128,.22);
    text-align: left;
    vertical-align: middle;
}

.erip-table th {
    white-space: nowrap;
    font-weight: 600;
}

.erip-table tbody tr:hover {
    background: rgba(128,128,128,.08);
}

.erip-table .date,
.erip-table .amount,
.erip-table .account {
    white-space: nowrap;
}

.erip-table .amount {
    text-align: right;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.erip-table .account {
    font-weight: 600;
}

.erip-table .small {
    font-size: 12px;
    color: var(--muted, #777);
}

.erip-empty {
    padding: 30px !important;
    text-align: center !important;
    color: var(--muted, #777);
}


.erip-next-update {
    margin-top: 12px;
}

.erip-progress {
    position: relative;
    height: 26px;
    background: rgba(128, 128, 128, 0.15);
    border-radius: 6px;
    overflow: hidden;
}

.erip-progress-fill {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 100%;
    background: #198754;
    transition: width 1s linear;
}

.erip-progress-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
    font-variant-numeric: tabular-nums;
    z-index: 1;
}


.erip-address-link {
    color: #1976d2;
    text-decoration: none;
}

.erip-address-link:hover {
    text-decoration: underline;
}


.erip-summary-today {
    margin-top: 10px;
    font-size: 12px;
    color: var(--muted, #777);
}

.erip-summary-today strong {
    margin-left: 5px;
    font-size: 13px;
    color: #198754;
}







.erip-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 14px;
}

.erip-toolbar-search,
.erip-toolbar-period {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.erip-toolbar-search {
    flex: 1 1 350px;
}

.erip-toolbar-search .input {
    flex: 1 1 220px;
    max-width: 420px;
}

.erip-toolbar-period {
    margin-left: auto;
    justify-content: flex-end;
}

.erip-period-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    white-space: nowrap;
    margin-right: 6px;
}

.erip-period-label input {
    cursor: pointer;
}

.erip-date {
    width: 100px;
    min-width: 0;
    max-width: 100px;
    padding: 4px;
    font-size: 12px;
    box-sizing: border-box;
}

.erip-date-separator {
    color: #888;
}

.erip-date:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* Подсветка совпадений при поиске ЕРИП */
.erip-table mark.erip-search-highlight {
    background: #ffff00 !important;
    color: #ff0000 !important;
    font-weight: 700;
    padding: 0px;
    border-radius: 2px;
}

</style>

<section class="erip-page">

    <div class="erip-summary">


        <div class="erip-summary-item">
            <div class="erip-summary-label">
                Последнее успешное подключение
            </div>

            <div class="erip-summary-value">
                <?= e($formatDate($lastConnected)) ?>
            </div>

            <?php if ($lastConnected): ?>
                <div class="erip-next-update">
                    <div
                        id="erip-countdown"
                        class="erip-progress"
                        data-next="<?= (new DateTimeImmutable(
                            (string) $lastConnected
                        ))->getTimestamp() + 301 ?>"
                    >
                        <div class="erip-progress-fill"></div>
                        <span class="erip-progress-text">—</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>


        <div class="erip-summary-item">
            <div class="erip-summary-label">
                Последняя синхронизация
            </div>

            <div class="erip-summary-value">
                <?= e($formatDate($lastSync['created_at'] ?? null)) ?>
            </div>

            <?php if ($lastSync): ?>
                <div class="<?= (int) $lastSync['connected'] === 1
                    ? 'erip-ok'
                    : 'erip-error' ?>">
                    <?= (int) $lastSync['connected'] === 1
                        ? 'Соединение установлено'
                        : 'Нет соединения' ?>
                </div>

                <div class="small">
                    Найдено: <?= (int) $lastSync['found'] ?>
                    · Перемещено: <?= (int) $lastSync['moved'] ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="erip-summary-item">
            <div class="erip-summary-label">
                Найдено платежей
            </div>

            <div class="erip-summary-value">
                <?= number_format($total, 0, '.', ' ') ?>
            </div>

            <div class="erip-summary-today">
                За сегодня:
                <strong>
                    <?= number_format($todayTotal, 0, '.', ' ') ?>
                </strong>
            </div>
        </div>


        <div class="erip-summary-item">
            <div class="erip-summary-label">
                Сумма найденных платежей
            </div>

            <div class="erip-summary-value">
                <?= e($formatMoney($totalAmount)) ?> BYN
            </div>

            <div class="erip-summary-today">
                За сегодня:
                <strong>
                    <?= e($formatMoney($todayAmount)) ?> BYN
                </strong>
            </div>
        </div>


    </div>

    <?php if (
        $lastSync
        && trim((string) ($lastSync['error'] ?? '')) !== ''
    ): ?>
        <div class="erip-sync-error">
            <strong>Ошибка синхронизации:</strong>
            <?= e((string) $lastSync['error']) ?>
        </div>
    <?php endif; ?>











    <form class="erip-toolbar" method="get">

        <input type="hidden" name="module" value="erip">

        <!-- Левая сторона: поиск -->
        <div class="erip-toolbar-search">
            <input
                class="input"
                type="search"
                name="search"
                value="<?= e($eripSearch) ?>"
                placeholder="Лицевой счёт, ФИО, адрес, операция..."
                autocomplete="off"
            >

            <button class="button primary" type="submit">
                Найти
            </button>

            <a class="button" href="<?= e(url(['module' => 'erip'])) ?>">
                Сбросить
            </a>
        </div>

        <!-- Правая сторона: период -->
        <div class="erip-toolbar-period">

            <label class="erip-period-label">
                <input
                    type="checkbox"
                    id="erip-period-enabled"
                    name="period"
                    value="1"
                    <?= $periodEnabled ? 'checked' : '' ?>
                >
                Период
            </label>

            <input
                class="input erip-date"
                type="date"
                id="erip-date-from"
                name="date_from"
                value="<?= e($dateFrom) ?>"
                <?= !$periodEnabled ? 'disabled' : '' ?>
            >

            <span class="erip-date-separator">—</span>

            <input
                class="input erip-date"
                type="date"
                id="erip-date-to"
                name="date_to"
                value="<?= e($dateTo) ?>"
                <?= !$periodEnabled ? 'disabled' : '' ?>
            >

            <button class="button primary" type="submit">
                Применить
            </button>

        </div>

    </form>














    <div class="erip-table-wrap">
        <table class="erip-table">
            <thead>
                <tr>
                    <th>Дата платежа</th>
                    <th>Услуга</th>
                    <th>Лицевой счёт</th>
                    <th>Плательщик / адрес</th>
                    <th>Период</th>
                    <th class="amount">Сумма</th>
                    <!-- <th>Операция</th> -->
                    <th>Метод / номер</th>
                    <!-- <th>Файл</th> -->
                </tr>
            </thead>
            <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="9" class="erip-empty">
                            Платежи не найдены
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="date">
                                <?= e($formatDate($row['payment_at'])) ?>
                            </td>


                            <td>
                                <?php
                                    $service = (string) $row['service'];

                                    $serviceName = [
                                        '1' => 'TV',
                                        '2' => 'ADV',
                                    ][$service] ?? $service;

                                    $serviceColor = [
                                        '1' => '#198754',
                                        '2' => '#dc3545',
                                    ][$service] ?? 'inherit';
                                ?>

                                <span style="color: <?= $serviceColor ?>; font-weight: 600;">
                                    <?= e($serviceName) ?>
                                </span>
                            </td>


                            <td class="account">
                                <?= e((string) ($row['account'] ?? '')) ?>
                            </td>


                            <td>
                                <strong>
                                    <?= e((string) ($row['name'] ?? '')) ?>
                                </strong>

                                <?php $address = trim((string) ($row['address'] ?? '')); ?>

                                <div class="small">
                                    <?php if ($address !== ''): ?>
                                        <a
                                            href="https://master2.trianda.by/index.php?module=stat&amp;house=<?= rawurlencode($address) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="erip-address-link"
                                        >
                                            <?= e($address) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>


                            <td>
                                <?= e((string) ($row['period'] ?? '')) ?>
                            </td>

                            <td class="amount">
                                <?= e($formatMoney($row['amount'])) ?>
                            </td>

                            <td>
                                <?= e((string) ($row['channel'] ?? '')) ?>
                                <div class="small">
                                    <?= e((string) ($row['operation_id'] ?? '')) ?>
                                </div>
                                <!-- <div class="small">
                                    <?= e((string) ($row['type'] ?? '')) ?>
                                </div> -->
                            </td>

                            <!-- <td class="small">
                                <?= e((string) $row['file_name']) ?>
                                <div>
                                    Строка <?= (int) $row['row_num'] ?>
                                </div>
                            </td> -->
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

</section>




<script>
(function () {
    const countdown = document.getElementById('erip-countdown');
    if (!countdown) return;

    const fill = countdown.querySelector('.erip-progress-fill');
    const label = countdown.querySelector('.erip-progress-text');

    const duration = 301;
    const nextUpdate = Number(countdown.dataset.next) * 1000;

    let timer;
    let reloadScheduled = false;

    function updateCountdown() {
        const remainingMs = Math.max(0, nextUpdate - Date.now());
        const remaining = Math.ceil(remainingMs / 1000);

        const percent = Math.min(
            100,
            (remainingMs / (duration * 1000)) * 100
        );

        fill.style.width = percent + '%';

        if (remaining <= 0) {
            label.textContent = 'Ожидание обновления...';

            clearInterval(timer);

            if (!reloadScheduled) {
                reloadScheduled = true;
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }

            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;

        label.textContent = `${minutes} мин ${seconds} сек`;
    }

    timer = setInterval(updateCountdown, 1000);
    updateCountdown();
})();
</script>
<script>
(function () {
    const checkbox = document.getElementById('erip-period-enabled');
    const dateFrom = document.getElementById('erip-date-from');
    const dateTo = document.getElementById('erip-date-to');

    if (!checkbox || !dateFrom || !dateTo) return;

    function updatePeriodState() {
        dateFrom.disabled = !checkbox.checked;
        dateTo.disabled = !checkbox.checked;
    }

    checkbox.addEventListener('change', updatePeriodState);

    updatePeriodState();
})();
</script>

<script>
(function () {
    const searchInput = document.querySelector(
        '.erip-toolbar-search input[name="search"]'
    );

    const tbody = document.querySelector(
        '.erip-table tbody'
    );

    if (!searchInput || !tbody) return;

    const search = searchInput.value.trim();
    if (!search) return;

    // Экранируем спецсимволы регулярного выражения
    const escaped = search.replace(
        /[.*+?^${}()|[\]\\]/g,
        '\\$&'
    );

    const regex = new RegExp(escaped, 'gi');

    const walker = document.createTreeWalker(
        tbody,
        NodeFilter.SHOW_TEXT
    );

    const nodes = [];

    while (walker.nextNode()) {
        const node = walker.currentNode;

        if (node.parentElement.closest('mark')) {
            continue;
        }

        if (node.nodeValue.trim()) {
            nodes.push(node);
        }
    }

    nodes.forEach(node => {
        const text = node.nodeValue;

        regex.lastIndex = 0;

        if (!regex.test(text)) return;

        regex.lastIndex = 0;

        const fragment = document.createDocumentFragment();
        let lastIndex = 0;

        for (const match of text.matchAll(regex)) {
            const index = match.index;

            fragment.appendChild(
                document.createTextNode(
                    text.slice(lastIndex, index)
                )
            );

            const mark = document.createElement('mark');
            mark.className = 'erip-search-highlight';
            mark.textContent = match[0];

            fragment.appendChild(mark);

            lastIndex = index + match[0].length;
        }

        fragment.appendChild(
            document.createTextNode(
                text.slice(lastIndex)
            )
        );

        node.replaceWith(fragment);
    });
})();
</script>