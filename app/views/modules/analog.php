
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






