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
            <option value="all">
                Все
            </option>

            <option value="1">
                Более 1 месяца
            </option>

            <option
                value="3"
                selected
            >
                Более 3 месяцев
            </option>

            <option value="6">
                Более 6 месяцев
            </option>
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
        <strong>
            Должники отсутствуют
        </strong>

        <span>
            В последнем обновлении нет абонентов
            с положительной задолженностью
            и действующим договором.
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

            $houseDebtors =
                $debtorHouse['debtors'] ?? [];

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
                        <h2>
                            <?= e($houseName) ?>
                        </h2>

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

                        $personal = trim(
                            (string) (
                                $debtor['personal'] ?? ''
                            )
                        );

                        $subscriber = trim(
                            (string) (
                                $debtor['subscriber'] ?? ''
                            )
                        );

                        $address = trim(
                            (string) (
                                $debtor['address'] ?? ''
                            )
                        );

                        $apartment = trim(
                            (string) (
                                $debtor['apartment'] ?? ''
                            )
                        );

                        $phone = trim(
                            (string) (
                                $debtor['phone'] ?? ''
                            )
                        );

                        $tariff = trim(
                            (string) (
                                $debtor['tariff'] ?? ''
                            )
                        );

                        $debt = (float) (
                            $debtor['debt'] ?? 0
                        );

                        $lastPaymentUpdate = trim(
                            (string) (
                                $debtor['last_payment_update']
                                ?? ''
                            )
                        );

                        $karandashDescr = trim(
                            (string) (
                                $debtor['karandash_descr']
                                ?? ''
                            )
                        );

                        /*
                         * Последние три SMS
                         * по адресу абонента.
                         */
                        $recentSms = $address !== ''
                            ? $smsRepository->latestByAddress(
                                $address,
                                3
                            )
                            : [];

                        ?>

                        <div
                            class="debtor-card"
                            data-last-payment="<?= e(
                                $lastPaymentUpdate
                            ) ?>"
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

                                <a
                                    class="debtor-card__subscriber-link"
                                    href="<?= e(url([
                                        'module' => 'stat',
                                        'house' => $houseName,
                                        'personal' => $personal,
                                    ])) ?>"
                                >
                                    <?= e(
                                        $subscriber !== ''
                                            ? $subscriber
                                            : 'Без имени'
                                    ) ?>
                                </a>


                                <small>
                                    <?= e($tariff) ?>
                                </small>


                                <small class="debtor-card__payment">
                                    Последняя оплата:

                                    <?php if (
                                        $lastPaymentUpdate !== ''
                                    ): ?>

                                        <?= e(format_unix_time(
                                            $lastPaymentUpdate,
                                            'd.m.Y'
                                        )) ?>

                                    <?php else: ?>

                                        не обнаружена

                                    <?php endif; ?>
                                </small>

                                <?php
                                $debtorPersonal = trim(
                                    (string) (
                                        $debtor['personal']
                                        ?? ''
                                    )
                                );

                                $disconnectAt = trim(
                                    (string) (
                                        $debtorDisconnects[
                                            $debtorPersonal
                                        ]
                                        ?? ''
                                    )
                                );
                                ?>

                                <?php if ($disconnectAt !== ''): ?>
                                    <?php
                                    $disconnectTimestamp = strtotime(
                                        $disconnectAt
                                    );
                                    ?>

                                    <?php if ($disconnectTimestamp !== false): ?>
                                        <div class="subscriber-disconnected">
                                            отключён <?= e(date(
                                                'd.m.Y',
                                                $disconnectTimestamp
                                            )) ?>
                                            в <?= e(date(
                                                'H:i:s',
                                                $disconnectTimestamp
                                            )) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>


                                <?php if (
                                    $karandashDescr !== ''
                                ): ?>

                                    <span
                                        class="debtor-card__karandash"
                                        title="<?= e(
                                            $karandashDescr
                                        ) ?>"
                                    >
                                        <span
                                            class="debtor-card__karandash-icon"
                                        >
                                            ✎
                                        </span>

                                        <span
                                            class="debtor-card__karandash-text"
                                        >
                                            <?= nl2br(
                                                e($karandashDescr)
                                            ) ?>
                                        </span>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="debtor-card__right">
                                <div class="debtor-card__sms">
                                    <?php
                                    render_sms_button(
                                        $address,
                                        $phone,
                                        $personal,
                                        $debt,
                                        $houseName,
                                        $tickets->countByAddress($address),
                                        $subscriber,
                                        $recentSms
                                    );
                                    ?>
                                </div>

                                <strong class="debtor-card__debt">
                                    <?= e(number_format(
                                        $debt,
                                        2,
                                        ',',
                                        ' '
                                    )) ?>
                                </strong>
                            </div>

                        </div>

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
        <strong>
            Должники не найдены
        </strong>

        <span>
            Для выбранного периода должники отсутствуют.
        </span>
    </div>


    <script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const filter = document.getElementById(
                'debtors-payment-filter'
            );

            if (!filter) {
                return;
            }

            const houses = Array.from(
                document.querySelectorAll(
                    '.debtor-house'
                )
            );

            const visibleCountElement =
                document.getElementById(
                    'debtors-visible-count'
                );

            const visibleTotalElement =
                document.getElementById(
                    'debtors-visible-total'
                );

            const emptyElement =
                document.getElementById(
                    'debtors-filter-empty'
                );

            filter.addEventListener(
                'change',
                applyFilter
            );

            function applyFilter() {
                const selectedMonths =
                    filter.value === 'all'
                        ? 0
                        : parseInt(
                            filter.value,
                            10
                        );

                let totalVisibleCount = 0;
                let totalVisibleDebt = 0;

                houses.forEach(
                    function (house) {
                        const cards = Array.from(
                            house.querySelectorAll(
                                '.debtor-card'
                            )
                        );

                        let houseVisibleCount = 0;
                        let houseVisibleDebt = 0;

                        cards.forEach(
                            function (card) {
                                const paymentTimestamp =
                                    parseInt(
                                        card.dataset
                                            .lastPayment
                                            || '',
                                        10
                                    );

                                const debt =
                                    parseFloat(
                                        card.dataset.debt
                                            || '0'
                                    ) || 0;

                                let visible = true;

                                if (
                                    selectedMonths > 0
                                ) {
                                    /*
                                     * Неизвестную дату считаем
                                     * очень давней.
                                     */
                                    if (
                                        Number.isFinite(
                                            paymentTimestamp
                                        )
                                    ) {
                                        const cutoff =
                                            new Date();

                                        cutoff.setMonth(
                                            cutoff.getMonth()
                                            - selectedMonths
                                        );

                                        visible =
                                            paymentTimestamp
                                            <
                                            Math.floor(
                                                cutoff.getTime()
                                                / 1000
                                            );
                                    }
                                }

                                card.hidden =
                                    !visible;

                                if (visible) {
                                    houseVisibleCount++;
                                    houseVisibleDebt += debt;
                                }
                            }
                        );

                        house.hidden =
                            houseVisibleCount === 0;

                        const houseCountElement =
                            house.querySelector(
                                '.debtor-house__visible-count'
                            );

                        const houseTotalElement =
                            house.querySelector(
                                '.debtor-house__total'
                            );

                        if (
                            houseCountElement
                        ) {
                            houseCountElement
                                .textContent =
                                formatInteger(
                                    houseVisibleCount
                                );
                        }

                        if (
                            houseTotalElement
                        ) {
                            houseTotalElement
                                .textContent =
                                formatMoney(
                                    houseVisibleDebt
                                );
                        }

                        totalVisibleCount +=
                            houseVisibleCount;

                        totalVisibleDebt +=
                            houseVisibleDebt;
                    }
                );

                if (
                    visibleCountElement
                ) {
                    visibleCountElement
                        .textContent =
                        formatInteger(
                            totalVisibleCount
                        );
                }

                if (
                    visibleTotalElement
                ) {
                    visibleTotalElement
                        .textContent =
                        formatMoney(
                            totalVisibleDebt
                        );
                }

                if (emptyElement) {
                    emptyElement.hidden =
                        totalVisibleCount !== 0;
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
        }
    );
    </script>

<?php endif; ?>