<?php

declare(strict_types=1);

/** @var array $data */
/** @var SmsRepository $smsRepository */
/** @var TicketRepository $tickets */

?>

<div class="subscriber-list">
    <?php foreach ($data['rows'] as $row): ?>
        <?php

        $address = trim(
            (string) (
                $row['address']
                ?? ''
            )
        );

        $subscriber = trim(
            (string) (
                $row['subscriber']
                ?? ''
            )
        );

        $personal = trim(
            (string) (
                $row['personal']
                ?? ''
            )
        );

        $phone = trim(
            (string) (
                $row['phone']
                ?? ''
            )
        );

        $tariff = trim(
            (string) (
                $row['tariff']
                ?? ''
            )
        );

        $summ = is_numeric(
            $row['summ'] ?? null
        )
            ? (float) $row['summ']
            : 0.0;

        /*
         * Из адреса квартиры получаем дом.
         *
         * Заводская-6-5
         * ->
         * Заводская-6
         */
        $addressParts = explode(
            '-',
            $address
        );

        if (count($addressParts) >= 3) {
            array_pop($addressParts);

            $subscriberHouse = implode(
                '-',
                $addressParts
            );
        } else {
            $subscriberHouse = $address;
        }

        $recentSms = $address !== ''
            ? $smsRepository->latestByAddress(
                $address,
                3
            )
            : [];

        $createdAt = trim(
            (string) (
                $row['created_at']
                ?? ''
            )
        );

        $createdTimestamp = $createdAt !== ''
            ? strtotime($createdAt)
            : false;

        ?>

        <div class="subscriber-card">
            <div>
                <a
                    class="subscriber-card__name"
                    href="<?= e(url([
                        'module' => 'stat',
                        'house' => $subscriberHouse,
                        'personal' => $personal,
                    ])) ?>"
                >
                    <strong>
                        <?= e($subscriber) ?>
                    </strong>
                </a>

                <div class="muted">
                    <?= e($address) ?>
                </div>

                <?php if ($createdTimestamp !== false): ?>
                    <div class="subscriber-disconnected">
                        отключён <?= e(date(
                            'd.m.Y',
                            $createdTimestamp
                        )) ?>
                        в <?= e(date(
                            'H:i:s',
                            $createdTimestamp
                        )) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <span class="label">
                    Лицевой счёт
                </span>

                <?= e($personal) ?>
            </div>

            <div>
                <span class="label">
                    Телефон
                </span>

                <?php if ($phone !== ''): ?>
                    <span>
                        <?= e($phone) ?>
                    </span>
                <?php else: ?>
                    <span class="muted">
                        —
                    </span>
                <?php endif; ?>
            </div>

            <div>
                <span class="label">
                    Тариф
                </span>

                <?= e($tariff) ?>
            </div>

            <div>
                <span class="label">
                    Сумма
                </span>

                <?= e(number_format(
                    $summ,
                    2,
                    ',',
                    ' '
                )) ?>
            </div>

            <div>
                <?php
                render_sms_button(
                    $address,
                    $phone,
                    $personal,
                    $summ,
                    $subscriberHouse,
                    $tickets->countByAddress(
                        $address
                    ),
                    $subscriber,
                    $recentSms
                );
                ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($data['rows'])): ?>
        <div class="muted">
            Отключек не найдено.
        </div>
    <?php endif; ?>
</div>