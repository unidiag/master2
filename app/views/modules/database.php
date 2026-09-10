 <?php
 


/** @var string $action */
/** @var array $data */


 
 
 if ($action === 'history'): ?><a class="button" href="<?= e(url(['module'=>'database'])) ?>">← Назад</a><h1>История лицевого счёта <?= e(get_string('personal',10)) ?></h1><?php endif; ?>
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


                            <?php
                            $rowPersonal = trim(
                                (string) (
                                    $row['personal']
                                    ?? ''
                                )
                            );

                            $disconnectAt = trim(
                                (string) (
                                    $databaseDisconnects[
                                        $rowPersonal
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


                            <?php
                            $karandashDescr = trim(
                                (string) (
                                    $databaseKarandash[
                                        $address
                                    ]
                                    ?? ''
                                )
                            );
                            ?>

                            <?php if ($karandashDescr !== ''): ?>
                                <div class="house-card__karandash">
                                    <?= nl2br(e($karandashDescr)) ?>
                                </div>
                            <?php endif; ?>                            


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
                            $recentSms = $address !== ''
                                ? $smsRepository->latestByAddress(
                                    $address,
                                    3
                                )
                                : [];

                            render_sms_button(
                                $address,
                                (string) ($row['phone'] ?? ''),
                                (string) ($row['personal'] ?? ''),
                                is_numeric($row['summ'] ?? null)
                                    ? ((float) $row['summ'])
                                    : 0.0,
                                $subscriberHouse,
                                $tickets->countByAddress($address),
                                (string) ($row['account'] ?? ''),
                                $recentSms
                            );
                            ?>
                        </div>

                    </div>

                <?php endforeach; ?>




