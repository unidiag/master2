<?php

/** @var array $config */
/** @var PDO $pdo */

declare(strict_types=1);




$subscribersCount = subscribers_count_by_date(
    $pdo,
    $config
);



$reportDistributors =
    $data['distributors']
    ?? [];

$reportHistory =
    $data['history']
    ?? [];



?>

<section class="channels-page">

    <div class="page-heading">
        <div>
            <h1>Отчёты</h1>
        </div>
    </div>

<?php if (!$reportDistributors): ?>

    <div class="empty-state">
        <strong>
            Нет доступных отчётов
        </strong>

        <span>
            У дистрибьюторов не указан report_tpl.
        </span>
    </div>

<?php else: ?>

    <div
        style="
            display:flex;
            align-items:center;
            gap:16px;
        "
    >
        <select
            class="digital-server-filter__select"
            id="reportDistributor"
            style="max-width:420px;"
        >
            <option value="">
                Выберите дистрибьютора
            </option>

            <?php foreach (
                $reportDistributors
                as $distributor
            ): ?>

                <?php
                $name = trim(
                    (string) (
                        $distributor['name']
                        ?? ''
                    )
                );

                $fullname = trim(
                    (string) (
                        $distributor['fullname']
                        ?? ''
                    )
                );

                $phone = trim(
                    (string) (
                        $distributor['phone']
                        ?? ''
                    )
                );                

                $mail = trim(
                    (string) (
                        $distributor['mail']
                        ?? ''
                    )
                );

                if ($name === '') {
                    continue;
                }
                ?>

                <option
                    value="<?= e($name) ?>"
                    data-mail="<?= e($mail) ?>"
                    data-phone="<?= e($phone) ?>"
                >
                    <?= e(
                        $fullname !== ''
                            ? $fullname
                            : $name
                    ) ?>
                </option>

            <?php endforeach; ?>
        </select>

        <input
            type="date"
            id="reportDateEnd"
            value="<?= e(
                date(
                    'Y-m-t',
                    strtotime(
                        'last month'
                    )
                )
            ) ?>"
            style="
                height:38px;
                padding:0 10px;
            "
        >

        <div
            id="reportDistributorPhone"
            class="report-distributor-phone"
        ></div>        

        <button
            type="button"
            class="button primary"
            id="reportSendButton"
            style="margin-left:auto;"
            disabled
        >
            Отправить
        </button>
    </div>

    <div
        id="reportSendStatus"
        class="report-send-status"
    ></div>





<div
    id="reportHistory"
    style="
        margin-top:20px;
    "
>

    <?php if (!$reportHistory): ?>

        <div class="empty-state">
            <strong>
                Отчёты ещё не отправлялись
            </strong>
        </div>

    <?php else: ?>

        <div class="card">

            <div class="card-body">

                <table class="table report-history-table">
                    <thead>
                    <tr>
                        <th>
                            Дата
                        </th>

                        <th>
                            Дистрибьютор
                        </th>

                        <th>
                            E-mail
                        </th>

                        <th>
                            Период
                        </th>

                        <th>
                            Пользователь
                        </th>

                        <th>
                            IP
                        </th>

                        <th>
                            Статус
                        </th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach (
                        $reportHistory
                        as $report
                    ): ?>

                        <?php
                        $success =
                            !empty(
                                $report['success']
                            );

                        $createdAt =
                            trim(
                                (string) (
                                    $report['created_at']
                                    ?? ''
                                )
                            );

                        $dateStart =
                            trim(
                                (string) (
                                    $report['date_start']
                                    ?? ''
                                )
                            );

                        $dateEnd =
                            trim(
                                (string) (
                                    $report['date_end']
                                    ?? ''
                                )
                            );

                        if ($dateStart !== '') {
                            $dateStartTime =
                                strtotime(
                                    $dateStart
                                );

                            if ($dateStartTime) {
                                $dateStart =
                                    date(
                                        'd.m.Y',
                                        $dateStartTime
                                    );
                            }
                        }

                        if ($dateEnd !== '') {
                            $dateEndTime =
                                strtotime(
                                    $dateEnd
                                );

                            if ($dateEndTime) {
                                $dateEnd =
                                    date(
                                        'd.m.Y',
                                        $dateEndTime
                                    );
                            }
                        }

                        if ($createdAt !== '') {
                            $createdTime =
                                strtotime(
                                    $createdAt
                                );

                            if ($createdTime) {
                                $createdAt =
                                    date(
                                        'd.m.Y H:i:s',
                                        $createdTime
                                    );
                            }
                        }
                        ?>

                        <tr>

                            <td>
                                <?= e($createdAt) ?>
                            </td>

                            <td>
                                <?= e(
                                    $report['distributor']
                                    ?? ''
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $report['email']
                                    ?? ''
                                ) ?>
                            </td>

                            <td>
                                <a
                                    class="report-period-link"
                                    href="report.php?distributor=<?= urlencode(
                                        (string) (
                                            $report['distributor']
                                            ?? ''
                                        )
                                    ) ?>&date_end=<?= urlencode(
                                        (string) (
                                            $report['date_end']
                                            ?? ''
                                        )
                                    ) ?>&nosign"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <?= e($dateStart) ?>
                                    —
                                    <?= e($dateEnd) ?>
                                </a>
                            </td>

                            <td>
                                <?= e(
                                    $report['username']
                                    ?? ''
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $report['ip']
                                    ?? ''
                                ) ?>
                            </td>

                            <td>

                                <?php if ($success): ?>

                                    <span
                                        style="
                                            color:#198754;
                                            font-weight:600;
                                        "
                                    >
                                        Отправлен
                                    </span>

                                <?php else: ?>

                                    <span
                                        style="
                                            color:#dc3545;
                                            font-weight:600;
                                        "
                                        title="<?= e(
                                            $report['error']
                                            ?? ''
                                        ) ?>"
                                    >
                                        Ошибка
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        </div>

    <?php endif; ?>

</div>






    <div
        id="reportFrameWrap"
        style="
            display:none;
            margin-top:20px;
        "
    >
        <iframe
            id="reportFrame"
            src=""
            style="
                width:100%;
                height:900px;
                border:1px solid #ccc;
                border-radius:6px;
                background:#fff;
            "
        ></iframe>
    </div>

<?php endif; ?>

</section>



<style>
.report-period-link {
    color: inherit;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.report-period-link:hover {
    text-decoration: none;
}


.report-history-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
}

.report-history-table th,
.report-history-table td {
    padding: 6px 8px;
}


</style>








<script>






document.addEventListener(
    'DOMContentLoaded',
    function () {
        const select =
            document.getElementById(
                'reportDistributor'
            );

        const dateEnd =
            document.getElementById(
                'reportDateEnd'
            );

        const frame =
            document.getElementById(
                'reportFrame'
            );

        const frameWrap =
            document.getElementById(
                'reportFrameWrap'
            );

        const sendButton =
            document.getElementById(
                'reportSendButton'
            );

        const sendStatus =
            document.getElementById(
                'reportSendStatus'
            );

        const phoneBox =
            document.getElementById(
                'reportDistributorPhone'
            );

        const reportHistory =
            document.getElementById(
                'reportHistory'
            );

        if (
            !select
            || !dateEnd
            || !frame
            || !frameWrap
            || !sendButton
        ) {
            return;
        }

        function updateReport() {

            sendStatus.className =
                'report-send-status';

            sendStatus.textContent = '';


            const distributor =
                select.value.trim();

            const selectedOption =
                select.options[
                    select.selectedIndex
                ];

            const mail =
                selectedOption
                    ? (
                        selectedOption.dataset.mail
                        || ''
                    ).trim()
                    : '';


            const phone =
                selectedOption
                    ? (
                        selectedOption.dataset.phone
                        || ''
                    ).trim()
                    : '';

            if (phoneBox) {
                phoneBox.textContent =
                    distributor === ''
                        ? 'Авторассылка 01 числа в 00:01'
                        : (
                            phone !== ''
                                ? 'Тел.: ' + phone
                                : ''
                        );
            }


            const reportDate =
                dateEnd.value.trim();

            sendStatus.style.display =
                'none';

            sendStatus.textContent = '';

            if (
                !distributor
                || !reportDate
            ) {
                frame.src = '';

                frameWrap.style.display =
                    'none';

                sendButton.disabled = true;

                sendButton.textContent =
                    'Отправить';
                 

                if (reportHistory) {
                    reportHistory.style.display =
                        'block';
                }

                return;
            }

            if (reportHistory) {
                reportHistory.style.display =
                    'none';
            }

            frame.src =
                'report.php?distributor='
                + encodeURIComponent(
                    distributor
                )
                + '&date_end='
                + encodeURIComponent(
                    reportDate
                );

            frameWrap.style.display =
                'block';

            sendButton.disabled =
                mail === '';

            sendButton.textContent =
                mail !== ''
                    ? mail
                    : 'E-mail не указан';
        }

        select.addEventListener(
            'change',
            updateReport
        );

        dateEnd.addEventListener(
            'change',
            updateReport
        );

        updateReport();

sendButton.addEventListener(
    'click',
    async function () {
        const distributor =
            select.value.trim();

        const reportDate =
            dateEnd.value.trim();

        const selectedOption =
            select.options[
                select.selectedIndex
            ];

        const mail =
            selectedOption
                ? (
                    selectedOption.dataset.mail
                    || ''
                ).trim()
                : '';

        if (
            !distributor
            || !reportDate
            || !mail
        ) {
            return;
        }

        const distributorTitle =
            selectedOption
                ? selectedOption.textContent.trim()
                : distributor;

        const confirmed = confirm(
            'Действительно отправить отчёт для '
            + distributorTitle
            + ' на '
            + mail
            + '?'
        );

        if (!confirmed) {
            return;
        }

        sendButton.disabled = true;

        const oldText =
            sendButton.textContent;

        sendButton.textContent =
            'Отправка...';

        sendStatus.style.display =
            'none';

        try {
            const body =
                new URLSearchParams();

            body.set(
                'distributor',
                distributor
            );

            body.set(
                'date_end',
                reportDate
            );

            const response =
                await fetch(
                    'report_send.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',
                        },

                        body:
                            body.toString(),
                    }
                );

            const result =
                await response.json();

            sendStatus.style.display =
                'block';

            if (
                response.ok
                && result.ok
            ) {
                window.location.href =
                    'index.php?module=reports';
            } else {
                sendStatus.className =
                    'report-send-status error';

                sendStatus.textContent =
                    result.error
                    || 'Ошибка отправки.';
            }
        } catch (error) {
            sendStatus.className =
                'report-send-status error';

            sendStatus.textContent =
                'Ошибка отправки отчёта.';
        } finally {
            sendButton.disabled = false;

            sendButton.textContent =
                oldText;
        }
    }
);
    }
);
</script>